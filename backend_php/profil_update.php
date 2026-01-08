<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';

// Hanya terima method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    kirimRespon(false, 'Method tidak diizinkan');
    exit;
}

// Ambil data dari request (support JSON dan form-data)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    $inputData = json_decode(file_get_contents('php://input'), true);
} else {
    $inputData = $_POST;
}

// Ambil parameter
$user_id = $inputData['user_id'] ?? null;
$nik = $inputData['nik'] ?? null;
$nama_lengkap = $inputData['nama_lengkap'] ?? null;
$tahun_lahir = $inputData['tahun_lahir'] ?? null;
$tempat_lahir = $inputData['tempat_lahir'] ?? null;
$alamat = $inputData['alamat'] ?? null;

// Validasi user_id wajib ada
if (!$user_id) {
    http_response_code(400);
    kirimRespon(false, 'user_id wajib diisi');
    exit;
}

// Cek apakah user ada
$stmt = $koneksi->prepare("SELECT id FROM pengguna WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    kirimRespon(false, 'User tidak ditemukan');
    exit;
}
$stmt->close();

// Validasi NIK jika diisi (harus 16 digit)
if ($nik !== null && $nik !== '') {
    if (!preg_match('/^\d{16}$/', $nik)) {
        http_response_code(400);
        kirimRespon(false, 'NIK harus 16 digit angka');
        exit;
    }
    
    // Cek apakah NIK sudah digunakan user lain
    $stmt = $koneksi->prepare("SELECT id FROM pengguna WHERE nik = ? AND id != ?");
    $stmt->bind_param("si", $nik, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        kirimRespon(false, 'NIK sudah digunakan oleh pengguna lain');
        exit;
    }
    $stmt->close();
}

// Validasi tahun lahir jika diisi (harus 4 digit dan masuk akal)
if ($tahun_lahir !== null && $tahun_lahir !== '') {
    $tahun_lahir = (int)$tahun_lahir;
    $tahun_sekarang = (int)date('Y');
    
    if ($tahun_lahir < 1900 || $tahun_lahir > $tahun_sekarang) {
        http_response_code(400);
        kirimRespon(false, 'Tahun lahir tidak valid (harus antara 1900 - ' . $tahun_sekarang . ')');
        exit;
    }
}

// Bangun query update secara dinamis
$updateFields = [];
$params = [];
$types = '';

if ($nik !== null && $nik !== '') {
    $updateFields[] = "nik = ?";
    $params[] = bersihkanInput($koneksi, $nik);
    $types .= 's';
}

if ($nama_lengkap !== null && $nama_lengkap !== '') {
    $updateFields[] = "nama_lengkap = ?";
    $params[] = bersihkanInput($koneksi, $nama_lengkap);
    $types .= 's';
}

if ($tahun_lahir !== null && $tahun_lahir !== '') {
    $updateFields[] = "tahun_lahir = ?";
    $params[] = (int)$tahun_lahir;
    $types .= 'i';
}

if ($tempat_lahir !== null && $tempat_lahir !== '') {
    $updateFields[] = "tempat_lahir = ?";
    $params[] = bersihkanInput($koneksi, $tempat_lahir);
    $types .= 's';
}

if ($alamat !== null && $alamat !== '') {
    $updateFields[] = "alamat = ?";
    $params[] = bersihkanInput($koneksi, $alamat);
    $types .= 's';
}

// Cek apakah ada field yang diupdate
if (empty($updateFields)) {
    http_response_code(400);
    kirimRespon(false, 'Tidak ada data yang diupdate. Harap isi minimal satu field: nik, nama_lengkap, tahun_lahir, tempat_lahir, atau alamat');
    exit;
}

// Tambahkan updated_at
$updateFields[] = "updated_at = NOW()";

// Tambahkan user_id ke params
$params[] = $user_id;
$types .= 'i';

// Bangun dan eksekusi query
$sql = "UPDATE pengguna SET " . implode(', ', $updateFields) . " WHERE id = ?";
$stmt = $koneksi->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    kirimRespon(false, 'Gagal mempersiapkan query: ' . $koneksi->error);
    exit;
}

// Bind parameter secara dinamis
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    // Ambil data user yang sudah diupdate
    $stmtSelect = $koneksi->prepare("SELECT id, nik, nama_lengkap, email, tahun_lahir, tempat_lahir, alamat, updated_at FROM pengguna WHERE id = ?");
    $stmtSelect->bind_param("i", $user_id);
    $stmtSelect->execute();
    $resultSelect = $stmtSelect->get_result();
    $userData = $resultSelect->fetch_assoc();
    $stmtSelect->close();
    
    kirimRespon(true, 'Profil berhasil diperbarui', ['data' => $userData]);
} else {
    http_response_code(500);
    kirimRespon(false, 'Gagal memperbarui profil: ' . $stmt->error);
}

$stmt->close();
$koneksi->close();
?>
