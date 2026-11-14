// Mencari nama pelajar di tabel hasil kuis

document.addEventListener('DOMContentLoaded', function () {
    var inputCari = document.getElementById('cariPelajar');
    var tabelNilai = document.getElementById('tabelNilai');
    var pesanKosong = document.getElementById('pesanKosongCari');

    if (!inputCari || !tabelNilai) {
        return;
    }

    var daftarBaris = tabelNilai.querySelectorAll('tbody tr');

    inputCari.addEventListener('input', function () {
        var kataKunci = inputCari.value.trim().toLowerCase();
        var jumlahTampil = 0;

        for (var i = 0; i < daftarBaris.length; i++) {
            var baris = daftarBaris[i];
            var kolomNama = baris.cells[1];
            var namaPelajar = kolomNama ? kolomNama.textContent.toLowerCase() : '';

            if (kataKunci === '' || namaPelajar.indexOf(kataKunci) !== -1) {
                baris.classList.remove('sembunyi');
                jumlahTampil++;
            } else {
                baris.classList.add('sembunyi');
            }
        }

        if (pesanKosong) {
            if (jumlahTampil === 0) {
                pesanKosong.classList.remove('sembunyi');
            } else {
                pesanKosong.classList.add('sembunyi');
            }
        }
    });
});
