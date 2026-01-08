<?php
header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kirimRespon(false, 'Method tidak diizinkan');
}

$nikPewaris = bersihkanInput($koneksi, $_POST['nik_pewaris'] ?? '');

if (empty($nikPewaris)) {
    kirimRespon(false, 'NIK pewaris harus diisi');
}

$query = $koneksi->prepare(
    "SELECT a.*, p.nama_lengkap as nama_pengusul 
     FROM aset a
     LEFT JOIN pengguna p ON a.id_pengusul = p.id
     WHERE a.nik_pewaris = ?  
     ORDER BY a.tanggal_dibuat DESC"
);
$query->bind_param("s", $nikPewaris);
$query->execute();
$hasil = $query->get_result();

$daftarAset = [];
while ($baris = $hasil->fetch_assoc()) {
    $daftarAset[] = $baris;
}

kirimRespon(true, 'Data berhasil diambil', ['data' => $daftarAset]);

$koneksi->close();
?>