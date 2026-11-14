<?php
// ============================================================================
// Endpoint API: Pengiriman Jawaban Soal Kuis (api/jawab.php)
// Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
// PIC: Wulan (Sesi 3 - API jawab.php)
//
// Alur pengerjaan (KRJ-04, KRJ-05, SEC-07):
// 1. Menerima payload JSON: {"kuis_id": 12, "soal_id": 87, "opsi_id": 341}
// 2. Memeriksa token CSRF dan status login (Pelajar terdaftar atau Guest)
// 3. Memeriksa sisa waktu pengerjaan di server
// 4. Memastikan soal belum pernah dijawab pada percobaan ini (cegah kirim ganda / 409)
// 5. Menyimpan jawaban dan mengembalikan kunci jawaban, pembahasan, serta sisa waktu
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

// Jika bukan format JSON mentah, coba ambil dari $_POST
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

// 4. Ambil dan validasi parameter kuis_id, soal_id, dan opsi_id
$kuisId = (int) ($data['kuis_id'] ?? 0);
$soalId = (int) ($data['soal_id'] ?? 0);
$opsiId = (int) ($data['opsi_id'] ?? 0);

if ($kuisId <= 0 || $soalId <= 0 || $opsiId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'kode' => 'data_tidak_valid']);
    exit;
}

// 5. Verifikasi bahwa soal ini memang milik kuis_id dan opsi_id milik soal_id
global $pdo;

$stmtCekSoal = $pdo->prepare("SELECT id, kuis_id, poin, pembahasan FROM soal WHERE id = ? AND kuis_id = ? LIMIT 1");
$stmtCekSoal->execute([$soalId, $kuisId]);
$dataSoal = $stmtCekSoal->fetch(PDO::FETCH_ASSOC);

if (!$dataSoal) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'kode' => 'data_tidak_valid']);
    exit;
}

$stmtCekOpsi = $pdo->prepare("SELECT id, soal_id, benar FROM opsi_jawaban WHERE id = ? AND soal_id = ? LIMIT 1");
$stmtCekOpsi->execute([$opsiId, $soalId]);
$dataOpsi = $stmtCekOpsi->fetch(PDO::FETCH_ASSOC);

if (!$dataOpsi) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'kode' => 'data_tidak_valid']);
    exit;
}

// Ambil info kunci jawaban yang benar untuk umpan balik
$stmtKunci = $pdo->prepare("SELECT id, label FROM opsi_jawaban WHERE soal_id = ? AND benar = 1 LIMIT 1");
$stmtKunci->execute([$soalId]);
$dataKunci = $stmtKunci->fetch(PDO::FETCH_ASSOC);
$opsiKunciId = $dataKunci ? (int) $dataKunci['id'] : 0;
$opsiKunciLabel = $dataKunci ? (string) $dataKunci['label'] : '';

// Hitung total seluruh soal pada kuis ini
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM soal WHERE kuis_id = ?");
$stmtTotal->execute([$kuisId]);
$totalSoalKuis = (int) $stmtTotal->fetchColumn();

$isBenar = ((int) $dataOpsi['benar'] === 1);
$poinDidapat = $isBenar ? (int) $dataSoal['poin'] : 0;
$pembahasan = $dataSoal['pembahasan'] ?? '';

