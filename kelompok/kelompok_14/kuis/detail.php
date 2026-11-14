<?php
// ============================================================================
// Halaman Detail Kuis (PL-04 - kuis/detail.php)
// Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
// PIC: Wulan (Sesi 4 - PL-04 Detail Kuis)
//
// Aturan & Fitur (KAT-04, KAT-05, BR-12, BR-14, KRJ-08, AC-17):
// 1. Menampilkan detail judul, deskripsi, mapel, tingkat, jumlah soal, durasi, pengajar
// 2. Menampilkan skor terbaik sebelumnya untuk Pelajar
// 3. Jika ada percobaan berjalan, tombol berubah jadi 'Lanjutkan' (KRJ-08)
// 4. Validasi akses kuis privat (BR-12, AC-17): tolak dengan 404 jika belum masukkan kode
// 5. Hanya kuis berstatus 'terbit' yang dapat dibuka, selain itu 404
// ============================================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/percobaan.php';

// Pastikan sesi kuis kedaluwarsa sudah ditutup
tutupPercobaanKedaluwarsa();

// Jika pengunjung belum login, aktifkan sesi Guest default
if (!sudahMasuk()) {
    if (!isset($_SESSION['guest'])) {
        $_SESSION['guest'] = [
            'username' => 'Tamu DynaQuiz'
        ];
    }
}

$peran = peranSaatIni();
$isGuest = ($peran === 'guest');
$pelajarId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

$kuisId = (int) ($_GET['id'] ?? 0);

// ----------------------------------------------------------------------------
// 1. Ambil Data Kuis dari Database
// ----------------------------------------------------------------------------
global $pdo;

$sqlKuis = "SELECT k.*, 
                   t.nama AS topik, 
                   j.nama AS tingkat, 
                   u.nama AS pengajar,
                   COUNT(s.id) AS jumlah_soal,
                   COALESCE(SUM(s.poin), 0) AS total_poin
            FROM kuis k
            JOIN topik t ON k.topik_id = t.id
            JOIN tingkat j ON k.tingkat_id = j.id
            JOIN user u ON k.pengajar_id = u.id
            LEFT JOIN soal s ON k.id = s.kuis_id
            WHERE k.id = ?
            GROUP BY k.id
            LIMIT 1";
$stmtKuis = $pdo->prepare($sqlKuis);
$stmtKuis->execute([$kuisId]);
$kuis = $stmtKuis->fetch(PDO::FETCH_ASSOC);

// ----------------------------------------------------------------------------
// 2. Validasi Kuis Ada & Berstatus 'terbit' (KAT-05)
// ----------------------------------------------------------------------------
$root = '../';
$halamanAktif = 'katalog';

if (!$kuis || $kuis['status'] !== 'terbit') {
    http_response_code(404);
    $judulHalaman = '404 - Kuis Tidak Ditemukan';
    require_once __DIR__ . '/../includes/partials/header-pelajar.php';
    ?>
    <div class="card" style="max-width: 600px; margin: 48px auto; text-align: center; padding: 48px 24px;">
      <div style="font-size: 3.5rem; margin-bottom: 16px;" aria-hidden="true"><i class="fa-solid fa-map-location-dot"></i></div>
      <h1 style="font-size: 1.8rem; margin-bottom: 12px;">Kuis Tidak Ditemukan (404)</h1>
      <p style="color: var(--teks-2); font-size: 1rem; margin-bottom: 28px; line-height: 1.5;">
        Kuis yang kamu cari tidak tersedia, belum dipublikasikan, atau telah ditarik dari sistem.
      </p>
      <a href="katalog.php" class="btn btn-primary" style="text-decoration: none;">
        &larr; Kembali ke Katalog Kuis
      </a>
    </div>
    <?php
    require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
    exit;
}

