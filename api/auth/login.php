<?php
session_start();
require_once '../../core/db_connection.php';
require_once '../../core/audit_logger.php';

// Controlla se è una richiesta AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$isAjax = $isAjax || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Inizializza audit logger
$auditLogger = new AuditLogger();

// Prepara un array di risposta di default
$response = ['success' => false, 'message' => 'Richiesta non valida.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $response['message'] = 'Errore: Email e password sono obbligatori.';
        if (!$isAjax) {
            header('Location: ../../pages/auth/login.html?error=missing');
            exit();
        }
    } else {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE email = ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login riuscito
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];

                // Log successful login
                $auditLogger->logLogin($user['id'], $email, true, [
                    'user_type' => $user['user_type'],
                    'session_id' => session_id()
                ]);

                $response['success'] = true;
                $response['message'] = 'Login effettuato con successo!';
                $redirectUrl = ($user['user_type'] == 'admin') ? '../../admin/home.php' : '../../user/home.php';
                
                if ($isAjax) {
                    // Risposta JSON per AJAX
                    $response['redirect'] = $redirectUrl;
                } else {
                    // Redirect normale per form submit
                    header('Location: ' . $redirectUrl);
                    exit();
                }
            } else {
                // Credenziali non valide - Log failed login
                $failed_user_id = $user ? $user['id'] : null;
                $auditLogger->logLogin($failed_user_id, $email, false, [
                    'reason' => 'invalid_credentials',
                    'attempted_email' => $email
                ]);
                
                $response['message'] = 'Credenziali non corrette. Riprova.';
                if (!$isAjax) {
                    header('Location: ../../pages/auth/login.html?error=login');
                    exit();
                }
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $response['message'] = 'Errore del server. Per favore, riprova più tardi.';
            if (!$isAjax) {
                header('Location: ../../pages/auth/login.html?error=server');
                exit();
            }
        }
    }
}

// Se siamo arrivati qui, è una richiesta AJAX - invia la risposta JSON
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>
