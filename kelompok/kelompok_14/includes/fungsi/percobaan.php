<?php
// ============================================================================
// Fungsi-fungsi Pengelolaan Percobaan Kuis (percobaan.php)
// Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
// PIC: Wulan (Sesi 2 - Fungsi Percobaan & Kuis Publik)
//
// File ini mengatur:
// - Menampilkan daftar kuis publik untuk katalog & dashboard
// - Memulai pengerjaan kuis (percobaan baru atau melanjutkan sesi)
// - Menghitung sisa waktu timer di server
// - Menyimpan jawaban pelajar per soal (untuk API jawab)
// - Menghitung skor akhir dan menyelesaikan kuis
// - Menutup otomatis kuis yang kedaluwarsa atau ditarik
// - Mengelola riwayat & hasil pengerjaan kuis (termasuk mode Guest)
// ============================================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

// ----------------------------------------------------------------------------
// 1. Ambil daftar kuis publik yang berstatus 'terbit' (untuk Katalog & Dashboard)
// ----------------------------------------------------------------------------
function daftarKuisPublik($filterTopikId = null, $filterTingkatId = null, $kataKunci = null) {
    global $pdo;

    // Pastikan kuis yang kedaluwarsa ditutup dulu sebelum data kuis dibaca
    tutupPercobaanKedaluwarsa();

    $sql = "SELECT k.*, 
                   t.nama AS topik, 
                   j.nama AS tingkat, 
                   u.nama AS pengajar,
                   COUNT(s.id) AS jumlah_soal,
                   COALESCE(SUM(s.poin), 0) AS total_poin
            FROM kuis k
            JOIN topik t ON k.topik_id = t.id
            JOIN tingkat j ON k.tingkat_id = j.id
            JOIN user u ON k.pengajar_id = u.id
            LEFT JOIN soal s ON k.id = s.kuis_id
            WHERE k.status = 'terbit' AND k.akses = 'publik'";

    $params = [];

    // Filter berdasarkan topik mata pelajaran jika dipilih
    if (!empty($filterTopikId)) {
        $sql .= " AND k.topik_id = ?";
        $params[] = (int) $filterTopikId;
    }

    // Filter berdasarkan jenjang tingkat pendidikan jika dipilih
    if (!empty($filterTingkatId)) {
        $sql .= " AND k.tingkat_id = ?";
        $params[] = (int) $filterTingkatId;
    }

    // Filter pencarian berdasarkan judul kuis
    if (!empty($kataKunci)) {
        $sql .= " AND k.judul LIKE ?";
        $params[] = '%' . trim($kataKunci) . '%';
    }

    $sql .= " GROUP BY k.id ORDER BY k.diterbitkan_pada DESC, k.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $daftarKuis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tambahkan alias kolom yang ramah untuk tampilan template Farel (PL-02 & PL-03)
    foreach ($daftarKuis as $index => $kuis) {
        $daftarKuis[$index]['durasi'] = (int) $kuis['durasi_menit'];
        $daftarKuis[$index]['jumlah_soal'] = (int) $kuis['jumlah_soal'];

        // Tentukan perkiraan tingkat kesulitan berdasarkan durasi dan jumlah soal
        if ($kuis['durasi_menit'] <= 10) {
            $daftarKuis[$index]['kesulitan'] = 'mudah';
        } elseif ($kuis['durasi_menit'] <= 20) {
            $daftarKuis[$index]['kesulitan'] = 'sedang';
        } else {
            $daftarKuis[$index]['kesulitan'] = 'sulit';
        }
    }

    return $daftarKuis;
}

