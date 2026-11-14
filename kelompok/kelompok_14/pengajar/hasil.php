<?php
// PJ-06: Hasil Kuis Pengajar

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Hanya pengajar yang boleh membuka halaman ini
wajibPeran(['pengajar']);

$pengajarId = (int) $_SESSION['user']['id'];
$kuisId = isset($_GET['kuis_id']) ? (int) $_GET['kuis_id'] : 0;

// Ambil data hasil kuis (sekaligus cek kepemilikan pengajar)
$hasil = ambilHasilKuisPengajar($kuisId, $pengajarId);
if (!$hasil) {
    redirect(BASE_URL . '/404.php');
}

$kuis             = $hasil['kuis'];
$jumlahPercobaan  = $hasil['jumlah_percobaan'];
$rataRata         = $hasil['rata_rata'];
$nilaiTertinggi   = $hasil['nilai_tertinggi'];
$nilaiTerendah    = $hasil['nilai_terendah'];
$daftarNilai      = $hasil['daftar_nilai'];
$soalTersulit     = $hasil['soal_tersulit'];

// Pengaturan template panel pengajar
$judulHalaman = 'Hasil Kuis - ' . $kuis['judul'];
$halamanAktif = 'kuis';
$root = '../';

require_once __DIR__ . '/../includes/partials/header-panel.php';
?>

<link rel="stylesheet" href="<?= e($root) ?>assets/css/hasil-pengajar.css">

<!-- Judul dan Info Kuis -->
<div class="card">
    <h1 class="card-title">Hasil Kuis: <?= e($kuis['judul']) ?></h1>
    <p class="form-helper">
        Topik: <?= e($kuis['nama_topik']) ?> &bull;
        Tingkat: <?= e($kuis['nama_tingkat']) ?> &bull;
        Jumlah Soal: <?= e($kuis['jumlah_soal']) ?>
    </p>
    <p>
        <a href="kuis.php" class="btn btn-batal btn-sm">&laquo; Kembali ke Kuis Saya</a>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
    </p>
</div>

<!-- Ringkasan Statistik Hasil Kuis -->
<div class="bento-grid">
    <div class="card">
        <p class="form-helper">Jumlah Percobaan</p>
        <h2><?= e($jumlahPercobaan) ?></h2>
    </div>
    <div class="card">
        <p class="form-helper">Rata-rata Nilai</p>
        <h2><?= e($rataRata) ?>%</h2>
    </div>
    <div class="card">
        <p class="form-helper">Nilai Tertinggi</p>
        <h2><?= e($nilaiTertinggi) ?>%</h2>
    </div>
    <div class="card">
        <p class="form-helper">Nilai Terendah</p>
        <h2><?= e($nilaiTerendah) ?>%</h2>
    </div>
</div>

<!-- Tabel Nilai Pelajar -->
<section class="card">
    <h2 class="card-title">Daftar Nilai Pelajar</h2>

    <?php if (empty($daftarNilai)): ?>
        <p class="form-helper">Belum ada pelajar yang menyelesaikan kuis ini.</p>
    <?php else: ?>
        <div class="form-group">
            <label for="cariPelajar" class="form-label">Cari Nama Pelajar</label>
            <input type="text" id="cariPelajar" class="form-input" placeholder="Ketik nama pelajar...">
        </div>

        <div class="tabel-wadah">
            <table id="tabelNilai" class="tabel-hasil">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Pelajar</th>
                        <th>Skor</th>
                        <th>Persen</th>
                        <th>Waktu</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($daftarNilai as $item): ?>
                        <?php
                        $lamaPengerjaan = '-';
                        if (!empty($item['waktu_mulai']) && !empty($item['waktu_selesai'])) {
                            $selisihDetik = strtotime($item['waktu_selesai']) - strtotime($item['waktu_mulai']);
                            if ($selisihDetik < 0) {
                                $selisihDetik = 0;
                            }
                            $lamaPengerjaan = formatMenitDetik($selisihDetik);
                        }

                        $tanggalSelesai = !empty($item['waktu_selesai']) ? $item['waktu_selesai'] : $item['waktu_mulai'];
                        $labelStatus = ($item['status'] === 'waktu_habis') ? 'Waktu Habis' : 'Selesai';
                        $kelasStatus = ($item['status'] === 'waktu_habis') ? 'badge-menunggu' : 'badge-terbit';
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($item['nama_pelajar']) ?></td>
                            <td><?= e($item['skor']) ?> / <?= e($item['skor_maksimal']) ?></td>
                            <td><strong><?= e($item['persen']) ?>%</strong></td>
                            <td><?= e($lamaPengerjaan) ?></td>
                            <td><?= e(formatTanggal($tanggalSelesai)) ?></td>
                            <td>
                                <span class="badge <?= $kelasStatus ?>"><?= e($labelStatus) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p id="pesanKosongCari" class="form-helper sembunyi">Nama pelajar tidak ditemukan.</p>
    <?php endif; ?>
</section>

<!-- Daftar Soal Tersulit Berdasarkan Persentase Salah -->
<section class="card">
    <h2 class="card-title">Soal Tersulit (Urutan Salah Terbanyak)</h2>

    <?php if (empty($soalTersulit)): ?>
        <p class="form-helper">Belum ada data soal pada kuis ini.</p>
    <?php else: ?>
        <?php foreach ($soalTersulit as $soal): ?>
            <?php
            $kelasSalah = 'badge-mudah';
            if ($soal['persen_salah'] >= 60) {
                $kelasSalah = 'badge-sulit';
            } elseif ($soal['persen_salah'] >= 30) {
                $kelasSalah = 'badge-sedang';
            }
            ?>
            <div class="card card-sm">
                <p>
                    <strong>Soal <?= e($soal['urutan']) ?></strong>
                    <span class="badge <?= $kelasSalah ?>">
                        <?= e($soal['persen_salah']) ?>% Salah
                    </span>
                </p>
                <p><?= e($soal['pertanyaan']) ?></p>
                <p class="form-helper">
                    Benar: <?= e($soal['jumlah_benar']) ?> &bull; Salah: <?= e($soal['jumlah_salah']) ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<script src="<?= e($root) ?>assets/js/hasil-pengajar.js"></script>
<?php
require_once __DIR__ . '/../includes/partials/footer-panel.php';
