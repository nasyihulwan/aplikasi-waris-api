<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_log("===== AHLI_WARIS_AMBIL. PHP DIPANGGIL =====");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Method tidak diizinkan'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$nikPewaris = isset($_POST['nik_pewaris']) ? trim($_POST['nik_pewaris']) : '';

error_log("NIK Pewaris yang diterima: '$nikPewaris'");

if (empty($nikPewaris)) {
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'NIK pewaris harus diisi'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$query = $koneksi->prepare(
    "SELECT id, nik_pewaris, nama_lengkap, hubungan, jenis_kelamin, tanggal_dibuat 
     FROM ahli_waris 
     WHERE nik_pewaris = ?     
     ORDER BY tanggal_dibuat DESC"
);

if (! $query) {
    error_log("Error prepare statement: " . $koneksi->error);
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Database error:  ' . $koneksi->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$query->bind_param("s", $nikPewaris);
$query->execute();
$hasil = $query->get_result();

$daftarAhliWaris = [];
while ($baris = $hasil->fetch_assoc()) {
    $daftarAhliWaris[] = $baris;
}

error_log("Jumlah ahli waris ditemukan: " . count($daftarAhliWaris));
error_log("Data:  " . print_r($daftarAhliWaris, true));

http_response_code(200);
echo json_encode([
    'sukses' => true,
    'pesan' => 'Data berhasil diambil',
    'data' => $daftarAhliWaris
], JSON_UNESCAPED_UNICODE);

$query->close();
$koneksi->close();
?>