// ----------------------------------------------------------------------------
// 2. Mulai percobaan kuis baru atau lanjutkan yang sedang berjalan (KRJ-01, KRJ-08)
// ----------------------------------------------------------------------------
function mulaiPercobaanKuis($kuisId, $pelajarId) {
    global $pdo;

    // Bersihkan dulu percobaan yang sudah lewat batas waktu
    tutupPercobaanKedaluwarsa();

    // Pastikan kuisnya ada dan berstatus 'terbit'
    $stmtKuis = $pdo->prepare("SELECT * FROM kuis WHERE id = ? AND status = 'terbit' LIMIT 1");
    $stmtKuis->execute([$kuisId]);
    $kuis = $stmtKuis->fetch(PDO::FETCH_ASSOC);

    if (!$kuis) {
        return null;
    }

    // Cek apakah pelajar ini sudah punya percobaan yang masih berjalan pada kuis ini (KRJ-10)
    $stmtCek = $pdo->prepare("SELECT * FROM percobaan_kuis WHERE kuis_id = ? AND pelajar_id = ? AND status = 'berjalan' LIMIT 1");
    $stmtCek->execute([$kuisId, $pelajarId]);
    $percobaanAktif = $stmtCek->fetch(PDO::FETCH_ASSOC);

    if ($percobaanAktif) {
        // Cek apakah waktu pengerjaan sudah habis
        $sisaWaktu = hitungSisaWaktuDetik($percobaanAktif);
        if ($sisaWaktu <= 0) {
            // Waktu habis, tutup percobaan lama
            selesaikanPercobaan($percobaanAktif['id'], 'waktu_habis');
        } else {
            // Sesi masih aktif dan belum habis, lanjutkan percobaan ini (KRJ-08)
            return $percobaanAktif;
        }
    }

    // Hitung total poin semua soal kuis ini sebagai snapshot skor_maksimal (BR-07)
    $stmtPoin = $pdo->prepare("SELECT COALESCE(SUM(poin), 0) FROM soal WHERE kuis_id = ?");
    $stmtPoin->execute([$kuisId]);
    $skorMaksimal = (int) $stmtPoin->fetchColumn();

    // Buat baris baru di tabel percobaan_kuis
    $sqlInsert = "INSERT INTO percobaan_kuis 
                  (kuis_id, pelajar_id, waktu_mulai, durasi_menit, skor, skor_maksimal, jumlah_benar, jumlah_salah, status) 
                  VALUES (?, ?, NOW(), ?, 0, ?, 0, 0, 'berjalan')";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        $kuisId,
        $pelajarId,
        $kuis['durasi_menit'],
        $skorMaksimal
    ]);

    $percobaanIdBaru = (int) $pdo->lastInsertId();

    return ambilPercobaanById($percobaanIdBaru, $pelajarId);
}

// ----------------------------------------------------------------------------
// 3. Ambil 1 data percobaan kuis berdasarkan ID (disertai proteksi kepemilikan)
// ----------------------------------------------------------------------------
function ambilPercobaanById($percobaanId, $pelajarId = null) {
    global $pdo;

    $sql = "SELECT pk.*, 
                   k.judul AS judul_kuis, 
                   k.deskripsi AS deskripsi_kuis,
                   k.akses AS akses_kuis,
                   t.nama AS nama_topik, 
                   j.nama AS nama_tingkat
            FROM percobaan_kuis pk
            JOIN kuis k ON pk.kuis_id = k.id
            JOIN topik t ON k.topik_id = t.id
            JOIN tingkat j ON k.tingkat_id = j.id
            WHERE pk.id = ?";

    $params = [(int) $percobaanId];

    // Jika pelajarId diberikan, pastikan percobaan tersebut memang milik pelajar ini (SEC-06)
    if ($pelajarId !== null) {
        $sql .= " AND pk.pelajar_id = ?";
        $params[] = (int) $pelajarId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        return null;
    }

    return $data;
}

// ----------------------------------------------------------------------------
// 4. Ambil percobaan yang sedang berjalan untuk pelajar pada kuis tertentu
// ----------------------------------------------------------------------------
function ambilPercobaanAktif($pelajarId, $kuisId) {
    global $pdo;

    tutupPercobaanKedaluwarsa();

    $stmt = $pdo->prepare("SELECT * FROM percobaan_kuis WHERE pelajar_id = ? AND kuis_id = ? AND status = 'berjalan' LIMIT 1");
    $stmt->execute([(int) $pelajarId, (int) $kuisId]);
    $percobaan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$percobaan) {
        return null;
    }

    // Cek apakah waktu sudah lewat
    $sisa = hitungSisaWaktuDetik($percobaan);
    if ($sisa <= 0) {
        selesaikanPercobaan($percobaan['id'], 'waktu_habis');
        return null;
    }

    return $percobaan;
}

