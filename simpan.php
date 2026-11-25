<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$nimPlain       = trim($_POST['nim'] ?? '');
$namaPlain      = trim($_POST['nama'] ?? '');
$kategoriPlain  = trim($_POST['kategori'] ?? '');
$pengaduanPlain = trim($_POST['isi_pengaduan'] ?? '');
$statusPlain    = 'Menunggu Ditinjau';
$waktuPlain     = date('Y-m-d H:i:s');

if ($nimPlain === '' || $namaPlain === '' || $kategoriPlain === '' || $pengaduanPlain === '') {
    die("Semua kolom wajib diisi. <a href='index.php'>Kembali</a>");
}

// Enkripsi seluruh field sebelum dikirim ke MySQL
$nimCipher       = enkripsiData($nimPlain);
$namaCipher      = enkripsiData($namaPlain);
$kategoriCipher  = enkripsiData($kategoriPlain);
$pengaduanCipher = enkripsiData($pengaduanPlain);
$statusCipher    = enkripsiData($statusPlain);
$waktuCipher     = enkripsiData($waktuPlain);

$stmt = $conn->prepare("INSERT INTO pengaduan (nim, nama, kategori, isi_pengaduan, status_laporan, created_at) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $nimCipher, $namaCipher, $kategoriCipher, $pengaduanCipher, $statusCipher, $waktuCipher);
$berhasil = $stmt->execute();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Penyimpanan - SI-PEKA</title>
    <?php tampilkanStyle(); ?>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="brand">SI-PEKA <span>Konseling & Pengaduan Kampus</span></div>
            <div class="nav-links">
                <a href="index.php">Buat Pengaduan</a>
                <a href="baca.php">Panel Konselor</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <?php if ($berhasil): ?>
                <div class="alert alert-success">
                    <strong>Berhasil!</strong> Seluruh kolom data (Waktu, NIM, Nama, Kategori, Isi, dan Status) telah dienkripsi menggunakan AES-256-CBC dan disimpan ke database MySQL.
                </div>

                <div class="compare-grid">
                    <div class="compare-box">
                        <h3>Data Asli (Dikirim via HTTP POST)</h3>
                        <p><strong>Waktu:</strong> <?= htmlspecialchars($waktuPlain, ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>NIM:</strong> <?= htmlspecialchars($nimPlain, ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Nama:</strong> <?= htmlspecialchars($namaPlain, ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Kategori:</strong> <?= htmlspecialchars($kategoriPlain, ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Isi:</strong> <?= nl2br(htmlspecialchars($pengaduanPlain, ENT_QUOTES, 'UTF-8')) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($statusPlain, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="compare-box">
                        <h3>Ciphertext (Disimpan di MySQL)</h3>
                        <p><strong>Waktu:</strong><br><span class="cipher-text"><?= htmlspecialchars($waktuCipher, ENT_QUOTES, 'UTF-8') ?></span></p>
                        <p><strong>NIM:</strong><br><span class="cipher-text"><?= htmlspecialchars($nimCipher, ENT_QUOTES, 'UTF-8') ?></span></p>
                        <p><strong>Nama:</strong><br><span class="cipher-text"><?= htmlspecialchars($namaCipher, ENT_QUOTES, 'UTF-8') ?></span></p>
                        <p><strong>Kategori:</strong><br><span class="cipher-text"><?= htmlspecialchars($kategoriCipher, ENT_QUOTES, 'UTF-8') ?></span></p>
                        <p><strong>Isi:</strong><br><span class="cipher-text"><?= htmlspecialchars($pengaduanCipher, ENT_QUOTES, 'UTF-8') ?></span></p>
                        <p><strong>Status:</strong><br><span class="cipher-text"><?= htmlspecialchars($statusCipher, ENT_QUOTES, 'UTF-8') ?></span></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    Gagal menyimpan data ke database.
                </div>
            <?php endif; ?>

            <a href="index.php" class="btn btn-secondary">Input Laporan Baru</a>
            <a href="baca.php" class="btn btn-primary">Buka Panel Konselor</a>
        </div>
    </div>
</body>
</html>