<?php
date_default_timezone_set('Asia/Jakarta');

// Koneksi via 127.0.0.1 (bukan localhost) agar trafik port 3306 terbaca di Wireshark Loopback
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "db_konseling";
$port = 3306;

$conn = new mysqli($host, $user, $pass, "", $port);
if ($conn->connect_error) {
    die("Koneksi MySQL gagal: " . $conn->connect_error);
}

// Buat database & tabel otomatis (semua kolom data bertipe TEXT agar muat menyimpan ciphertext)
$conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($db);

$conn->query("
    CREATE TABLE IF NOT EXISTS pengaduan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nim TEXT NOT NULL,
        nama TEXT NOT NULL,
        kategori TEXT NOT NULL,
        isi_pengaduan TEXT NOT NULL,
        status_laporan TEXT NOT NULL,
        created_at TEXT NOT NULL
    )
");

// Konfigurasi Enkripsi AES-256-CBC
define('APP_SECRET_KEY', 'KunciRahasiaKampus2026!');
define('CIPHER_METHOD', 'AES-256-CBC');

function enkripsiData(string $plaintext, string $key = APP_SECRET_KEY): string
{
    $keyHash   = hash('sha256', $key, true);
    $ivLength  = openssl_cipher_iv_length(CIPHER_METHOD);
    $iv        = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt($plaintext, CIPHER_METHOD, $keyHash, OPENSSL_RAW_DATA, $iv);

    return base64_encode($iv . $encrypted);
}

function dekripsiData(string $ciphertext, string $key = APP_SECRET_KEY)
{
    $keyHash  = hash('sha256', $key, true);
    $data     = base64_decode($ciphertext, true);
    $ivLength = openssl_cipher_iv_length(CIPHER_METHOD);

    if ($data === false || strlen($data) <= $ivLength) {
        return false;
    }

    $iv        = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);

    return openssl_decrypt($encrypted, CIPHER_METHOD, $keyHash, OPENSSL_RAW_DATA, $iv);
}

// Migrasi otomatis 1: pastikan kategori & created_at bertipe TEXT dan terenkripsi
$colCheck = $conn->query("SHOW COLUMNS FROM pengaduan LIKE 'created_at'");
if ($colCheck && ($col = $colCheck->fetch_assoc())) {
    if (stripos($col['Type'], 'text') === false) {
        $conn->query("ALTER TABLE pengaduan MODIFY kategori TEXT NOT NULL, MODIFY created_at TEXT NOT NULL");

        $cekLama = $conn->query("SELECT id, kategori, created_at FROM pengaduan");
        if ($cekLama) {
            $upd = $conn->prepare("UPDATE pengaduan SET kategori = ?, created_at = ? WHERE id = ?");
            while ($r = $cekLama->fetch_assoc()) {
                $katBaru = (dekripsiData($r['kategori']) === false) ? enkripsiData($r['kategori']) : $r['kategori'];
                $wktBaru = (dekripsiData($r['created_at']) === false) ? enkripsiData($r['created_at']) : $r['created_at'];
                if ($katBaru !== $r['kategori'] || $wktBaru !== $r['created_at']) {
                    $upd->bind_param("ssi", $katBaru, $wktBaru, $r['id']);
                    $upd->execute();
                }
            }
            $upd->close();
        }
    }
}

// Migrasi otomatis 2: tambahkan kolom status_laporan & enkripsi nilai status untuk data lama
$statusCol = $conn->query("SHOW COLUMNS FROM pengaduan LIKE 'status_laporan'");
if ($statusCol && $statusCol->num_rows === 0) {
    $conn->query("ALTER TABLE pengaduan ADD COLUMN status_laporan TEXT NOT NULL AFTER isi_pengaduan");
}

$cekStatusKosong = $conn->query("SELECT id, status_laporan FROM pengaduan");
if ($cekStatusKosong) {
    $updStatus = $conn->prepare("UPDATE pengaduan SET status_laporan = ? WHERE id = ?");
    while ($r = $cekStatusKosong->fetch_assoc()) {
        if ($r['status_laporan'] === '' || dekripsiData($r['status_laporan']) === false) {
            $statusPlain = ($r['status_laporan'] !== '') ? $r['status_laporan'] : 'Menunggu Ditinjau';
            $statusCipher = enkripsiData($statusPlain);
            $updStatus->bind_param("si", $statusCipher, $r['id']);
            $updStatus->execute();
        }
    }
    $updStatus->close();
}

// Style internal
function tampilkanStyle(): void
{
    echo <<<CSS
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: "Segoe UI", system-ui, sans-serif; }
        body { background-color: #f1f5f9; color: #0f172a; line-height: 1.6; }
        .navbar { background-color: #0f172a; color: #ffffff; padding: 16px 24px; border-bottom: 3px solid #2563eb; }
        .nav-container { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .brand { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.3px; }
        .brand span { color: #60a5fa; }
        .nav-links { display: flex; gap: 12px; }
        .nav-links a { color: #cbd5e1; text-decoration: none; font-size: 0.92rem; padding: 6px 12px; border-radius: 6px; transition: 0.2s; }
        .nav-links a:hover, .nav-links a.active { background-color: #1e293b; color: #ffffff; }
        .container { max-width: 1100px; margin: 32px auto; padding: 0 20px; }
        .card { background-color: #ffffff; border-radius: 10px; padding: 28px; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06); border: 1px solid #e2e8f0; margin-bottom: 24px; }
        .card-header { margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0; }
        .card-header h1 { font-size: 1.45rem; color: #0f172a; margin-bottom: 4px; }
        .card-header p { font-size: 0.92rem; color: #475569; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 0.9rem; font-weight: 600; color: #1e293b; margin-bottom: 6px; }
        input[type="text"], input[type="password"], select, textarea { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; color: #0f172a; background-color: #f8fafc; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #2563eb; background-color: #ffffff; }
        textarea { min-height: 120px; resize: vertical; }
        .btn { display: inline-block; padding: 10px 20px; border: none; border-radius: 8px; font-size: 0.92rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: 0.2s; }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-primary:hover { background-color: #1d4ed8; }
        .btn-secondary { background-color: #e2e8f0; color: #1e293b; }
        .btn-secondary:hover { background-color: #cbd5e1; }
        .alert { padding: 14px 16px; border-radius: 8px; font-size: 0.9rem; margin-bottom: 20px; }
        .alert-info { background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .alert-success { background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-danger { background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .key-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; padding: 16px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
        .key-bar input { flex: 1; min-width: 220px; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background-color: #f8fafc; color: #334155; font-weight: 700; white-space: nowrap; }
        .cipher-text { font-family: Consolas, "Courier New", monospace; font-size: 0.78rem; color: #b91c1c; word-break: break-all; background-color: #fef2f2; padding: 4px 6px; border-radius: 4px; display: inline-block; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 600; }
        .badge-decrypted { background-color: #dcfce7; color: #166534; }
        .badge-category { background-color: #e0e7ff; color: #3730a3; }
        .compare-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 18px 0; }
        .compare-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; }
        .compare-box h3 { font-size: 0.95rem; margin-bottom: 10px; color: #1e293b; }
        @media (max-width: 700px) { .compare-grid { grid-template-columns: 1fr; } }
    </style>
CSS;
}