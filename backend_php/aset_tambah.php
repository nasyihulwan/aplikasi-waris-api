<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type:  application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_log("===== ASET_TAMBAH.PHP DIPANGGIL =====");
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
$idPengusul = isset($_POST['id_pengusul']) ? trim($_POST['id_pengusul']) : '';
$namaAset = isset($_POST['nama_aset']) ? trim($_POST['nama_aset']) : '';
$jenisAset = isset($_POST['jenis_aset']) ? trim($_POST['jenis_aset']) : '';
$nilai = isset($_POST['nilai']) ? trim($_POST['nilai']) : '0';
$keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : '';

error_log("NIK Pewaris:  '$nikPewaris'");
error_log("ID Pengusul: '$idPengusul'");
error_log("Nama Aset: '$namaAset'");
error_log("Jenis Aset:  '$jenisAset'");
error_log("Nilai: '$nilai'");
error_log("Keterangan: '$keterangan'");

if (empty($nikPewaris) || empty($idPengusul) || empty($namaAset) || empty($jenisAset)) {
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Data tidak lengkap',
        'debug' => [
            'nik_pewaris' => $nikPewaris,
            'id_pengusul' => $idPengusul,
            'nama_aset' => $namaAset,
            'jenis_aset' => $jenisAset,
            'nilai' => $nilai
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validasi dan konversi nilai
$nilaiFloat = floatval(str_replace(',', '', $nilai));
if ($nilaiFloat <= 0) {
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Nilai aset harus lebih dari 0',
        'nilai_diterima' => $nilai,
        'nilai_converted' => $nilaiFloat
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validasi jenis aset
$jenisValid = ['tanah', 'rumah', 'kendaraan', 'tabungan', 'emas', 'saham', 'lainnya'];
if (!in_array($jenisAset, $jenisValid)) {
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Jenis aset tidak valid.  Jenis yang valid: ' . implode(', ', $jenisValid),
        'jenis_aset_diterima' => $jenisAset
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Default status verifikasi
$statusVerifikasi = 'menunggu';

$query = $koneksi->prepare(
    "INSERT INTO aset (nik_pewaris, id_pengusul, nama_aset, jenis_aset, nilai, keterangan, status_verifikasi, tanggal_dibuat) 
     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
);

if (!$query) {
    error_log("Error prepare statement: " . $koneksi->error);
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Database error: ' . $koneksi->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$query->bind_param("sissdss", $nikPewaris, $idPengusul, $namaAset, $jenisAset, $nilaiFloat, $keterangan, $statusVerifikasi);

if ($query->execute()) {
    $idAset = $koneksi->insert_id;
    error_log("✅ Aset berhasil ditambahkan dengan ID: $idAset");
    
    http_response_code(200);
    echo json_encode([
        'sukses' => true,
        'pesan' => 'Aset berhasil ditambahkan',
        'id' => $idAset
    ], JSON_UNESCAPED_UNICODE);
} else {
    error_log("❌ Error SQL: " . $query->error);
    
    http_response_code(200);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Gagal menambahkan aset:  ' . $query->error
    ], JSON_UNESCAPED_UNICODE);
}

$query->close();
$koneksi->close();
?>