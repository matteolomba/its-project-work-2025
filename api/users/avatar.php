<?php
session_start();
require_once '../../core/db_connection.php';

// Verifica autenticazione utente (solo utenti normali)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['profile_picture'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida']);
    exit();
}

$file = $_FILES['profile_picture'];

// Verifica errori upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'upload del file']);
    exit();
}

// Verifica tipo file accettato
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = mime_content_type($file['tmp_name']);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Tipo di file non supportato. Usa JPG, PNG o GIF']);
    exit();
}

// Verifica dimensione file (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Il file è troppo grande. Massimo 5MB']);
    exit();
}

// Crea directory se non esiste
$upload_dir = '../../uploads/profile_pics/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Genera nome file unico
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = $_SESSION['user_id'] . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Rimuovi vecchia foto se esiste
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user && $user['profile_picture'] && file_exists($upload_dir . $user['profile_picture'])) {
    unlink($upload_dir . $user['profile_picture']);
}

// Sposta il file caricato e aggiorna il database
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$filename, $_SESSION['user_id']]);
        echo json_encode([
            'success' => true,
            'message' => 'Foto profilo aggiornata con successo',
            'filename' => $filename
        ]);
    } catch (PDOException $e) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nel caricamento del file']);
}
?>
