<?php
/**
 * Script Audit & Rekapitulasi Kontribusi Git per Kelompok
 * Tubes Praktikum Pemrograman Web 2026 - LabKom TI Unila
 * 
 * Penggunaan:
 *   php .github/scripts/audit_kelompok.php --kelompok=22
 *   php .github/scripts/audit_kelompok.php --all
 *   php .github/scripts/audit_kelompok.php --all --export=rekap_nilai.xls   (Format Microsoft Excel Rapi)
 *   php .github/scripts/audit_kelompok.php --all --export=rekap_nilai.csv   (Format CSV UTF-8)
 *   php .github/scripts/audit_kelompok.php --all --export=rekap_nilai.md    (Format Dokumen Markdown)
 */

// Pastikan dijalankan dari CLI
if (php_sapi_name() !== 'cli') {
    die("Script ini hanya dapat dijalankan melalui CLI/terminal.\n");
}

$options = getopt('', ['kelompok:', 'all', 'export:', 'limit::', 'help']);

if (isset($options['help']) || (empty($options['kelompok']) && !isset($options['all']))) {
    echo "=======================================================================\n";
    echo "📋 AUDIT KONTRIBUSI GIT PER KELOMPOK - PEMWEB 2026\n";
    echo "=======================================================================\n";
    echo "Penggunaan:\n";
    echo "  php .github/scripts/audit_kelompok.php --kelompok=XX\n";
    echo "      Audit satu kelompok (menampilkan semua commit tanpa dibatasi)\n";
    echo "      Contoh: php .github/scripts/audit_kelompok.php --kelompok=09\n\n";
    echo "  php .github/scripts/audit_kelompok.php --all\n";
    echo "      Audit semua kelompok yang ada di folder kelompok/\n\n";
    echo "  php .github/scripts/audit_kelompok.php --all --export=laporan.xls\n";
    echo "      ⭐ Ekspor ke format Microsoft Excel dengan sheet terpisah per kelompok (beserta ringkasan)\n\n";
    echo "  php .github/scripts/audit_kelompok.php --all --export=laporan.csv\n";
    echo "      Ekspor ke file spreadsheet CSV standar\n\n";
    echo "  php .github/scripts/audit_kelompok.php --all --export=laporan.md\n";
    echo "      Ekspor ke file laporan Markdown\n\n";
    echo "  --limit=N\n";
    echo "      (Opsional) Batasi jumlah commit yang ditampilkan. Default: 0 (Semua Commit)\n";
    echo "=======================================================================\n";
    exit(0);
}

// Default limit = 0 (tampilkan keseluruhan commit tanpa dibatasi)
$commitLimit = isset($options['limit']) ? (int)$options['limit'] : 0;
$exportFile = $options['export'] ?? null;

// Deteksi folder repo root
$repoRoot = realpath(__DIR__ . '/../..');
chdir($repoRoot);

// Cek apakah git terpasang dan merupakan repositori git
exec('git rev-parse --is-inside-work-tree 2>&1', $gitCheck, $gitReturn);
if ($gitReturn !== 0) {
    die("❌ Error: Direktori saat ini bukan repositori Git yang valid.\n");
}

function getAvailableKelompok(): array {
    $dirs = glob('kelompok/kelompok_*', GLOB_ONLYDIR);
    $kelompokList = [];
    foreach ($dirs as $dir) {
        $basename = basename($dir);
        if (preg_match('/^kelompok_(\d+)$/', $basename, $matches)) {
            $kelompokList[] = [
                'folder' => $basename,
                'path' => 'kelompok/' . $basename,
                'id' => $matches[1]
            ];
        }
    }
    usort($kelompokList, fn($a, $b) => (int)$a['id'] <=> (int)$b['id']);
    return $kelompokList;
}

/**
 * Mengambil peta Pull Request terakhir yang berhasil di-merge ke main untuk setiap kelompok
 */
