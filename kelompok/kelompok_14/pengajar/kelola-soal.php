<?php
// PJ-04: Kelola Soal (tambah, edit, hapus, urutkan soal, dan ajukan publish)

// Muat file konfigurasi dan helper
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';
require_once __DIR__ . '/../includes/fungsi/soal.php';

// Hanya pengajar yang boleh membuka halaman ini
wajibPeran(['pengajar']);

$pengajarId = (int) $_SESSION['user']['id'];

// Ambil id kuis dari URL 
$kuisId = isset($_GET['kuis_id']) ? (int) $_GET['kuis_id'] : 0;

// Cek kuis ada dan milik pengajar ini
$kuis = ambilKuisById($kuisId, $pengajarId);
if (!$kuis) {
    redirect(BASE_URL . '/404.php');
}

// Kuis yang menunggu persetujuan atau sudah terbit dikunci (hanya boleh dilihat)
$terkunci = false;
if ($kuis['status'] == 'menunggu' || $kuis['status'] == 'terbit') {
    $terkunci = true;
}

// Ambil data soal dan cek kelengkapan kuis
$daftarSoal = ambilSoalByKuis($kuisId);
$kelengkapan = validasiKelengkapanKuis($kuisId);

$pesanError = '';
$daftarError = [];

// Nilai form soal (dipakai lagi jika ada error supaya isian tidak hilang)
$nilaiSoal = [
    'pertanyaan' => '',
    'pembahasan' => '',
    'poin'       => 10,
    'opsi'       => ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''],
    'kunci'      => ''
];

// Proses saat ada form yang dikirim (POST)
if (isPost()) {
    verifikasiCsrf();

    $aksi = $_POST['aksi'] ?? '';
    $soalId = (int) ($_POST['soal_id'] ?? 0);

    // Kuis yang terkunci tidak boleh diubah
    if ($terkunci) {
        $pesanError = 'Kuis sedang menunggu persetujuan atau sudah terbit, jadi tidak bisa diubah.';
    } else {
        // Ambil isian form soal
        $nilaiSoal['pertanyaan'] = trim($_POST['pertanyaan'] ?? '');
        $nilaiSoal['pembahasan'] = trim($_POST['pembahasan'] ?? '');
        $nilaiSoal['poin']       = (int) ($_POST['poin'] ?? 10);
        $nilaiSoal['kunci']      = $_POST['kunci'] ?? '';

        $opsiPost = $_POST['opsi'] ?? [];
        foreach ($nilaiSoal['opsi'] as $huruf => $teks) {
            $nilaiSoal['opsi'][$huruf] = trim($opsiPost[$huruf] ?? '');
        }

        $hasil = null;
        $pesanSukses = '';
        $halamanTujuan = 'kelola-soal.php?kuis_id=' . $kuisId;

        if ($aksi == 'tambah') {
            $hasil = tambahSoal($kuisId, $pengajarId, $nilaiSoal);
            $pesanSukses = 'Soal berhasil ditambahkan.';
        } elseif ($aksi == 'edit') {
            $hasil = editSoal($soalId, $kuisId, $pengajarId, $nilaiSoal);
            $pesanSukses = 'Soal berhasil diubah.';
        } elseif ($aksi == 'hapus') {
            $hasil = hapusSoal($soalId, $kuisId, $pengajarId);
            $pesanSukses = 'Soal berhasil dihapus.';
        } elseif ($aksi == 'naik' || $aksi == 'turun') {
            $hasil = geserUrutanSoal($soalId, $kuisId, $pengajarId, $aksi);
            $pesanSukses = 'Urutan soal berhasil diubah.';
        } elseif ($aksi == 'ajukan') {
            // Ajukan publish: kalau berhasil, pindah ke halaman Kuis Saya
            $hasil = ajukanPublishKuis($kuisId, $pengajarId);
            $pesanSukses = 'Kuis berhasil diajukan, tunggu persetujuan admin.';
            $halamanTujuan = 'kuis.php';
        }

        if ($hasil != null) {
            if ($hasil['berhasil']) {
                // Berhasil: simpan pesan lalu muat ulang halaman supaya data terbaru tampil
                flash('sukses', $pesanSukses);
                redirect($halamanTujuan);
            } else {
                $pesanError = $hasil['pesan'] ?? 'Terjadi kesalahan.';
                // Kalau ajukan gagal, ada daftar alasan kenapa kuis belum lengkap
                if (isset($hasil['errors'])) {
                    $daftarError = $hasil['errors'];
                }
            }
        }

        // Ambil ulang data soal dan cek kelengkapan untuk jaga-jaga
        $daftarSoal = ambilSoalByKuis($kuisId);
        $kelengkapan = validasiKelengkapanKuis($kuisId);
    }
}

