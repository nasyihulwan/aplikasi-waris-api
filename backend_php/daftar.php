<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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
        'pesan' => 'Koneksi database gagal: ' . $koneksi->connect_error
    ]);
    exit;
}

$koneksi->set_charset("utf8mb4");

// Cek method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sukses' => false, 'pesan' => 'Method harus POST']);
    exit;
}

// Ambil data POST - LANGSUNG tanpa bersihkan dulu
$data = [
    'nama_lengkap' => $_POST['nama_lengkap'] ?? '',
    'email' => $_POST['email'] ?? '',
    'password' => $_POST['password'] ?? '',
    'tahun_lahir' => $_POST['tahun_lahir'] ?? '',
    'tempat_lahir' => $_POST['tempat_lahir'] ?? '',
    'alamat' => $_POST['alamat'] ?? '',
    'nik' => $_POST['nik'] ?? '',
    'nama_pewaris' => $_POST['nama_pewaris'] ?? '',
    'tahun_lahir_pewaris' => $_POST['tahun_lahir_pewaris'] ?? '',
    'tempat_lahir_pewaris' => $_POST['tempat_lahir_pewaris'] ?? '',
    'nik_pewaris' => $_POST['nik_pewaris'] ?? ''
];

// Log data yang diterima
error_log("===== DAFTAR.PHP DIPANGGIL =====");
error_log("Data POST: " . json_encode($data));

// Validasi cepat
foreach ($data as $key => $value) {
    if (empty(trim($value))) {
        echo json_encode([
            'sukses' => false,
            'pesan' => "Field $key kosong!"
        ]);
        exit;
    }
}

// Trim semua data
foreach ($data as $key => $value) {
    $data[$key] = trim($value);
}

// Validasi NIK
if (strlen($data['nik']) != 16) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'NIK harus 16 digit (Anda: ' . strlen($data['nik']) . ' digit)'
    ]);
    exit;
}

if (strlen($data['nik_pewaris']) != 16) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'NIK Pewaris harus 16 digit (Anda: ' . strlen($data['nik_pewaris']) . ' digit)'
    ]);
    exit;
}

// Validasi email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Email tidak valid'
    ]);
    exit;
}

// Validasi password
if (strlen($data['password']) < 6) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Password minimal 6 karakter'
    ]);
    exit;
}

// Cek email duplikat
$stmt = $koneksi->prepare("SELECT id FROM pengguna WHERE email = ?");
$stmt->bind_param("s", $data['email']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Email sudah terdaftar!'
    ]);
    exit;
}

// Cek NIK duplikat
$stmt = $koneksi->prepare("SELECT id FROM pengguna WHERE nik = ?");
$stmt->bind_param("s", $data['nik']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'NIK sudah terdaftar!'
    ]);
    exit;
}

// Mulai transaksi
$koneksi->begin_transaction();

try {
    // 1. Cek/Insert Pewaris
    $stmt = $koneksi->prepare("SELECT nik FROM pewaris WHERE nik = ?");
    $stmt->bind_param("s", $data['nik_pewaris']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Insert pewaris baru
        $stmt = $koneksi->prepare(
            "INSERT INTO pewaris (nik, nama_lengkap, tahun_lahir, tempat_lahir) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssss",
            $data['nik_pewaris'],
            $data['nama_pewaris'],
            $data['tahun_lahir_pewaris'],
            $data['tempat_lahir_pewaris']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Gagal insert pewaris: ' . $stmt->error);
        }
    }
    
    // 2. Hash password
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // 3. Insert pengguna
    $stmt = $koneksi->prepare(
        "INSERT INTO pengguna 
         (nik, nik_pewaris, nama_lengkap, email, password, tahun_lahir, tempat_lahir, alamat) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $stmt->bind_param(
        "ssssssss",
        $data['nik'],
        $data['nik_pewaris'],
        $data['nama_lengkap'],
        $data['email'],
        $password_hash,
        $data['tahun_lahir'],
        $data['tempat_lahir'],
        $data['alamat']
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal insert pengguna: ' . $stmt->error);
    }
    
    $id_pengguna = $koneksi->insert_id;
    
    // Commit
    $koneksi->commit();
    
    echo json_encode([
        'sukses' => true,
        'pesan' => 'Pendaftaran berhasil! Silakan login',
        'id_pengguna' => $id_pengguna,
        'nama_pengguna' => $data['nama_lengkap'],
        'email' => $data['email']
    ]);
    
} catch (Exception $e) {
    $koneksi->rollback();
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Error: ' . $e->getMessage()
    ]);
}

$koneksi->close();
?>