function getLastMergedPrMap(): array {
    $mappingFile = __DIR__ . '/../fork_mapping.json';
    $forkMapping = file_exists($mappingFile) ? json_decode(file_get_contents($mappingFile), true) : [];
    $forkToGroup = $forkMapping['fork_to_group'] ?? [];

    $cmd = 'git log origin/main --merges --pretty="format:%H|%ad|%s" --date=format:"%Y-%m-%d %H:%M"';
    exec($cmd, $mergeLines);

    $lastPrMap = [];
    foreach ($mergeLines as $line) {
        if (preg_match('/^([^|]+)\|([^|]+)\|Merge pull request #(\d+) from ([^\/]+)\/(.+)$/i', $line, $m)) {
            $hash = $m[1];
            $date = $m[2];
            $prNum = $m[3];
            $owner = strtolower(trim($m[4]));
            $branch = trim($m[5]);

            // Deteksi kelompok dari owner mapping atau nama branch
            $group = $forkToGroup[$owner] ?? null;
            if (!$group && preg_match('/kelompok[-_]?(\d+)/i', $branch . ' ' . $line, $gm)) {
                $group = 'kelompok_' . str_pad($gm[1], 2, '0', STR_PAD_LEFT);
            }

            if ($group && !isset($lastPrMap[$group])) {
                $lastPrMap[$group] = [
                    'pr_number' => '#' . $prNum,
                    'date'      => $date,
                    'owner'     => '@' . $m[4],
                    'branch'    => $branch,
                    'hash'      => substr($hash, 0, 7)
                ];
            }
        }
    }
    return $lastPrMap;
}

function auditKelompok(string $folderPath, int $commitLimit, ?array $lastPr): ?array {
    if (!is_dir($folderPath)) {
        return null;
    }

    $folder = rtrim($folderPath, '/\\');
    
    // 1. Ambil data commit numstat untuk menghitung baris tambah/kurang per author
    $cmdNumstat = "git log --no-merges --numstat --pretty=format:\"COMMIT_META:%H|%an|%ae|%ad|%s\" --date=format:\"%Y-%m-%d %H:%M\" -- \"$folder/\"";
    exec($cmdNumstat, $numstatOutput, $retNumstat);

    if ($retNumstat !== 0 || empty($numstatOutput)) {
        return [
            'folder' => basename($folder),
            'total_commits' => 0,
            'authors' => [],
            'recent_commits' => [],
            'last_pr' => $lastPr
        ];
    }

    $authors = [];
    $commits = [];
    $currentAuthor = null;
    $currentEmail = null;

    foreach ($numstatOutput as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        if (str_starts_with($line, 'COMMIT_META:')) {
            $meta = substr($line, strlen('COMMIT_META:'));
            $parts = explode('|', $meta, 5);
            $hash = $parts[0] ?? '';
            $name = $parts[1] ?? 'Unknown';
            $email = $parts[2] ?? '';
            $date = $parts[3] ?? '';
            $msg = $parts[4] ?? '';

            $currentAuthor = $name;
            $currentEmail = $email;
            $authorKey = strtolower(trim($email ?: $name));

            if (!isset($authors[$authorKey])) {
                $authors[$authorKey] = [
                    'name' => $name,
                    'email' => $email,
                    'commits' => 0,
                    'added' => 0,
                    'deleted' => 0,
                    'files' => []
                ];
            }
            $authors[$authorKey]['commits']++;

            if (!isset($commits[$hash])) {
                $commits[$hash] = [
                    'hash' => substr($hash, 0, 7),
                    'full_hash' => $hash,
                    'author' => $name,
                    'email' => $email,
                    'date' => $date,
                    'message' => $msg
                ];
            }
        } elseif ($currentAuthor !== null && preg_match('/^(\d+|-)\s+(\d+|-)\s+(.+)$/', $line, $m)) {
            $authorKey = strtolower(trim($currentEmail ?: $currentAuthor));
            $add = ($m[1] === '-') ? 0 : (int)$m[1];
            $del = ($m[2] === '-') ? 0 : (int)$m[2];
            $file = $m[3];

            $authors[$authorKey]['added'] += $add;
            $authors[$authorKey]['deleted'] += $del;
            if (!in_array($file, $authors[$authorKey]['files'])) {
                $authors[$authorKey]['files'][] = $file;
            }
        }
    }

    $totalCommits = count($commits);

    // Hitung persentase kontribusi per anggota
    foreach ($authors as $key => &$data) {
        $data['percentage'] = $totalCommits > 0 ? round(($data['commits'] / $totalCommits) * 100, 1) : 0;
        $data['file_count'] = count($data['files']);
    }
    unset($data);

    // Urutkan author berdasarkan jumlah commit terbanyak
    uasort($authors, fn($a, $b) => $b['commits'] <=> $a['commits']);

    // Tampilkan keseluruhan commit jika limit <= 0
    $allCommits = ($commitLimit > 0) ? array_slice(array_values($commits), 0, $commitLimit) : array_values($commits);

    return [
        'folder' => basename($folder),
        'total_commits' => $totalCommits,
        'authors' => array_values($authors),
        'recent_commits' => $allCommits,
        'last_pr' => $lastPr
    ];
}

/**
 * Menghasilkan file Microsoft Excel dengan tab per kelompok yang memuat banner audit,
 * rekapitulasi kontribusi anggota, dan daftar seluruh commit (mirip tampilan di terminal).
 */
