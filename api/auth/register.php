<?php
require_once '../../core/db_connection.php';
require_once '../../core/audit_logger.php';

// Controlla se è una richiesta AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$isAjax = $isAjax || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Inizializza audit logger
$auditLogger = new AuditLogger();

$response = ['success' => false, 'message' => 'Richiesta non valida.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['nome']) || empty($_POST['cognome']) || empty($_POST['email']) || empty($_POST['password'])) {
        $response['message'] = 'Errore: Tutti i campi sono obbligatori.';
        if (!$isAjax) {
            header('Location: ../../pages/auth/register.html?error=missing');
            exit();
        }
    } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Errore: L\'indirizzo email inserito non è valido.';
        if (!$isAjax) {
            header('Location: ../../pages/auth/register.html?error=email');
            exit();
        }
    } else {
        $nome = htmlspecialchars($_POST['nome']);
        $cognome = htmlspecialchars($_POST['cognome']);
        $email = $_POST['email'];
        $password_hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (nome, cognome, email, password) VALUES (?, ?, ?, ?)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $cognome, $email, $password_hashed]);
            
            // Get the new user ID
            $new_user_id = $pdo->lastInsertId();
            
            // Log successful registration
            $auditLogger->logDataOperation('user_registered', $new_user_id, 'users', $new_user_id, [
                'email' => $email,
                'nome' => $nome,
                'cognome' => $cognome
            ]);
            
            $response['success'] = true;
            
            if ($isAjax) {
                $response['redirect'] = '../../pages/auth/login.html?success=registered';
            } else {
                header('Location: ../../pages/auth/login.html?success=registered');
                exit();
            }
        } catch (PDOException $e) {
            // Log failed registration attempt
            $auditLogger->logSecurityEvent('registration_failed', null, [
                'email' => $email,
                'error' => $e->errorInfo[1] == 1062 ? 'duplicate_email' : 'database_error'
            ]);
            
            if ($e->errorInfo[1] == 1062) {
                $response['message'] = 'Errore: Questo indirizzo email è già registrato.';
            } else {
                error_log($e->getMessage());
                $response['message'] = 'Errore del server durante la registrazione.';
            }
            
            if (!$isAjax) {
                header('Location: ../../pages/auth/register.html?error=server');
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
