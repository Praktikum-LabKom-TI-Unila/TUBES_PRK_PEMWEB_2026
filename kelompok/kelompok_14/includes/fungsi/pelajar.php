<?php
// includes/fungsi/pelajar.php
// Kumpulan fungsi logika bisnis untuk halaman khusus Pelajar (Riwayat, Peringkat, Dashboard)
// PIC: Farrel

// 1. Ambil semua daftar topik / mata pelajaran untuk pilihan filter
function daftarSemuaTopik($pdo) {
    $stmt = $pdo->prepare("SELECT id, nama FROM topik ORDER BY nama ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Hitung total riwayat pengerjaan kuis milik seorang pelajar (untuk pagination)
function hitungTotalRiwayatPelajar($pdo, $pelajarId, $topikId = 0, $tanggalMulai = '', $tanggalSelesai = '') {
    $sql = "SELECT COUNT(*)
            FROM percobaan_kuis pk
            JOIN kuis k ON pk.kuis_id = k.id
            WHERE pk.pelajar_id = ?";
    $params = [(int) $pelajarId];

    if ($topikId > 0) {
        $sql .= " AND k.topik_id = ?";
        $params[] = (int) $topikId;
    }

    if ($tanggalMulai !== '') {
        $sql .= " AND DATE(pk.waktu_mulai) >= ?";
        $params[] = $tanggalMulai;
    }

    if ($tanggalSelesai !== '') {
        $sql .= " AND DATE(pk.waktu_mulai) <= ?";
        $params[] = $tanggalSelesai;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

// 3. Ambil daftar riwayat pengerjaan kuis milik seorang pelajar dengan filter dan pagination (HSL-03)
function ambilRiwayatPelajar($pdo, $pelajarId, $topikId = 0, $tanggalMulai = '', $tanggalSelesai = '', $limit = 10, $offset = 0) {
    $sql = "SELECT pk.*,
                   k.judul AS judul_kuis,
                   k.status AS status_kuis,
                   t.nama AS nama_topik,
                   j.nama AS nama_tingkat
            FROM percobaan_kuis pk
            JOIN kuis k ON pk.kuis_id = k.id
            JOIN topik t ON k.topik_id = t.id
            JOIN tingkat j ON k.tingkat_id = j.id
            WHERE pk.pelajar_id = ?";
    $params = [(int) $pelajarId];

    if ($topikId > 0) {
        $sql .= " AND k.topik_id = ?";
        $params[] = (int) $topikId;
    }

    if ($tanggalMulai !== '') {
        $sql .= " AND DATE(pk.waktu_mulai) >= ?";
        $params[] = $tanggalMulai;
    }

    if ($tanggalSelesai !== '') {
        $sql .= " AND DATE(pk.waktu_mulai) <= ?";
        $params[] = $tanggalSelesai;
    }

    $sql .= " ORDER BY pk.waktu_mulai DESC LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);

    // Bind parameter filter secara berurutan
    $index = 1;
    foreach ($params as $nilai) {
        $tipe = is_int($nilai) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($index, $nilai, $tipe);
        $index++;
    }

    // Bind LIMIT dan OFFSET sebagai integer agar aman di MySQL/MariaDB
    $stmt->bindValue($index++, (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue($index, (int) $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// 4. Ambil data papan peringkat Pelajar berdasarkan rumus KT-03 & BR-15:
// Total poin dari skor terbaik tiap kuis per Pelajar. Tie-breaker: total waktu tercepat.
function ambilPeringkatPelajar($pdo, $topikId = 0) {
    $filterTopikSql = '';
    $params = [];

    if ($topikId > 0) {
        $filterTopikSql = ' AND k.topik_id = ?';
        $params[] = (int) $topikId;
    }

    $sql = "SELECT
                u.id AS pelajar_id,
                u.nama,
                SUM(best.skor_terbaik) AS total_poin,
                COUNT(best.kuis_id) AS jumlah_kuis,
                SUM(best.waktu_tercepat_detik) AS total_waktu_detik
            FROM (
                SELECT
                    pk.pelajar_id,
                    pk.kuis_id,
                    MAX(pk.skor) AS skor_terbaik,
                    MIN(GREATEST(0, TIMESTAMPDIFF(SECOND, pk.waktu_mulai, COALESCE(pk.waktu_selesai, pk.waktu_mulai)))) AS waktu_tercepat_detik
                FROM percobaan_kuis pk
                JOIN kuis k ON pk.kuis_id = k.id
                WHERE pk.status IN ('selesai', 'waktu_habis')
                {$filterTopikSql}
                GROUP BY pk.pelajar_id, pk.kuis_id
            ) AS best
            JOIN user u ON best.pelajar_id = u.id
            WHERE u.peran = 'pelajar'
            GROUP BY u.id, u.nama
            ORDER BY total_poin DESC, total_waktu_detik ASC, u.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $daftar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tambahkan nomor peringkat secara berurutan
    $peringkat = 1;
    foreach ($daftar as $index => $baris) {
        $daftar[$index]['peringkat'] = $peringkat;
        $daftar[$index]['pelajar_id'] = (int) $baris['pelajar_id'];
        $daftar[$index]['total_poin'] = (int) $baris['total_poin'];
        $daftar[$index]['jumlah_kuis'] = (int) $baris['jumlah_kuis'];
        $daftar[$index]['total_waktu_detik'] = (int) $baris['total_waktu_detik'];
        $peringkat++;
    }

    return $daftar;
}

// 5. Ubah detik menjadi format durasi yang mudah dibaca (mis. "12m 30d")
function formatDurasiSingkat($totalDetik) {
    $totalDetik = max(0, (int) $totalDetik);
    $jam = floor($totalDetik / 3600);
    $menit = floor(($totalDetik % 3600) / 60);
    $detik = $totalDetik % 60;

    if ($jam > 0) {
        return $jam . 'j ' . $menit . 'm';
    } elseif ($menit > 0) {
        return $menit . 'm ' . $detik . 'd';
    }
    return $detik . 'd';
}


// 6. Hitung streak harian belajar (hari kalender WIB berturut-turut)
function hitungStreakPelajar($pdo, $pelajarId) {
    $sql = "SELECT DISTINCT DATE(waktu_mulai) AS tgl
            FROM percobaan_kuis
            WHERE pelajar_id = ?
              AND status IN ('selesai', 'waktu_habis')
            ORDER BY tgl DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pelajarId]);
    $daftarTanggal = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($daftarTanggal)) {
        return 0;
    }

    $hariIni = date('Y-m-d');
    $kemarin = date('Y-m-d', strtotime('-1 day'));

    // Streak aktif jika percobaan terakhir dilakukan hari ini atau kemarin
    if ($daftarTanggal[0] !== $hariIni && $daftarTanggal[0] !== $kemarin) {
        return 0;
    }

    $streak = 0;
    $tanggalHarapan = $daftarTanggal[0];

    foreach ($daftarTanggal as $tgl) {
        if ($tgl === $tanggalHarapan) {
            $streak++;
            $tanggalHarapan = date('Y-m-d', strtotime($tanggalHarapan . ' -1 day'));
        } else {
            break;
        }
    }

    return $streak;
}

// 7. Ambil ringkasan statistik untuk dashboard pelajar
function ambilStatistikDashboardPelajar($pdo, $pelajarId) {
    $sql = "SELECT COUNT(*) AS total_latihan,
                   SUM(CASE WHEN status IN ('selesai', 'waktu_habis') THEN 1 ELSE 0 END) AS total_selesai,
                   AVG(CASE
                       WHEN status IN ('selesai', 'waktu_habis') AND skor_maksimal > 0
                       THEN (skor / skor_maksimal) * 100
                       ELSE NULL
                   END) AS rata_rata_skor
            FROM percobaan_kuis
            WHERE pelajar_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pelajarId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'total_latihan' => (int) ($data['total_latihan'] ?? 0),
        'total_selesai' => (int) ($data['total_selesai'] ?? 0),
        'rata_rata_skor' => (int) round((float) ($data['rata_rata_skor'] ?? 0)),
        'streak_hari' => hitungStreakPelajar($pdo, $pelajarId),
    ];
}

// 8. Ambil 1 kuis yang masih berjalan atau 1 kuis terakhir selesai
function ambilKuisBerjalanDanTerakhir($pdo, $pelajarId) {
    $sqlBerjalan = "SELECT p.id AS percobaan_id,
                           p.kuis_id,
                           p.waktu_mulai,
                           p.durasi_menit,
                           p.status,
                           k.judul,
                           t.nama AS nama_topik,
                           tg.nama AS nama_tingkat
                    FROM percobaan_kuis p
                    JOIN kuis k ON p.kuis_id = k.id
                    JOIN topik t ON k.topik_id = t.id
                    JOIN tingkat tg ON k.tingkat_id = tg.id
                    WHERE p.pelajar_id = ?
                      AND p.status = 'berjalan'
                    ORDER BY p.waktu_mulai DESC, p.id DESC
                    LIMIT 1";
    $stmtBerjalan = $pdo->prepare($sqlBerjalan);
    $stmtBerjalan->execute([$pelajarId]);
    $berjalan = $stmtBerjalan->fetch(PDO::FETCH_ASSOC);

    if ($berjalan) {
        if (function_exists('hitungSisaWaktuDetik')) {
            $berjalan['sisa_detik'] = hitungSisaWaktuDetik($berjalan);
        } else {
            $berjalan['sisa_detik'] = (int) $berjalan['durasi_menit'] * 60;
        }
        return [
            'berjalan' => $berjalan,
            'terakhir' => null,
        ];
    }

    $sqlTerakhir = "SELECT p.id AS percobaan_id,
                           p.kuis_id,
                           p.skor,
                           p.skor_maksimal,
                           p.jumlah_benar,
                           p.jumlah_salah,
                           p.waktu_mulai,
                           p.waktu_selesai,
                           p.status,
                           k.judul,
                           t.nama AS nama_topik,
                           tg.nama AS nama_tingkat
                    FROM percobaan_kuis p
                    JOIN kuis k ON p.kuis_id = k.id
                    JOIN topik t ON k.topik_id = t.id
                    JOIN tingkat tg ON k.tingkat_id = tg.id
                    WHERE p.pelajar_id = ?
                      AND p.status IN ('selesai', 'waktu_habis')
                    ORDER BY COALESCE(p.waktu_selesai, p.waktu_mulai) DESC, p.id DESC
                    LIMIT 1";
    $stmtTerakhir = $pdo->prepare($sqlTerakhir);
    $stmtTerakhir->execute([$pelajarId]);
    $terakhir = $stmtTerakhir->fetch(PDO::FETCH_ASSOC);

    return [
        'berjalan' => null,
        'terakhir' => $terakhir ?: null,
    ];
}

// 9. Ambil rekomendasi kuis pintar (maks 4 kuis publik terbit belum dikerjakan)
function ambilRekomendasiKuisPelajar($pdo, $pelajarId, $batas = 4) {
    $batas = max(1, (int) $batas);

    // Cari topik yang paling sering dikerjakan oleh pelajar ini
    $sqlTopik = "SELECT k.topik_id, COUNT(*) AS jumlah
                 FROM percobaan_kuis p
                 JOIN kuis k ON p.kuis_id = k.id
                 WHERE p.pelajar_id = ?
                 GROUP BY k.topik_id
                 ORDER BY jumlah DESC
                 LIMIT 1";
    $stmtTopik = $pdo->prepare($sqlTopik);
    $stmtTopik->execute([$pelajarId]);
    $topikFavorit = (int) ($stmtTopik->fetchColumn() ?: 0);

    $params = [$pelajarId];
    $urutanTopik = "";
    if ($topikFavorit > 0) {
        $urutanTopik = "(k.topik_id = ?) DESC, ";
        $params[] = $topikFavorit;
    }

    $sql = "SELECT k.id,
                   k.judul,
                   k.durasi_menit,
                   k.diterbitkan_pada,
                   t.nama AS nama_topik,
                   tg.nama AS nama_tingkat,
                   u.nama AS nama_pengajar,
                   (SELECT COUNT(*) FROM soal s WHERE s.kuis_id = k.id) AS jumlah_soal
            FROM kuis k
            JOIN topik t ON k.topik_id = t.id
            JOIN tingkat tg ON k.tingkat_id = tg.id
            JOIN user u ON k.pengajar_id = u.id
            WHERE k.status = 'terbit'
              AND k.akses = 'publik'
              AND NOT EXISTS (
                  SELECT 1
                  FROM percobaan_kuis p
                  WHERE p.kuis_id = k.id
                    AND p.pelajar_id = ?
              )
            ORDER BY {$urutanTopik}k.diterbitkan_pada DESC, k.id DESC
            LIMIT {$batas}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $daftar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($daftar as $index => $kuis) {
        $durasi = (int) $kuis['durasi_menit'];
        $daftar[$index]['durasi_menit'] = $durasi;
        $daftar[$index]['jumlah_soal'] = (int) $kuis['jumlah_soal'];
        if ($durasi <= 10) {
            $daftar[$index]['kesulitan'] = 'Mudah';
        } elseif ($durasi <= 20) {
            $daftar[$index]['kesulitan'] = 'Sedang';
        } else {
            $daftar[$index]['kesulitan'] = 'Sulit';
        }
    }

    return $daftar;
}
