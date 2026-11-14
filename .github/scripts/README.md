# 🛠️ Panduan Skrip Otomatisasi & Audit Praktikum Pemweb 2026

Direktori ini berisi skrip utilitas untuk tim Asisten Praktikum dan CI/CD GitHub Actions:

---

## 1. `audit_kelompok.php` (Audit Kontribusi Lokal)
Skrip ini digunakan oleh asisten/dosen untuk merekap riwayat commit, kontribusi anggota, baris kode (`+` / `-`), dan status Pull Request terakhir per kelompok secara langsung dari repositori Git lokal.

Secara *default*, skrip menampilkan **keseluruhan riwayat commit** tanpa dibatasi.

### Cara Penggunaan:
```bash
# 1. Audit satu kelompok (menampilkan semua commit tanpa dibatasi)
php .github/scripts/audit_kelompok.php --kelompok=09

# 2. Audit seluruh kelompok sekaligus di terminal
php .github/scripts/audit_kelompok.php --all

# 3. ⭐ Ekspor ke file Microsoft Excel (.xls / .xlsx) dengan sheet khusus per kelompok (mirip tampilan audit di terminal) + Sheet Ringkasan
php .github/scripts/audit_kelompok.php --all --export=rekap_nilai_kontribusi.xls

# 4. Ekspor ke file spreadsheet CSV (UTF-8 BOM siap buka di Excel)
php .github/scripts/audit_kelompok.php --all --export=rekap_nilai_kontribusi.csv

# 5. Ekspor ke format dokumen laporan Markdown
php .github/scripts/audit_kelompok.php --all --export=rekap_nilai_kontribusi.md

# 6. (Opsional) Membatasi jumlah commit terakhir di terminal
php .github/scripts/audit_kelompok.php --kelompok=09 --limit=10
```

---

## 2. `validate_fork.php` (Validasi Batas Kepemilikan Fork Kelompok)
Skrip ini dipanggil otomatis di Step 5 pada workflow `.github/workflows/pr-validation.yml`.

### Cara Kerja:
* Memeriksa pemilik repositori fork asal PR (`HEAD_REPO_OWNER`) dan mencocokkannya dengan folder kelompok yang disentuh (`GROUP_TOUCHED`) mengacu pada file `.github/fork_mapping.json`.
* **Jika fork kelompok A mencoba menyentuh folder kelompok B:** PR langsung **DITOLAK** dan ditutup otomatis.
* **Jika ada fork tak dikenal yang mencoba mengubah kelompok yang sudah terdaftar:** PR langsung **DITOLAK**.

---

## 3. `pr_rekap_kontribusi.php` (Bot CI/CD Pull Request)
Skrip ini dijalankan secara otomatis oleh workflow `.github/workflows/pr-validation.yml` setiap kali mahasiswa mengajukan Pull Request (PR) ke branch `main`.

### Fungsi:
- Menganalisis commit yang dibawa oleh PR kelompok tersebut.
- Memposting komentar otomatis berisi tabel kontribusi anggota (nama, email, jumlah commit, % kontribusi, dan statistik baris kode) langsung di halaman PR GitHub sebelum PR di-merge.
