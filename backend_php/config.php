<?php
// Konfigurasi Database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'aplikasi_waris';

// Koneksi ke database
$koneksi = new mysqli($host, $username, $password, $database);

// Cek koneksi
if ($koneksi->connect_error) {
    die(json_encode([
        'sukses' => false,
        'pesan' => 'Koneksi database gagal: ' . $koneksi->connect_error
    ]));
}

// Set charset
$koneksi->set_charset("utf8mb4");

// Fungsi helper untuk membersihkan input
function bersihkanInput($koneksi, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $koneksi->real_escape_string($data);
}

// Fungsi helper untuk mengirim response JSON
function kirimRespon($sukses, $pesan, $data = null) {
    $response = [
        'sukses' => $sukses,
        'pesan' => $pesan
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>
