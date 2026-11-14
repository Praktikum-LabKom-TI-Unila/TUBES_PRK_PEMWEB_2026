document.addEventListener('DOMContentLoaded', function () {
    const daftarForm = document.querySelectorAll('.form-konfirmasi');

    daftarForm.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const pesan = form.getAttribute('data-konfirmasi') || 'Yakin ingin melanjutkan aksi ini?';
            if (!window.confirm(pesan)) {
                event.preventDefault();
            }
        });
    });
});
