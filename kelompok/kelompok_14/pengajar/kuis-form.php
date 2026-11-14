<?php
// PJ-03: Form Buat dan Edit Kuis untuk Pengajar

// Muat file konfigurasi dan helper
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Pastikan yang mengakses halaman ini adalah pengajar
wajibPeran(['pengajar']);

// Ambil id pengajar yang sedang login dari session
$pengajarId = (int) $_SESSION['user']['id'];

// Cek apakah ini mode Edit (ada parameter ?id= di URL) atau mode Buat Baru
$kuisId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modeEdit = $kuisId > 0;
$kuis = null;

if ($modeEdit) {
    $kuis = ambilKuisById($kuisId, $pengajarId);

    // Kalau kuis tidak ditemukan atau bukan milik pengajar ini, arahkan ke halaman 404
    if (!$kuis) {
        redirect(BASE_URL . '/404.php');
    }

    // Kuis yang sedang menunggu persetujuan atau sudah terbit tidak boleh diedit
    if ($kuis['status'] == 'menunggu' || $kuis['status'] == 'terbit') {
        flash('galat', 'Kuis yang sedang menunggu persetujuan atau sudah terbit tidak dapat diedit.');
        redirect('kuis.php');
    }
}

// Ambil daftar topik dan tingkat untuk pilihan dropdown di form
$daftarTopik = ambilSemuaTopik();
$daftarTingkat = ambilSemuaTingkat();

// Nilai awal untuk diisikan ke kolom-kolom form
$nilaiForm = [
    'judul'        => $kuis['judul'] ?? '',
    'deskripsi'    => $kuis['deskripsi'] ?? '',
    'topik_id'     => $kuis['topik_id'] ?? '',
    'tingkat_id'   => $kuis['tingkat_id'] ?? '',
    'durasi_menit' => $kuis['durasi_menit'] ?? 15,
    'akses'        => $kuis['akses'] ?? 'publik',
    'kode'         => $kuis['kode'] ?? ''
];

$errors = [];
$pesanErrorUmum = '';

// Proses saat form dikirim (POST)
if (isPost()) {
    // Cek token CSRF
    verifikasiCsrf();

    // Ambil input dari form
    $nilaiForm['judul']        = trim($_POST['judul'] ?? '');
    $nilaiForm['deskripsi']    = trim($_POST['deskripsi'] ?? '');
    $nilaiForm['topik_id']     = (int) ($_POST['topik_id'] ?? 0);
    $nilaiForm['tingkat_id']   = (int) ($_POST['tingkat_id'] ?? 0);
    $nilaiForm['durasi_menit'] = (int) ($_POST['durasi_menit'] ?? 15);
    $nilaiForm['akses']        = $_POST['akses'] ?? 'publik';

    // Validasi per kolom supaya error bisa ditampilkan di bawah masing-masing input
    if ($nilaiForm['judul'] === '') {
        $errors['judul'] = 'Judul kuis wajib diisi.';
    } elseif (strlen($nilaiForm['judul']) < 3 || strlen($nilaiForm['judul']) > 150) {
        $errors['judul'] = 'Judul kuis harus 3 sampai 150 karakter.';
    }

    if ($nilaiForm['topik_id'] <= 0) {
        $errors['topik_id'] = 'Silakan pilih topik kuis.';
    }

    if ($nilaiForm['tingkat_id'] <= 0) {
        $errors['tingkat_id'] = 'Silakan pilih tingkat jenjang.';
    }

    if ($nilaiForm['durasi_menit'] < 1 || $nilaiForm['durasi_menit'] > 240) {
        $errors['durasi_menit'] = 'Durasi pengerjaan harus antara 1 sampai 240 menit.';
    }

    if ($nilaiForm['akses'] !== 'publik' && $nilaiForm['akses'] !== 'privat') {
        $errors['akses'] = 'Pilih jenis akses publik atau privat.';
    }

    // Kalau tidak ada error validasi, simpan ke database
    if (empty($errors)) {
        if ($modeEdit) {
            $hasil = editKuis($kuisId, $pengajarId, $nilaiForm);
            if ($hasil['berhasil']) {
                flash('sukses', 'Perubahan kuis berhasil disimpan.');

                // Cek apakah pengajar menekan tombol lanjut kelola soal
                $tujuan = isset($_POST['lanjut_soal'])
                    ? 'kelola-soal.php?kuis_id=' . $kuisId
                    : 'kuis.php';

                redirect($tujuan);
            } else {
                $pesanErrorUmum = $hasil['pesan'] ?? 'Gagal memperbarui kuis.';
            }
        } else {
            $hasil = tambahKuis($pengajarId, $nilaiForm);
            if ($hasil['berhasil']) {
                $idBaru = (int) $hasil['kuis_id'];
                flash('sukses', 'Draft kuis berhasil dibuat. Silakan tambahkan soal.');

                // Setelah buat kuis baru, mengarah ke halaman kelola soal
                $tujuan = 'kelola-soal.php?kuis_id=' . $idBaru;
                redirect($tujuan);
            } else {
                $pesanErrorUmum = $hasil['pesan'] ?? 'Gagal membuat kuis baru.';
            }
        }
    }
}

// Bagian tampilan halaman (HTML)
$judulHalaman = $modeEdit ? 'Edit Kuis' : 'Buat Kuis Baru';
$halamanAktif = 'kuis';
$root = '../';

// Kalau kuis pernah ditolak atau ditarik, ambil alasannya untuk ditampilkan
$catatanRevisi = '';
if ($modeEdit && ($kuis['status'] == 'ditolak' || $kuis['status'] == 'ditarik')) {
    $catatanRevisi = ambilCatatanStatusTerakhir($kuisId);
}

