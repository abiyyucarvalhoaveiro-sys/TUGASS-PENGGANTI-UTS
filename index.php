<?php
// Pastikan session aktif untuk pengamanan aplikasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hubungkan ke database menggunakan class Database Anda
require_once '../config/Database.php';
$pdo = Database::connect();

$errors = [];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$successMessage = "";

// ==========================================
// PROSES LOGIKA INPUT DATA & UPLOAD FOTO
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_dosen') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { exit('CSRF token tidak valid'); }
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $nidn   = trim($_POST['nidn'] ?? '');
    $nama   = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Validasi input teks dasar
    if (empty($nidn)) $errors[] = "NIDN wajib diisi!";
    if (empty($nama)) $errors[] = "Nama wajib diisi!";
    if (empty($email)) $errors[] = "Email wajib diisi!";

    $newFileName = null; // Default null jika foto tidak diunggah

    // Memproses file gambar jika dipilih oleh pengguna
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['foto']['tmp_name'];
        $fileOrigName  = $_FILES['foto']['name'];
        $fileSize      = $_FILES['foto']['size'];
        
        // 1. Batasi ukuran gambar (Maksimal 2MB sesuai regulasi UTS)
        $maxSize = 2 * 1024 * 1024; 
        if ($fileSize > $maxSize) {
            $errors[] = 'Ukuran berkas foto terlalu besar! Maksimal diperbolehkan 2MB.';
        }
        
        // 2. Validasi biner MIME asli menggunakan finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($fileTmpPath);
        
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            $errors[] = 'Format file salah! Hanya berkas asli berformat JPG, PNG, atau WebP yang diterima.';
        }
        
        // 3. Proses penamaan berkas dengan Hash SHA256 & memindahkannya ke server
        if (empty($errors)) {
            $fileExtension = pathinfo($fileOrigName, PATHINFO_EXTENSION);
            
            // Generate nama acak biner terenkripsi SHA256 agar nama aman dan tidak bentrok
            $randomString = bin2hex(random_bytes(16));
            $newFileName = hash('sha256', $randomString . time()) . '.' . $fileExtension;
            
            // Mengarah ke folder uploads (berada 1 tingkat di luar folder public)
            $uploadFileDir = __DIR__ . '/../uploads/';
            $dest_path = $uploadFileDir . $newFileName;
            
            // Buat folder uploads otomatis jika belum ada di server
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                $errors[] = 'Gagal menyimpan foto ke folder uploads server.';
            }
        }
    } else {
        $errors[] = "Silakan pilih file foto terlebih dahulu!";
    }

    // 4. Masukkan data ke tabel database jika tidak ada error sama sekali
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO dosen (nidn, nama, email, foto) VALUES (:nidn, :nama, :email, :foto)");
            $stmt->execute([
                ':nidn'  => $nidn,
                ':nama'  => $nama,
                ':email' => $email,
                ':foto'  => $newFileName
            ]);
            $successMessage = "Data Dosen Baru dan Foto Aman berhasil disimpan!";
        } catch (PDOException $e) {
            $errors[] = "Gagal menyimpan data ke database: " . $e->getMessage();
        }
    }
}

// ==========================================
// [FITUR TAMBAHAN] LOGIKA PENCARIAN DOSEN
// ==========================================
$keyword = trim($_GET['keyword'] ?? '');

