<?php
// Fungsi-fungsi Admin untuk mengelola Data Master: Mapel (Topik) dan Tingkat (ADM-06, BR-18)

// ============================================================================
// BAGIAN 1: KELOLA MAPEL / TOPIK (Tahap 4)
// ============================================================================

// 1. Ambil semua mapel beserta jumlah kuis yang memakainya
function daftarTopikDenganJumlahKuis() {
    global $pdo;

    $sql = "SELECT topik.*, COUNT(kuis.id) AS jumlah_kuis
            FROM topik
            LEFT JOIN kuis ON kuis.topik_id = topik.id
            GROUP BY topik.id
            ORDER BY topik.nama ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Tambah mapel / topik baru (ADM-06, BR-18)
function tambahTopik($nama, $deskripsi = '') {
    global $pdo;

    $nama = trim($nama);
    $deskripsi = trim($deskripsi);

    if ($nama === '' || strlen($nama) > 100) {
        return ['berhasil' => false, 'pesan' => 'Nama mapel wajib diisi (maksimal 100 karakter).'];
    }

    // Cek nama kembar (tidak boleh sama persis, BR-18)
    $stmtCek = $pdo->prepare("SELECT id FROM topik WHERE LOWER(nama) = LOWER(?) LIMIT 1");
    $stmtCek->execute([$nama]);
    if ($stmtCek->fetch()) {
        return ['berhasil' => false, 'pesan' => 'Nama mapel "' . $nama . '" sudah ada.'];
    }

    $sql = "INSERT INTO topik (nama, deskripsi) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $deskripsi !== '' ? $deskripsi : null]);

    return ['berhasil' => true, 'pesan' => 'Mapel "' . $nama . '" berhasil ditambahkan.'];
}

// 3. Ubah nama atau deskripsi mapel / topik
function ubahTopik($id, $nama, $deskripsi = '') {
    global $pdo;

    $nama = trim($nama);
    $deskripsi = trim($deskripsi);

    if ($nama === '' || strlen($nama) > 100) {
        return ['berhasil' => false, 'pesan' => 'Nama mapel wajib diisi (maksimal 100 karakter).'];
    }

    $stmtCek = $pdo->prepare("SELECT id FROM topik WHERE LOWER(nama) = LOWER(?) AND id != ? LIMIT 1");
    $stmtCek->execute([$nama, $id]);
    if ($stmtCek->fetch()) {
        return ['berhasil' => false, 'pesan' => 'Nama mapel tersebut sudah digunakan oleh mapel lain.'];
    }

    $sql = "UPDATE topik SET nama = ?, deskripsi = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $deskripsi !== '' ? $deskripsi : null, $id]);

    return ['berhasil' => true, 'pesan' => 'Data mapel berhasil diperbarui.'];
}

// 4. Hapus mapel / topik (Ditolak jika masih dipakai oleh kuis, BR-18, AC-22)
function hapusTopik($id) {
    global $pdo;

    $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM kuis WHERE topik_id = ?");
    $stmtCek->execute([$id]);
    $jumlahKuis = (int)$stmtCek->fetchColumn();

    if ($jumlahKuis > 0) {
        return [
            'berhasil' => false,
            'pesan'    => 'Mapel tidak dapat dihapus karena masih dipakai oleh ' . $jumlahKuis . ' kuis.'
        ];
    }

    $stmtHapus = $pdo->prepare("DELETE FROM topik WHERE id = ?");
    $stmtHapus->execute([$id]);

    return ['berhasil' => true, 'pesan' => 'Mapel berhasil dihapus.'];
}

// ============================================================================
// BAGIAN 2: KELOLA TINGKAT / JENJANG PENDIDIKAN (Tahap 5)
// ============================================================================

// 5. Ambil semua tingkat diurutkan berdasarkan kolom 'urutan' (ADM-06)
function daftarTingkatDenganJumlahKuis() {
    global $pdo;

    $sql = "SELECT tingkat.*, COUNT(kuis.id) AS jumlah_kuis
            FROM tingkat
            LEFT JOIN kuis ON kuis.tingkat_id = tingkat.id
            GROUP BY tingkat.id
            ORDER BY tingkat.urutan ASC, tingkat.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 6. Tambah tingkat jenjang baru (ADM-06, BR-18)
function tambahTingkat($nama, $urutan = 1) {
    global $pdo;

    $nama = trim($nama);
    $urutan = (int)$urutan;

    if ($nama === '' || strlen($nama) > 50) {
        return ['berhasil' => false, 'pesan' => 'Nama tingkat wajib diisi (maksimal 50 karakter).'];
    }
    if ($urutan < 1) {
        $urutan = 1;
    }

    // Cek nama kembar (BR-18)
    $stmtCek = $pdo->prepare("SELECT id FROM tingkat WHERE LOWER(nama) = LOWER(?) LIMIT 1");
    $stmtCek->execute([$nama]);
    if ($stmtCek->fetch()) {
        return ['berhasil' => false, 'pesan' => 'Nama tingkat "' . $nama . '" sudah ada.'];
    }

    $sql = "INSERT INTO tingkat (nama, urutan) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $urutan]);

    return ['berhasil' => true, 'pesan' => 'Tingkat "' . $nama . '" berhasil ditambahkan.'];
}

// 7. Ubah data tingkat jenjang
function ubahTingkat($id, $nama, $urutan = 1) {
    global $pdo;

    $nama = trim($nama);
    $urutan = (int)$urutan;

    if ($nama === '' || strlen($nama) > 50) {
        return ['berhasil' => false, 'pesan' => 'Nama tingkat wajib diisi (maksimal 50 karakter).'];
    }
    if ($urutan < 1) {
        $urutan = 1;
    }

    $stmtCek = $pdo->prepare("SELECT id FROM tingkat WHERE LOWER(nama) = LOWER(?) AND id != ? LIMIT 1");
    $stmtCek->execute([$nama, $id]);
    if ($stmtCek->fetch()) {
        return ['berhasil' => false, 'pesan' => 'Nama tingkat tersebut sudah digunakan oleh tingkat lain.'];
    }

    $sql = "UPDATE tingkat SET nama = ?, urutan = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $urutan, $id]);

    return ['berhasil' => true, 'pesan' => 'Data tingkat berhasil diperbarui.'];
}

// 8. Hapus tingkat jenjang (Ditolak jika masih dipakai oleh kuis, ADM-06, BR-18)
function hapusTingkat($id) {
    global $pdo;

    $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM kuis WHERE tingkat_id = ?");
    $stmtCek->execute([$id]);
    $jumlahKuis = (int)$stmtCek->fetchColumn();

    if ($jumlahKuis > 0) {
        return [
            'berhasil' => false,
            'pesan'    => 'Tingkat tidak dapat dihapus karena masih dipakai oleh ' . $jumlahKuis . ' kuis.'
        ];
    }

    $stmtHapus = $pdo->prepare("DELETE FROM tingkat WHERE id = ?");
    $stmtHapus->execute([$id]);

    return ['berhasil' => true, 'pesan' => 'Tingkat berhasil dihapus.'];
}