// ============================================================================
// ALUR A: Pengguna adalah PELAJAR TERDAFTAR
// ============================================================================
if ($peran === 'pelajar') {
    $pelajarId = (int) $_SESSION['user']['id'];

    // Ambil sesi percobaan yang sedang berjalan
    $percobaan = ambilPercobaanAktif($pelajarId, $kuisId);

    if (!$percobaan) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'kode' => 'percobaan_tidak_ada']);
        exit;
    }

    // Cek sisa waktu (toleransi 5 detik untuk latency jaringan, BR-09)
    $sisaDetik = hitungSisaWaktuDetik($percobaan);
    if ($sisaDetik <= 0) {
        selesaikanPercobaan($percobaan['id'], 'waktu_habis');
        http_response_code(410);
        echo json_encode([
            'ok' => false,
            'kode' => 'waktu_habis',
            'redirect' => BASE_URL . '/kuis/hasil.php?id=' . $percobaan['id']
        ]);
        exit;
    }

    // Cek apakah soal ini sudah pernah dijawab sebelumnya (BR-11, AC-13)
    $stmtCekSudah = $pdo->prepare("SELECT id FROM jawaban_percobaan WHERE percobaan_id = ? AND soal_id = ? LIMIT 1");
    $stmtCekSudah->execute([$percobaan['id'], $soalId]);
    if ($stmtCekSudah->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'kode' => 'sudah_dijawab']);
        exit;
    }

    // Simpan jawaban ke tabel jawaban_percobaan
    $stmtInsert = $pdo->prepare("INSERT INTO jawaban_percobaan (percobaan_id, soal_id, opsi_id, benar, poin, dijawab_pada) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmtInsert->execute([
        $percobaan['id'],
        $soalId,
        $opsiId,
        $isBenar ? 1 : 0,
        $poinDidapat
    ]);

    // Hitung berapa banyak soal yang sudah terjawab pada percobaan ini
    $stmtTerjawab = $pdo->prepare("SELECT COUNT(*) FROM jawaban_percobaan WHERE percobaan_id = ?");
    $stmtTerjawab->execute([$percobaan['id']]);
    $jumlahTerjawab = (int) $stmtTerjawab->fetchColumn();

    // Perbarui waktu sisa setelah jawaban berhasil disimpan
    $sisaDetikSekarang = hitungSisaWaktuDetik($percobaan);

    // Kirim respons sukses 200 sesuai spesifikasi PRD 9.5
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'benar' => $isBenar,
        'opsi_benar_id' => $opsiKunciId,
        'kunci_label' => $opsiKunciLabel,
        'pembahasan' => $pembahasan,
        'terjawab' => $jumlahTerjawab,
        'total' => $totalSoalKuis,
        'sisa_detik' => $sisaDetikSekarang
    ]);
    exit;
}

// ============================================================================
// ALUR B: Pengguna adalah GUEST / TAMU (Data disimpan di sesi PHP, KT-13, BR-14)
// ============================================================================
if ($peran === 'guest') {
    $percobaanGuest = ambilPercobaanGuest($kuisId);

    if (!$percobaanGuest || $percobaanGuest['status'] !== 'berjalan') {
        http_response_code(404);
        echo json_encode(['ok' => false, 'kode' => 'percobaan_tidak_ada']);
        exit;
    }

    // Hitung sisa waktu untuk Guest
    $waktuMulai = strtotime($percobaanGuest['waktu_mulai']);
    $totalDurasiDetik = (int) $percobaanGuest['durasi_menit'] * 60;
    $batasWaktu = $waktuMulai + $totalDurasiDetik;
    $sisaDetik = $batasWaktu - time();

    // Toleransi 5 detik
    if ($sisaDetik < -5) {
        selesaikanPercobaanGuest($kuisId, $totalSoalKuis);
        http_response_code(410);
        echo json_encode([
            'ok' => false,
            'kode' => 'waktu_habis',
            'redirect' => BASE_URL . '/kuis/hasil.php?id=' . $kuisId
        ]);
        exit;
    }

    // Cek apakah soal ini sudah dijawab di sesi Guest (BR-11)
    if (isset($percobaanGuest['jawaban'][$soalId])) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'kode' => 'sudah_dijawab']);
        exit;
    }

    // Simpan jawaban ke sesi Guest
    simpanJawabanGuest($kuisId, $soalId, $opsiId, $isBenar, $poinDidapat);

    $percobaanGuestBaru = ambilPercobaanGuest($kuisId);
    $jumlahTerjawab = count($percobaanGuestBaru['jawaban'] ?? []);

    $sisaDetikPositif = max(0, $sisaDetik);

    // Kirim respons sukses 200
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'benar' => $isBenar,
        'opsi_benar_id' => $opsiKunciId,
        'kunci_label' => $opsiKunciLabel,
        'pembahasan' => $pembahasan,
        'terjawab' => $jumlahTerjawab,
        'total' => $totalSoalKuis,
        'sisa_detik' => $sisaDetikPositif
    ]);
    exit;
}