if ($keyword !== '') {
    // Menggunakan :keyword1 dan :keyword2 untuk menghindari error HY093 pada PDO
    $stmt = $pdo->prepare("SELECT * FROM dosen WHERE deleted_at IS NULL AND (nama LIKE :keyword1 OR nidn LIKE :keyword2) ORDER BY id ASC");
    $stmt->execute([
        ':keyword1' => '%' . $keyword . '%',
        ':keyword2' => '%' . $keyword . '%'
    ]);
    $data = $stmt->fetchAll();
} else {
    // Jika tidak mencari apa-apa, ambil semua data seperti semula
    $data = $pdo->query("SELECT * FROM dosen WHERE deleted_at IS NULL ORDER BY id ASC")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SIAKAD Mini - Data Dosen</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #fff; color: #000; }
        .navigasi { margin-bottom: 20px; font-size: 16px; }
        .navigasi a { text-decoration: none; color: purple; margin-right: 15px; font-weight: bold; }
        .navigasi a:hover { text-decoration: underline; }
        .error-box { color: red; margin-bottom: 15px; font-weight: bold; }
        .success-box { color: green; margin-bottom: 15px; font-weight: bold; }
        .form-box { background: #f9f9f9; padding: 20px; border: 1px solid #ccc; max-width: 450px; margin-bottom: 30px; border-radius: 4px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: inline-block; width: 80px; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #fafafa; }
        .img-dosen { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .no-photo { color: #888; font-style: italic; font-size: 13px; }
        
        /* Style Tambahan untuk Form Pencarian */
        .search-container { margin-bottom: 15px; }
        .search-container input[type="text"] { padding: 6px; width: 250px; border: 1px solid #ccc; border-radius: 4px; }
        .search-container button { padding: 6px 12px; cursor: pointer; font-weight: bold; }
        .search-container .btn-reset { color: red; margin-left: 8px; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

    <h1>Data Dosen</h1>

    <div class="navigasi">
        <a href="dashboard.php">Dashboard</a>
        <a href="#form-tambah">Tambah Dosen</a>
        <a href="trash.php">Trash</a>
        <a href="export.php">Export CSV</a>
        <a href="logout.php">Logout</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($successMessage !== ""): ?>
        <div class="success-box"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <div class="form-box" id="form-tambah">
        <h3 style="margin-top:0;">Form Tambah Data & Upload Foto</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="tambah_dosen">
            
            <div class="form-group">
                <label for="nidn">NIDN:</label>
                <input type="text" name="nidn" id="nidn" required placeholder="Contoh: 001">
            </div>

            <div class="form-group">
                <label for="nama">Nama:</label>
                <input type="text" name="nama" id="nama" required placeholder="Nama lengkap">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required placeholder="alamat@gmail.com">
            </div>

            <div class="form-group">
                <label for="foto">Foto:</label>
                <input type="file" name="foto" id="foto" accept="image/*" required>
            </div>

            <button type="submit" style="cursor:pointer; padding: 5px 15px;">Simpan & Unggah</button>
        </form>
    </div>

    <div class="search-container">
        <form method="GET" action="index.php">
            <input type="text" name="keyword" placeholder="Cari Berdasarkan Nama / NIDN..." value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit">Cari</button>
            <?php if ($keyword !== ''): ?>
                <a href="index.php" class="btn-reset">❌ Reset Pencarian</a>
            <?php endif; ?>
        </form>
    </div>

    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th style="width: 50px;">No</th> <th style="width: 120px;">Foto</th>
                <th style="width: 100px;">NIDN</th>
                <th>Nama</th>
                <th>Email</th>
                <th style="width: 120px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #888;">Data dosen tidak ditemukan atau belum ada dalam sistem.</td>
                </tr>
            <?php else: ?>
                <?php 
                $no = 1; 
                foreach ($data as $d): 
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        
                        <td style="text-align: center;">
                            <?php if (!empty($d['foto']) && file_exists(__DIR__ . '/../uploads/' . $d['foto'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($d['foto']) ?>" class="img-dosen" alt="Foto Dosen">
                            <?php else: ?>
                                <span class="no-photo">Tidak ada foto</span>
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($d['nidn']) ?></td>
                        <td><?= htmlspecialchars($d['nama']) ?></td>
                        <td><?= htmlspecialchars($d['email']) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $d['id'] ?>" style="color:blue; text-decoration:none;">Edit</a> | 
                            <form method="POST" action="delete.php" style="display:inline;">
<input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
<button type="submit" onclick="return confirm('Yakin ingin menghapus?')">Delete</button>
</form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>