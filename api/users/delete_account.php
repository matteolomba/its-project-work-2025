<?php
session_start();
require_once '../../core/db_connection.php';
require_once '../../core/account_delete_manager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

// Verifica autenticazione utente
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

// Se è una cancellazione da admin
if (isset($input['user_id']) && isset($input['admin_deletion'])) {
    // Solo admin può eliminare altri account
    if ($_SESSION['user_type'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Solo gli amministratori possono eliminare altri account']);
        exit;
    }
    $user_to_delete = $input['user_id'];
    $admin_id = $_SESSION['user_id'];
    $deleteManager = new AccountDeleteManager();
    $can_delete = $deleteManager->canDeleteUser($user_to_delete, $admin_id, true);
    if (!$can_delete['can_delete']) {
        echo json_encode(['success' => false, 'message' => $can_delete['reason']]);
        exit;
    }
} else {
    // Cancellazione del proprio account
    $password = $input['password'] ?? '';
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Password richiesta per confermare la cancellazione']);
        exit;
    }
    // Verifica password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password non corretta']);
        exit;
    }
    $user_to_delete = $_SESSION['user_id'];
    $admin_id = null;
}

try {
    $deleteManager = new AccountDeleteManager();
    
    $reason = isset($input['admin_deletion']) ? 'admin_action' : 'user_request';
    $result = $deleteManager->deleteAccount($user_to_delete, $reason, $admin_id, $ip_address);
    
    if ($result['success']) {
        // Se l'utente ha cancellato il proprio account, effettua il logout
        if ($user_to_delete == $_SESSION['user_id']) {
            session_destroy();
        }
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Errore cancellazione account: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
}
?>
