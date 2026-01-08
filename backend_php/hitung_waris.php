<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = bersihkanInput($koneksi, $_POST['aksi']);
    
    switch ($aksi) {
        case 'simpan':
            simpanPerhitungan($koneksi);
            break;
        case 'ambil_riwayat':
            ambilRiwayat($koneksi);
            break;
        default:
            kirimRespon(false, 'Aksi tidak valid');
    }
} else {
    kirimRespon(false, 'Method tidak diizinkan');
}

function simpanPerhitungan($koneksi) {
    $nikPewaris = bersihkanInput($koneksi, $_POST['nik_pewaris']);
    $dataPerhitungan = $_POST['data_perhitungan'];
    
    if (empty($nikPewaris) || empty($dataPerhitungan)) {
        kirimRespon(false, 'Data tidak lengkap');
    }
    
    // Parse data perhitungan
    $dataJson = json_decode($dataPerhitungan, true);
    
    if (!$dataJson) {
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
        
        // Catat aktivitas
        // Ambil ID user pertama yang terkait dengan pewaris ini
        $queryUser = $koneksi->prepare(
            "SELECT id FROM pengguna WHERE nik_pewaris = ? LIMIT 1"
        );
        $queryUser->bind_param("s", $nikPewaris);
        $queryUser->execute();
        $hasilUser = $queryUser->get_result();
        
        if ($baris = $hasilUser->fetch_assoc()) {
            $idUser = $baris['id'];
            $deskripsi = "Menyimpan perhitungan waris dengan total harta Rp " . number_format($totalHarta, 0, ',', '.');
            
            $queryAktivitas = $koneksi->prepare(
                "INSERT INTO riwayat_aktivitas (id_pengguna, nik_pewaris, jenis_aktivitas, deskripsi) 
                 VALUES (?, ?, 'hitung_waris', ?)"
            );
            $queryAktivitas->bind_param("iss", $idUser, $nikPewaris, $deskripsi);
            $queryAktivitas->execute();
        }
        
        kirimRespon(true, 'Perhitungan berhasil disimpan', ['id' => $idPerhitungan]);
    } else {
        kirimRespon(false, 'Gagal menyimpan perhitungan');
    }
}

function ambilRiwayat($koneksi) {
    $nikPewaris = bersihkanInput($koneksi, $_POST['nik_pewaris']);
    
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
    
    kirimRespon(true, 'Data berhasil diambil', $daftarRiwayat);
}

$koneksi->close();
?>