<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gunakan config database yang sudah ada
require_once __DIR__ . '/../backend_php/config.php';

// Cek apakah vendor/autoload ada
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    http_response_code(500);
    echo json_encode(['error' => 'Composer dependencies not installed.  Run:  composer install']);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

// HANYA TULIS INI 1x DI SINI
use OTPHP\TOTP;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Ambil method dan action dari request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 1. SETUP - Generate QR Code
if ($method === 'POST' && $action === 'setup') {
    $user_id = $_POST['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['error' => 'user_id required']);
        exit;
    }
    
    // Generate secret
    $totp = TOTP::create();
    $secret = $totp->getSecret();
    
    // Ambil email user dari database (tabel pengguna)
    $stmt = $koneksi->prepare("SELECT email, nama_lengkap FROM pengguna WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Generate QR Code
    $totp->setLabel($user['email']);
    $totp->setIssuer('AplikasiWaris');
    $provisioningUri = $totp->getProvisioningUri();
    
    $qrCode = QrCode::create($provisioningUri);
    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    $qrCodeBase64 = base64_encode($result->getString());
    
    // Simpan secret ke database (belum aktifkan)
    $stmt = $koneksi->prepare("UPDATE pengguna SET two_factor_secret = ? WHERE id = ?");
    $stmt->bind_param("si", $secret, $user_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'qr_code' => 'data:image/png;base64,' . $qrCodeBase64,
        'user' => [
            'nama' => $user['nama_lengkap'],
            'email' => $user['email']
        ]
    ]);
    exit;
}

// 2. VERIFY - Aktifkan 2FA
if ($method === 'POST' && $action === 'verify') {
    $user_id = $_POST['user_id'] ?? null;
    $code = $_POST['code'] ?? null;
    
    if (!$user_id || !$code) {
        echo json_encode(['error' => 'user_id and code required']);
        exit;
    }
    
    // Ambil secret dari database
    $stmt = $koneksi->prepare("SELECT two_factor_secret FROM pengguna WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user || !$user['two_factor_secret']) {
        echo json_encode(['error' => 'Setup 2FA dulu']);
        exit;
    }
    
    // Verifikasi kode
    $totp = TOTP::create($user['two_factor_secret']);
    if ($totp->verify($code)) {
        // Aktifkan 2FA
        $stmt = $koneksi->prepare("UPDATE pengguna SET two_factor_enabled = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => '2FA berhasil diaktifkan'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kode salah atau sudah expired'
        ]);
    }
    exit;
}

// 3. VALIDATE - Cek kode saat login
if ($method === 'POST' && $action === 'validate') {
    $user_id = $_POST['user_id'] ?? null;
    $code = $_POST['code'] ?? null;
    
    if (!$user_id || !$code) {
        echo json_encode(['error' => 'user_id and code required']);
        exit;
    }
    
    // Ambil secret dari database
    $stmt = $koneksi->prepare("SELECT two_factor_secret, two_factor_enabled FROM pengguna WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['valid' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }
    
    if (!$user['two_factor_enabled']) {
        echo json_encode(['valid' => false, 'message' => '2FA belum diaktifkan']);
        exit;
    }
    
    if (! $user['two_factor_secret']) {
        echo json_encode(['valid' => false, 'message' => '2FA not setup']);
        exit;
    }
    
    // Validasi kode
    $totp = TOTP::create($user['two_factor_secret']);
    if ($totp->verify($code)) {
        echo json_encode(['valid' => true, 'message' => 'Kode valid']);
    } else {
        echo json_encode(['valid' => false, 'message' => 'Kode salah atau expired']);
    }
    exit;
}

// 4. CHECK STATUS - Cek apakah user sudah aktifkan 2FA
if ($method === 'GET' && $action === 'status') {
    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['error' => 'user_id required']);
        exit;
    }
    
    $stmt = $koneksi->prepare("SELECT two_factor_enabled FROM pengguna WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    echo json_encode([
        'two_factor_enabled' => (bool)$user['two_factor_enabled']
    ]);
    exit;
}

// 5. DISABLE - Nonaktifkan 2FA
if ($method === 'POST' && $action === 'disable') {
    // Ambil data dari request (support JSON dan form-data)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $inputData = json_decode(file_get_contents('php://input'), true);
    } else {
        $inputData = $_POST;
    }
    
    $user_id = $inputData['user_id'] ?? null;
    $code = $inputData['code'] ?? null;
    
    if (!$user_id || !$code) {
        echo json_encode([
            'success' => false,
            'error' => 'user_id dan code (6 digit) wajib diisi'
        ]);
        exit;
    }
    
    // Validasi kode harus 6 digit
    if (!preg_match('/^\d{6}$/', $code)) {
        echo json_encode([
            'success' => false,
            'error' => 'Kode harus 6 digit angka'
        ]);
        exit;
    }
    
    // Ambil data user dari database
    $stmt = $koneksi->prepare("SELECT two_factor_secret, two_factor_enabled FROM pengguna WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'error' => 'User tidak ditemukan'
        ]);
        exit;
    }
    
    if (!$user['two_factor_enabled']) {
        echo json_encode([
            'success' => false,
            'error' => '2FA belum diaktifkan untuk user ini'
        ]);
        exit;
    }
    
    if (!$user['two_factor_secret']) {
        echo json_encode([
            'success' => false,
            'error' => '2FA secret tidak ditemukan'
        ]);
        exit;
    }
    
    // Verifikasi kode TOTP
    $totp = TOTP::create($user['two_factor_secret']);
    if (!$totp->verify($code)) {
        echo json_encode([
            'success' => false,
            'error' => 'Kode tidak valid atau sudah expired'
        ]);
        exit;
    }
    
    // Nonaktifkan 2FA dan hapus secret
    $stmt = $koneksi->prepare("UPDATE pengguna SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => '2FA berhasil dinonaktifkan'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Gagal menonaktifkan 2FA: ' . $stmt->error
        ]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>