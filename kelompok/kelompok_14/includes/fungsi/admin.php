<?php
// Fungsi-fungsi Admin untuk Moderasi Kuis dan Statistik Dashboard (Jobdesk Nanda - Sesi 6)

// ============================================================================
// BAGIAN 1: MODERASI KUIS (AD-06, ADM-07, KT-05)
// ============================================================================

// 1. Ambil daftar pengajar untuk dropdown filter di halaman Moderasi
function daftarSemuaPengajar() {
    global $pdo;

    $sql = "SELECT id, nama FROM user WHERE peran = 'pengajar' ORDER BY nama ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Ambil daftar kuis berstatus 'terbit' atau 'ditarik' dengan filter mapel dan pengajar
function daftarKuisUntukModerasi($status = 'terbit', $topikId = 0, $pengajarId = 0) {
    global $pdo;

    // Pastikan hanya status 'terbit' atau 'ditarik'
    if ($status !== 'ditarik') {
        $status = 'terbit';
    }

    $sql = "SELECT kuis.*,
                   topik.nama AS nama_topik,
                   tingkat.nama AS nama_tingkat,
                   user.nama AS nama_pengajar,
                   COUNT(DISTINCT soal.id) AS jumlah_soal,
                   (SELECT catatan FROM log_status_kuis
                    WHERE kuis_id = kuis.id AND status_ke = 'ditarik'
                    ORDER BY dibuat_pada DESC, id DESC LIMIT 1) AS alasan_ditarik
            FROM kuis
            JOIN topik ON kuis.topik_id = topik.id
            JOIN tingkat ON kuis.tingkat_id = tingkat.id
            JOIN user ON kuis.pengajar_id = user.id
            LEFT JOIN soal ON soal.kuis_id = kuis.id
            WHERE kuis.status = ?";

    $params = [$status];

    if ((int)$topikId > 0) {
        $sql .= " AND kuis.topik_id = ?";
        $params[] = (int)$topikId;
    }

    if ((int)$pengajarId > 0) {
        $sql .= " AND kuis.pengajar_id = ?";
        $params[] = (int)$pengajarId;
    }

    $sql .= " GROUP BY kuis.id ORDER BY kuis.diperbarui_pada DESC, kuis.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 3. Tarik kuis yang sudah terbit oleh Admin (Moderasi, ADM-07, KT-05, AC-20)
// Memanggil tutupPercobaanKuis() milik Wulan dan ubahStatusKuis() milik Zalfa dalam satu transaksi
function tarikKuisModerasi($kuisId, $alasan, $adminId) {
    global $pdo;

    $alasan = trim($alasan ?? '');
    if ($alasan === '') {
        return ['berhasil' => false, 'pesan' => 'Alasan penarikan kuis wajib diisi.'];
    }
    if (strlen($alasan) < 10) {
        return ['berhasil' => false, 'pesan' => 'Alasan penarikan minimal 10 karakter agar pengajar memahami masalahnya.'];
    }

    try {
        $pdo->beginTransaction();

        // Ubah status dari 'terbit' ke 'ditarik' lewat fungsi milik Zalfa
        $ubah = ubahStatusKuis($kuisId, 'terbit', 'ditarik', $adminId, $alasan);
        if (empty($ubah['berhasil'])) {
            $pdo->rollBack();
            return [
                'berhasil' => false,
                'pesan'    => $ubah['pesan'] ?? 'Gagal menarik kuis.'
            ];
        }

        // Tutup semua percobaan pelajar yang masih berjalan di kuis ini lewat fungsi Wulan (KT-05)
        $jumlahDitutup = 0;
        if (function_exists('tutupPercobaanKuis')) {
            $jumlahDitutup = tutupPercobaanKuis($kuisId);
        }

        $pdo->commit();

        $pesan = 'Kuis berhasil ditarik dari katalog.';
        if ($jumlahDitutup > 0) {
            $pesan .= ' Sebanyak ' . $jumlahDitutup . ' percobaan yang sedang berjalan ikut ditutup.';
        }

        return ['berhasil' => true, 'pesan' => $pesan];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['berhasil' => false, 'pesan' => 'Terjadi kesalahan saat menarik kuis.'];
    }
}

// ============================================================================
// BAGIAN 2: STATISTIK & AKTIVITAS DASHBOARD ADMIN (AD-01, ADM-01)
// ============================================================================

// 4. Hitung ringkasan statistik untuk Dashboard Admin
function hitungStatistikAdmin() {
    global $pdo;

    $statistik = [
        'total_pelajar'  => 0,
        'total_pengajar' => 0,
        'total_admin'    => 0,
        'kuis_draft'     => 0,
        'kuis_menunggu'  => 0,
        'kuis_terbit'    => 0,
        'kuis_ditolak'   => 0,
        'kuis_ditarik'   => 0
    ];

    // Hitung jumlah pengguna per peran
    $stmtUser = $pdo->query("SELECT peran, COUNT(*) AS jumlah FROM user GROUP BY peran");
    $hasilUser = $stmtUser->fetchAll(PDO::FETCH_ASSOC);
    foreach ($hasilUser as $baris) {
        if ($baris['peran'] === 'pelajar') {
            $statistik['total_pelajar'] = (int)$baris['jumlah'];
        } elseif ($baris['peran'] === 'pengajar') {
            $statistik['total_pengajar'] = (int)$baris['jumlah'];
        } elseif ($baris['peran'] === 'admin') {
            $statistik['total_admin'] = (int)$baris['jumlah'];
        }
    }

    // Hitung jumlah kuis per status
    $stmtKuis = $pdo->query("SELECT status, COUNT(*) AS jumlah FROM kuis GROUP BY status");
    $hasilKuis = $stmtKuis->fetchAll(PDO::FETCH_ASSOC);
    foreach ($hasilKuis as $baris) {
        $kunci = 'kuis_' . $baris['status'];
        if (isset($statistik[$kunci])) {
            $statistik[$kunci] = (int)$baris['jumlah'];
        }
    }

    return $statistik;
}

// 5. Ambil daftar aktivitas terbaru perubahan status kuis (ADM-01)
function ambilAktivitasTerbaruAdmin($batas = 10) {
    global $pdo;

    $batas = (int)$batas;
    if ($batas <= 0) {
        $batas = 10;
    }

    $sql = "SELECT log_status_kuis.*,
                   kuis.judul AS judul_kuis,
                   user.nama AS nama_pelaku,
                   user.peran AS peran_pelaku
            FROM log_status_kuis
            JOIN kuis ON log_status_kuis.kuis_id = kuis.id
            JOIN user ON log_status_kuis.diubah_oleh = user.id
            ORDER BY log_status_kuis.dibuat_pada DESC, log_status_kuis.id DESC
            LIMIT ?";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $batas, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

