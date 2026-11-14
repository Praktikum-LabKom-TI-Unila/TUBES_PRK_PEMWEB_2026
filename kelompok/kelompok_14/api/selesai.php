<?php
// ============================================================================
// Endpoint API: Menyelesaikan Pengerjaan Kuis (api/selesai.php)
// Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
// PIC: Wulan (Sesi 3 - API selesai.php)
//
// Alur penyelesaian kuis (KRJ-07, REL-02, HSL-01):
// 1. Menerima payload JSON: {"kuis_id": 12}
// 2. Memeriksa token CSRF dan status sesi (Pelajar atau Guest)
// 3. Menghitung total skor akhir dari jawaban yang benar
// 4. Mengubah status percobaan menjadi 'selesai' atau 'waktu_habis'
// 5. Bersifat Idempotent: dipanggil 2 kali tidak akan menghitung ulang skor
// 6. Mengembalikan URL redirect ke halaman kuis/hasil.php
// ============================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/percobaan.php';

// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'kode' => 'metode_tidak_diizinkan']);
    exit;
}

// 1. Ambil data JSON dari body request
$inputMentah = file_get_contents('php://input');
$data = json_decode($inputMentah, true);

// Fallback jika dikirim lewat $_POST biasa
if (!is_array($data)) {
    $data = $_POST;
}

// 2. Validasi Token Keamanan CSRF
$tokenCsrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? ($_POST['csrf_token'] ?? ''));
$tokenSesi = $_SESSION['csrf_token'] ?? '';

if (empty($tokenCsrf) || empty($tokenSesi) || !hash_equals($tokenSesi, $tokenCsrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'kode' => 'ditolak']);
    exit;
}

// 3. Cek Autentikasi dan Peran Pengguna (Pelajar atau Guest)
$peran = peranSaatIni();

if ($peran === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'kode' => 'sesi_habis']);
    exit;
}

if ($peran !== 'pelajar' && $peran !== 'guest') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'kode' => 'ditolak']);
    exit;
}

// 4. Validasi parameter kuis_id
$kuisId = (int) ($data['kuis_id'] ?? 0);

if ($kuisId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'kode' => 'data_tidak_valid']);
    exit;
}

global $pdo;

// Hitung total soal pada kuis untuk verifikasi
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM soal WHERE kuis_id = ?");
$stmtTotal->execute([$kuisId]);
$totalSoalKuis = (int) $stmtTotal->fetchColumn();

// ============================================================================
// ALUR A: Pengguna adalah PELAJAR TERDAFTAR
// ============================================================================
if ($peran === 'pelajar') {
    $pelajarId = (int) $_SESSION['user']['id'];

    // 1. Cari percobaan yang sedang berjalan
    $percobaan = ambilPercobaanAktif($pelajarId, $kuisId);

    // 2. Jika tidak ada yang berstatus berjalan, cari percobaan terakhir kuis ini
    // untuk mendukung sifat Idempotent (REL-02: dipanggil dua kali tetap mengembalikan 200)
    if (!$percobaan) {
        $stmtTerakhir = $pdo->prepare("SELECT * FROM percobaan_kuis WHERE kuis_id = ? AND pelajar_id = ? ORDER BY id DESC LIMIT 1");
        $stmtTerakhir->execute([$kuisId, $pelajarId]);
        $percobaanTerakhir = $stmtTerakhir->fetch(PDO::FETCH_ASSOC);

        if ($percobaanTerakhir && ($percobaanTerakhir['status'] === 'selesai' || $percobaanTerakhir['status'] === 'waktu_habis')) {
            http_response_code(200);
            echo json_encode([
                'ok' => true,
                'status' => $percobaanTerakhir['status'],
                'redirect' => BASE_URL . '/kuis/hasil.php?id=' . $percobaanTerakhir['id']
            ]);
            exit;
        }

        http_response_code(404);
        echo json_encode(['ok' => false, 'kode' => 'percobaan_tidak_ada']);
        exit;
    }

    // 3. Tentukan status akhir: apakah selesai normal atau waktu habis (BR-10)
    $sisaDetik = hitungSisaWaktuDetik($percobaan);
    $alasanKlien = (string) ($data['alasan'] ?? '');
    $statusAkhir = ($sisaDetik <= 0 || $alasanKlien === 'waktu_habis') ? 'waktu_habis' : 'selesai';

    // 4. Selesaikan percobaan dan hitung total perolehan skor
    $percobaanSelesai = selesaikanPercobaan($percobaan['id'], $statusAkhir);

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'status' => $percobaanSelesai['status'] ?? $statusAkhir,
        'redirect' => BASE_URL . '/kuis/hasil.php?id=' . $percobaan['id']
    ]);
    exit;
}

// ============================================================================
// ALUR B: Pengguna adalah GUEST / MODE TAMU (Sesi PHP)
// ============================================================================
if ($peran === 'guest') {
    // 1. Cek sifat idempotent: jika hasil sudah pernah disimpan di sesi
    $hasilSudahAda = ambilHasilGuest($kuisId);
    if ($hasilSudahAda) {
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'status' => 'selesai',
            'redirect' => BASE_URL . '/kuis/hasil.php?id=' . $kuisId
        ]);
        exit;
    }

    // 2. Ambil sesi pengerjaan guest
    $percobaanGuest = ambilPercobaanGuest($kuisId);
    if (!$percobaanGuest) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'kode' => 'percobaan_tidak_ada']);
        exit;
    }

    // 3. Selesaikan percobaan guest di sesi
    selesaikanPercobaanGuest($kuisId, $totalSoalKuis);

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'status' => 'selesai',
        'redirect' => BASE_URL . '/kuis/hasil.php?id=' . $kuisId
    ]);
    exit;
}
