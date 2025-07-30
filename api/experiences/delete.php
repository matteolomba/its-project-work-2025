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

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'], $data['type'])) {
    $user_id = $_SESSION['user_id'];
    $id = $data['id'];
    $type = $data['type'];

    $table_name = '';
    // Seleziona la tabella corretta in base al tipo
    if ($type == 'formativa') $table_name = 'esperienze_formative';
    if ($type == 'lavorativa') $table_name = 'esperienze_lavorative';
    
    if ($table_name) {
        try {
            // Verifica che l'utente sia il proprietario prima di cancellare
            $sql = "DELETE FROM $table_name WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $user_id]);

            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Esperienza eliminata con successo.';
            } else {
                $response['message'] = 'Permesso negato o esperienza non trovata.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Errore del database.';
            error_log($e->getMessage());
        }
    }
}

echo json_encode($response);
exit();
?>