// ----------------------------------------------------------------------------
// 5. Hitung sisa waktu pengerjaan di server dalam satuan detik (KRJ-03)
// Refresh browser tidak akan mengulang timer karena dihitung dari waktu_mulai server!
// ----------------------------------------------------------------------------
function hitungSisaWaktuDetik($percobaan) {
    if (empty($percobaan['waktu_mulai']) || empty($percobaan['durasi_menit'])) {
        return 0;
    }

    $waktuMulaiTimestamp = strtotime($percobaan['waktu_mulai']);
    $totalDurasiDetik = (int) $percobaan['durasi_menit'] * 60;
    $batasWaktuTimestamp = $waktuMulaiTimestamp + $totalDurasiDetik;

    $sisaDetik = $batasWaktuTimestamp - time();

    if ($sisaDetik < 0) {
        return 0;
    }

    return $sisaDetik;
}

// ----------------------------------------------------------------------------
// 6. Simpan jawaban per soal pada percobaan kuis (KRJ-04, KRJ-05, BR-11)
// Digunakan oleh api/jawab.php pada Sesi 3 nanti
// ----------------------------------------------------------------------------
function simpanJawabanPercobaan($percobaanId, $soalId, $opsiId) {
    global $pdo;

    // Ambil data percobaan
    $stmtPercobaan = $pdo->prepare("SELECT * FROM percobaan_kuis WHERE id = ? LIMIT 1");
    $stmtPercobaan->execute([(int) $percobaanId]);
    $percobaan = $stmtPercobaan->fetch(PDO::FETCH_ASSOC);

    if (!$percobaan) {
        return [
            'sukses' => false,
            'pesan' => 'Data sesi percobaan kuis tidak ditemukan.'
        ];
    }

    // Pastikan percobaan masih berstatus berjalan
    if ($percobaan['status'] !== 'berjalan') {
        return [
            'sukses' => false,
            'pesan' => 'Percobaan kuis ini sudah selesai atau waktu pengerjaan telah habis.'
        ];
    }

    // Cek batas waktu dengan toleransi 5 detik untuk keterlambatan jaringan (BR-09)
    $waktuMulai = strtotime($percobaan['waktu_mulai']);
    $batasWaktu = $waktuMulai + ((int)$percobaan['durasi_menit'] * 60) + 5;
    if (time() > $batasWaktu) {
        selesaikanPercobaan($percobaan['id'], 'waktu_habis');
        return [
            'sukses' => false,
            'pesan' => 'Waktu pengerjaan kuis sudah habis.',
            'waktu_habis' => true
        ];
    }

    // Aturan BR-11: Satu soal hanya boleh dikirim sekali. Jawaban kedua ditolak (409)
    $stmtCekJawaban = $pdo->prepare("SELECT id FROM jawaban_percobaan WHERE percobaan_id = ? AND soal_id = ? LIMIT 1");
    $stmtCekJawaban->execute([(int) $percobaanId, (int) $soalId]);
    if ($stmtCekJawaban->fetch()) {
        return [
            'sukses' => false,
            'pesan' => 'Jawaban untuk soal ini sudah pernah dikirim dan terkunci.',
            'sudah_dijawab' => true
        ];
    }

    // Ambil data soal untuk mengetahui poin dan pembahasan
    $stmtSoal = $pdo->prepare("SELECT id, kuis_id, poin, pembahasan FROM soal WHERE id = ? AND kuis_id = ? LIMIT 1");
    $stmtSoal->execute([(int) $soalId, (int) $percobaan['kuis_id']]);
    $soal = $stmtSoal->fetch(PDO::FETCH_ASSOC);

    if (!$soal) {
        return [
            'sukses' => false,
            'pesan' => 'Soal tidak ditemukan pada kuis ini.'
        ];
    }

    // Ambil opsi yang dipilih pengguna dan cek kebenarannya
    $stmtOpsi = $pdo->prepare("SELECT id, benar FROM opsi_jawaban WHERE id = ? AND soal_id = ? LIMIT 1");
    $stmtOpsi->execute([(int) $opsiId, (int) $soalId]);
    $opsi = $stmtOpsi->fetch(PDO::FETCH_ASSOC);

    if (!$opsi) {
        return [
            'sukses' => false,
            'pesan' => 'Opsi jawaban tidak valid untuk soal ini.'
        ];
    }

    $isBenar = (int) $opsi['benar'] === 1 ? 1 : 0;
    $poinDidapat = $isBenar ? (int) $soal['poin'] : 0;

    // Simpan baris ke tabel jawaban_percobaan
    $sqlInsert = "INSERT INTO jawaban_percobaan (percobaan_id, soal_id, opsi_id, benar, poin, dijawab_pada) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        (int) $percobaanId,
        (int) $soalId,
        (int) $opsiId,
        $isBenar,
        $poinDidapat
    ]);

    // Ambil opsi yang merupakan kunci jawaban yang benar untuk umpan balik
    $stmtKunci = $pdo->prepare("SELECT id, label FROM opsi_jawaban WHERE soal_id = ? AND benar = 1 LIMIT 1");
    $stmtKunci->execute([(int) $soalId]);
    $kunci = $stmtKunci->fetch(PDO::FETCH_ASSOC);

    return [
        'sukses' => true,
        'benar' => (bool) $isBenar,
        'poin' => $poinDidapat,
        'pembahasan' => $soal['pembahasan'] ?? '',
        'kunci_id' => $kunci ? (int) $kunci['id'] : null,
        'kunci_label' => $kunci ? $kunci['label'] : ''
    ];
}

