<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kirimRespon(false, 'Method tidak diizinkan');
}

$idAhliWaris = bersihkanInput($koneksi, $_POST['id'] ?? '');

if (empty($idAhliWaris)) {
    kirimRespon(false, 'ID ahli waris harus diisi');
}

$query = $koneksi->prepare("DELETE FROM ahli_waris WHERE id = ?");
$query->bind_param("i", $idAhliWaris);

if ($query->execute()) {
    if ($query->affected_rows > 0) {
        kirimRespon(true, 'Ahli waris berhasil dihapus');
    } else {
        kirimRespon(false, 'ID tidak ditemukan');
    }
} else {
    kirimRespon(false, 'Gagal menghapus ahli waris: ' . $query->error);
}

$koneksi->close();
?>