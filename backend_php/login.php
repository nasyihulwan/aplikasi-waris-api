<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db = "aplikasi_waris";

$koneksi = new mysqli($host, $user, $pass, $db);

if ($koneksi->connect_error) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Koneksi database gagal: ' .  $koneksi->connect_error
    ]);
    exit;
}

$koneksi->set_charset("utf8mb4");

// Ambil data dari request
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ??  '';

// Validasi input
if (empty($email) || empty($password)) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Email dan password harus diisi'
    ]);
    exit;
}

// Query user
$stmt = $koneksi->prepare("SELECT id, nama_lengkap, email, password, nik, nik_pewaris, tahun_lahir, tempat_lahir, alamat FROM pengguna WHERE email = ? ");

if (!$stmt) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Error prepare statement: ' . $koneksi->error
    ]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Email tidak terdaftar'
    ]);
    exit;
}

$user = $result->fetch_assoc();

// Verifikasi password
if (password_verify($password, $user['password'])) {
    // Ambil data pewaris dari tabel pewaris
    $stmt_pewaris = $koneksi->prepare("SELECT nama_lengkap, tahun_lahir, tempat_lahir FROM pewaris WHERE nik = ?");
    
    if ($stmt_pewaris) {
        $stmt_pewaris->bind_param("s", $user['nik_pewaris']);
        $stmt_pewaris->execute();
        $result_pewaris = $stmt_pewaris->get_result();
        
        if ($result_pewaris->num_rows > 0) {
            $pewaris = $result_pewaris->fetch_assoc();
        } else {
            $pewaris = ['nama_lengkap' => '', 'tahun_lahir' => '', 'tempat_lahir' => ''];
        }
        
        $stmt_pewaris->close();
    } else {
        $pewaris = ['nama_lengkap' => '', 'tahun_lahir' => '', 'tempat_lahir' => ''];
    }
    
    echo json_encode([
        'sukses' => true,
        'pesan' => 'Login berhasil',
        'id_pengguna' => (int)$user['id'],
        'nama_pengguna' => $user['nama_lengkap'] ?? '',
        'email' => $user['email'] ?? '',
        'nik' => $user['nik'] ?? '',
        'nik_pewaris' => $user['nik_pewaris'] ?? '',
        'tahun_lahir' => $user['tahun_lahir'] ??  '',
        'tempat_lahir' => $user['tempat_lahir'] ?? '',
        'alamat' => $user['alamat'] ?? '',
        'nama_pewaris' => $pewaris['nama_lengkap'] ?? '',
        'tahun_lahir_pewaris' => $pewaris['tahun_lahir'] ?? '',
        'tempat_lahir_pewaris' => $pewaris['tempat_lahir'] ?? ''
    ]);
} else {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Password salah'
    ]);
}

$stmt->close();
$koneksi->close();
?>
