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
        $admin_id = $_SESSION['user_id'];
        $current_password = trim($_POST['current_password']);
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validazione campi
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $response['message'] = 'Tutti i campi sono obbligatori.';
            echo json_encode($response); exit();
        }
        if (strlen($new_password) < 6) {
            $response['message'] = 'La nuova password deve essere lunga almeno 6 caratteri.';
            echo json_encode($response); exit();
        }
        if ($new_password !== $confirm_password) {
            $response['message'] = 'Le nuove password non coincidono.';
            echo json_encode($response); exit();
        }
        // Verifica la password attuale
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($current_password, $user['password'])) {
            $response['message'] = 'La password attuale non è corretta.';
            echo json_encode($response); exit();
        }
        // Aggiorna la password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$hashed_password, $admin_id]);
        $response['success'] = true;
        $response['message'] = 'Password aggiornata con successo!';
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response['message'] = 'Errore durante l\'aggiornamento della password.';
    }
}
echo json_encode($response);
exit();
?>
