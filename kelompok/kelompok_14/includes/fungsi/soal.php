<?php
// Fungsi-fungsi untuk mengelola soal dan pilihan jawaban

require_once __DIR__ . '/kuis.php';

// Ambil semua soal beserta pilihan jawabannya berdasarkan id kuis
function ambilSoalByKuis($kuisId) {
    global $pdo;

    $stmtSoal = $pdo->prepare("SELECT * FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
    $stmtSoal->execute([$kuisId]);
    $daftarSoal = $stmtSoal->fetchAll(PDO::FETCH_ASSOC);

    $stmtOpsi = $pdo->prepare("SELECT * FROM opsi_jawaban WHERE soal_id = ? ORDER BY label ASC");

    foreach ($daftarSoal as $index => $soal) {
        $stmtOpsi->execute([$soal['id']]);
        $daftarSoal[$index]['opsi'] = $stmtOpsi->fetchAll(PDO::FETCH_ASSOC);
    }

    return $daftarSoal;
}

// Ambil 1 soal beserta pilihan jawabannya berdasarkan id soal dan id kuis
function ambilSoalById($soalId, $kuisId) {
    global $pdo;

    $stmtSoal = $pdo->prepare("SELECT * FROM soal WHERE id = ? AND kuis_id = ?");
    $stmtSoal->execute([$soalId, $kuisId]);
    $soal = $stmtSoal->fetch(PDO::FETCH_ASSOC);

    if (!$soal) {
        return null;
    }

    $stmtOpsi = $pdo->prepare("SELECT * FROM opsi_jawaban WHERE soal_id = ? ORDER BY label ASC");
    $stmtOpsi->execute([$soalId]);
    $soal['opsi'] = $stmtOpsi->fetchAll(PDO::FETCH_ASSOC);

    return $soal;
}

// Validasi data input form soal dan pilihan jawaban
function validasiInputSoal($data) {
    $pertanyaan = trim($data['pertanyaan'] ?? '');
    $pembahasan = trim($data['pembahasan'] ?? '');
    $poin = (int) ($data['poin'] ?? 10);
    $inputOpsi = $data['opsi'] ?? [];
    $kunci = strtoupper(trim($data['kunci'] ?? ''));

    if ($pertanyaan == '') {
        return ['valid' => false, 'pesan' => 'Teks pertanyaan tidak boleh kosong.'];
    }

    if ($poin < 1 || $poin > 100) {
        return ['valid' => false, 'pesan' => 'Poin soal harus antara 1 sampai 100.'];
    }

    $daftarLabel = ['A', 'B', 'C', 'D', 'E'];
    $opsiRapih = [];
    $adaKunci = false;
    $index = 0;

    foreach ($inputOpsi as $key => $teks) {
        $teksBersih = trim($teks);
        $keyHuruf = strtoupper((string) $key);
        $labelAsli = $daftarLabel[$index] ?? '';
        $index++;

        // Lewati pilihan yang dikosongkan
        if ($teksBersih == '') {
            continue;
        }

        if (strlen($teksBersih) > 255) {
            return ['valid' => false, 'pesan' => 'Teks pilihan jawaban maksimal 255 karakter.'];
        }

        $nomorBaru = count($opsiRapih);
        if ($nomorBaru >= 5) {
            break;
        }

        $labelBaru = $daftarLabel[$nomorBaru];
        $apakahBenar = ($kunci == $keyHuruf || $kunci == $labelAsli) ? 1 : 0;

        if ($apakahBenar == 1) {
            $adaKunci = true;
        }

        $opsiRapih[] = [
            'label' => $labelBaru,
            'teks'  => $teksBersih,
            'benar' => $apakahBenar
        ];
    }

    if (count($opsiRapih) < 2) {
        return ['valid' => false, 'pesan' => 'Soal harus memiliki minimal 2 pilihan jawaban yang diisi.'];
    }

    if (!$adaKunci) {
        return ['valid' => false, 'pesan' => 'Pilih salah satu opsi yang terisi sebagai kunci jawaban.'];
    }

    return [
        'valid'      => true,
        'pertanyaan' => $pertanyaan,
        'pembahasan' => $pembahasan != '' ? $pembahasan : null,
        'poin'       => $poin,
        'opsi'       => $opsiRapih
    ];
}

// Tambah soal baru beserta opsi jawabannya
function tambahSoal($kuisId, $pengajarId, $data) {
    global $pdo;

    $kuis = ambilKuisById($kuisId, $pengajarId);
    if (!$kuis) {
        return ['berhasil' => false, 'pesan' => 'Kuis tidak ditemukan.'];
    }

    if ($kuis['status'] == 'menunggu' || $kuis['status'] == 'terbit') {
        return ['berhasil' => false, 'pesan' => 'Tidak bisa menambah soal saat kuis sedang menunggu atau sudah terbit.'];
    }

    $cek = validasiInputSoal($data);
    if (!$cek['valid']) {
        return ['berhasil' => false, 'pesan' => $cek['pesan']];
    }

    $pdo->beginTransaction();

    try {
        // Kalau kuis sebelumnya ditolak atau ditarik, kembalikan ke draft dulu
        if ($kuis['status'] == 'ditolak' || $kuis['status'] == 'ditarik') {
            ubahStatusKuis($kuisId, $kuis['status'], 'draft', $pengajarId, null);
        }

        // Cari urutan terakhir
        $stmtUrutan = $pdo->prepare("SELECT COALESCE(MAX(urutan), 0) + 1 FROM soal WHERE kuis_id = ?");
        $stmtUrutan->execute([$kuisId]);
        $urutanBaru = (int) $stmtUrutan->fetchColumn();

        // Simpan ke tabel soal
        $stmtSoal = $pdo->prepare(
            "INSERT INTO soal (kuis_id, urutan, pertanyaan, pembahasan, poin) VALUES (?, ?, ?, ?, ?)"
        );
        $stmtSoal->execute([
            $kuisId,
            $urutanBaru,
            $cek['pertanyaan'],
            $cek['pembahasan'],
            $cek['poin']
        ]);

        $soalId = $pdo->lastInsertId();

        // Simpan tiap opsi jawaban ke tabel opsi_jawaban
        $stmtOpsi = $pdo->prepare(
            "INSERT INTO opsi_jawaban (soal_id, label, teks, benar) VALUES (?, ?, ?, ?)"
        );

        foreach ($cek['opsi'] as $item) {
            $stmtOpsi->execute([
                $soalId,
                $item['label'],
                $item['teks'],
                $item['benar']
            ]);
        }

        $pdo->commit();
        return ['berhasil' => true, 'soal_id' => $soalId];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['berhasil' => false, 'pesan' => 'Gagal menyimpan soal.'];
    }
}

// Edit soal beserta opsi jawabannya
function editSoal($soalId, $kuisId, $pengajarId, $data) {
    global $pdo;

    $kuis = ambilKuisById($kuisId, $pengajarId);
    if (!$kuis) {
        return ['berhasil' => false, 'pesan' => 'Kuis tidak ditemukan.'];
    }

    if ($kuis['status'] == 'menunggu' || $kuis['status'] == 'terbit') {
        return ['berhasil' => false, 'pesan' => 'Tidak bisa mengedit soal saat kuis sedang menunggu atau sudah terbit.'];
    }

    $soalLama = ambilSoalById($soalId, $kuisId);
    if (!$soalLama) {
        return ['berhasil' => false, 'pesan' => 'Soal tidak ditemukan.'];
    }

    $cek = validasiInputSoal($data);
    if (!$cek['valid']) {
        return ['berhasil' => false, 'pesan' => $cek['pesan']];
    }

    $pdo->beginTransaction();

    try {
        if ($kuis['status'] == 'ditolak' || $kuis['status'] == 'ditarik') {
            ubahStatusKuis($kuisId, $kuis['status'], 'draft', $pengajarId, null);
        }

        // Update tabel soal
        $stmtUpdate = $pdo->prepare(
            "UPDATE soal SET pertanyaan = ?, pembahasan = ?, poin = ? WHERE id = ? AND kuis_id = ?"
        );
        $stmtUpdate->execute([
            $cek['pertanyaan'],
            $cek['pembahasan'],
            $cek['poin'],
            $soalId,
            $kuisId
        ]);

        // Hapus opsi lama lalu masukkan opsi yang baru
        $stmtHapusOpsi = $pdo->prepare("DELETE FROM opsi_jawaban WHERE soal_id = ?");
        $stmtHapusOpsi->execute([$soalId]);

        $stmtOpsi = $pdo->prepare(
            "INSERT INTO opsi_jawaban (soal_id, label, teks, benar) VALUES (?, ?, ?, ?)"
        );

        foreach ($cek['opsi'] as $item) {
            $stmtOpsi->execute([
                $soalId,
                $item['label'],
                $item['teks'],
                $item['benar']
            ]);
        }

        $pdo->commit();
        return ['berhasil' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['berhasil' => false, 'pesan' => 'Gagal mengubah soal.'];
    }
}

// Hapus 1 soal dan rapikan kembali nomor urutan soal yang tersisa
function hapusSoal($soalId, $kuisId, $pengajarId) {
    global $pdo;

    $kuis = ambilKuisById($kuisId, $pengajarId);
    if (!$kuis) {
        return ['berhasil' => false, 'pesan' => 'Kuis tidak ditemukan.'];
    }

    if ($kuis['status'] == 'menunggu' || $kuis['status'] == 'terbit') {
        return ['berhasil' => false, 'pesan' => 'Tidak bisa menghapus soal saat kuis sedang menunggu atau sudah terbit.'];
    }

    $soal = ambilSoalById($soalId, $kuisId);
    if (!$soal) {
        return ['berhasil' => false, 'pesan' => 'Soal tidak ditemukan.'];
    }

    $pdo->beginTransaction();

    try {
        if ($kuis['status'] == 'ditolak' || $kuis['status'] == 'ditarik') {
            ubahStatusKuis($kuisId, $kuis['status'], 'draft', $pengajarId, null);
        }

        // Hapus soal (opsi_jawaban otomatis ikut terhapus karena ON DELETE CASCADE)
        $stmtHapus = $pdo->prepare("DELETE FROM soal WHERE id = ? AND kuis_id = ?");
        $stmtHapus->execute([$soalId, $kuisId]);

        // Ambil sisa soal untuk dirapikan nomor urutannya
        $stmtSisa = $pdo->prepare("SELECT id FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
        $stmtSisa->execute([$kuisId]);
        $daftarSisa = $stmtSisa->fetchAll(PDO::FETCH_ASSOC);

        // Geser urutan +1000 dulu supaya tidak bentrok dengan UNIQUE(kuis_id, urutan)
        $stmtGeser = $pdo->prepare("UPDATE soal SET urutan = urutan + 1000 WHERE kuis_id = ?");
        $stmtGeser->execute([$kuisId]);

        // Tulis urutan baru mulai dari 1
        $stmtUrut = $pdo->prepare("UPDATE soal SET urutan = ? WHERE id = ?");
        $nomor = 1;
        foreach ($daftarSisa as $baris) {
            $stmtUrut->execute([$nomor, $baris['id']]);
            $nomor++;
        }

        $pdo->commit();
        return ['berhasil' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['berhasil' => false, 'pesan' => 'Gagal menghapus soal.'];
    }
}

// Geser urutan soal ke atas ('naik') atau ke bawah ('turun')
function geserUrutanSoal($soalId, $kuisId, $pengajarId, $arah) {
    global $pdo;

    $kuis = ambilKuisById($kuisId, $pengajarId);
    if (!$kuis) {
        return ['berhasil' => false, 'pesan' => 'Kuis tidak ditemukan.'];
    }

    if ($kuis['status'] == 'menunggu' || $kuis['status'] == 'terbit') {
        return ['berhasil' => false, 'pesan' => 'Urutan soal tidak bisa diubah saat kuis sedang menunggu atau terbit.'];
    }

    // Ambil semua soal diurutkan dari urutan terkecil
    $stmt = $pdo->prepare("SELECT id, urutan FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
    $stmt->execute([$kuisId]);
    $daftarSoal = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cari posisi soal yang mau digeser
    $posisi = -1;
    foreach ($daftarSoal as $index => $item) {
        if ($item['id'] == $soalId) {
            $posisi = $index;
            break;
        }
    }

    if ($posisi == -1) {
        return ['berhasil' => false, 'pesan' => 'Soal tidak ditemukan.'];
    }

    // Tentukan posisi tujuan tukar
    $posisiTukar = -1;
    if ($arah == 'naik' && $posisi > 0) {
        $posisiTukar = $posisi - 1;
    } else if ($arah == 'turun' && $posisi < count($daftarSoal) - 1) {
        $posisiTukar = $posisi + 1;
    } else {
        // Sudah paling atas atau paling bawah, tidak perlu ditukar
        return ['berhasil' => true];
    }

    $soalA = $daftarSoal[$posisi];
    $soalB = $daftarSoal[$posisiTukar];

    $pdo->beginTransaction();

    try {
        if ($kuis['status'] == 'ditolak' || $kuis['status'] == 'ditarik') {
            ubahStatusKuis($kuisId, $kuis['status'], 'draft', $pengajarId, null);
        }

        // Langkah 1: pindahkan soalA ke urutan sementara (+1000) agar tidak bentrok unique
        $stmtTemp = $pdo->prepare("UPDATE soal SET urutan = ? WHERE id = ?");
        $stmtTemp->execute([$soalA['urutan'] + 1000, $soalA['id']]);

        // Langkah 2: pindahkan soalB ke urutan soalA
        $stmtTemp->execute([$soalA['urutan'], $soalB['id']]);

        // Langkah 3: pindahkan soalA ke urutan soalB
        $stmtTemp->execute([$soalB['urutan'], $soalA['id']]);

        $pdo->commit();
        return ['berhasil' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['berhasil' => false, 'pesan' => 'Gagal mengubah urutan soal.'];
    }
}
