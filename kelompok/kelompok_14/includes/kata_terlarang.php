<?php
// Daftar kata terlarang untuk filter username Guest (SEC-13, AUT-03)

function daftarKataTerlarang() {
    return [
        'admin',
        'administrator',
        'moderator',
        'pengajar',
        'guru',
        'dosen',
        'dynaquiz',
        'official',
        'sistem',
        'system',
        'root',
        'anjing',
        'babi',
        'bangsat',
        'bodoh',
        'goblok',
        'tolol',
        'kampret',
        'setan',
        'asu',
        'kontol',
        'memek',
        'jancok',
        'bajingan'
    ];
}

// Fungsi untuk mengecek apakah username mengandung kata yang dilarang
// Mengembalikan true kalau mengandung kata terlarang, false kalau aman
function cekKataTerlarang($username) {
    // Ubah ke huruf kecil semua dan hapus spasi serta underscore sesuai aturan SEC-13
    $teks_bersih = strtolower($username);
    $teks_bersih = str_replace([' ', '_'], '', $teks_bersih);

    $daftar_kata = daftarKataTerlarang();

    foreach ($daftar_kata as $kata) {
        if (strpos($teks_bersih, $kata) !== false) {
            return true;
        }
    }

    return false;
}