// ----------------------------------------------------------------------------
// 3. Validasi Hak Akses Kuis Privat (KAT-05, BR-12, BR-14, KT-04, AC-17)
// ----------------------------------------------------------------------------
if ($kuis['akses'] === 'privat') {
    // Guest dilarang mengakses kuis privat (BR-14)
    if ($isGuest) {
        http_response_code(404);
        $judulHalaman = '404 - Akses Ditolak';
        require_once __DIR__ . '/../includes/partials/header-pelajar.php';
        ?>
        <div class="card" style="max-width: 600px; margin: 48px auto; text-align: center; padding: 48px 24px;">
          <div style="font-size: 3.5rem; margin-bottom: 16px;" aria-hidden="true"><i class="fa-solid fa-lock"></i></div>
          <h1 style="font-size: 1.8rem; margin-bottom: 12px;">Kuis Privat Khusus Pelajar</h1>
          <p style="color: var(--teks-2); font-size: 1rem; margin-bottom: 28px; line-height: 1.5;">
            Kuis ini bersifat privat dan hanya dapat diakses oleh Pelajar terdaftar yang memiliki kode kuis. Pengguna tamu tidak diizinkan membuka kuis ini.
          </p>
          <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="<?= $root ?>register.php" class="btn btn-primary" style="text-decoration: none;">
              Daftar Akun Pelajar
            </a>
            <a href="katalog.php" class="btn btn-outline" style="text-decoration: none;">
              Kembali ke Katalog
            </a>
          </div>
        </div>
        <?php
        require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
        exit;
    }

    // Pelajar harus sudah memasukkan kode kuis di sesi ini ATAU sudah punya riwayat percobaan
    $sudahPunyaIzinSesi = !empty($_SESSION['akses_kuis_privat'][$kuisId]);

    $stmtCekRiwayat = $pdo->prepare("SELECT id FROM percobaan_kuis WHERE kuis_id = ? AND pelajar_id = ? LIMIT 1");
    $stmtCekRiwayat->execute([$kuisId, $pelajarId]);
    $sudahPunyaPercobaan = (bool) $stmtCekRiwayat->fetch();

    if (!$sudahPunyaIzinSesi && !$sudahPunyaPercobaan) {
        // Jika belum ada kode, tolak dengan 404 sesuai AC-17
        http_response_code(404);
        $judulHalaman = '404 - Kuis Tidak Ditemukan';
        require_once __DIR__ . '/../includes/partials/header-pelajar.php';
        ?>
        <div class="card" style="max-width: 600px; margin: 48px auto; text-align: center; padding: 48px 24px;">
          <div style="font-size: 3.5rem; margin-bottom: 16px;" aria-hidden="true"><i class="fa-solid fa-key"></i></div>
          <h1 style="font-size: 1.8rem; margin-bottom: 12px;">Kuis Privat Terkunci</h1>
          <p style="color: var(--teks-2); font-size: 1rem; margin-bottom: 28px; line-height: 1.5;">
            Kuis ini memerlukan kode akses dari pengajar. Silakan masukkan kode kuis terlebih dahulu melalui halaman katalog.
          </p>
          <a href="katalog.php" class="btn btn-primary" style="text-decoration: none;">
            &larr; Masukkan Kode di Katalog
          </a>
        </div>
        <?php
        require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
        exit;
    }
}

// ----------------------------------------------------------------------------
// 4. Cek Apakah Sedang Ada Percobaan Berjalan (KAT-04, KRJ-08)
// ----------------------------------------------------------------------------
$percobaanAktif = null;
$sisaWaktuDetik = 0;

if ($isGuest) {
    $percobaanGuest = ambilPercobaanGuest($kuisId);
    if ($percobaanGuest && $percobaanGuest['status'] === 'berjalan') {
        $percobaanAktif = $percobaanGuest;
        $mulai = strtotime($percobaanGuest['waktu_mulai']);
        $sisaWaktuDetik = max(0, ($mulai + ((int)$percobaanGuest['durasi_menit'] * 60)) - time());
    }
} else {
    $percobaanAktif = ambilPercobaanAktif($pelajarId, $kuisId);
    if ($percobaanAktif) {
        $sisaWaktuDetik = hitungSisaWaktuDetik($percobaanAktif);
    }
}

$sedangBerjalan = ($percobaanAktif !== null && $sisaWaktuDetik > 0);

// ----------------------------------------------------------------------------
// 5. Ambil Skor Terbaik dan Riwayat Sebelumnya (Khusus Pelajar, KAT-04)
// ----------------------------------------------------------------------------
$skorTerbaik = null;
$riwayatSebelumnya = [];