// Ambil token CSRF dari file csrf.php
$tokenCsrf = ambilTokenCsrf();

require_once __DIR__ . '/../includes/partials/header-panel.php';
?>
<div class="card">
    <h1 class="card-title"><?= e($judulHalaman) ?></h1>

    <?php if ($pesanErrorUmum !== '') { ?>
        <div class="alert alert-galat" role="alert">
            <?= e($pesanErrorUmum) ?>
        </div>
    <?php } ?>

    <?php if ($catatanRevisi !== '') { ?>
        <div class="alert alert-info" role="status">
            <strong>
                Kuis ini <?= ($kuis['status'] == 'ditolak') ? 'ditolak' : 'ditarik' ?> oleh admin.
            </strong>
            <p>Catatan: <?= e($catatanRevisi) ?></p>
            <p>Silakan perbaiki kuis lalu ajukan kembali.</p>
        </div>
    <?php } ?>

    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($tokenCsrf) ?>">

        <!-- Judul -->
        <div class="form-group">
            <label for="judul" class="form-label">Judul Kuis</label>
            <input type="text" id="judul" name="judul" class="form-input" maxlength="150"
                   value="<?= e($nilaiForm['judul']) ?>" required>
            <?php if (isset($errors['judul'])) { ?>
                <p class="form-error"><?= e($errors['judul']) ?></p>
            <?php } ?>
        </div>

        <!-- Deskripsi -->
        <div class="form-group">
            <label for="deskripsi" class="form-label">Deskripsi (boleh dikosongkan)</label>
            <textarea id="deskripsi" name="deskripsi" class="form-textarea" rows="4"><?= e($nilaiForm['deskripsi']) ?></textarea>
        </div>

        <!-- Topik -->
        <div class="form-group">
            <label for="topik_id" class="form-label">Topik</label>
            <select id="topik_id" name="topik_id" class="form-select" required>
                <option value="">-- Pilih topik --</option>
                <?php foreach ($daftarTopik as $topik) { ?>
                    <option value="<?= e($topik['id']) ?>"
                        <?= ($nilaiForm['topik_id'] == $topik['id']) ? 'selected' : '' ?>>
                        <?= e($topik['nama']) ?>
                    </option>
                <?php } ?>
            </select>
            <?php if (isset($errors['topik_id'])) { ?>
                <p class="form-error"><?= e($errors['topik_id']) ?></p>
            <?php } ?>
        </div>

        <!-- Tingkat -->
        <div class="form-group">
            <label for="tingkat_id" class="form-label">Tingkat</label>
            <select id="tingkat_id" name="tingkat_id" class="form-select" required>
                <option value="">-- Pilih tingkat --</option>
                <?php foreach ($daftarTingkat as $tingkat) { ?>
                    <option value="<?= e($tingkat['id']) ?>"
                        <?= ($nilaiForm['tingkat_id'] == $tingkat['id']) ? 'selected' : '' ?>>
                        <?= e($tingkat['nama']) ?>
                    </option>
                <?php } ?>
            </select>
            <?php if (isset($errors['tingkat_id'])) { ?>
                <p class="form-error"><?= e($errors['tingkat_id']) ?></p>
            <?php } ?>
        </div>

        <!-- Durasi -->
        <div class="form-group">
            <label for="durasi_menit" class="form-label">Durasi (menit)</label>
            <input type="number" id="durasi_menit" name="durasi_menit" class="form-input" min="1" max="240"
                   value="<?= e($nilaiForm['durasi_menit']) ?>" required>
            <?php if (isset($errors['durasi_menit'])) { ?>
                <p class="form-error"><?= e($errors['durasi_menit']) ?></p>
            <?php } ?>
        </div>

        <!-- Jenis akses -->
        <div class="form-group">
            <p class="form-label">Jenis Akses</p>
            <label for="akses_publik">
                <input type="radio" id="akses_publik" name="akses" value="publik"
                    <?= ($nilaiForm['akses'] == 'publik') ? 'checked' : '' ?>>
                Publik (tampil di katalog)
            </label>
            <label for="akses_privat">
                <input type="radio" id="akses_privat" name="akses" value="privat"
                    <?= ($nilaiForm['akses'] == 'privat') ? 'checked' : '' ?>>
                Privat (dibuka dengan kode kuis)
            </label>
            <?php if (isset($errors['akses'])) { ?>
                <p class="form-error"><?= e($errors['akses']) ?></p>
            <?php } ?>
        </div>

        <!-- Kode kuis hanya tampil jika kuis privat sudah punya kode -->
        <?php if ($modeEdit && $nilaiForm['akses'] == 'privat' && $nilaiForm['kode'] !== '') { ?>
            <div class="form-group card card-sm">
                <p class="form-label">Kode Kuis (bagikan ke pelajar)</p>
                <p><strong><?= e($nilaiForm['kode']) ?></strong></p>
            </div>
        <?php } elseif ($nilaiForm['akses'] == 'privat') { ?>
            <p class="form-helper">Kode kuis akan dibuat otomatis setelah kuis disimpan.</p>
        <?php } ?>

        <div class="form-group">
            <a href="kuis.php" class="btn btn-batal">Batal</a>
            <button type="submit" name="simpan" value="1" class="btn btn-primary">Simpan</button>
            <?php if ($modeEdit) { ?>
                <button type="submit" name="lanjut_soal" value="1" class="btn btn-accent">
                    Simpan &amp; Kelola Soal
                </button>
            <?php } ?>
        </div>
    </form>
</div>
<?php
require_once __DIR__ . '/../includes/partials/footer-panel.php';
