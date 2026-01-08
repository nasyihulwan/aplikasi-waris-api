<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_log("===== AHLI_WARIS_TAMBAH. PHP DIPANGGIL =====");
error_log("REQUEST_METHOD:  " . $_SERVER['REQUEST_METHOD']);
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
$namaLengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
$hubungan = isset($_POST['hubungan']) ? trim($_POST['hubungan']) : '';
$jenisKelamin = isset($_POST['jenis_kelamin']) ? trim($_POST['jenis_kelamin']) : '';

error_log("NIK Pewaris:  '$nikPewaris'");
error_log("Nama:  '$namaLengkap'");
error_log("Hubungan: '$hubungan'");
error_log("Jenis Kelamin: '$jenisKelamin'");

if (empty($nikPewaris) || empty($namaLengkap) || empty($hubungan) || empty($jenisKelamin)) {
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Data tidak lengkap',
        'debug' => [
            'nik_pewaris' => $nikPewaris,
            'nama_lengkap' => $namaLengkap,
            'hubungan' => $hubungan,
            'jenis_kelamin' => $jenisKelamin
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validasi hubungan
$hubunganValid = ['istri', 'suami', 'anak_laki', 'anak_perempuan', 'ayah', 'ibu', 'saudara_laki', 'saudara_perempuan'];
if (!in_array($hubungan, $hubunganValid)) {
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Hubungan tidak valid.  Hubungan yang valid: ' . implode(', ', $hubunganValid),
        'hubungan_diterima' => $hubungan
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validasi jenis kelamin
$jenisKelaminValid = ['laki-laki', 'perempuan'];
if (!in_array($jenisKelamin, $jenisKelaminValid)) {
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Jenis kelamin tidak valid. Nilai yang valid: laki-laki, perempuan',
        'jenis_kelamin_diterima' => $jenisKelamin
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Cek apakah NIK pewaris ada di database
$cekNik = $koneksi->prepare("SELECT nik_pewaris FROM pengguna WHERE nik_pewaris = ?");
$cekNik->bind_param("s", $nikPewaris);
$cekNik->execute();
$hasilCek = $cekNik->get_result();

if ($hasilCek->num_rows === 0) {
    error_log("⚠️ NIK Pewaris tidak ditemukan di tabel pengguna:  $nikPewaris");
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'NIK Pewaris tidak ditemukan.  Pastikan Anda sudah terdaftar.',
        'nik_pewaris' => $nikPewaris
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$cekNik->close();

// Insert ahli waris
$query = $koneksi->prepare(
    "INSERT INTO ahli_waris (nik_pewaris, nama_lengkap, hubungan, jenis_kelamin, tanggal_dibuat) 
     VALUES (?, ?, ?, ?, NOW())"
);

if (!$query) {
    error_log("Error prepare statement: " . $koneksi->error);
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Database error:  ' . $koneksi->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$query->bind_param("ssss", $nikPewaris, $namaLengkap, $hubungan, $jenisKelamin);

if ($query->execute()) {
    $idBaru = $koneksi->insert_id;
    error_log("✅ Ahli waris berhasil ditambahkan dengan ID: $idBaru");
    
    // Verifikasi data tersimpan
    $verifikasi = $koneksi->prepare("SELECT * FROM ahli_waris WHERE id = ?");
    $verifikasi->bind_param("i", $idBaru);
    $verifikasi->execute();
    $dataVerifikasi = $verifikasi->get_result()->fetch_assoc();
    error_log("📋 Data yang tersimpan:  " . print_r($dataVerifikasi, true));
    $verifikasi->close();
    
    http_response_code(200);
    echo json_encode([
        'sukses' => true,
        'pesan' => 'Ahli waris berhasil ditambahkan',
        'id' => $idBaru,
        'data' => $dataVerifikasi
    ], JSON_UNESCAPED_UNICODE);
} else {
    error_log("❌ Error SQL: " . $query->error);
    
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Gagal menambahkan ahli waris: ' . $query->error
    ], JSON_UNESCAPED_UNICODE);
}

$query->close();
$koneksi->close();
?>