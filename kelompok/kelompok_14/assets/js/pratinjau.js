document.addEventListener('DOMContentLoaded', function () {
  var areaPratinjau = document.getElementById('areaPratinjau');
  if (!areaPratinjau) {
    return;
  }

  var kartuSoal = document.querySelectorAll('.kartu-soal-pratinjau');
  var tombolGrid = document.querySelectorAll('.tombol-grid-soal');
  var tombolOpsi = document.querySelectorAll('.tombol-opsi-pratinjau');

  var tombolSebelumnya = document.getElementById('tombolSebelumnya');
  var tombolBerikutnya = document.getElementById('tombolBerikutnya');
  var tombolSelesai = document.getElementById('tombolSelesaiPratinjau');
  var tombolKeluar = document.getElementById('tombolKeluarFokus');

  var infoNomorSoal = document.getElementById('infoNomorSoal');
  var infoPoinSoal = document.getElementById('infoPoinSoal');
  var progressFill = document.getElementById('progressFill');

  var timerDisplay = document.getElementById('timerDisplay');
  var wadahTimer = document.getElementById('wadahTimer');

  var hasilPratinjau = document.getElementById('hasilPratinjau');
  var hasilSkorAkhir = document.getElementById('hasilSkorAkhir');
  var hasilSkorMaks = document.getElementById('hasilSkorMaks');
  var hasilPersenAkhir = document.getElementById('hasilPersenAkhir');
  var hasilJumlahBenar = document.getElementById('hasilJumlahBenar');
  var hasilJumlahSalah = document.getElementById('hasilJumlahSalah');

  var totalSoal = kartuSoal.length;
  var soalAktif = 0;

  // Simpan status jawaban tiap soal di memori browser
  var jawabanPengguna = {};

  // Hitung total poin maksimal semua soal
  var skorMaksimal = 0;
  for (var i = 0; i < kartuSoal.length; i++) {
    skorMaksimal += parseInt(kartuSoal[i].getAttribute('data-poin') || '10', 10);
  }

  // Fungsi untuk menampilkan soal pada index tertentu
  function tampilkanSoal(index) {
    if (index < 0 || index >= totalSoal) {
      return;
    }

    soalAktif = index;

    // Sembunyikan semua kartu soal, lalu tampilkan yang aktif saja
    for (var i = 0; i < kartuSoal.length; i++) {
      if (i === soalAktif) {
        kartuSoal[i].removeAttribute('hidden');
      } else {
        kartuSoal[i].setAttribute('hidden', 'hidden');
      }
    }

    // Perbarui tulisan "Soal X dari Y" dan poin soal
    var poinSaatIni = kartuSoal[soalAktif].getAttribute('data-poin') || '10';
    if (infoNomorSoal) {
      infoNomorSoal.textContent = 'Soal ' + (soalAktif + 1) + ' dari ' + totalSoal;
    }
    if (infoPoinSoal) {
      infoPoinSoal.textContent = poinSaatIni + ' Poin';
    }

    // Atur tombol Sebelumnya & Lanjut
    if (tombolSebelumnya) {
      tombolSebelumnya.disabled = (soalAktif === 0);
    }
    if (tombolBerikutnya) {
      tombolBerikutnya.disabled = (soalAktif === totalSoal - 1);
    }

    perbaruiTampilanGrid();
  }

  // Fungsi untuk memperbarui warna tombol grid nomor soal dan progress bar
  function perbaruiTampilanGrid() {
    var jumlahTerjawab = 0;

    for (var i = 0; i < tombolGrid.length; i++) {
      var idx = parseInt(tombolGrid[i].getAttribute('data-target-index'), 10);
      tombolGrid[i].classList.remove('btn-primary', 'btn-outline', 'btn-secondary');

      if (jawabanPengguna[idx]) {
        jumlahTerjawab++;
        tombolGrid[i].classList.add('btn-primary');
      } else if (idx === soalAktif) {
        tombolGrid[i].classList.add('btn-secondary');
      } else {
        tombolGrid[i].classList.add('btn-outline');
      }
    }

    if (progressFill && totalSoal > 0) {
      var persenProgress = Math.round((jumlahTerjawab / totalSoal) * 100);
      progressFill.style.width = persenProgress + '%';
    }
  }

  // Pasang event klik untuk setiap tombol pilihan jawaban (A-E)
  for (var i = 0; i < tombolOpsi.length; i++) {
    tombolOpsi[i].addEventListener('click', function () {
      var idxSoal = parseInt(this.getAttribute('data-soal-index'), 10);

      // Jika soal ini sudah pernah dijawab, jangan izinkan ganti jawaban
      if (jawabanPengguna[idxSoal]) {
        return;
      }

      var labelDipilih = this.getAttribute('data-label');
      var apakahBenar = this.getAttribute('data-benar') === '1';
      var poinSoal = parseInt(kartuSoal[idxSoal].getAttribute('data-poin') || '10', 10);

      // Simpan hasil ke objek memori sementara
      jawabanPengguna[idxSoal] = {
        benar: apakahBenar,
        poin: apakahBenar ? poinSoal : 0
      };

      // Kunci semua opsi di soal ini dan beri tanda visual benar/salah
      var semuaOpsiDiSoal = kartuSoal[idxSoal].querySelectorAll('.tombol-opsi-pratinjau');
      var labelKunci = '';

      for (var j = 0; j < semuaOpsiDiSoal.length; j++) {
        var btn = semuaOpsiDiSoal[j];
        btn.disabled = true;

        var lbl = btn.getAttribute('data-label');
        var kunci = btn.getAttribute('data-benar') === '1';

        if (kunci) {
          labelKunci = lbl;
          btn.classList.remove('btn-outline');
          btn.classList.add('btn-primary');
        } else if (lbl === labelDipilih && !apakahBenar) {
          btn.classList.remove('btn-outline');
          btn.classList.add('btn-danger');
        }
      }

      // Tampilkan pembahasan
      var kotakUmpan = document.getElementById('umpan-balik-' + idxSoal);
      if (kotakUmpan) {
        var isiStatus = kotakUmpan.querySelector('.isi-status-jawaban');
        if (isiStatus) {
          isiStatus.classList.remove('alert-sukses', 'alert-galat');
          if (apakahBenar) {
            isiStatus.classList.add('alert-sukses');
            isiStatus.textContent = '✔ Benar! Jawaban Anda tepat.';
          } else {
            isiStatus.classList.add('alert-galat');
            isiStatus.textContent = '✘ Belum tepat. Kunci jawaban yang benar adalah ' + labelKunci + '.';
          }
        }
        kotakUmpan.removeAttribute('hidden');
      }

      perbaruiTampilanGrid();
    });
  }

  // Event klik pada tombol grid nomor soal
  for (var k = 0; k < tombolGrid.length; k++) {
    tombolGrid[k].addEventListener('click', function () {
      var target = parseInt(this.getAttribute('data-target-index'), 10);
      tampilkanSoal(target);
    });
  }

  // Event tombol Sebelumnya & Lanjut
  if (tombolSebelumnya) {
    tombolSebelumnya.addEventListener('click', function () {
      tampilkanSoal(soalAktif - 1);
    });
  }

  if (tombolBerikutnya) {
    tombolBerikutnya.addEventListener('click', function () {
      tampilkanSoal(soalAktif + 1);
    });
  }

  // Fungsi untuk menyelesaikan pratinjau dan menghitung skor akhir di browser
  var timerInterval = null;
  function selesaikanPratinjau() {
    if (timerInterval) {
      clearInterval(timerInterval);
    }

    var skorAkhir = 0;
    var jumlahBenar = 0;

    for (var i = 0; i < totalSoal; i++) {
      if (jawabanPengguna[i] && jawabanPengguna[i].benar) {
        jumlahBenar++;
        skorAkhir += jawabanPengguna[i].poin;
      }
    }

    var jumlahSalah = totalSoal - jumlahBenar;
    var persenAkhir = skorMaksimal > 0 ? Math.round((skorAkhir / skorMaksimal) * 100) : 0;

    if (hasilSkorAkhir) hasilSkorAkhir.textContent = skorAkhir;
    if (hasilSkorMaks) hasilSkorMaks.textContent = skorMaksimal;
    if (hasilPersenAkhir) hasilPersenAkhir.textContent = persenAkhir + '%';
    if (hasilJumlahBenar) hasilJumlahBenar.textContent = jumlahBenar;
    if (hasilJumlahSalah) hasilJumlahSalah.textContent = jumlahSalah;

    areaPratinjau.setAttribute('hidden', 'hidden');
    if (hasilPratinjau) {
      hasilPratinjau.removeAttribute('hidden');
    }  
  }

  // Event tombol Selesai Pratinjau
  if (tombolSelesai) {
    tombolSelesai.addEventListener('click', function () {
      var jumlahTerjawab = Object.keys(jawabanPengguna).length;
      if (jumlahTerjawab < totalSoal) {
        var sisa = totalSoal - jumlahTerjawab;
        if (!confirm('Masih ada ' + sisa + ' soal yang belum dijawab. Akhiri pratinjau sekarang?')) {
          return;
        }
      }
      selesaikanPratinjau();
    });
  }

  // Konfirmasi jika menekan tombol Keluar
  if (tombolKeluar) {
    tombolKeluar.addEventListener('click', function (e) {
      if (!confirm('Keluar dari mode pratinjau?')) {
        e.preventDefault();
      }
    });
  }

  // Timer mundur simulasi pratinjau
  var durasiMenit = parseInt(areaPratinjau.getAttribute('data-durasi') || '15', 10);
  var sisaDetik = durasiMenit * 60;

  function perbaruiTimer() {
    var menit = Math.floor(sisaDetik / 60);
    var detik = sisaDetik % 60;
    var teksMenit = menit < 10 ? '0' + menit : '' + menit;
    var teksDetik = detik < 10 ? '0' + detik : '' + detik;

    if (timerDisplay) {
      timerDisplay.textContent = teksMenit + ':' + teksDetik;
    }

    // Jika sisa waktu kurang dari atau sama dengan 1 menit, beri warna peringatan
    if (wadahTimer && sisaDetik <= 60) {
      wadahTimer.classList.add('timer-peringatan');
    }

    if (sisaDetik <= 0) {
      alert('Waktu pratinjau habis! Menampilkan ringkasan hasil.');
      selesaikanPratinjau();
      return;
    }

    sisaDetik--;
  }

  // Jalankan timer dan tampilkan soal pertama
  perbaruiTimer();
  timerInterval = setInterval(perbaruiTimer, 1000);
  tampilkanSoal(0);
});