// ----------------------------------------------------------------------------
// 7. Selesaikan percobaan kuis dan hitung total skor akhir (KRJ-07, BR-07, BR-08, BR-10)
// ----------------------------------------------------------------------------
function selesaikanPercobaan($percobaanId, $statusAkhir = 'selesai') {
    global $pdo;

    $stmtPercobaan = $pdo->prepare("SELECT * FROM percobaan_kuis WHERE id = ? LIMIT 1");
    $stmtPercobaan->execute([(int) $percobaanId]);
    $percobaan = $stmtPercobaan->fetch(PDO::FETCH_ASSOC);

    if (!$percobaan) {
        return null;
    }

    // Jika sudah selesai sebelumnya, kembalikan data yang ada
    if ($percobaan['status'] === 'selesai' || $percobaan['status'] === 'waktu_habis') {
        return $percobaan;
    }

    // Hitung total skor dari jawaban yang benar
    $stmtHitung = $pdo->prepare("SELECT 
                                    COALESCE(SUM(CASE WHEN benar = 1 THEN poin ELSE 0 END), 0) AS total_skor,
                                    COALESCE(SUM(CASE WHEN benar = 1 THEN 1 ELSE 0 END), 0) AS jumlah_benar
                                 FROM jawaban_percobaan 
                                 WHERE percobaan_id = ?");
    $stmtHitung->execute([(int) $percobaanId]);
    $hasilJawaban = $stmtHitung->fetch(PDO::FETCH_ASSOC);

    $totalSkor = (int) $hasilJawaban['total_skor'];
    $jumlahBenar = (int) $hasilJawaban['jumlah_benar'];

    // Hitung total soal pada kuis untuk menghitung jumlah salah (BR-08)
    $stmtTotalSoal = $pdo->prepare("SELECT COUNT(*) FROM soal WHERE kuis_id = ?");
    $stmtTotalSoal->execute([(int) $percobaan['kuis_id']]);
    $totalSoalKuis = (int) $stmtTotalSoal->fetchColumn();

    // Soal yang tidak dijawab dihitung salah (BR-08)
    $jumlahSalah = max(0, $totalSoalKuis - $jumlahBenar);

    // Update status dan nilai akhir di tabel percobaan_kuis
    $sqlUpdate = "UPDATE percobaan_kuis SET 
                    skor = ?, 
                    jumlah_benar = ?, 
                    jumlah_salah = ?, 
                    status = ?, 
                    waktu_selesai = NOW() 
                  WHERE id = ?";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        $totalSkor,
        $jumlahBenar,
        $jumlahSalah,
        $statusAkhir,
        (int) $percobaanId
    ]);

    return ambilPercobaanById($percobaanId);
}

// ----------------------------------------------------------------------------
// 8. Tutup percobaan berjalan yang sudah kedaluwarsa / lewat batas waktu (PRD 9.7)
// Wajib dipanggil saat halaman Dashboard, Riwayat, Peringkat, dan Detail diakses
// ----------------------------------------------------------------------------
function tutupPercobaanKedaluwarsa() {
    global $pdo;

    // Cari percobaan berjalan yang waktu_mulai + durasi_menit sudah terlewat
    $sqlCari = "SELECT id, kuis_id FROM percobaan_kuis 
                WHERE status = 'berjalan' 
                  AND NOW() > DATE_ADD(waktu_mulai, INTERVAL durasi_menit MINUTE)";
    $stmtCari = $pdo->prepare($sqlCari);
    $stmtCari->execute();
    $daftarKedaluwarsa = $stmtCari->fetchAll(PDO::FETCH_ASSOC);

    $jumlahDitutup = 0;
    foreach ($daftarKedaluwarsa as $baris) {
        selesaikanPercobaan($baris['id'], 'waktu_habis');
        $jumlahDitutup++;
    }

    return $jumlahDitutup;
}

