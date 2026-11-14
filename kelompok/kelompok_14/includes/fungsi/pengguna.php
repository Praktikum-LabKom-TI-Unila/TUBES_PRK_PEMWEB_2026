<?php
// Fungsi-fungsi untuk mengelola data pengguna (user) dan autentikasi
require_once __DIR__ . '/../kata_terlarang.php';

// 1. Cari 1 data pengguna berdasarkan alamat email
function cariPenggunaByEmail($email) {
    global $pdo;

    $sql = "SELECT * FROM user WHERE email = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    return $user;
}

// 2. Ambil 1 data pengguna berdasarkan ID
function ambilPenggunaById($id) {
    global $pdo;

    $sql = "SELECT * FROM user WHERE id = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    return $user;
}

// 3. Validasi input form pendaftaran (Register)
// Mengembalikan array berisi pesan error per kolom (kosong jika semua valid)
function validasiRegister($data) {
    $errors = [];

    $nama = trim($data['nama'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $konfirmasi = $data['konfirmasi_password'] ?? '';
    $peran = $data['peran'] ?? '';

    // Validasi nama lengkap (2 sampai 100 karakter dan bebas kata terlarang)
    if ($nama === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (strlen($nama) < 2 || strlen($nama) > 100) {
        $errors['nama'] = 'Nama lengkap harus antara 2 sampai 100 karakter.';
    } elseif (cekKataTerlarang($nama)) {
        $errors['nama'] = 'Nama lengkap mengandung kata yang tidak diizinkan.';
    }

    // Validasi email (format valid, maksimal 150 karakter, dan belum terdaftar)
    if ($email === '') {
        $errors['email'] = 'Alamat email wajib diisi.';
    } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format alamat email tidak valid.';
    } else {
        $sudahAda = cariPenggunaByEmail($email);
        if ($sudahAda !== null) {
            $errors['email'] = 'Email ini sudah terdaftar. Silakan gunakan email lain.';
        }
    }

    // Validasi password (minimal 8 karakter, maksimal 72 karakter)
    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password minimal harus 8 karakter.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Password maksimal 72 karakter.';
    }

    // Validasi konfirmasi password (harus sama dengan password)
    if ($konfirmasi === '') {
        $errors['konfirmasi_password'] = 'Konfirmasi password wajib diisi.';
    } elseif ($password !== $konfirmasi) {
        $errors['konfirmasi_password'] = 'Konfirmasi password tidak cocok.';
    }

    // Validasi peran (hanya boleh pelajar atau pengajar, tidak boleh admin)
    if ($peran !== 'pelajar' && $peran !== 'pengajar') {
        $errors['peran'] = 'Silakan pilih peran sebagai Pelajar atau Pengajar.';
    }

    return $errors;
}

// 4. Simpan data pengguna baru ke tabel user
function daftarPenggunaBaru($nama, $email, $password, $peran) {
    global $pdo;

    // Enkripsi password sebelum disimpan ke database (AUT-01, SEC-05)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO user (nama, email, password, peran) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $berhasil = $stmt->execute([$nama, $email, $passwordHash, $peran]);

    return $berhasil;
}

// 5. Fungsi verifikasi login (Sesi 3 + Pembatasan Login Gagal Sesi 8)
// Mengecek email dan password waktu user login
// Mengembalikan array hasil: ['berhasil' => true/false, 'pesan' => '...', 'pengguna' => data/null]
function verifikasiLogin($email, $password) {
    $hasil = [
        'berhasil' => false,
        'pesan'    => '',
        'pengguna' => null
    ];

    $email = trim($email);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // 1. Cek apakah email atau password kosong
    if ($email === '' || $password === '') {
        $hasil['pesan'] = 'Email dan password wajib diisi.';
        return $hasil;
    }

    // 2. Cek apakah email + IP ini sedang dibatasi karena terlalu banyak gagal (AUT-10, KT-07)
    if (cekBatasLogin($email, $ip)) {
        $hasil['pesan'] = 'Terlalu banyak percobaan login gagal. Silakan tunggu 15 menit sebelum mencoba lagi.';
        return $hasil;
    }

    // 3. Cari user berdasarkan email
    $pengguna = cariPenggunaByEmail($email);

    // 4. Kalau email tidak ditemukan
    // Pesan dibuat sama dengan password salah (AUT-02, SEC-05)
    if (!$pengguna) {
        catatLoginGagal($email, $ip);
        $hasil['pesan'] = 'Email atau password salah.';
        return $hasil;
    }

    // 5. Cek password dengan password_verify()
    if (!password_verify($password, $pengguna['password'])) {
        catatLoginGagal($email, $ip);
        $hasil['pesan'] = 'Email atau password salah.';
        return $hasil;
    }

    // 6. Cek apakah akun dinonaktifkan admin (AUT-02, AUT-07)
    if (isset($pengguna['aktif']) && (int)$pengguna['aktif'] === 0) {
        $hasil['pesan'] = 'Akun Anda telah dinonaktifkan oleh Admin.';
        return $hasil;
    }
    if (isset($pengguna['status']) && $pengguna['status'] === 'nonaktif') {
        $hasil['pesan'] = 'Akun Anda telah dinonaktifkan oleh Admin.';
        return $hasil;
    }

    // 7. Login berhasil -> bersihkan catatan gagal untuk email dan IP ini
    hapusLogLogin($email, $ip);

    $hasil['berhasil'] = true;
    $hasil['pengguna'] = $pengguna;
    return $hasil;
}

// 6. Perbarui nama lengkap pengguna (BS-01)
function ubahNamaPengguna($id, $namaBaru) {
    global $pdo;

    $namaBaru = trim($namaBaru);
    if (strlen($namaBaru) < 2 || strlen($namaBaru) > 100) {
        return [
            'berhasil' => false,
            'pesan'    => 'Nama lengkap harus antara 2 sampai 100 karakter.'
        ];
    }

    // Validasi kata terlarang (SEC-13, AUT-03)
    if (cekKataTerlarang($namaBaru)) {
        return [
            'berhasil' => false,
            'pesan'    => 'Nama lengkap mengandung kata yang tidak diizinkan.'
        ];
    }

    $sql = "UPDATE user SET nama = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$namaBaru, $id]);

    // Perbarui nama di sesi aktif
    if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === (int)$id) {
        $_SESSION['user']['nama'] = $namaBaru;
    }

    return [
        'berhasil' => true,
        'pesan'    => 'Nama profil berhasil diperbarui.'
    ];
}

