<?php
// Halaman PJ-05: Pratinjau Kuis (Pengajar)

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';
require_once __DIR__ . '/../includes/fungsi/soal.php';

// Hanya pengajar yang boleh mengakses halaman pratinjau ini
wajibPeran(['pengajar']);

$pengajarId = (int) $_SESSION['user']['id'];

// Ambil ID kuis dari URL (?id=)
$kuisId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Ambil data kuis dan pastikan kuis tersebut milik pengajar yang login
$kuis = ambilKuisById($kuisId, $pengajarId);
if (!$kuis) {
    redirect(BASE_URL . '/404.php');
}

// Ambil seluruh daftar soal beserta opsi jawabannya (sudah termasuk kunci & pembahasan karena milik pengajar sendiri)
$daftarSoal = ambilSoalByKuis($kuisId);
$totalSoal  = count($daftarSoal);

// Siapkan variabel untuk header-fokus.php
$root         = '../';
$judulHalaman = 'Pratinjau: ' . $kuis['judul'] . ' - DynaQuiz';
$judulKuis    = $kuis['judul'];
$sisaWaktu    = sprintf('%02d:00', (int) $kuis['durasi_menit']);
$linkKeluar   = 'kuis.php';

// Muat header mode
require_once __DIR__ . '/../includes/partials/header-fokus.php';
?>

<link rel="stylesheet" href="<?= e($root) ?>assets/css/kerjakan.css">

<div class="kerjakan-wrapper">
    <!-- Banner Pemberitahuan Mode Pratinjau -->
    <div class="alert alert-info" role="status">
        <strong>Mode Pratinjau Pengajar:</strong>
        Anda sedang mencoba tampilan pengerjaan kuis milik sendiri (status: <strong><?= e(ucfirst($kuis['status'])) ?></strong>).
        Hasil pratinjau tidak akan disimpan ke database.
    </div>

    <?php if ($totalSoal === 0): ?>
        <!-- Jika Kuis Belum Memiliki Soal -->
        <div class="card">
            <h2 class="card-title">Belum Ada Soal</h2>
            <p class="form-helper">
                Kuis ini belum memiliki soal untuk dipratinjau. Silakan tambahkan soal terlebih dahulu.
            </p>
            <div class="nav-kerjakan">
                <a href="kelola-soal.php?kuis_id=<?= e($kuisId) ?>" class="btn btn-primary">Kelola Soal</a>
                <a href="kuis.php" class="btn btn-secondary">Kembali ke Kuis Saya</a>
            </div>
        </div>
    <?php else: ?>

        <!-- Area Pengerjaan Pratinjau -->
        <div id="areaPratinjau" data-durasi="<?= e((int) $kuis['durasi_menit']) ?>" data-total-soal="<?= e($totalSoal) ?>">

            <!-- Keterangan Nomor Soal -->
            <div class="soal-header">
                <span id="infoNomorSoal" class="soal-nomor-label">Soal 1 dari <?= e($totalSoal) ?></span>
                <span class="badge badge-sedang" id="infoPoinSoal">10 Poin</span>
            </div>

            <!-- Daftar Kartu Soal (Hanya soal yang aktif yang ditampilkan) -->
            <?php foreach ($daftarSoal as $index => $soal): ?>
                <div class="soal-card kartu-soal-pratinjau"
                     id="soal-<?= e($index) ?>"
                     data-index="<?= e($index) ?>"
                     data-poin="<?= e((int) $soal['poin']) ?>"
                     data-pembahasan="<?= e($soal['pembahasan'] ?? '') ?>"
                     <?= $index > 0 ? 'hidden' : '' ?>>

                    <h2 class="soal-pertanyaan">
                        <?= e($soal['pertanyaan']) ?>
                    </h2>

                    <!-- Pilihan Jawaban A - E -->
                    <div class="opsi-list">
                        <?php foreach ($soal['opsi'] as $opsi): ?>
                            <button type="button"
                                    class="btn btn-outline tombol-opsi-pratinjau"
                                    data-soal-index="<?= e($index) ?>"
                                    data-label="<?= e($opsi['label']) ?>"
                                    data-benar="<?= e((int) $opsi['benar']) ?>">
                                <span class="badge badge-sedang"><?= e($opsi['label']) ?></span>
                                <span><?= e($opsi['teks']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Kotak Umpan Balik & Pembahasan (Muncul setelah pengajar memilih opsi) -->
                    <div class="kotak-umpan-balik" id="umpan-balik-<?= e($index) ?>" hidden>
                        <div class="alert isi-status-jawaban"></div>
                        <?php if (!empty($soal['pembahasan'])): ?>
                            <div class="card card-sm">
                                <strong>Pembahasan:</strong>
                                <p><?= e($soal['pembahasan']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Grid Nomor Soal & Tombol Navigasi -->
            <div class="grid-nomor-wrapper">
                <p class="grid-nomor-header">Navigasi Nomor Soal:</p>
                <div id="gridNomorSoal" class="grid-nomor-list">
                    <?php foreach ($daftarSoal as $index => $soal): ?>
                        <button type="button"
                                class="btn btn-sm btn-outline btn-nomor tombol-grid-soal"
                                data-target-index="<?= e($index) ?>">
                            <?= e($index + 1) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="nav-kerjakan">
                <button type="button" id="tombolSebelumnya" class="btn btn-secondary" disabled>
                    &larr; Sebelumnya
                </button>
                <button type="button" id="tombolBerikutnya" class="btn btn-primary">
                    Lanjut &rarr;
                </button>
                <button type="button" id="tombolSelesaiPratinjau" class="btn btn-accent">
                    Selesai Pratinjau
                </button>
            </div>

        </div>

        <!-- Ringkasan Hasil Akhir Pratinjau (Disembunyikan sampai Selesai diklik) -->
        <div id="hasilPratinjau" class="card" hidden>
            <span class="badge badge-sedang">Ringkasan Mode Pratinjau</span>
            <h2 class="card-title">
                Skor Anda: <span id="hasilSkorAkhir">0</span> / <span id="hasilSkorMaks">0</span>
            </h2>
            <p id="hasilPersenAkhir">0%</p>

            <div class="form-group">
                <span class="badge badge-terbit"><i class="fa-solid fa-circle-check"></i> Benar: <strong id="hasilJumlahBenar">0</strong></span>
                <span class="badge badge-ditolak"><i class="fa-solid fa-circle-xmark"></i> Salah / Kosong: <strong id="hasilJumlahSalah">0</strong></span>
            </div>

            <p class="form-helper">
                Catatan: Ini hanyalah simulasi pratinjau pengajar. Data pengerjaan ini tidak disimpan ke database.
            </p>

            <div class="nav-kerjakan">
                <a href="pratinjau.php?id=<?= e($kuisId) ?>" class="btn btn-primary">Ulangi Pratinjau</a>
                <a href="kelola-soal.php?kuis_id=<?= e($kuisId) ?>" class="btn btn-secondary">Kelola Soal</a>
                <a href="kuis.php" class="btn btn-batal">Kembali ke Kuis Saya</a>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Muat skrip interaktif mode pratinjau -->
<script src="<?= e($root) ?>assets/js/pratinjau.js"></script>

<?php
// Muat template footer mode fokus
require_once __DIR__ . '/../includes/partials/footer-fokus.php';