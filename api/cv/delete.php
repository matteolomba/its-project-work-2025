<?php
session_start();
require_once '../../core/db_connection.php';

// Abilita CORS per richieste AJAX
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Verifica autenticazione utente
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

// Gestione compatibilità tra DELETE e POST
$cv_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $cv_id = $_GET['id'] ?? null;
} else {
    $data = json_decode(file_get_contents('php://input'), true);
    $cv_id = $data['id'] ?? null;
}

if (!$cv_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID curriculum mancante']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    // Verifica che il CV appartenga all'utente
    $stmt = $pdo->prepare("SELECT file_path, nome_file FROM curricula WHERE id = ? AND user_id = ?");
    $stmt->execute([$cv_id, $user_id]);
    $cv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cv) {
        http_response_code(404);
        echo json_encode(['error' => 'Curriculum non trovato']);
        exit;
    }
    
    // Elimina il file fisico se esiste
    $deleted = false;
    if (!empty($cv['file_path'])) {
        $file_path = '../../' . ltrim($cv['file_path'], '/');
        if (file_exists($file_path)) {
            $deleted = unlink($file_path);
        }
    } elseif (!empty($cv['nome_file'])) {
        $file_path = '../../uploads/cvs/' . $cv['nome_file'];
        if (file_exists($file_path)) {
            $deleted = unlink($file_path);
        }
    }
    // Log se il file non è stato eliminato
    if (!$deleted) {
        error_log('Attenzione: file CV non trovato o non eliminato: ' . ($file_path ?? 'N/A'));
    }
    // Elimina il record dal database
    $stmt = $pdo->prepare("DELETE FROM curricula WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$cv_id, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Curriculum eliminato con successo']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Errore durante l\'eliminazione']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore del server: ' . $e->getMessage()]);
}
?>