// Ambil data soal yang mau diedit 
$editSoalId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$modeEditSoal = false;

if ($editSoalId > 0 && !$terkunci) {
    $dataEdit = ambilSoalById($editSoalId, $kuisId);
    if ($dataEdit) {
        $modeEditSoal = true;
        // Jika belum ada input error dari POST sebelumnya, isi form dengan data yang mau diedit
        if (!isPost()) {
            $nilaiSoal['pertanyaan'] = $dataEdit['pertanyaan'];
            $nilaiSoal['pembahasan'] = $dataEdit['pembahasan'] ?? '';
            $nilaiSoal['poin']       = (int) $dataEdit['poin'];

            foreach ($dataEdit['opsi'] as $opsiItem) {
                $lbl = $opsiItem['label'];
                if (isset($nilaiSoal['opsi'][$lbl])) {
                    $nilaiSoal['opsi'][$lbl] = $opsiItem['teks'];
                }
                if ($opsiItem['benar'] == 1) {
                    $nilaiSoal['kunci'] = $lbl;
                }
            }
        }
    }
}

// Bagian tampilan halaman (HTML)
$judulHalaman = 'Kelola Soal';
$halamanAktif = 'kuis';
$root = '../';

// Ambil token CSRF
$tokenCsrf = ambilTokenCsrf();

require_once __DIR__ . '/../includes/partials/header-panel.php';
?>

