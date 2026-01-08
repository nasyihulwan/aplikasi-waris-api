cat > /Applications/XAMPP/xamppfiles/htdocs/aplikasi_waris/backend_php/ahli_waris_verifikasi.php << 'EOF' <?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'koneksi.php';

$data = json_decode(file_get_contents('php://input'), true);

$id_ahli_waris = $data['id_ahli_waris'] ?? null;
$id_verifikator = $data['id_verifikator'] ?? null;
$status = $data['status'] ?? null;
$keterangan = $data['keterangan'] ?? null;

if (!$id_ahli_waris || !$id_verifikator || !$status) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Data tidak lengkap'
    ]);
    exit;
}

try {
    // Cek apakah sudah pernah verifikasi
    $cek = $pdo->prepare("SELECT id FROM verifikasi_ahli_waris WHERE id_ahli_waris = : id_ahli_waris AND id_verifikator = :id_verifikator");
    $cek->execute([
        ': id_ahli_waris' => $id_ahli_waris,
        ':id_verifikator' => $id_verifikator
    ]);

    if ($cek->rowCount() > 0) {
        echo json_encode([
            'sukses' => false,
            'pesan' => 'Anda sudah melakukan verifikasi sebelumnya'
        ]);
        exit;
    }

    // Insert verifikasi
    $query = "INSERT INTO verifikasi_ahli_waris (id_ahli_waris, id_verifikator, status, keterangan)
              VALUES (:id_ahli_waris, :id_verifikator, :status, : keterangan)";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':id_ahli_waris' => $id_ahli_waris,
        ':id_verifikator' => $id_verifikator,
        ':status' => $status,
        ':keterangan' => $keterangan
    ]);

    // Update jumlah verifikasi
    if ($status == 'disetujui') {
        $pdo->prepare("UPDATE ahli_waris SET jumlah_verifikasi = jumlah_verifikasi + 1 WHERE id = :id")
            ->execute([':id' => $id_ahli_waris]);

        // Cek apakah sudah mayoritas (misal 2 verifikasi = disetujui)
        $ahli = $pdo->prepare("SELECT jumlah_verifikasi FROM ahli_waris WHERE id = : id");
        $ahli->execute([':id' => $id_ahli_waris]);
        $data_ahli = $ahli->fetch(PDO::FETCH_ASSOC);

        if ($data_ahli['jumlah_verifikasi'] >= 2) {
            $pdo->prepare("UPDATE ahli_waris SET status_verifikasi = 'disetujui' WHERE id = :id")
                ->execute([':id' => $id_ahli_waris]);
        }
    } else {
        // Kalau ditolak langsung update status
        $pdo->prepare("UPDATE ahli_waris SET status_verifikasi = 'ditolak' WHERE id = : id")
            ->execute([': id' => $id_ahli_waris]);
    }

    echo json_encode([
        'sukses' => true,
        'pesan' => 'Verifikasi berhasil disimpan'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Error: ' . $e->getMessage()
    ]);
}
?> EOF