<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// HANDLE PREFLIGHT UNTUK CHROME
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sukses' => false, 'pesan' => 'Method tidak diizinkan']);
    exit;
}

$idAhliWaris = isset($_POST['id']) ? $_POST['id'] : '';

if (empty($idAhliWaris)) {
    echo json_encode(['sukses' => false, 'pesan' => 'ID ahli waris harus diisi']);
    exit;
}

$query = $koneksi->prepare("DELETE FROM ahli_waris WHERE id = ?");
$query->bind_param("i", $idAhliWaris);

if ($query->execute()) {
    if ($query->affected_rows > 0) {
        echo json_encode(['sukses' => true, 'pesan' => 'Ahli waris berhasil dihapus']);
    } else {
        echo json_encode(['sukses' => false, 'pesan' => 'ID tidak ditemukan atau sudah dihapus']);
    }
} else {
    echo json_encode(['sukses' => false, 'pesan' => 'Error database: ' . $query->error]);
}

$koneksi->close();
?>