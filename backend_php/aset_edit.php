<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods:  POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'koneksi. php';

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ??  null;
$nama_aset = $data['nama_aset'] ?? null;
$jenis_aset = $data['jenis_aset'] ?? null;
$nilai = $data['nilai'] ?? null;
$keterangan = $data['keterangan'] ?? null;

if (!$id || !$nama_aset || !$jenis_aset || !$nilai) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Data tidak lengkap'
    ]);
    exit;
}

try {
    $query = "UPDATE aset_harta SET 
              nama_aset = : nama_aset,
              jenis_aset = :jenis_aset,
              nilai = :nilai,
              keterangan = :keterangan,
              tanggal_update = NOW()
              WHERE id = :id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':id' => $id,
        ':nama_aset' => $nama_aset,
        ':jenis_aset' => $jenis_aset,
        ':nilai' => $nilai,
        ':keterangan' => $keterangan
    ]);

    echo json_encode([
        'sukses' => true,
        'pesan' => 'Aset berhasil diupdate'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Error: ' . $e->getMessage()
    ]);
}
?>
