// Script sederhana untuk tombol ikon mata (tampilkan / sembunyikan password)
document.addEventListener('DOMContentLoaded', function () {
    var daftarTombol = document.querySelectorAll('.btn-toggle-sandi');

    for (var i = 0; i < daftarTombol.length; i++) {
        daftarTombol[i].addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);

            if (!input) {
                return;
            }

            if (input.type === 'password') {
                input.type = 'text';
                this.classList.add('terlihat');
                this.setAttribute('aria-label', 'Sembunyikan password');
                this.setAttribute('title', 'Sembunyikan password');
            } else {
                input.type = 'password';
                this.classList.remove('terlihat');
                this.setAttribute('aria-label', 'Tampilkan password');
                this.setAttribute('title', 'Tampilkan password');
            }
        });
    }
});