function generateExcelXml(array $results): string {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
    $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
    $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
    $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
    $xml .= ' <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">' . "\n";
    $xml .= '  <Author>LabKom TI Unila</Author>' . "\n";
    $xml .= '  <Title>Rekapitulasi Kontribusi Tubes Pemweb 2026</Title>' . "\n";
    $xml .= '  <Created>' . date('Y-m-d\TH:i:s\Z') . '</Created>' . "\n";
    $xml .= ' </DocumentProperties>' . "\n";
    $xml .= ' <Styles>' . "\n";
    $xml .= '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11"/></Style>' . "\n";
    
    // Banner Utama Kelompok
    $xml .= '  <Style ss:ID="TitleBanner">' . "\n";
    $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
    $xml .= '   <Font ss:FontName="Calibri" ss:Size="13" ss:Color="#FFFFFF" ss:Bold="1"/>' . "\n";
    $xml .= '   <Interior ss:Color="#1F4E78" ss:Pattern="Solid"/>' . "\n";
    $xml .= '  </Style>' . "\n";

    // Baris Info / Status Kelompok
    $xml .= '  <Style ss:ID="InfoBar">' . "\n";
    $xml .= '   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>' . "\n";
    $xml .= '   <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#1F4E78" ss:Bold="1"/>' . "\n";
    $xml .= '   <Interior ss:Color="#F2F5F9" ss:Pattern="Solid"/>' . "\n";
    $xml .= '   <Borders>' . "\n";
    $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>' . "\n";
    $xml .= '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>' . "\n";
    $xml .= '   </Borders>' . "\n";
    $xml .= '  </Style>' . "\n";

    // Section Header Biru (Rekap Kontribusi)
    $xml .= '  <Style ss:ID="SectionHeaderContrib">' . "\n";
    $xml .= '   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>' . "\n";
    $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>' . "\n";
    $xml .= '   <Interior ss:Color="#2F5597" ss:Pattern="Solid"/>' . "\n";
    $xml .= '  </Style>' . "\n";

    // Section Header Hijau (Daftar Commit)
    $xml .= '  <Style ss:ID="SectionHeaderCommits">' . "\n";
    $xml .= '   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>' . "\n";
    $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>' . "\n";
    $xml .= '   <Interior ss:Color="#385723" ss:Pattern="Solid"/>' . "\n";
    $xml .= '  </Style>' . "\n";

    // Header Kolom Tabel
    $xml .= '  <Style ss:ID="HeaderStyle">' . "\n";
    $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
    $xml .= '   <Borders>' . "\n";
    $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1F4E78"/>' . "\n";
    $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1F4E78"/>' . "\n";
    $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1F4E78"/>' . "\n";
    $xml .= '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1F4E78"/>' . "\n";
    $xml .= '   </Borders>' . "\n";
    $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>' . "\n";
    $xml .= '   <Interior ss:Color="#2F5597" ss:Pattern="Solid"/>' . "\n";
    $xml .= '  </Style>' . "\n";

    $xml .= '  <Style ss:ID="HeaderSubStyle">' . "\n";
    $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
    $xml .= '   <Borders>' . "\n";
    $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#276A3C"/>' . "\n";
    $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#276A3C"/>' . "\n";
    $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#276A3C"/>' . "\n";
    $xml .= '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#276A3C"/>' . "\n";
    $xml .= '   </Borders>' . "\n";
    $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>' . "\n";
    $xml .= '   <Interior ss:Color="#385723" ss:Pattern="Solid"/>' . "\n";
    $xml .= '  </Style>' . "\n";

    // Format Sel Data
    $xml .= '  <Style ss:ID="DataCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="DataCellWrap"><Alignment ss:Horizontal="Left" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="CenterCell"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="HashCell"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Consolas" ss:Size="10" ss:Bold="1" ss:Color="#1F4E78"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="NumberCell"><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders><NumberFormat ss:Format="#,##0"/></Style>' . "\n";
    $xml .= '  <Style ss:ID="NumberCenterCell"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders><NumberFormat ss:Format="#,##0"/></Style>' . "\n";
    $xml .= '  <Style ss:ID="PercentCell"><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders><NumberFormat ss:Format="0.0%"/></Style>' . "\n";
    $xml .= '  <Style ss:ID="AddedCell"><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#276A3C"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders><NumberFormat ss:Format="+#,##0"/></Style>' . "\n";
    $xml .= '  <Style ss:ID="DeletedCell"><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#C00000"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders><NumberFormat ss:Format="-#,##0"/></Style>' . "\n";

    // Format Baris Total Ringkasan
    $xml .= '  <Style ss:ID="TotalLabel"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/><Interior ss:Color="#F2F4F8" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#1F4E78"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1F4E78"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="TotalNumber"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/><Interior ss:Color="#F2F4F8" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#1F4E78"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1F4E78"/></Borders><NumberFormat ss:Format="#,##0"/></Style>' . "\n";
    $xml .= '  <Style ss:ID="TotalPercent"><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/><Interior ss:Color="#F2F4F8" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#1F4E78"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1F4E78"/></Borders><NumberFormat ss:Format="0.0%"/></Style>' . "\n";
    $xml .= '  <Style ss:ID="TotalAdded"><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#276A3C"/><Interior ss:Color="#F2F4F8" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#1F4E78"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1F4E78"/></Borders><NumberFormat ss:Format="+#,##0"/></Style>' . "\n";
    $xml .= '  <Style ss:ID="TotalDeleted"><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#C00000"/><Interior ss:Color="#F2F4F8" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#1F4E78"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1F4E78"/></Borders><NumberFormat ss:Format="-#,##0"/></Style>' . "\n";
    $xml .= ' </Styles>' . "\n";

    // SHEET 1 (OPSIONAL): RINGKASAN SEMUA KELOMPOK jika terdapat lebih dari 1 kelompok
    if (count($results) > 1) {
        $xml .= ' <Worksheet ss:Name="Ringkasan Semua Kelompok">' . "\n";
        $xml .= '  <Table>' . "\n";
        $xml .= '   <Column ss:Width="110"/>' . "\n"; // Kelompok
        $xml .= '   <Column ss:Width="85"/>' . "\n";  // PR Terakhir
        $xml .= '   <Column ss:Width="125"/>' . "\n"; // Waktu PR
        $xml .= '   <Column ss:Width="130"/>' . "\n"; // Pengirim PR
        $xml .= '   <Column ss:Width="125"/>' . "\n"; // Branch PR
        $xml .= '   <Column ss:Width="105"/>' . "\n"; // Jml Anggota
        $xml .= '   <Column ss:Width="95"/>' . "\n";  // Total Commits
        $xml .= '   <Column ss:Width="110"/>' . "\n"; // Total Baris (+)
        $xml .= '   <Column ss:Width="110"/>' . "\n"; // Total Baris (-)
        
        // Title Bar
        $xml .= '   <Row ss:Height="26">' . "\n";
        $xml .= '    <Cell ss:StyleID="TitleBanner" ss:MergeAcross="8"><Data ss:Type="String">RINGKASAN AUDIT KONTRIBUSI SELURUH KELOMPOK - PEMWEB 2026</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        // Info Bar
        $xml .= '   <Row ss:Height="20">' . "\n";
        $xml .= '    <Cell ss:StyleID="InfoBar" ss:MergeAcross="8"><Data ss:Type="String">ℹ️ Diperbarui: ' . date('d F Y, H:i') . ' WIB | Total Kelompok: ' . count($results) . ' | Setiap kelompok memiliki sheet tersendiri di bawah.</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";
        $xml .= '   <Row ss:Height="8"/>' . "\n";

        // Headers
        $xml .= '   <Row ss:Height="24">' . "\n";
        foreach (['Nama Kelompok', 'PR Terakhir', 'Waktu PR', 'Pengirim PR', 'Branch PR', 'Jumlah Anggota', 'Total Commits', 'Total Baris (+)', 'Total Baris (-)'] as $h) {
            $xml .= '    <Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">' . htmlspecialchars($h, ENT_XML1) . '</Data></Cell>' . "\n";
        }
        $xml .= '   </Row>' . "\n";

        $grandCommits = 0;
        $grandAdded = 0;
        $grandDeleted = 0;

        foreach ($results as $res) {
            $sheetName = ucwords(str_replace('_', ' ', $res['folder']));
            $prNum = $res['last_pr']['pr_number'] ?? '-';
            $prDate = $res['last_pr']['date'] ?? '-';
            $prOwner = $res['last_pr']['owner'] ?? '-';
            $prBranch = $res['last_pr']['branch'] ?? '-';
            $memberCount = count($res['authors']);

            $sumAdded = 0;
            $sumDeleted = 0;
            foreach ($res['authors'] as $a) {
                $sumAdded += $a['added'];
                $sumDeleted += $a['deleted'];
            }

            $grandCommits += $res['total_commits'];
            $grandAdded += $sumAdded;
            $grandDeleted += $sumDeleted;

            $xml .= '   <Row ss:Height="19">' . "\n";
            $xml .= '    <Cell ss:StyleID="CenterCell"><Data ss:Type="String">' . htmlspecialchars($sheetName, ENT_XML1) . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="CenterCell"><Data ss:Type="String">' . htmlspecialchars($prNum, ENT_XML1) . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="CenterCell"><Data ss:Type="String">' . htmlspecialchars($prDate, ENT_XML1) . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="CenterCell"><Data ss:Type="String">' . htmlspecialchars($prOwner, ENT_XML1) . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="CenterCell"><Data ss:Type="String">' . htmlspecialchars($prBranch, ENT_XML1) . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="NumberCenterCell"><Data ss:Type="Number">' . $memberCount . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="NumberCenterCell"><Data ss:Type="Number">' . $res['total_commits'] . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="AddedCell"><Data ss:Type="Number">' . $sumAdded . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DeletedCell"><Data ss:Type="Number">' . $sumDeleted . '</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";
        }

        // Baris Total Grand
        $xml .= '   <Row ss:Height="22">' . "\n";
        $xml .= '    <Cell ss:StyleID="TotalLabel" ss:MergeAcross="5"><Data ss:Type="String">TOTAL KESELURUHAN SELURUH KELOMPOK</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="TotalNumber"><Data ss:Type="Number">' . $grandCommits . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="TotalAdded"><Data ss:Type="Number">' . $grandAdded . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="TotalDeleted"><Data ss:Type="Number">' . $grandDeleted . '</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        $xml .= '  </Table>' . "\n";
        $xml .= ' </Worksheet>' . "\n";
    }

    // SHEET PER KELOMPOK (Sesuai nama kelompok, e.g. "Kelompok 01", "Kelompok 02")
    foreach ($results as $res) {
        $sheetTitle = ucwords(str_replace('_', ' ', $res['folder']));
        $prNum = $res['last_pr']['pr_number'] ?? '-';
        $prDate = $res['last_pr']['date'] ?? '-';
        $prOwner = $res['last_pr']['owner'] ?? '-';
        $prBranch = $res['last_pr']['branch'] ?? '-';
        $memberCount = count($res['authors']);

        $xml .= ' <Worksheet ss:Name="' . htmlspecialchars($sheetTitle, ENT_XML1) . '">' . "\n";
        $xml .= '  <Table>' . "\n";
        $xml .= '   <Column ss:Width="160"/>' . "\n"; // Col A: Nama / Commit Hash
        $xml .= '   <Column ss:Width="190"/>' . "\n"; // Col B: Email / Waktu Commit
        $xml .= '   <Column ss:Width="160"/>' . "\n"; // Col C: Commits / Author
        $xml .= '   <Column ss:Width="190"/>' . "\n"; // Col D: % Kontribusi / Email
        $xml .= '   <Column ss:Width="210"/>' . "\n"; // Col E: Baris Ditambah (+) / Pesan Commit part 1
        $xml .= '   <Column ss:Width="210"/>' . "\n"; // Col F: Baris Dihapus (-) / Pesan Commit part 2

        // 1. BANNER UTAMA KELOMPOK
        $xml .= '   <Row ss:Height="26">' . "\n";
        $xml .= '    <Cell ss:StyleID="TitleBanner" ss:MergeAcross="5"><Data ss:Type="String">' . htmlspecialchars(strtoupper($sheetTitle) . ' - AUDIT KONTRIBUSI REPOSITORI', ENT_XML1) . '</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        // 2. STATUS PULL REQUEST TERAKHIR KE MAIN
        $prInfoText = (!empty($res['last_pr']))
            ? "🚀 Pull Request Terakhir ke Main : PR " . $prNum . " dari " . $prOwner . " (" . $prDate . ") [Branch: " . $prBranch . "]"
            : "🚀 Pull Request Terakhir ke Main : (Belum ada riwayat Pull Request yang di-merge)";
        $xml .= '   <Row ss:Height="20">' . "\n";
        $xml .= '    <Cell ss:StyleID="InfoBar" ss:MergeAcross="5"><Data ss:Type="String">' . htmlspecialchars($prInfoText, ENT_XML1) . '</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        // 3. STATISTIK COMMIT & ANGGOTA
        $xml .= '   <Row ss:Height="20">' . "\n";
        $statText = "📁 Total Non-Merge Commits: " . $res['total_commits'] . " commit  |  Jumlah Kontributor: " . $memberCount . " orang";
        $xml .= '    <Cell ss:StyleID="InfoBar" ss:MergeAcross="5"><Data ss:Type="String">' . htmlspecialchars($statText, ENT_XML1) . '</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        // Spacer Row
        $xml .= '   <Row ss:Height="8"/>' . "\n";

        // 4. BAGIAN 1: REKAPITULASI KONTRIBUSI ANGGOTA
        $xml .= '   <Row ss:Height="22">' . "\n";
        $xml .= '    <Cell ss:StyleID="SectionHeaderContrib" ss:MergeAcross="5"><Data ss:Type="String">📊 REKAPITULASI KONTRIBUSI ANGGOTA</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        $xml .= '   <Row ss:Height="24">' . "\n";
        foreach (['Nama Kontributor', 'Email Kontributor', 'Jumlah Commit', '% Kontribusi', 'Baris Ditambah (+)', 'Baris Dihapus (-)'] as $h) {
            $xml .= '    <Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">' . htmlspecialchars($h, ENT_XML1) . '</Data></Cell>' . "\n";
        }
        $xml .= '   </Row>' . "\n";

        if (empty($res['authors'])) {
            $xml .= '   <Row ss:Height="20">' . "\n";
            $xml .= '    <Cell ss:StyleID="DataCell" ss:MergeAcross="5"><Data ss:Type="String">⚠️ Belum ada commit yang tercatat di folder ini.</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";
        } else {
            $totCommits = 0;
            $totAdd = 0;
            $totDel = 0;

            foreach ($res['authors'] as $a) {
                $pctVal = round($a['percentage'] / 100, 4);
                $totCommits += $a['commits'];
                $totAdd += $a['added'];
                $totDel += $a['deleted'];

                $xml .= '   <Row ss:Height="19">' . "\n";
                $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($a['name'], ENT_XML1) . '</Data></Cell>' . "\n";
                $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($a['email'] ?: '-', ENT_XML1) . '</Data></Cell>' . "\n";
                $xml .= '    <Cell ss:StyleID="NumberCenterCell"><Data ss:Type="Number">' . (int)$a['commits'] . '</Data></Cell>' . "\n";
                $xml .= '    <Cell ss:StyleID="PercentCell"><Data ss:Type="Number">' . $pctVal . '</Data></Cell>' . "\n";
                $xml .= '    <Cell ss:StyleID="AddedCell"><Data ss:Type="Number">' . (int)$a['added'] . '</Data></Cell>' . "\n";
                $xml .= '    <Cell ss:StyleID="DeletedCell"><Data ss:Type="Number">' . (int)$a['deleted'] . '</Data></Cell>' . "\n";
                $xml .= '   </Row>' . "\n";
            }

            // Baris Total Anggota
            $xml .= '   <Row ss:Height="22">' . "\n";
            $xml .= '    <Cell ss:StyleID="TotalLabel" ss:MergeAcross="1"><Data ss:Type="String">TOTAL KONTRIBUSI</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="TotalNumber"><Data ss:Type="Number">' . $totCommits . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="TotalPercent"><Data ss:Type="Number">1.0</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="TotalAdded"><Data ss:Type="Number">' . $totAdd . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="TotalDeleted"><Data ss:Type="Number">' . $totDel . '</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";
        }

        // Spacer Row
        $xml .= '   <Row ss:Height="12"/>' . "\n";

        // 5. BAGIAN 2: DAFTAR SELURUH COMMIT
        $commitCount = count($res['recent_commits']);
        $xml .= '   <Row ss:Height="22">' . "\n";
        $xml .= '    <Cell ss:StyleID="SectionHeaderCommits" ss:MergeAcross="5"><Data ss:Type="String">🕒 DAFTAR SELURUH COMMIT (' . $commitCount . ' COMMIT)</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        $xml .= '   <Row ss:Height="24">' . "\n";
        $xml .= '    <Cell ss:StyleID="HeaderSubStyle"><Data ss:Type="String">Commit Hash</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="HeaderSubStyle"><Data ss:Type="String">Waktu Commit</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="HeaderSubStyle"><Data ss:Type="String">Author</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="HeaderSubStyle"><Data ss:Type="String">Email</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="HeaderSubStyle" ss:MergeAcross="1"><Data ss:Type="String">Pesan Commit</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        if (empty($res['recent_commits'])) {
            $xml .= '   <Row ss:Height="20">' . "\n";
            $xml .= '    <Cell ss:StyleID="DataCell" ss:MergeAcross="5"><Data ss:Type="String">⚠️ Belum ada commit yang tercatat.</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";
        } else {
            foreach ($res['recent_commits'] as $c) {
                $xml .= '   <Row ss:Height="19">' . "\n";
                $xml .= '    <Cell ss:StyleID="HashCell"><Data ss:Type="String">' . htmlspecialchars($c['hash'], ENT_XML1) . '</Data></Cell>' . "\n";
                $xml .= '    <Cell ss:StyleID="CenterCell"><Data ss:Type="String">' . htmlspecialchars($c['date'], ENT_XML1) . '</Data></Cell>' . "\n";
                $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($c['author'], ENT_XML1) . '</Data></Cell>' . "\n";
                $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($c['email'] ?: '-', ENT_XML1) . '</Data></Cell>' . "\n";
                $xml .= '    <Cell ss:StyleID="DataCellWrap" ss:MergeAcross="1"><Data ss:Type="String">' . htmlspecialchars($c['message'], ENT_XML1) . '</Data></Cell>' . "\n";
                $xml .= '   </Row>' . "\n";
            }
        }

        $xml .= '  </Table>' . "\n";
        $xml .= ' </Worksheet>' . "\n";
    }

    $xml .= '</Workbook>' . "\n";
    return $xml;
}

