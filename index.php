<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Konseling & Pengaduan Akademik Kampus</title>
    <?php tampilkanStyle(); ?>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="brand">SI-PEKA <span>Konseling & Pengaduan Kampus</span></div>
            <div class="nav-links">
                <a href="index.php" class="active">Buat Pengaduan</a>
                <a href="baca.php">Panel Konselor</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Formulir Konseling & Pengaduan Akademik</h1>
                <p>Data sensitif mahasiswa (NIM, Nama, dan Detail Laporan) dienkripsi menggunakan <strong>AES-256-CBC</strong> sebelum disimpan ke database.</p>
            </div>

            <form action="simpan.php" method="POST">
                <div class="form-group">
                    <label for="nim">NIM Mahasiswa</label>
                    <input type="text" id="nim" name="nim" placeholder="Contoh: 2430511045" required>
                </div>

                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap Anda" required>
                </div>

                <div class="form-group">
                    <label for="kategori">Kategori Layanan</label>
                    <select id="kategori" name="kategori" required>
                        <option value="Konseling Akademik">Konseling Akademik</option>
                        <option value="Konseling Psikologi">Konseling Psikologi</option>
                        <option value="Pengaduan Perkuliahan">Pengaduan Perkuliahan</option>
                        <option value="Administrasi & Beasiswa">Administrasi & Beasiswa</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="isi_pengaduan">Detail Konseling / Pengaduan</label>
                    <textarea id="isi_pengaduan" name="isi_pengaduan" placeholder="Tuliskan keluhan, kendala akademik, atau topik konseling Anda secara jelas..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Kirim & Enkripsi Laporan</button>
            </form>
        </div>

        <div class="alert alert-info">
            <strong>Catatan Pengujian Jaringan:</strong> Pengiriman formulir menggunakan HTTP lokal (port 80) ke PHP, lalu PHP mengenkripsi data sebelum mengirim query INSERT ke MySQL (port 3306).
        </div>
    </div>
</body>
</html>