// 7. Ganti kata sandi pengguna (BS-01, AUT-08)
// Memeriksa password lama terlebih dahulu, lalu mengupdate hash password baru
function ubahPasswordPengguna($id, $passwordLama, $passwordBaru, $konfirmasiBaru) {
    global $pdo;

    $user = ambilPenggunaById($id);
    if (!$user) {
        return [
            'berhasil' => false,
            'pesan'    => 'Pengguna tidak ditemukan.'
        ];
    }

    // Verifikasi password lama
    if (!password_verify($passwordLama, $user['password'])) {
        return [
            'berhasil' => false,
            'pesan'    => 'Password lama yang Anda masukkan salah.'
        ];
    }

    // Validasi panjang password baru
    if (strlen($passwordBaru) < 8) {
        return [
            'berhasil' => false,
            'pesan'    => 'Password baru minimal harus 8 karakter.'
        ];
    }
    if (strlen($passwordBaru) > 72) {
        return [
            'berhasil' => false,
            'pesan'    => 'Password baru maksimal 72 karakter.'
        ];
    }

    // Validasi konfirmasi password baru
    if ($passwordBaru !== $konfirmasiBaru) {
        return [
            'berhasil' => false,
            'pesan'    => 'Konfirmasi password baru tidak cocok.'
        ];
    }

    // Enkripsi password baru
    $hashBaru = password_hash($passwordBaru, PASSWORD_DEFAULT);

    // Update database (dan reset wajib_ganti_sandi = 0)
    pastikanKolomKelolaPengguna();
    $sql = "UPDATE user SET password = ?, wajib_ganti_sandi = 0 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hashBaru, $id]);

    // Reset status wajib_ganti_sandi di session jika sebelumnya diwajibkan
    $_SESSION['user']['wajib_ganti_sandi'] = 0;

    return [
        'berhasil' => true,
        'pesan'    => 'Password berhasil diubah.'
    ];
}

// ============================================================================
// FUNGSI ADMIN: KELOLA PENGGUNA & RESET SANDI (Jobdesk Nanda - Sesi 5)
// ============================================================================

// Pastikan kolom 'aktif' dan 'wajib_ganti_sandi' tersedia di tabel user
function pastikanKolomKelolaPengguna() {
    global $pdo;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM user LIKE 'aktif'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE user ADD COLUMN aktif TINYINT(1) NOT NULL DEFAULT 1 AFTER peran");
        }

        $stmt2 = $pdo->query("SHOW COLUMNS FROM user LIKE 'wajib_ganti_sandi'");
        if (!$stmt2->fetch()) {
            $pdo->exec("ALTER TABLE user ADD COLUMN wajib_ganti_sandi TINYINT(1) NOT NULL DEFAULT 0 AFTER aktif");
        }
    } catch (PDOException $e) {
        // Biarkan jika user tidak punya izin ALTER
    }
}

