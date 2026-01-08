<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kirimRespon(false, 'Method tidak diizinkan');
}

$nikPewaris = bersihkanInput($koneksi, $_POST['nik_pewaris'] ?? '');
$dataPerhitungan = $_POST['data_perhitungan'] ??  '';

if (empty($nikPewaris) || empty($dataPerhitungan)) {
    kirimRespon(false, 'Data tidak lengkap');
}

// Parse data perhitungan
$dataJson = json_decode($dataPerhitungan, true);

if (! $dataJson) {
    kirimRespon(false, 'Format data tidak valid');
}

$totalHarta = $dataJson['total_harta'] ?? 0;

$query = $koneksi->prepare(
    "INSERT INTO perhitungan_waris (nik_pewaris, total_harta, data_perhitungan, tanggal_dibuat) 
     VALUES (?, ?, ?, NOW())"
);
$query->bind_param("sds", $nikPewaris, $totalHarta, $dataPerhitungan);

if ($query->execute()) {
    $idPerhitungan = $koneksi->insert_id;
    kirimRespon(true, 'Perhitungan berhasil disimpan', ['id' => $idPerhitungan]);
} else {
    kirimRespon(false, 'Gagal menyimpan perhitungan: ' . $query->error);
}

$koneksi->close();
?>