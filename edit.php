<?php
// Pastikan session aktif untuk pengamanan aplikasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// [PROSEDUR LEVEL 1] GUARD: Halaman protected menolak akses tanpa sesi login
// =========================================================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Alihkan ke halaman login jika tidak ada sesi
    exit;
}

// Hubhubung ke database menggunakan class Database Anda
require_once '../config/Database.php';
$pdo = Database::connect();

$errors = [];
$successMessage = "";

// =========================================================================
// [PROSEDUR LEVEL 1] CSRF TOKEN GENERATOR (Dibuat saat form pertama kali dibuka)
// =========================================================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. Ambil ID dari parameter URL dan pastikan datanya ada
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Ambil data dosen lama berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM dosen WHERE id = :id AND deleted_at IS NULL");
$stmt->execute([':id' => $id]);
$dosen = $stmt->fetch();

// Jika data dosen tidak ditemukan di database, kembalikan ke halaman utama
if (!$dosen) {
    header("Location: index.php");
    exit;
}

// 2. PROSES LOGIKA UPDATE DATA, UPDATE FOTO, & HAPUS FOTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_dosen') {
    
    // =========================================================================
    // [PROSEDUR LEVEL 1] VALIDASI CSRF TOKEN: Mencegah serangan Cross-Site
    // =========================================================================
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Akses ilegal: Token CSRF tidak valid atau telah kedaluwarsa!");
    }
    
    // Regenerasi token setelah digunakan agar bersifat sekali pakai (Single-use)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $nidn       = trim($_POST['nidn'] ?? '');
    $nama       = trim($_POST['nama'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $hapus_foto = isset($_POST['hapus_foto']); // Memeriksa apakah user mencentang hapus foto

    // Validasi input teks dasar
    if (empty($nidn)) $errors[] = "NIDN wajib diisi!";
    if (empty($nama)) $errors[] = "Nama wajib diisi!";
    if (empty($email)) $errors[] = "Email wajib diisi!";

    // Folder tujuan upload berkas foto 
    $uploadFileDir = __DIR__ . '/../uploads/';

    // Gunakan nama foto lama sebagai default awal
    $fileNameToSave = $dosen['foto'];

    // LOGIKA A: Jika pengguna memilih opsi "Hapus Foto Saat Ini"
    if ($hapus_foto && !empty($dosen['foto'])) {
        $oldFilePath = $uploadFileDir . $dosen['foto'];
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath); // Hapus file fisik di server
        }
        $fileNameToSave = null; // Set nilainya jadi kosong/null di DB
    }

    // LOGIKA B: Periksa jika pengguna malah mengunggah berkas foto baru
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['foto']['tmp_name'];
        $fileOrigName  = $_FILES['foto']['name'];
        $fileSize      = $_FILES['foto']['size'];
        
        // 1. Batasi ukuran gambar (Maksimal 2MB sesuai regulasi)
        $maxSize = 2 * 1024 * 1024; 
        if ($fileSize > $maxSize) {
            $errors[] = 'Ukuran berkas foto baru terlalu besar! Maksimal diperbolehkan 2MB.';
        }
        
        // 2. Validasi biner MIME asli menggunakan finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($fileTmpPath);
        
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            $errors[] = 'Format file salah! Hanya berkas asli berformat JPG, PNG, atau WebP yang diterima.';
        }
        
        // 3. Jika berkas baru valid, generate nama terenkripsi SHA256
        if (empty($errors)) {
            $fileExtension = pathinfo($fileOrigName, PATHINFO_EXTENSION);
            
            $randomString = bin2hex(random_bytes(16));
            $newFileName = hash('sha256', $randomString . time()) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Hapus berkas foto lama jika ada, karena diganti foto baru
                if (!empty($dosen['foto'])) {
                    $oldFilePath = $uploadFileDir . $dosen['foto'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                $fileNameToSave = $newFileName;
            } else {
                $errors[] = 'Gagal menyimpan foto baru ke folder uploads server.';
            }
        }
    }

    // 3. Jalankan query UPDATE ke database jika tidak ada error
    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare("UPDATE dosen SET nidn = :nidn, nama = :nama, email = :email, foto = :foto WHERE id = :id");
            $updateStmt->execute([
                ':nidn'  => $nidn,
                ':nama'  => $nama,
                ':email' => $email,
                ':foto'  => $fileNameToSave,
                ':id'    => $id
            ]);
            
            // Refresh data dosen yang baru di-update agar tampilan form langsung sinkron
            $stmt->execute([':id' => $id]);
            $dosen = $stmt->fetch();

            $successMessage = "Data Dosen berhasil diperbarui!";
        } catch (PDOException $e) {
            $errors[] = "Gagal memperbarui data ke database: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SIAKAD Mini - Edit Dosen</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #fff; color: #000; }
        .navigasi { margin-bottom: 20px; font-size: 16px; }
        .navigasi a { text-decoration: none; color: purple; margin-right: 15px; font-weight: bold; }
        .navigasi a:hover { text-decoration: underline; }
        .error-box { color: red; margin-bottom: 15px; font-weight: bold; }
        .success-box { color: green; margin-bottom: 15px; font-weight: bold; }
        .form-box { background: #f9f9f9; padding: 20px; border: 1px solid #ccc; max-width: 450px; margin-bottom: 30px; border-radius: 4px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], .form-group input[type="email"] { width: 100%; padding: 6px; box-sizing: border-box; }
        .img-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; margin-top: 5px; display: block; }
        .no-photo { color: #888; font-style: italic; font-size: 13px; }
        .checkbox-container { display: flex; align-items: center; gap: 5px; margin-top: 8px; font-size: 14px; color: #c00; font-weight: bold; }
    </style>
</head>
<body>

    <h1>Edit Data Dosen</h1>

    <div class="navigasi">
        <a href="index.php">⬅️ Kembali ke Data Dosen</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($successMessage !== ""): ?>
        <div class="success-box"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="form-box">
        <h3 style="margin-top:0;">Form Perubahan Data Dosen</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_dosen">
            
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="nidn">NIDN:</label>
                <input type="text" name="nidn" id="nidn" required value="<?= htmlspecialchars($dosen['nidn'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="nama">Nama:</label>
                <input type="text" name="nama" id="nama" required value="<?= htmlspecialchars($dosen['nama'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required value="<?= htmlspecialchars($dosen['email'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label>Foto Saat Ini:</label>
                <?php if (!empty($dosen['foto']) && file_exists(__DIR__ . '/../uploads/' . $dosen['foto'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($dosen['foto'], ENT_QUOTES, 'UTF-8') ?>" class="img-preview" alt="Foto Sekarang">
                    
                    <div class="checkbox-container">
                        <input type="checkbox" name="hapus_foto" id="hapus_foto" value="1">
                        <label for="hapus_foto" style="display:inline; cursor:pointer; color: red;">Centang untuk hapus foto ini</label>
                    </div>
                <?php else: ?>
                    <span class="no-photo">Tidak ada foto profil yang tersimpan</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="foto">Ganti / Tambah Foto Baru (Opsional):</label>
                <input type="file" name="foto" id="foto" accept="image/*">
                <small style="color: #666; display: block; margin-top: 4px;">Biarkan kosong jika tidak ingin merubah/menambah foto.</small>
            </div>

            <button type="submit" style="cursor:pointer; padding: 7px 20px; font-weight: bold;">Perbarui Data</button>
        </form>
    </div>

</body>
</html>