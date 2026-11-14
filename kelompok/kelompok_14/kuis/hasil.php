<?php
// ============================================================================
// Halaman Hasil Kuis (PL-06 - kuis/hasil.php)
// Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
// PIC: Wulan (Sesi 7 - PL-06 Hasil Kuis)
//
// Aturan & Fitur (HSL-01, HSL-02, KT-11, AC-16, AC-18, SEC-06):
// 1. Parameter id = percobaan_kuis.id untuk Pelajar, atau kuis.id untuk Guest
// 2. Pelajar hanya bisa melihat hasil percobaan miliknya sendiri (cegah IDOR, AC-18)
// 3. Menampilkan skor, nilai persen, jumlah benar/salah, dan durasi pengerjaan
// 4. Menampilkan rincian pembahasan per soal (pertanyaan, jawaban user, kunci, pembahasan)
// 5. Untuk Guest, membaca dari sesi PHP dan menampilkan banner ajakan mendaftar (HSL-02)
// ============================================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fungsi/percobaan.php';

// Tutup percobaan kedaluwarsa terlebih dahulu
tutupPercobaanKedaluwarsa();

$peran = peranSaatIni();
$root = '../';
$halamanAktif = 'riwayat';

// Hanya Pelajar dan Guest yang boleh mengakses halaman hasil ini
if ($peran !== 'pelajar' && $peran !== 'guest') {
    if ($peran === null) {
        flash('galat', 'Silakan masuk terlebih dahulu untuk melihat hasil kuis.');
        redirect($root . 'login.php');
    } else {
        http_response_code(403);
        require_once __DIR__ . '/../403.php';
        exit;
    }
}

$isGuest = ($peran === 'guest');
$idParam = (int) ($_GET['id'] ?? 0);

// Variabel penampung hasil untuk tampilan seragam antara Pelajar & Guest
$hasil = null;
$kuisId = 0;
$statusKuisSaatIni = '';
$jumlahSoalDiubah = 0;

global $pdo;