// 8. Ambil daftar semua pengguna dengan filter peran, status aktif, dan pencarian (ADM-04)
function daftarSemuaPengguna($filterPeran = '', $filterStatus = '', $kataKunci = '') {
    global $pdo;
    pastikanKolomKelolaPengguna();

    $sql = "SELECT id, nama, email, peran, aktif, wajib_ganti_sandi, dibuat_pada
            FROM user
            WHERE 1=1";
    $params = [];

    if ($filterPeran !== '' && in_array($filterPeran, ['pelajar', 'pengajar', 'admin'], true)) {
        $sql .= " AND peran = ?";
        $params[] = $filterPeran;
    }

    if ($filterStatus === 'aktif') {
        $sql .= " AND aktif = 1";
    } elseif ($filterStatus === 'nonaktif') {
        $sql .= " AND aktif = 0";
    }

    $kataKunci = trim($kataKunci);
    if ($kataKunci !== '') {
        $sql .= " AND (nama LIKE ? OR email LIKE ?)";
        $cari = '%' . $kataKunci . '%';
        $params[] = $cari;
        $params[] = $cari;
    }

    $sql .= " ORDER BY dibuat_pada DESC, id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 9. Aktifkan atau nonaktifkan akun pengguna (ADM-04, ADM-08)
function ubahStatusAktifPengguna($targetUserId, $statusBaru, $adminId) {
    global $pdo;
    pastikanKolomKelolaPengguna();

    // Admin tidak boleh menonaktifkan akunnya sendiri (ADM-08)
    if ((int)$targetUserId === (int)$adminId && (int)$statusBaru === 0) {
        return [
            'berhasil' => false,
            'pesan'    => 'Anda tidak dapat menonaktifkan akun Anda sendiri.'
        ];
    }

    $user = ambilPenggunaById($targetUserId);
    if (!$user) {
        return [
            'berhasil' => false,
            'pesan'    => 'Pengguna tidak ditemukan.'
        ];
    }

    $nilaiAktif = ((int)$statusBaru === 1) ? 1 : 0;
    $sql = "UPDATE user SET aktif = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nilaiAktif, $targetUserId]);

    $teksStatus = ($nilaiAktif === 1) ? 'diaktifkan' : 'dinonaktifkan';
    return [
        'berhasil' => true,
        'pesan'    => 'Akun ' . $user['nama'] . ' berhasil ' . $teksStatus . '.'
    ];
}

// 10. Reset sandi pengguna oleh Admin (ADM-05, BR-17, AC-21)
// Menghasilkan sandi sementara acak 10 karakter yang hanya dikembalikan satu kali
function resetSandiSementara($targetUserId) {
    global $pdo;
    pastikanKolomKelolaPengguna();

    $user = ambilPenggunaById($targetUserId);
    if (!$user) {
        return [
            'berhasil' => false,
            'pesan'    => 'Pengguna tidak ditemukan.'
        ];
    }

    // Buat sandi sementara acak tanpa karakter yang membingungkan (0, O, 1, I, l)
    $karakter = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
    $panjang = strlen($karakter) - 1;
    $sandiSementara = '';

    for ($i = 0; $i < 10; $i++) {
        $sandiSementara .= $karakter[random_int(0, $panjang)];
    }

    $hashBaru = password_hash($sandiSementara, PASSWORD_DEFAULT);

    // Simpan hash baru dan set wajib_ganti_sandi = 1
    $sql = "UPDATE user SET password = ?, wajib_ganti_sandi = 1 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hashBaru, $targetUserId]);

    return [
        'berhasil'        => true,
        'pesan'           => 'Sandi sementara berhasil dibuat untuk ' . $user['nama'] . '.',
        'sandi_sementara' => $sandiSementara,
        'nama_pengguna'   => $user['nama'],
        'email_pengguna'  => $user['email']
    ];
}

// ============================================================================
// FUNGSI KEAMANAN: PEMBATASAN PERCOBAAN LOGIN GAGAL (Jobdesk Nanda - Sesi 8)
// ============================================================================

// 11. Cek apakah email dan IP ini sudah gagal login >= 5 kali dalam 15 menit terakhir (AUT-10, KT-07)
function cekBatasLogin($email, $ip) {
    global $pdo;

    try {
        $sql = "SELECT COUNT(*) FROM log_login
                WHERE email = ? AND alamat_ip = ?
                AND waktu >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $ip]);
        $jumlahGagal = (int)$stmt->fetchColumn();

        return $jumlahGagal >= 5;
    } catch (PDOException $e) {
        return false;
    }
}

// 12. Catat percobaan login yang gagal ke tabel log_login
function catatLoginGagal($email, $ip) {
    global $pdo;

    try {
        $sql = "INSERT INTO log_login (email, alamat_ip, waktu) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $ip]);
    } catch (PDOException $e) {
        // Abaikan jika tabel belum tersedia
    }
}

// 13. Hapus catatan gagal setelah pengguna berhasil login
function hapusLogLogin($email, $ip) {
    global $pdo;

    try {
        $sql = "DELETE FROM log_login WHERE email = ? AND alamat_ip = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $ip]);
    } catch (PDOException $e) {
        // Abaikan jika tabel belum tersedia
    }
}



