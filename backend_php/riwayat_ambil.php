<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kirimRespon(false, 'Method tidak diizinkan');
}

$nikPewaris = bersihkanInput($koneksi, $_POST['nik_pewaris'] ?? '');

if (empty($nikPewaris)) {
    kirimRespon(false, 'NIK pewaris harus diisi');
}

$query = $koneksi->prepare(
    "SELECT * FROM perhitungan_waris 
     WHERE nik_pewaris = ? 
     ORDER BY tanggal_dibuat DESC"
);
$query->bind_param("s", $nikPewaris);
$query->execute();
$hasil = $query->get_result();

$daftarRiwayat = [];
while ($baris = $hasil->fetch_assoc()) {
    $baris['data_perhitungan'] = json_decode($baris['data_perhitungan'], true);
    $daftarRiwayat[] = $baris;
}

kirimRespon(true, 'Data berhasil diambil', ['data' => $daftarRiwayat]);

$koneksi->close();
?>