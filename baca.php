<?php
require_once 'config.php';

$inputKey       = '';
$statusDekripsi = 'terkunci'; // terkunci | berhasil | salah

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset'])) {
        $inputKey       = '';
        $statusDekripsi = 'terkunci';
    } else {
        $inputKey       = $_POST['secret_key'] ?? '';
        $statusDekripsi = ($inputKey === APP_SECRET_KEY) ? 'berhasil' : 'salah';
    }
}

$result = $conn->query("SELECT * FROM pengaduan ORDER BY id DESC");

function pastikanCiphertext(string $nilai): string
{
    return (dekripsiData($nilai) === false) ? enkripsiData($nilai) : $nilai;
}

// Enkripsi juga seluruh judul kolom tabel
$headersPlain = [
    'No',
    'Waktu',
    'NIM',
    'Nama Mahasiswa',
    'Kategori',
    'Isi Pengaduan',
    'Status'
];

$headersTampil = [];
foreach ($headersPlain as $h) {
    $hCipher = enkripsiData($h);
    $headersTampil[] = ($statusDekripsi === 'berhasil')
        ? dekripsiData($hCipher, $inputKey)
        : $hCipher;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Konselor - SI-PEKA</title>
    <?php tampilkanStyle(); ?>
    <style>
        th { white-space: normal; }
        .cipher-text { font-size: 0.72rem; line-height: 1.4; }
        th .cipher-text { font-weight: 700; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="brand">SI-PEKA <span>Konseling & Pengaduan Kampus</span></div>
            <div class="nav-links">
                <a href="index.php">Buat Pengaduan</a>
                <a href="baca.php" class="active">Panel Konselor</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Panel Konselor & Dekripsi Laporan</h1>
                <p>Seluruh tabel (judul kolom beserta seluruh isi datanya) ditampilkan dalam bentuk ciphertext acak. Masukkan kata sandi untuk mendekripsi semua bagian tabel.</p>
            </div>

            <form method="POST" class="key-bar">
                <input type="password" name="secret_key" placeholder="Masukkan kata sandi dekripsi (Default: KunciRahasiaKampus2026!)" value="<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary">Dekripsi Data</button>
                <button type="submit" name="reset" value="1" class="btn btn-secondary">Tampilkan Ciphertext</button>
            </form>

            <?php if ($statusDekripsi === 'berhasil'): ?>
                <div class="alert alert-success">
                    <strong>Dekripsi Berhasil:</strong> Kata sandi valid. Seluruh judul kolom dan data tabel berhasil didekripsi menggunakan AES-256-CBC.
                </div>
            <?php elseif ($statusDekripsi === 'salah'): ?>
                <div class="alert alert-danger">
                    <strong>Kata Sandi Salah:</strong> Gagal mendekripsi data. Seluruh bagian tabel tetap ditampilkan dalam bentuk ciphertext.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <strong>Mode Terenkripsi:</strong> Seluruh judul kolom dan isi tabel di bawah ini tampil sebagai ciphertext acak hingga kata sandi yang benar dimasukkan.
                </div>
            <?php endif; ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($headersTampil as $hText): ?>
                                <th>
                                    <?php if ($statusDekripsi === 'berhasil'): ?>
                                        <?= htmlspecialchars($hText, ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="cipher-text"><?= htmlspecialchars($hText, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    $noPlain   = (string) $no++;
                                    $noCipher  = enkripsiData($noPlain);
                                    $waktuRaw  = pastikanCiphertext((string) ($row['created_at'] ?? ''));
                                    $nimRaw    = pastikanCiphertext((string) ($row['nim'] ?? ''));
                                    $namaRaw   = pastikanCiphertext((string) ($row['nama'] ?? ''));
                                    $katRaw    = pastikanCiphertext((string) ($row['kategori'] ?? ''));
                                    $isiRaw    = pastikanCiphertext((string) ($row['isi_pengaduan'] ?? ''));
                                    $statusRaw = !empty($row['status_laporan'])
                                        ? pastikanCiphertext((string) $row['status_laporan'])
                                        : enkripsiData('Menunggu Ditinjau');

                                    $isDecrypted  = false;
                                    $noTampil     = $noCipher;
                                    $waktuTampil  = $waktuRaw;
                                    $nimTampil    = $nimRaw;
                                    $namaTampil   = $namaRaw;
                                    $katTampil    = $katRaw;
                                    $isiTampil    = $isiRaw;
                                    $statusTampil = $statusRaw;

                                    if ($statusDekripsi === 'berhasil') {
                                        $dWaktu  = dekripsiData($waktuRaw, $inputKey);
                                        $dNim    = dekripsiData($nimRaw, $inputKey);
                                        $dNama   = dekripsiData($namaRaw, $inputKey);
                                        $dKat    = dekripsiData($katRaw, $inputKey);
                                        $dIsi    = dekripsiData($isiRaw, $inputKey);
                                        $dStatus = dekripsiData($statusRaw, $inputKey);

                                        if (
                                            $dWaktu !== false &&
                                            $dNim !== false &&
                                            $dNama !== false &&
                                            $dKat !== false &&
                                            $dIsi !== false &&
                                            $dStatus !== false
                                        ) {
                                            $noTampil     = $noPlain;
                                            $waktuTampil  = $dWaktu;
                                            $nimTampil    = $dNim;
                                            $namaTampil   = $dNama;
                                            $katTampil    = $dKat;
                                            $isiTampil    = $dIsi;
                                            $statusTampil = $dStatus;
                                            $isDecrypted  = true;
                                        }
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($isDecrypted): ?>
                                            <?= htmlspecialchars($noTampil, ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            <span class="cipher-text"><?= htmlspecialchars($noTampil, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isDecrypted): ?>
                                            <?= htmlspecialchars($waktuTampil, ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            <span class="cipher-text"><?= htmlspecialchars($waktuTampil, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isDecrypted): ?>
                                            <?= htmlspecialchars($nimTampil, ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            <span class="cipher-text"><?= htmlspecialchars($nimTampil, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isDecrypted): ?>
                                            <?= htmlspecialchars($namaTampil, ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            <span class="cipher-text"><?= htmlspecialchars($namaTampil, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isDecrypted): ?>
                                            <span class="badge badge-category"><?= htmlspecialchars($katTampil, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <span class="cipher-text"><?= htmlspecialchars($katTampil, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isDecrypted): ?>
                                            <?= nl2br(htmlspecialchars($isiTampil, ENT_QUOTES, 'UTF-8')) ?>
                                        <?php else: ?>
                                            <span class="cipher-text"><?= htmlspecialchars($isiTampil, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isDecrypted): ?>
                                            <span class="badge badge-decrypted"><?= htmlspecialchars($statusTampil, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <span class="cipher-text"><?= htmlspecialchars($statusTampil, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; color:#64748b;">Belum ada data pengaduan tersimpan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>