// ----------------------------------------------------------------------------
// 9. Tutup percobaan saat kuis ditarik oleh pengajar atau admin (KT-05)
// Dipanggil di dalam transaksi Zalfa (PJ-02) dan Nanda (AD-06)
// ----------------------------------------------------------------------------
function tutupPercobaanKuis($kuisId) {
    global $pdo;

    // Cari semua percobaan kuis ini yang statusnya masih berjalan
    $stmtCari = $pdo->prepare("SELECT id FROM percobaan_kuis WHERE kuis_id = ? AND status = 'berjalan'");
    $stmtCari->execute([(int) $kuisId]);
    $daftarPercobaan = $stmtCari->fetchAll(PDO::FETCH_ASSOC);

    $jumlahDitutup = 0;
    foreach ($daftarPercobaan as $baris) {
        selesaikanPercobaan($baris['id'], 'selesai');

        // Beri catatan bahwa kuis ditarik saat sedang dikerjakan
        $stmtCatat = $pdo->prepare("UPDATE percobaan_kuis SET catatan = 'Kuis ditarik oleh pengajar/admin saat pengerjaan berlangsung' WHERE id = ?");
        $stmtCatat->execute([(int) $baris['id']]);

        $jumlahDitutup++;
    }

    return $jumlahDitutup;
}

// ----------------------------------------------------------------------------
// 10. Ambil daftar jawaban yang sudah tersimpan pada suatu percobaan
// Sangat berguna saat halaman Kerjakan di-refresh (AC-12) agar jawaban lama tetap terpilih!
// ----------------------------------------------------------------------------
function ambilJawabanPercobaanByPercobaanId($percobaanId) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM jawaban_percobaan WHERE percobaan_id = ?");
    $stmt->execute([(int) $percobaanId]);
    $daftar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format menjadi array dengan key soal_id agar mudah dicek di tampilan
    $hasil = [];
    foreach ($daftar as $baris) {
        $hasil[(int) $baris['soal_id']] = $baris;
    }

    return $hasil;
}