// Ambil data PR terakhir untuk semua kelompok
$lastPrMap = getLastMergedPrMap();

// Tentukan kelompok yang akan diaudit
$targets = [];
if (isset($options['all'])) {
    $targets = getAvailableKelompok();
} else {
    $input = trim($options['kelompok']);
    $folderName = str_starts_with($input, 'kelompok_') ? $input : 'kelompok_' . str_pad($input, 2, '0', STR_PAD_LEFT);
    $path = 'kelompok/' . $folderName;
    if (!is_dir($path)) {
        die("❌ Error: Folder '$path' tidak ditemukan.\n");
    }
    $targets[] = [
        'folder' => $folderName,
        'path' => $path,
        'id' => preg_replace('/^kelompok_/', '', $folderName)
    ];
}

$results = [];
echo "\n🔍 Menjalankan audit riwayat Git per folder kelompok...\n";

foreach ($targets as $target) {
    $folderKey = strtolower($target['folder']);
    $prInfo = $lastPrMap[$folderKey] ?? null;
    $audit = auditKelompok($target['path'], $commitLimit, $prInfo);
    if ($audit) {
        $results[] = $audit;
    }
}

// Render output ke CLI
foreach ($results as $res) {
    echo "\n" . str_repeat('=', 75) . "\n";
    echo "📁 KELOMPOK: " . strtoupper($res['folder']) . " (Total Non-Merge Commits: " . $res['total_commits'] . ")\n";
    
    // Tampilkan informasi PR terakhir yang berhasil masuk ke main
    if (!empty($res['last_pr'])) {
        $pr = $res['last_pr'];
        echo "🚀 PR Terakhir ke Main : PR " . $pr['pr_number'] . " dari " . $pr['owner'] . " (" . $pr['date'] . ") [Branch: " . $pr['branch'] . "]\n";
    } else {
        echo "🚀 PR Terakhir ke Main : (Belum ada riwayat Pull Request yang di-merge)\n";
    }
    echo str_repeat('=', 75) . "\n";

    if ($res['total_commits'] === 0) {
        echo "⚠️ Belum ada commit yang tercatat di folder ini.\n";
        continue;
    }

    // Hitung lebar kolom nama secara dinamis agar nama lengkap tidak terpotong
    $maxNameLen = 16;
    foreach ($res['authors'] as $a) {
        $maxNameLen = max($maxNameLen, mb_strlen($a['name']));
    }
    $colWidth = min(max($maxNameLen + 2, 25), 40);

    $headerFormat = "%-" . $colWidth . "s | %-7s | %-6s | %-12s | %-12s\n";
    $tableWidth = $colWidth + 47;

    printf($headerFormat, "Nama Kontributor", "Commits", "%", "Baris (+)", "Baris (-)");
    echo str_repeat('-', $tableWidth) . "\n";
    foreach ($res['authors'] as $a) {
        $displayName = mb_strimwidth($a['name'], 0, $colWidth - 1, '..');
        printf("%-" . $colWidth . "s | %-7d | %5.1f%% | %-12s | %-12s\n", 
            $displayName, 
            $a['commits'], 
            $a['percentage'], 
            "+" . number_format($a['added']), 
            "-" . number_format($a['deleted'])
        );
    }
    echo str_repeat('-', $tableWidth) . "\n";

    echo "🕒 Daftar Seluruh Commit (" . count($res['recent_commits']) . " commit):\n";
    foreach ($res['recent_commits'] as $c) {
        // Tampilkan pesan commit secara utuh tanpa pemotongan
        echo "  • [" . $c['hash'] . "] " . $c['date'] . " - " . $c['author'] . ": " . $c['message'] . "\n";
    }
}

