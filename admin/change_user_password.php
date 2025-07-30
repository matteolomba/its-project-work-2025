<?php
session_start();
require_once '../core/db_connection.php';
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Richiesta non valida.'];

// Consenti accesso solo agli admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $response['message'] = 'Accesso negato.';
    echo json_encode($response); exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $user_id_to_update = $_POST['user_id'];
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validazione campi
        if (empty($new_password) || empty($confirm_password)) {
            $response['message'] = 'Entrambi i campi password sono obbligatori.';
            echo json_encode($response); exit();
        }
        if (strlen($new_password) < 6) {
            $response['message'] = 'La password deve essere lunga almeno 6 caratteri.';
            echo json_encode($response); exit();
        }
        if ($new_password !== $confirm_password) {
            $response['message'] = 'Le password non coincidono.';
            echo json_encode($response); exit();
        }
        // Verifica che l'utente esista
        $stmt = $pdo->prepare("SELECT id, nome, cognome FROM users WHERE id = ?");
        $stmt->execute([$user_id_to_update]);
        $user = $stmt->fetch();
        if (!$user) {
            $response['message'] = 'Utente non trovato.';
            echo json_encode($response); exit();
        }
        // Aggiorna la password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id_to_update]);
        $response['success'] = true;
        $response['message'] = 'Password di ' . $user['nome'] . ' ' . $user['cognome'] . ' aggiornata con successo!';
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response['message'] = 'Errore durante l\'aggiornamento della password.';
    }
}
echo json_encode($response);
exit();
?>