// ----------------------------------------------------------------------------
// 11. Ambil detail hasil lengkap untuk halaman Hasil Kuis (HSL-01, PL-06)
// ----------------------------------------------------------------------------
function ambilHasilPercobaan($percobaanId, $pelajarId = null) {
    global $pdo;

    $percobaan = ambilPercobaanById($percobaanId, $pelajarId);
    if (!$percobaan) {
        return null;
    }

    // Hitung persentase nilai (BR-07)
    $persenNilai = 0;
    if ($percobaan['skor_maksimal'] > 0) {
        $persenNilai = (int) round(($percobaan['skor'] / $percobaan['skor_maksimal']) * 100);
    }
    $percobaan['persen_nilai'] = $persenNilai;

    // Ambil jawaban pelajar yang ada
    $jawabanPelajar = ambilJawabanPercobaanByPercobaanId($percobaanId);

    // Ambil semua soal dan opsi pada kuis ini untuk rincian pembahasan
    $stmtSoal = $pdo->prepare("SELECT * FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
    $stmtSoal->execute([(int) $percobaan['kuis_id']]);
    $daftarSoal = $stmtSoal->fetchAll(PDO::FETCH_ASSOC);

    $stmtOpsi = $pdo->prepare("SELECT * FROM opsi_jawaban WHERE soal_id = ? ORDER BY label ASC");

    foreach ($daftarSoal as $idx => $s) {
        $stmtOpsi->execute([(int) $s['id']]);
        $daftarOpsi = $stmtOpsi->fetchAll(PDO::FETCH_ASSOC);

        $daftarSoal[$idx]['opsi'] = $daftarOpsi;

        // Pasangkan dengan jawaban pelajar jika ada
        if (isset($jawabanPelajar[(int) $s['id']])) {
            $daftarSoal[$idx]['sudah_dijawab'] = true;
            $daftarSoal[$idx]['opsi_terpilih_id'] = (int) $jawabanPelajar[(int) $s['id']]['opsi_id'];
            $daftarSoal[$idx]['status_benar'] = (int) $jawabanPelajar[(int) $s['id']]['benar'] === 1;
        } else {
            $daftarSoal[$idx]['sudah_dijawab'] = false;
            $daftarSoal[$idx]['opsi_terpilih_id'] = null;
            $daftarSoal[$idx]['status_benar'] = false;
        }
    }

    $percobaan['rincian_soal'] = $daftarSoal;

    return $percobaan;
}

// ----------------------------------------------------------------------------
// 12. Ambil riwayat percobaan kuis milik Pelajar (untuk PL-07 Riwayat)
// ----------------------------------------------------------------------------
function riwayatPercobaanPelajar($pelajarId, $limit = 20) {
    global $pdo;

    tutupPercobaanKedaluwarsa();

    $sql = "SELECT pk.*, 
                   k.judul AS judul_kuis, 
                   t.nama AS nama_topik, 
                   j.nama AS nama_tingkat
            FROM percobaan_kuis pk
            JOIN kuis k ON pk.kuis_id = k.id
            JOIN topik t ON k.topik_id = t.id
            JOIN tingkat j ON k.tingkat_id = j.id
            WHERE pk.pelajar_id = ? AND pk.status IN ('selesai', 'waktu_habis')
            ORDER BY pk.waktu_mulai DESC
            LIMIT ?";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, (int) $pelajarId, PDO::PARAM_INT);
    $stmt->bindValue(2, (int) $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ----------------------------------------------------------------------------
// 13. Kumpulan Fungsi Percobaan untuk Mode Tamu / Guest (Sesi PHP Saja, KT-13, BR-14)
// Data Guest tidak disimpan di database agar privasi terjaga dan database bersih
// ----------------------------------------------------------------------------

function mulaiPercobaanGuest($kuisId, $durasiMenit, $skorMaksimal) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['guest_percobaan'])) {
        $_SESSION['guest_percobaan'] = [];
    }

    $_SESSION['guest_percobaan'][$kuisId] = [
        'kuis_id' => (int) $kuisId,
        'waktu_mulai' => date('Y-m-d H:i:s'),
        'durasi_menit' => (int) $durasiMenit,
        'skor_maksimal' => (int) $skorMaksimal,
        'jawaban' => [],
        'status' => 'berjalan'
    ];

    return $_SESSION['guest_percobaan'][$kuisId];
}

function ambilPercobaanGuest($kuisId) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return $_SESSION['guest_percobaan'][$kuisId] ?? null;
}

function simpanJawabanGuest($kuisId, $soalId, $opsiId, $isBenar, $poin) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['guest_percobaan'][$kuisId])) {
        return false;
    }

    // Kunci jawaban jika sudah pernah dijawab
    if (isset($_SESSION['guest_percobaan'][$kuisId]['jawaban'][$soalId])) {
        return false;
    }

    $_SESSION['guest_percobaan'][$kuisId]['jawaban'][$soalId] = [
        'opsi_id' => (int) $opsiId,
        'benar' => (bool) $isBenar,
        'poin' => (int) $poin
    ];

    return true;
}

function selesaikanPercobaanGuest($kuisId, $totalSoalKuis = 0) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['guest_percobaan'][$kuisId])) {
        return null;
    }

    $percobaan = $_SESSION['guest_percobaan'][$kuisId];
    $totalSkor = 0;
    $jumlahBenar = 0;

    foreach ($percobaan['jawaban'] as $jwb) {
        if ($jwb['benar']) {
            $totalSkor += (int) $jwb['poin'];
            $jumlahBenar++;
        }
    }

    $jumlahSalah = max(0, $totalSoalKuis - $jumlahBenar);

    $hasil = [
        'kuis_id' => (int) $kuisId,
        'skor' => $totalSkor,
        'skor_maksimal' => (int) $percobaan['skor_maksimal'],
        'jumlah_benar' => $jumlahBenar,
        'jumlah_salah' => $jumlahSalah,
        'waktu_mulai' => $percobaan['waktu_mulai'],
        'waktu_selesai' => date('Y-m-d H:i:s'),
        'jawaban' => $percobaan['jawaban']
    ];

    $_SESSION['guest_hasil'][$kuisId] = $hasil;
    $_SESSION['guest_percobaan'][$kuisId]['status'] = 'selesai';

    return $hasil;
}

function ambilHasilGuest($kuisId) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return $_SESSION['guest_hasil'][$kuisId] ?? null;
}
