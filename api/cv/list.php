<?php
session_start();
require_once '../../core/db_connection.php';

// Abilita CORS per richieste AJAX
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Verifica autenticazione utente
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT id, nome_file, nome_originale, tipo, uploaded_at 
                          FROM curricula 
                          WHERE user_id = ? 
                          ORDER BY uploaded_at DESC");
    $stmt->execute([$user_id]);
    $curricula = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Prepara la risposta con i dati formattati
    $response = [];
    foreach ($curricula as $cv) {
        $response[] = [
            'id' => $cv['id'],
            'nome_file' => $cv['nome_file'],
            'nome_display' => $cv['nome_originale'],
            'tipo' => $cv['tipo'],
            'data_caricamento' => date('d/m/Y H:i', strtotime($cv['uploaded_at']))
        ];
    }
    echo json_encode(['success' => true, 'curricula' => $response]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore del server: ' . $e->getMessage()]);
}
?>
