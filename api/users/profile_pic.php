<?php
// Restituisce la foto profilo solo se l'utente ha i permessi
session_start();
require_once '../../core/db_connection.php';

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    exit('Parametro user_id mancante');
}

$user_id = (int)$_GET['user_id'];

// Solo admin o l'utente stesso può vedere la foto
if (!isset($_SESSION['user_id']) || ($_SESSION['user_id'] != $user_id && $_SESSION['user_type'] !== 'admin')) {
    http_response_code(403);
    exit('Non autorizzato');
}

$stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !$user['profile_picture']) {
    http_response_code(404);
    exit('Immagine non trovata');
}

$img_path = '../../uploads/profile_pics/' . $user['profile_picture'];
if (!file_exists($img_path)) {
    http_response_code(404);
    exit('File non trovato');
}

// Determina il content-type
$ext = strtolower(pathinfo($img_path, PATHINFO_EXTENSION));
$content_types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
];
header('Content-Type: ' . ($content_types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($img_path));
readfile($img_path);
exit;
