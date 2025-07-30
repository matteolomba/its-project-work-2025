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
    $new_email = $_POST['new_email'] ?? '';

    // Validazione input
    if (empty($current_password) || empty($new_email)) {
        $response['message'] = 'Password attuale e nuova email sono obbligatorie.';
    } else if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Il formato della nuova email non è valido.';
    } else {
        try {
            // Verifica password attuale
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($current_password, $user['password'])) {
                $response['message'] = 'Password attuale non corretta.';
            } else {
                // Controlla se l'email è già in uso
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$new_email, $user_id]);
                if ($stmt->fetch()) {
                    $response['message'] = 'Questa email è già in uso da un altro utente.';
                } else {
                    // Aggiorna email e sessione
                    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $stmt->execute([$new_email, $user_id]);
                    $_SESSION['user_email'] = $new_email;
                    $response['success'] = true;
                    $response['message'] = 'Email aggiornata con successo!';
                }
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
