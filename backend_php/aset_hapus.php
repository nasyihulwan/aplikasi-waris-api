<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kirimRespon(false, 'Method tidak diizinkan');
}

$idAset = bersihkanInput($koneksi, $_POST['id'] ?? '');

if (empty($idAset)) {
    kirimRespon(false, 'ID aset harus diisi');
}

// Hapus verifikasi terkait terlebih dahulu
$queryVerifikasi = $koneksi->prepare("DELETE FROM verifikasi_aset WHERE id_aset = ?");
$queryVerifikasi->bind_param("i", $idAset);
$queryVerifikasi->execute();

// Hapus aset
$query = $koneksi->prepare("DELETE FROM aset WHERE id = ? ");
$query->bind_param("i", $idAset);

if ($query->execute()) {
    if ($query->affected_rows > 0) {
        kirimRespon(true, 'Aset berhasil dihapus');
    } else {
        kirimRespon(false, 'ID tidak ditemukan');
    }
} else {
    kirimRespon(false, 'Gagal menghapus aset:  ' . $query->error);
}

$koneksi->close();
?>