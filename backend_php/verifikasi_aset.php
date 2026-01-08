<?php
require_once 'config.php';

// Header CORS untuk mendukung Flutter Web (Chrome)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Menangani Preflight Request dari Chrome
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Menangkap ID Aset dan Status dari Flutter
    $idAset = isset($_POST['id_aset']) ? bersihkanInput($koneksi, $_POST['id_aset']) : '';
    $statusInput = isset($_POST['status']) ? bersihkanInput($koneksi, $_POST['status']) : '';

    // Validasi input
    if (empty($idAset) || empty($statusInput)) {
        kirimRespon(false, 'Data tidak lengkap: ID Aset dan Status wajib diisi');
    }

    try {
        // Update langsung ke kolom status_verifikasi di tabel aset
        $sql = "UPDATE aset SET status_verifikasi = ? WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("si", $statusInput, $idAset);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                kirimRespon(true, 'Status aset berhasil diperbarui menjadi ' . $statusInput);
            } else {
                kirimRespon(false, 'ID tidak ditemukan atau status sudah sama');
            }
        } else {
            kirimRespon(false, 'Gagal memperbarui database: ' . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        kirimRespon(false, 'Kesalahan sistem: ' . $e->getMessage());
    }
} else {
    kirimRespon(false, 'Method tidak diizinkan');
}

$koneksi->close();
?>