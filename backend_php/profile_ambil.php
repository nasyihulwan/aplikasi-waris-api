<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['sukses' => false, 'pesan' => 'user_id wajib diisi']);
    exit;
}

$user_id = intval($data['user_id']);

$query = "SELECT id, nik, nama_lengkap, email, tahun_lahir, tempat_lahir, alamat 
          FROM pengguna WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $profil = $result->fetch_assoc();
    echo json_encode([
        'sukses' => true,
        'data' => $profil
    ]);
} else {
    http_response_code(404);
    echo json_encode(['sukses' => false, 'pesan' => 'Pengguna tidak ditemukan']);
}

$stmt->close();
$conn->close();
?>