<div class="card">
    <h1 class="card-title"><?= e($judulHalaman) ?></h1>
    <p class="form-helper">
        Kuis: <strong><?= e($kuis['judul']) ?></strong>
        <span class="badge badge-<?= e($kuis['status']) ?>"><?= e(ucfirst($kuis['status'])) ?></span>
    </p>

    <?php if ($pesanError !== '') { ?>
        <div class="alert alert-galat" role="alert">
            <p><?= e($pesanError) ?></p>
            <?php if (!empty($daftarError)) { ?>
                <ul>
                    <?php foreach ($daftarError as $alasan) { ?>
                        <li><?= e($alasan) ?></li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if ($terkunci) { ?>
        <div class="alert alert-info" role="status">
            Kuis ini sedang menunggu persetujuan atau sudah terbit, jadi soal hanya bisa dilihat.
        </div>
    <?php } ?>
</div>

<!-- Indikator kelengkapan kuis -->
<section class="card card-sm">
    <h2 class="card-title">Kelengkapan Kuis</h2>
    <p>
        Jumlah soal: <strong><?= e($kelengkapan['jumlah_soal']) ?></strong>,
        soal lengkap: <strong><?= e($kelengkapan['soal_lengkap']) ?></strong>
    </p>
    <?php if ($kelengkapan['lengkap']) { ?>
        <div class="alert alert-sukses"><i class="fa-solid fa-circle-check"></i> Kuis sudah lengkap dan siap diajukan.</div>
    <?php } else { ?>
        <div class="alert alert-galat">
            <p><i class="fa-solid fa-circle-xmark"></i> Kuis belum lengkap:</p>
            <ul>
                <?php foreach ($kelengkapan['errors'] as $alasan) { ?>
                    <li><?= e($alasan) ?></li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>
</section>

<!-- Daftar soal -->
<section class="card">
    <h2 class="card-title">Daftar Soal</h2>

    <?php if (empty($daftarSoal)) { ?>
        <p class="form-helper">Belum ada soal. Silakan tambah soal pertama kamu.</p>
    <?php } ?>

    <?php foreach ($daftarSoal as $soal) { ?>
        <article class="card card-sm">
            <h3>Soal <?= e($soal['urutan']) ?> (<?= e($soal['poin']) ?> poin)</h3>
            <p><?= e($soal['pertanyaan']) ?></p>

            <ul>
                <?php foreach ($soal['opsi'] as $opsi) { ?>
                    <li>
                        <?= e($opsi['label']) ?>. <?= e($opsi['teks']) ?>
                        <?php if ($opsi['benar'] == 1) { ?>
                            <strong>(kunci jawaban)</strong>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>

            <?php if (!empty($soal['pembahasan'])) { ?>
                <p class="form-helper">Pembahasan: <?= e($soal['pembahasan']) ?></p>
            <?php } ?>

            <?php if (!$terkunci) { ?>
                <div class="form-group">
                    <a href="kelola-soal.php?kuis_id=<?= e($kuisId) ?>&amp;edit=<?= e($soal['id']) ?>"
                       class="btn btn-secondary btn-sm">Edit</a>

                    <!-- Tombol naik, turun, dan hapus memakai form POST kecil -->
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?= e($tokenCsrf) ?>">
                        <input type="hidden" name="soal_id" value="<?= e($soal['id']) ?>">
                        <button type="submit" name="aksi" value="naik" class="btn btn-batal btn-sm">Naik</button>
                        <button type="submit" name="aksi" value="turun" class="btn btn-batal btn-sm">Turun</button>
                        <button type="submit" name="aksi" value="hapus" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            <?php } ?>
        </article>
    <?php } ?>
</section>

<!-- Form Tambah / Edit Soal -->
<?php if (!$terkunci) { ?>
    <section class="card">
        <h2 class="card-title"><?= $modeEditSoal ? 'Edit Soal' : 'Tambah Soal Baru' ?></h2>

        <form method="post" action="kelola-soal.php?kuis_id=<?= e($kuisId) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($tokenCsrf) ?>">
            <input type="hidden" name="aksi" value="<?= $modeEditSoal ? 'edit' : 'tambah' ?>">
            <?php if ($modeEditSoal) { ?>
                <input type="hidden" name="soal_id" value="<?= e($editSoalId) ?>">
            <?php } ?>

            <div class="form-group">
                <label for="pertanyaan" class="form-label">Pertanyaan Soal</label>
                <textarea id="pertanyaan" name="pertanyaan" class="form-textarea" rows="3" required><?= e($nilaiSoal['pertanyaan']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="poin" class="form-label">Poin Soal (1 - 100)</label>
                <input type="number" id="poin" name="poin" class="form-input" min="1" max="100" value="<?= e($nilaiSoal['poin']) ?>" required>
            </div>

            <fieldset class="form-group card card-sm">
                <legend class="form-label">Pilihan Jawaban (isi minimal 2 opsi dan pilih 1 kunci jawaban)</legend>
                <?php foreach (['A', 'B', 'C', 'D', 'E'] as $huruf) { ?>
                    <div class="form-group">
                        <label>
                            <input type="radio" name="kunci" value="<?= $huruf ?>"
                                <?= ($nilaiSoal['kunci'] === $huruf) ? 'checked' : '' ?> required>
                            <strong><?= $huruf ?>.</strong>
                        </label>
                        <input type="text" name="opsi[<?= $huruf ?>]" class="form-input" maxlength="255"
                               placeholder="Teks pilihan <?= $huruf ?><?= in_array($huruf, ['A', 'B']) ? ' (wajib)' : ' (opsional)' ?>"
                               value="<?= e($nilaiSoal['opsi'][$huruf]) ?>"
                               <?= in_array($huruf, ['A', 'B']) ? 'required' : '' ?>>
                    </div>
                <?php } ?>
            </fieldset>

            <div class="form-group">
                <label for="pembahasan" class="form-label">Pembahasan (penjelasan setelah pelajar menjawab)</label>
                <textarea id="pembahasan" name="pembahasan" class="form-textarea" rows="3"><?= e($nilaiSoal['pembahasan']) ?></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <?= $modeEditSoal ? 'Simpan Perubahan Soal' : 'Tambah Soal' ?>
                </button>
                <?php if ($modeEditSoal) { ?>
                    <a href="kelola-soal.php?kuis_id=<?= e($kuisId) ?>" class="btn btn-batal">Batal Edit</a>
                <?php } ?>
            </div>
        </form>
    </section>

    <!-- Tombol ajukan publish -->
    <div class="card card-sm">
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= e($tokenCsrf) ?>">
            <button type="submit" name="aksi" value="ajukan" class="btn btn-accent">
                Ajukan Publish
            </button>
            <a href="kuis.php" class="btn btn-batal">Kembali ke Kuis Saya</a>
        </form>
    </div>
<?php } else { ?>
    <p><a href="kuis.php" class="btn btn-batal">&laquo; Kembali ke Kuis Saya</a></p>
<?php } ?>

<?php
require_once __DIR__ . '/../includes/partials/footer-panel.php';