// Export jika parameter --export diisi
if ($exportFile) {
    $ext = strtolower(pathinfo($exportFile, PATHINFO_EXTENSION));
    
    if ($ext === 'xls' || $ext === 'xlsx') {
        // Ekspor ke format Microsoft Excel dengan tampilan tabel rapi & multiple sheets
        $xmlContent = generateExcelXml($results);
        file_put_contents($exportFile, $xmlContent);
        echo "\n⭐ Hasil audit berhasil diekspor ke file Microsoft Excel: $exportFile\n";
        echo "   (File memuat sheet khusus per kelompok beserta log audit dan rekap kontribusi)\n";
    } elseif ($ext === 'csv') {
        $fp = fopen($exportFile, 'w');
        // Tambahkan UTF-8 BOM agar aksen dan karakter khusus terbaca sempurna di Excel
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($fp, [
            'Kelompok',
            'PR Terakhir (No)',
            'Waktu PR Terakhir',
            'Pengirim PR (Fork Owner)',
            'Branch PR',
            'Nama Kontributor',
            'Email',
            'Jumlah Commit',
            'Persentase Commit',
            'Baris Ditambah (+)',
            'Baris Dihapus (-)'
        ]);
        foreach ($results as $res) {
            $prNum = $res['last_pr']['pr_number'] ?? '-';
            $prDate = $res['last_pr']['date'] ?? '-';
            $prOwner = $res['last_pr']['owner'] ?? '-';
            $prBranch = $res['last_pr']['branch'] ?? '-';

            if (empty($res['authors'])) {
                fputcsv($fp, [
                    $res['folder'],
                    $prNum,
                    $prDate,
                    $prOwner,
                    $prBranch,
                    '-',
                    '-',
                    0,
                    '0%',
                    0,
                    0
                ]);
            } else {
                foreach ($res['authors'] as $a) {
                    fputcsv($fp, [
                        $res['folder'],
                        $prNum,
                        $prDate,
                        $prOwner,
                        $prBranch,
                        $a['name'],
                        $a['email'],
                        $a['commits'],
                        $a['percentage'] . '%',
                        $a['added'],
                        $a['deleted']
                    ]);
                }
            }
        }
        fclose($fp);
        echo "\n✅ Hasil audit berhasil diekspor ke format CSV: $exportFile\n";
    } else {
        // Default Markdown
        $md = "# 📊 Rekapitulasi Audit Kontribusi Tugas Besar Pemweb 2026\n\n";
        $md .= "Tanggal Generate: " . date('Y-m-d H:i:s') . "\n\n";
        $md .= "---\n\n";

        foreach ($results as $res) {
            $md .= "## 📁 " . strtoupper($res['folder']) . "\n";
            $md .= "- **Total Non-Merge Commits:** " . $res['total_commits'] . "\n";
            
            if (!empty($res['last_pr'])) {
                $pr = $res['last_pr'];
                $md .= "- **Pull Request Terakhir ke Main:** `" . $pr['pr_number'] . "` dari **" . $pr['owner'] . "** pada `" . $pr['date'] . "` (Branch: `" . $pr['branch'] . "`, Commit: `" . $pr['hash'] . "`)\n\n";
            } else {
                $md .= "- **Pull Request Terakhir ke Main:** _Belum ada PR yang ter-merge_\n\n";
            }

            if ($res['total_commits'] === 0) {
                $md .= "_Belum ada commit yang tercatat di folder ini._\n\n";
                continue;
            }

            $md .= "| Nama Kontributor | Email | Jumlah Commit | % Kontribusi | Baris (+) | Baris (-) |\n";
            $md .= "| :--- | :--- | :---: | :---: | :---: | :---: |\n";
            foreach ($res['authors'] as $a) {
                $md .= sprintf(
                    "| %s | %s | %d | %.1f%% | +%s | -%s |\n",
                    $a['name'],
                    $a['email'] ? '`' . $a['email'] . '`' : '-',
                    $a['commits'],
                    $a['percentage'],
                    number_format($a['added']),
                    number_format($a['deleted'])
                );
            }
            $md .= "\n<details>\n<summary>🔍 <b>Klik untuk melihat rincian seluruh commit (" . count($res['recent_commits']) . " commit)</b></summary>\n\n";
            foreach ($res['recent_commits'] as $c) {
                $md .= "- `" . $c['hash'] . "` (" . $c['date'] . ") **" . htmlspecialchars($c['author']) . "**: " . htmlspecialchars($c['message']) . "\n";
            }
            $md .= "</details>\n\n---\n\n";
        }

        file_put_contents($exportFile, $md);
        echo "\n✅ Hasil audit berhasil diekspor ke file Markdown: $exportFile\n";
    }
}

echo "\n✨ Audit selesai.\n";