if (!$isGuest && $pelajarId !== null) {
    // Skor terbaik
    $stmtSkor = $pdo->prepare("SELECT MAX(skor) AS skor_tertinggi, MAX(skor_maksimal) AS skor_maksimal, COUNT(*) AS total_tes
                               FROM percobaan_kuis 
                               WHERE kuis_id = ? AND pelajar_id = ? AND status IN ('selesai', 'waktu_habis')
                               GROUP BY kuis_id");
    $stmtSkor->execute([$kuisId, $pelajarId]);
    $skorTerbaik = $stmtSkor->fetch(PDO::FETCH_ASSOC);

    // 3 Percobaan terakhir pada kuis ini
    $stmtRiwayat = $pdo->prepare("SELECT * FROM percobaan_kuis 
                                  WHERE kuis_id = ? AND pelajar_id = ? AND status IN ('selesai', 'waktu_habis')
                                  ORDER BY waktu_mulai DESC 
                                  LIMIT 3");
    $stmtRiwayat->execute([$kuisId, $pelajarId]);
    $riwayatSebelumnya = $stmtRiwayat->fetchAll(PDO::FETCH_ASSOC);
}

// ----------------------------------------------------------------------------
// 6. Konfigurasi Template
// ----------------------------------------------------------------------------
$judulHalaman = e($kuis['judul']) . ' - Detail Kuis';

require_once __DIR__ . '/../includes/partials/header-pelajar.php';
?>

<!-- Breadcrumb Navigasi -->
<div style="margin: 16px 0 24px 0; font-size: 0.95rem; color: var(--teks-2);">
  <a href="<?= $isGuest ? $root . 'guest/dashboard.php' : $root . 'pelajar/dashboard.php' ?>" style="text-decoration: none; color: var(--primer);">Beranda</a>
  &rsaquo;
  <a href="katalog.php" style="text-decoration: none; color: var(--primer);">Katalog Kuis</a>
  &rsaquo;
  <span style="color: var(--teks); font-weight: 600;"><?= e($kuis['judul']) ?></span>
</div>

<!-- Banner Jika Ada Percobaan Berjalan (KRJ-08) -->
<?php if ($sedangBerjalan): ?>
  <?php
  $sisaMenit = floor($sisaWaktuDetik / 60);
  $sisaDetikSisa = $sisaWaktuDetik % 60;
  ?>
  <div class="alert alert-info" style="margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div>
      <strong><i class="fa-solid fa-stopwatch"></i> Kamu sedang mengerjakan kuis ini!</strong>
      <div>Sisa waktu pengerjaan: <strong><?= $sisaMenit ?> menit <?= $sisaDetikSisa ?> detik</strong>. Jawaban yang sudah kamu kirim tetap tersimpan aman.</div>
    </div>
    <a href="kerjakan.php?id=<?= $kuisId ?>" class="btn btn-primary" style="text-decoration: none;">
      Lanjutkan Sekarang &rarr;
    </a>
  </div>
<?php endif; ?>

<!-- Kartu Utama Detail Kuis -->
<div class="card" style="margin-bottom: 32px; padding: 36px 32px;">
  <!-- Baris Badge Kategori & Akses -->
  <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 16px; flex-wrap: wrap;">
    <span class="badge badge-sedang"><?= e($kuis['topik']) ?></span>
    <span class="badge badge-mudah"><?= e($kuis['tingkat']) ?></span>
    <?php if ($kuis['akses'] === 'privat'): ?>
      <span class="badge badge-sulit"><i class="fa-solid fa-lock"></i> Kuis Privat (Kode: <?= e($kuis['kode']) ?>)</span>
    <?php else: ?>
      <span class="badge badge-sedang"><i class="fa-solid fa-earth-americas"></i> Kuis Publik</span>
    <?php endif; ?>
  </div>

  <!-- Judul Kuis -->
  <h1 style="font-size: 2.2rem; margin-bottom: 16px; line-height: 1.25;">
    <?= e($kuis['judul']) ?>
  </h1>

  <!-- Deskripsi Kuis -->
  <div style="color: var(--teks); font-size: 1.05rem; line-height: 1.6; margin-bottom: 28px; max-width: 800px;">
    <?= nl2br(e($kuis['deskripsi'] ?? 'Tidak ada deskripsi tambahan untuk kuis ini.')) ?>
  </div>

  <hr style="border: none; border-top: 1px solid var(--border); margin-bottom: 28px;">

  <!-- Grid Bento Info Kuis & Ringkasan Nilai (KAT-04) -->
  <div class="bento-grid" style="margin-bottom: 32px;">
    <!-- Info 1: Jumlah Soal -->
    <div class="card" style="padding: 20px; text-align: center; background-color: var(--permukaan);">
      <div style="font-size: 2rem; margin-bottom: 4px;"><i class="fa-solid fa-list-check"></i></div>
      <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 4px;"><?= (int)$kuis['jumlah_soal'] ?> Soal</div>
      <div style="color: var(--teks-2); font-size: 0.88rem;">Pilihan Ganda</div>
    </div>

    <!-- Info 2: Durasi Waktu -->
    <div class="card" style="padding: 20px; text-align: center; background-color: var(--permukaan);">
      <div style="font-size: 2rem; margin-bottom: 4px;"><i class="fa-solid fa-stopwatch"></i></div>
      <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 4px;"><?= (int)$kuis['durasi_menit'] ?> Menit</div>
      <div style="color: var(--teks-2); font-size: 0.88rem;">Batas Pengerjaan</div>
    </div>

    <!-- Info 3: Total Skor Maksimal -->
    <div class="card" style="padding: 20px; text-align: center; background-color: var(--permukaan);">
      <div style="font-size: 2rem; margin-bottom: 4px;"><i class="fa-solid fa-trophy"></i></div>
      <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 4px;"><?= (int)$kuis['total_poin'] ?> Poin</div>
      <div style="color: var(--teks-2); font-size: 0.88rem;">Skor Maksimal</div>
    </div>

    <!-- Info 4: Pengajar / Penyusun -->
    <div class="card" style="padding: 20px; text-align: center; background-color: var(--permukaan);">
      <div style="font-size: 2rem; margin-bottom: 4px;"><i class="fa-solid fa-chalkboard-user"></i></div>
      <div style="font-size: 1.15rem; font-weight: bold; margin-bottom: 4px;"><?= e($kuis['pengajar']) ?></div>
      <div style="color: var(--teks-2); font-size: 0.88rem;">Penyusun Kuis</div>
    </div>
  </div>

  <!-- Skor Terbaik Pelajar (Khusus Pelajar Terdaftar, KAT-04) -->
  <?php if (!$isGuest): ?>
    <div class="card" style="padding: 24px; margin-bottom: 32px; background-color: var(--kartu); border-left: 4px solid var(--primer);">
      <h3 style="font-size: 1.2rem; margin-bottom: 8px;">Riwayat Nilai Kamu di Kuis Ini:</h3>
      <?php if ($skorTerbaik && $skorTerbaik['total_tes'] > 0): ?>
        <?php
        $persenTerbaik = 0;
        if ($skorTerbaik['skor_maksimal'] > 0) {
            $persenTerbaik = round(($skorTerbaik['skor_tertinggi'] / $skorTerbaik['skor_maksimal']) * 100);
        }
        ?>
        <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
          <div>
            <span style="font-size: 2.2rem; font-weight: bold; color: var(--primer);">
              <?= (int)$skorTerbaik['skor_tertinggi'] ?>
            </span>
            <span style="color: var(--teks-2); font-size: 1.1rem;"> / <?= (int)$skorTerbaik['skor_maksimal'] ?></span>
            <span class="badge badge-sukses" style="margin-left: 8px; font-size: 0.95rem;"><?= $persenTerbaik ?>%</span>
          </div>
          <div style="color: var(--teks-2); font-size: 0.95rem;">
            Kamu sudah pernah mencoba kuis ini sebanyak <strong><?= (int)$skorTerbaik['total_tes'] ?> kali</strong>.
          </div>
        </div>
      <?php else: ?>
        <p style="color: var(--teks-2); margin: 0; font-size: 0.95rem;">
          Kamu belum pernah mengerjakan kuis ini. Yuk mulai sekarang dan dapatkan skor terbaikmu!
        </p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Panduan Singkat Pengerjaan -->
  <div style="margin-bottom: 36px; padding: 20px; border-radius: 16px; background-color: var(--permukaan);">
    <h3 style="font-size: 1.1rem; margin-bottom: 12px;">Petunjuk Pengerjaan Kuis:</h3>
    <ul style="margin: 0; padding-left: 20px; color: var(--teks); font-size: 0.95rem; line-height: 1.6;">
      <li>Kuis dikerjakan dalam mode fokus tanpa gangguan navigasi lain.</li>
      <li>Setiap soal berupa pilihan ganda dengan 4 opsi jawaban.</li>
      <li>Umpan balik pembahasan soal akan langsung ditampilkan begitu kamu memilih salah satu jawaban.</li>
      <li>Waktu timer dihitung dari server, sehingga memuat ulang (refresh) halaman tidak akan mengulang timer.</li>
      <li>Pastikan menyelesaikan seluruh soal sebelum batas waktu berakhir.</li>
    </ul>
  </div>

  <!-- Tombol Aksi Utama (Mulai / Lanjutkan) -->
  <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
    <?php if ($sedangBerjalan): ?>
      <a href="kerjakan.php?id=<?= $kuisId ?>" class="btn btn-primary" style="padding: 14px 32px; font-size: 1.15rem; text-decoration: none;">
        <i class="fa-solid fa-play"></i> Lanjutkan Pengerjaan Kuis &rarr;
      </a>
    <?php else: ?>
      <a href="kerjakan.php?id=<?= $kuisId ?>" class="btn btn-primary" style="padding: 14px 32px; font-size: 1.15rem; text-decoration: none;">
        <i class="fa-solid fa-play"></i> Mulai Kerjakan Kuis Sekarang &rarr;
      </a>
    <?php endif; ?>

    <a href="katalog.php" class="btn btn-outline" style="padding: 14px 24px; text-decoration: none;">
      &larr; Kembali ke Katalog
    </a>
  </div>
</div>

<!-- Riwayat 3 Percobaan Terakhir (Khusus Pelajar) -->
<?php if (!$isGuest && !empty($riwayatSebelumnya)): ?>
  <div class="card" style="padding: 28px; margin-bottom: 40px;">
    <h3 style="font-size: 1.25rem; margin-bottom: 16px;">Catatan Percobaan Terakhirmu di Kuis Ini</h3>
    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
        <thead>
          <tr style="border-bottom: 2px solid var(--border); color: var(--teks-2);">
            <th style="padding: 10px 12px;">Tanggal Mulai</th>
            <th style="padding: 10px 12px;">Status</th>
            <th style="padding: 10px 12px;">Benar / Salah</th>
            <th style="padding: 10px 12px;">Skor Diperoleh</th>
            <th style="padding: 10px 12px; text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($riwayatSebelumnya as $tes): ?>
            <tr style="border-bottom: 1px solid var(--border);">
              <td style="padding: 12px;"><?= formatTanggal($tes['waktu_mulai']) ?></td>
              <td style="padding: 12px;">
                <?php if ($tes['status'] === 'selesai'): ?>
                  <span class="badge badge-sukses">Selesai</span>
                <?php else: ?>
                  <span class="badge badge-sulit">Waktu Habis</span>
                <?php endif; ?>
              </td>
              <td style="padding: 12px;">
                <i class="fa-solid fa-check" style="color: #2e7d32;"></i> <?= (int)$tes['jumlah_benar'] ?> &bull; <i class="fa-solid fa-xmark" style="color: #c62828;"></i> <?= (int)$tes['jumlah_salah'] ?>
              </td>
              <td style="padding: 12px; font-weight: bold;">
                <?= (int)$tes['skor'] ?> / <?= (int)$tes['skor_maksimal'] ?>
              </td>
              <td style="padding: 12px; text-align: right;">
                <a href="hasil.php?id=<?= (int)$tes['id'] ?>" class="btn btn-sm btn-outline" style="text-decoration: none;">
                  Lihat Hasil
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
?>