// ============================================================================
// ALUR A: PELAJAR TERDAFTAR (Baca dari Database)
// ============================================================================
if (!$isGuest) {
    $pelajarId = (int) $_SESSION['user']['id'];
    $hasil = ambilHasilPercobaan($idParam, $pelajarId);

    // Jika data tidak ada atau bukan milik pelajar yang login -> Tolak (AC-18, SEC-06)
    if (!$hasil) {
        http_response_code(404);
        $judulHalaman = 'Hasil Tidak Ditemukan - DynaQuiz';
        require_once __DIR__ . '/../includes/partials/header-pelajar.php';
        ?>
        <div class="card" style="max-width: 600px; margin: 48px auto; text-align: center; padding: 48px 24px;">
          <div style="font-size: 3.5rem; margin-bottom: 16px;" aria-hidden="true"><i class="fa-solid fa-lock"></i></div>
          <h1 style="font-size: 1.7rem; margin-bottom: 12px;">Hasil Kuis Tidak Ditemukan</h1>
          <p style="color: var(--teks-2); margin-bottom: 28px; line-height: 1.5;">
            Data hasil pengerjaan tidak tersedia atau Anda tidak memiliki hak akses untuk melihat hasil kuis ini.
          </p>
          <a href="<?= $root ?>pelajar/riwayat.php" class="btn btn-primary" style="text-decoration: none;">
            &larr; Lihat Riwayat Saya
          </a>
        </div>
        <?php
        require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
        exit;
    }

    // Jika ternyata statusnya masih 'berjalan', arahkan kembali ke halaman kerjakan
    if ($hasil['status'] === 'berjalan') {
        redirect('kerjakan.php?id=' . (int)$hasil['kuis_id']);
    }

    $kuisId = (int) $hasil['kuis_id'];

    // Cek status kuis saat ini (untuk menentukan apakah tombol Ulangi Kuis boleh muncul)
    $stmtStatusKuis = $pdo->prepare("SELECT status FROM kuis WHERE id = ? LIMIT 1");
    $stmtStatusKuis->execute([$kuisId]);
    $statusKuisSaatIni = (string) $stmtStatusKuis->fetchColumn();

    // Cek apakah ada jawaban yang soal_id-nya menjadi NULL karena soal dihapus pengajar saat revisi (KT-11)
    $stmtSoalNull = $pdo->prepare("SELECT COUNT(*) FROM jawaban_percobaan WHERE percobaan_id = ? AND soal_id IS NULL");
    $stmtSoalNull->execute([$idParam]);
    $jumlahSoalDiubah = (int) $stmtSoalNull->fetchColumn();

// ============================================================================
// ALUR B: MODE TAMU / GUEST (Baca dari Sesi PHP, HSL-02, AC-16)
// ============================================================================
} else {
    $kuisId = $idParam;
    $hasilSesi = ambilHasilGuest($kuisId);

    if (!$hasilSesi) {
        http_response_code(404);
        $judulHalaman = 'Hasil Tamu Tidak Ditemukan - DynaQuiz';
        require_once __DIR__ . '/../includes/partials/header-pelajar.php';
        ?>
        <div class="card" style="max-width: 600px; margin: 48px auto; text-align: center; padding: 48px 24px;">
          <div style="font-size: 3.5rem; margin-bottom: 16px;" aria-hidden="true"><i class="fa-solid fa-map-location-dot"></i></div>
          <h1 style="font-size: 1.7rem; margin-bottom: 12px;">Hasil Kuis Tidak Tersedia</h1>
          <p style="color: var(--teks-2); margin-bottom: 28px; line-height: 1.5;">
            Hasil pengerjaan dalam Mode Tamu hanya disimpan sementara di sesi browser dan akan hilang ketika sesi berakhir.
          </p>
          <a href="katalog.php" class="btn btn-primary" style="text-decoration: none;">
            &larr; Jelajahi Katalog Kuis
          </a>
        </div>
        <?php
        require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
        exit;
    }

    // Ambil informasi detail kuis dari database
    $stmtKuis = $pdo->prepare("SELECT k.*, t.nama AS nama_topik, j.nama AS nama_tingkat
                               FROM kuis k
                               JOIN topik t ON k.topik_id = t.id
                               JOIN tingkat j ON k.tingkat_id = j.id
                               WHERE k.id = ? LIMIT 1");
    $stmtKuis->execute([$kuisId]);
    $infoKuis = $stmtKuis->fetch(PDO::FETCH_ASSOC);

    if (!$infoKuis) {
        http_response_code(404);
        redirect('katalog.php');
    }

    $statusKuisSaatIni = $infoKuis['status'];

    // Hitung persentase nilai
    $persen = 0;
    if ($hasilSesi['skor_maksimal'] > 0) {
        $persen = (int) round(($hasilSesi['skor'] / $hasilSesi['skor_maksimal']) * 100);
    }

    // Ambil seluruh soal dan opsi untuk dicocokkan dengan jawaban sesi guest
    $stmtSoal = $pdo->prepare("SELECT * FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
    $stmtSoal->execute([$kuisId]);
    $daftarSoal = $stmtSoal->fetchAll(PDO::FETCH_ASSOC);

    $stmtOpsi = $pdo->prepare("SELECT * FROM opsi_jawaban WHERE soal_id = ? ORDER BY label ASC");
    $jawabanGuest = $hasilSesi['jawaban'] ?? [];

    foreach ($daftarSoal as $idx => $s) {
        $stmtOpsi->execute([(int) $s['id']]);
        $daftarSoal[$idx]['opsi'] = $stmtOpsi->fetchAll(PDO::FETCH_ASSOC);

        if (isset($jawabanGuest[(int) $s['id']])) {
            $daftarSoal[$idx]['sudah_dijawab'] = true;
            $daftarSoal[$idx]['opsi_terpilih_id'] = (int) $jawabanGuest[(int) $s['id']]['opsi_id'];
            $daftarSoal[$idx]['status_benar'] = (bool) $jawabanGuest[(int) $s['id']]['benar'];
        } else {
            $daftarSoal[$idx]['sudah_dijawab'] = false;
            $daftarSoal[$idx]['opsi_terpilih_id'] = null;
            $daftarSoal[$idx]['status_benar'] = false;
        }
    }

    $hasil = [
        'kuis_id' => $kuisId,
        'judul_kuis' => $infoKuis['judul'],
        'nama_topik' => $infoKuis['nama_topik'],
        'nama_tingkat' => $infoKuis['nama_tingkat'],
        'status' => 'selesai',
        'skor' => (int) $hasilSesi['skor'],
        'skor_maksimal' => (int) $hasilSesi['skor_maksimal'],
        'persen_nilai' => $persen,
        'jumlah_benar' => (int) $hasilSesi['jumlah_benar'],
        'jumlah_salah' => (int) $hasilSesi['jumlah_salah'],
        'waktu_mulai' => $hasilSesi['waktu_mulai'],
        'waktu_selesai' => $hasilSesi['waktu_selesai'],
        'catatan' => null,
        'rincian_soal' => $daftarSoal
    ];
}

// ----------------------------------------------------------------------------
// Hitung Durasi Pengerjaan (Menit & Detik)
// ----------------------------------------------------------------------------
$teksDurasi = '-';
if (!empty($hasil['waktu_mulai']) && !empty($hasil['waktu_selesai'])) {
    $detikSelisih = max(0, strtotime($hasil['waktu_selesai']) - strtotime($hasil['waktu_mulai']));
    $menit = floor($detikSelisih / 60);
    $detik = $detikSelisih % 60;
    if ($menit > 0) {
        $teksDurasi = $menit . ' menit ' . $detik . ' detik';
    } else {
        $teksDurasi = $detik . ' detik';
    }
}

// Tentukan ucapan apresiasi sesuai skor persen
$persenSkor = (int) $hasil['persen_nilai'];
if ($persenSkor >= 85) {
    $ucapan = 'Luar Biasa! Hasil yang sangat memuaskan! <i class="fa-solid fa-champagne-glasses"></i>';
} elseif ($persenSkor >= 65) {
    $ucapan = 'Bagus Sekali! Terus tingkatkan belajarmu! <i class="fa-solid fa-hands-clapping"></i>';
} else {
    $ucapan = 'Tetap Semangat! Pelajari pembahasannya dan coba lagi ya! <i class="fa-solid fa-dumbbell"></i>';
}

// ----------------------------------------------------------------------------
// Muat Header Template Pelajar & Guest
// ----------------------------------------------------------------------------
$judulHalaman = 'Hasil Kuis: ' . $hasil['judul_kuis'] . ' - DynaQuiz';
require_once __DIR__ . '/../includes/partials/header-pelajar.php';
?>

<!-- Gunakan CSS kerjakan.css agar tampilan pembahasan dan opsi seragam -->
<link rel="stylesheet" href="<?= $root ?>assets/css/kerjakan.css">

<?php if ($isGuest): ?>
  <!-- Banner Ajakan Daftar Khusus Pengguna Tamu (HSL-02, AC-16) -->
  <div class="alert alert-info" style="margin: 24px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <strong><i class="fa-solid fa-circle-info"></i> Mode Tamu (Sementara):</strong>
      Skor dan pembahasan ini hanya disimpan sementara di browsermu dan akan hilang ketika sesi berakhir. Daftar akun Pelajar gratis agar seluruh riwayat nilaimu tersimpan permanen!
    </div>
    <a href="<?= $root ?>register.php" class="btn btn-primary" style="text-decoration: none;">
      Daftar Akun Pelajar &rarr;
    </a>
  </div>
<?php endif; ?>

<?php if (!empty($hasil['catatan'])): ?>
  <!-- Catatan Khusus jika Kuis Ditarik Saat Pengerjaan (KT-05) -->
  <div class="alert alert-info" style="margin: 20px 0;">
    <strong>Catatan Sistem:</strong> <?= e($hasil['catatan']) ?>
  </div>
<?php endif; ?>

<!-- ==========================================================================
     KARTU RINGKASAN SKOR & HASIL AKHIR (HSL-01)
     ========================================================================== -->
<div class="card" style="margin: 24px 0 32px 0; padding: 36px 28px; text-align: center;">
  <div style="display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
    <span class="badge badge-sedang"><?= e($hasil['nama_topik']) ?></span>
    <span class="badge badge-mudah"><?= e($hasil['nama_tingkat']) ?></span>
    <?php if ($hasil['status'] === 'waktu_habis'): ?>
      <span class="badge badge-sulit"><i class="fa-solid fa-stopwatch"></i> Waktu Habis</span>
    <?php else: ?>
      <span class="badge badge-terbit"><i class="fa-solid fa-check"></i> Selesai</span>
    <?php endif; ?>
  </div>

  <h1 style="font-size: 1.85rem; margin-bottom: 8px;"><?= e($hasil['judul_kuis']) ?></h1>
  <p style="color: var(--teks-2); font-size: 1.05rem; margin-bottom: 28px;"><?= $ucapan ?></p>

  <!-- Angka Skor Besar -->
  <div style="margin-bottom: 32px;">
    <div style="font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px; color: var(--teks-2); margin-bottom: 6px;">
      Skor Akhir Kamu
    </div>
    <div style="font-size: 3.5rem; font-weight: 800; color: var(--primary); line-height: 1.1;">
      <?= (int)$hasil['skor'] ?> <span style="font-size: 1.6rem; font-weight: 600; color: var(--teks-2);">/ <?= (int)$hasil['skor_maksimal'] ?></span>
    </div>
    <div style="margin-top: 10px;">
      <span class="badge <?= $persenSkor >= 70 ? 'badge-terbit' : 'badge-sedang' ?>" style="font-size: 1.1rem; padding: 6px 16px;">
        Nilai: <?= $persenSkor ?>%
      </span>
    </div>
  </div>

  <!-- Grid Bento Statistik Pengerjaan -->
  <div class="bento-grid" style="margin-bottom: 32px; text-align: center;">
    <div class="card card-sm" style="background-color: var(--permukaan);">
      <div style="font-size: 1.6rem; color: #2e7d32; margin-bottom: 4px;"><i class="fa-solid fa-circle-check"></i></div>
      <div style="font-size: 1.5rem; font-weight: 700;"><?= (int)$hasil['jumlah_benar'] ?></div>
      <div style="font-size: 0.88rem; color: var(--teks-2);">Jawaban Benar</div>
    </div>

    <div class="card card-sm" style="background-color: var(--permukaan);">
      <div style="font-size: 1.6rem; color: #c62828; margin-bottom: 4px;"><i class="fa-solid fa-circle-xmark"></i></div>
      <div style="font-size: 1.5rem; font-weight: 700;"><?= (int)$hasil['jumlah_salah'] ?></div>
      <div style="font-size: 0.88rem; color: var(--teks-2);">Salah / Kosong</div>
    </div>

    <div class="card card-sm" style="background-color: var(--permukaan);">
      <div style="font-size: 1.6rem; margin-bottom: 4px;"><i class="fa-solid fa-stopwatch"></i></div>
      <div style="font-size: 1.25rem; font-weight: 700; margin-top: 6px;"><?= e($teksDurasi) ?></div>
      <div style="font-size: 0.88rem; color: var(--teks-2);">Waktu Pengerjaan</div>
    </div>
  </div>

  <!-- Tombol Aksi Navigasi (HSL-01) -->
  <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
    <?php if ($statusKuisSaatIni === 'terbit'): ?>
      <a href="detail.php?id=<?= $kuisId ?>" class="btn btn-primary" style="text-decoration: none;">
        <i class="fa-solid fa-rotate-right"></i> Ulangi Kuis
      </a>
    <?php endif; ?>
    <a href="katalog.php" class="btn btn-outline" style="text-decoration: none;">
      <i class="fa-solid fa-magnifying-glass"></i> Cari Kuis Lain
    </a>
    <a href="<?= $isGuest ? $root . 'guest/dashboard.php' : $root . 'pelajar/dashboard.php' ?>" class="btn btn-secondary" style="text-decoration: none;">
      <i class="fa-solid fa-house"></i> Ke Dashboard
    </a>
  </div>
</div>

<!-- ==========================================================================
     RINCIAN PEMBAHASAN PER SOAL (HSL-01, KT-11)
     ========================================================================== -->
<div style="margin-bottom: 20px;">
  <h2 style="font-size: 1.5rem; margin-bottom: 4px;">Pembahasan &amp; Kunci Jawaban</h2>
  <p style="color: var(--teks-2); font-size: 0.95rem; margin: 0;">
    Pelajari kembali setiap soal dan pembahasannya di bawah ini:
  </p>
</div>

<?php if ($jumlahSoalDiubah > 0): ?>
  <!-- Pesan KT-11 jika ada soal yang dihapus/diubah setelah dikerjakan -->
  <div class="alert alert-info" style="margin-bottom: 20px;">
    <strong>Catatan Revisi Kuis:</strong> Terdapat <?= $jumlahSoalDiubah ?> soal yang telah diubah atau dihapus oleh pengajar setelah Anda mengerjakan kuis ini. Skor perolehan Anda tetap tersimpan utuh.
  </div>
<?php endif; ?>

<div style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 48px;">
  <?php foreach ($hasil['rincian_soal'] as $index => $soal): ?>
    <?php
    $nomor = $index + 1;
    $sudahDijawab = $soal['sudah_dijawab'];
    $isBenar = $soal['status_benar'];
    $opsiTerpilihId = $soal['opsi_terpilih_id'];

    // Cari label kunci jawaban yang benar
    $labelKunci = '';
    foreach ($soal['opsi'] as $o) {
        if ((int)$o['benar'] === 1) {
            $labelKunci = $o['label'];
            break;
        }
    }
    ?>
    <div class="soal-card" style="margin-bottom: 0;">
      <!-- Header Soal & Status Benar/Salah -->
      <div class="soal-header">
        <span class="soal-nomor-label">Soal <?= $nomor ?></span>
        <?php if ($isBenar): ?>
          <span class="badge badge-terbit"><i class="fa-solid fa-check"></i> Benar (+<?= (int)$soal['poin'] ?> poin)</span>
        <?php elseif ($sudahDijawab): ?>
          <span class="badge badge-ditolak"><i class="fa-solid fa-xmark"></i> Belum Tepat (0 poin)</span>
        <?php else: ?>
          <span class="badge badge-sedang"><i class="fa-solid fa-circle"></i> Tidak Dijawab (0 poin)</span>
        <?php endif; ?>
      </div>

      <!-- Pertanyaan -->
      <div class="soal-pertanyaan" style="margin-bottom: 18px;">
        <?= nl2br(e($soal['pertanyaan'])) ?>
      </div>

      <!-- Pilihan Jawaban (Menandai pilihan user dan kunci jawaban) -->
      <div class="opsi-list" style="margin-bottom: 16px;">
        <?php foreach ($soal['opsi'] as $opsi): ?>
          <?php
          $opsiId = (int) $opsi['id'];
          $isDipilihUser = ($opsiTerpilihId === $opsiId);
          $isKunci = ((int)$opsi['benar'] === 1);

          $kelasOpsi = 'terkunci';
          $penandaTeks = '';

          if ($isDipilihUser && $isKunci) {
              $kelasOpsi .= ' opsi-benar';
              $penandaTeks = 'Jawaban Kamu (Benar)';
          } elseif ($isDipilihUser && !$isKunci) {
              $kelasOpsi .= ' opsi-salah';
              $penandaTeks = 'Jawaban Kamu (Salah)';
          } elseif (!$isDipilihUser && $isKunci) {
              $kelasOpsi .= ' opsi-kunci';
              $penandaTeks = 'Kunci Jawaban';
          }
          ?>
          <div class="opsi-item <?= $kelasOpsi ?>" style="cursor: default;">
            <span class="opsi-label"><?= e($opsi['label']) ?></span>
            <span class="opsi-teks"><?= e($opsi['teks']) ?></span>
            <?php if ($penandaTeks !== ''): ?>
              <span class="badge <?= $isKunci ? 'badge-terbit' : 'badge-ditolak' ?>" style="font-size: 0.78rem; margin-left: auto;">
                <?= e($penandaTeks) ?>
              </span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Kotak Pembahasan -->
      <div class="umpan-balik-box <?= $isBenar ? 'benar' : 'salah' ?>" style="margin-top: 0;">
        <div class="umpan-balik-status">
          <?php if ($isBenar): ?>
            <span><i class="fa-solid fa-check"></i> Jawaban kamu sudah benar!</span>
          <?php else: ?>
            <span><i class="fa-solid fa-xmark"></i> Kunci jawaban yang benar adalah opsi <strong><?= e($labelKunci) ?></strong>.</span>
          <?php endif; ?>
        </div>
        <div class="umpan-balik-pembahasan">
          <strong><i class="fa-solid fa-lightbulb"></i> Pembahasan:</strong>
          <div style="margin-top: 4px;">
            <?= nl2br(e($soal['pembahasan'] ?? 'Tidak ada pembahasan tambahan untuk soal ini.')) ?>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php
require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
?>
