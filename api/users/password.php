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
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validazione input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $response['message'] = 'Tutti i campi sono obbligatori.';
    } else if ($new_password !== $confirm_password) {
        $response['message'] = 'La nuova password e la conferma non coincidono.';
    } else if (strlen($new_password) < 6) {
        $response['message'] = 'La nuova password deve essere di almeno 6 caratteri.';
    } else {
        try {
            // Verifica password attuale
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($current_password, $user['password'])) {
                $response['message'] = 'Password attuale non corretta.';
            } else {
                // Aggiorna password
                $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_password_hashed, $user_id]);
                $response['success'] = true;
                $response['message'] = 'Password aggiornata con successo!';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $response['message'] = 'Errore del server durante l\'aggiornamento.';
        }
    }
}

echo json_encode($response);
exit();
?>
