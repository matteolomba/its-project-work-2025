<?php
session_start();
require_once '../../core/db_connection.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Richiesta non valida.'];

// Verifica autenticazione utente
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Accesso negato.';
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    
    // Campi aggiornabili
    $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
    $cognome = htmlspecialchars(trim($_POST['cognome'] ?? ''));
    $telefono = htmlspecialchars(trim($_POST['telefono'] ?? ''));
    $data_nascita = $_POST['data_nascita'] ?? null;
    if ($data_nascita === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_nascita)) {
        $data_nascita = null;
    }
    $indirizzo = htmlspecialchars(trim($_POST['indirizzo'] ?? ''));
    $citta = htmlspecialchars(trim($_POST['citta'] ?? ''));
    $cap = htmlspecialchars(trim($_POST['cap'] ?? ''));
    $sommario = htmlspecialchars(trim($_POST['sommario'] ?? ''));

    // Validazione input
    if (empty($nome) || empty($cognome)) {
        http_response_code(400);
        $response['message'] = 'Nome e cognome sono obbligatori.';
        $response['error_code'] = 'VALIDATION';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET nome = ?, cognome = ?, telefono = ?, data_nascita = ?, indirizzo = ?, citta = ?, cap = ?, sommario = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$nome, $cognome, $telefono, $data_nascita, $indirizzo, $citta, $cap, $sommario, $user_id]);
            
            // Recupera i dati aggiornati
            $stmt_updated = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt_updated->execute([$user_id]);
            $updated_user = $stmt_updated->fetch();
            
            $response['success'] = true;
            $response['message'] = 'Profilo aggiornato con successo!';
            $response['data'] = $updated_user;
        } catch (PDOException $e) {
            http_response_code(500);
            error_log($e->getMessage());
            $response['message'] = 'Errore del server durante l\'aggiornamento.';
            $response['error_code'] = $e->getCode();
            $response['error_details'] = $e->getMessage();
        }
    }
}

echo json_encode($response);
exit();
?>
