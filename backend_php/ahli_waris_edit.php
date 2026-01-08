<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$idAhliWaris = bersihkanInput($koneksi, $_POST['id'] ?? '');
$namaLengkap = bersihkanInput($koneksi, $_POST['nama_lengkap'] ?? '');
$hubungan = bersihkanInput($koneksi, $_POST['hubungan'] ?? '');
$jenisKelamin = bersihkanInput($koneksi, $_POST['jenis_kelamin'] ?? '');

if (empty($idAhliWaris) || empty($namaLengkap) || empty($hubungan) || empty($jenisKelamin)) {
    kirimRespon(false, 'Data tidak lengkap');
}

// Validasi hubungan
$hubunganValid = ['istri', 'suami', 'anak_laki', 'anak_perempuan', 'ayah', 'ibu', 'saudara_laki', 'saudara_perempuan'];
if (!in_array($hubungan, $hubunganValid)) {
    kirimRespon(false, 'Hubungan tidak valid');
}

// Validasi jenis kelamin
$jenisKelaminValid = ['laki-laki', 'perempuan'];
if (!in_array($jenisKelamin, $jenisKelaminValid)) {
    kirimRespon(false, 'Jenis kelamin tidak valid');
}

$query = $koneksi->prepare(
    "UPDATE ahli_waris 
     SET nama_lengkap = ?, hubungan = ?, jenis_kelamin = ?   
     WHERE id = ?"
);
$query->bind_param("sssi", $namaLengkap, $hubungan, $jenisKelamin, $idAhliWaris);

if ($query->execute()) {
    if ($query->affected_rows > 0) {
        kirimRespon(true, 'Ahli waris berhasil diupdate');
    } else {
        kirimRespon(false, 'Tidak ada perubahan atau ID tidak ditemukan');
    }
} else {
    kirimRespon(false, 'Gagal mengupdate ahli waris:  ' . $query->error);
}

$koneksi->close();
?>