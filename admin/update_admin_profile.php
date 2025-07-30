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
        $nome = trim($_POST['nome']);
        $cognome = trim($_POST['cognome']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);

        // Validazione base
        if (empty($nome) || empty($cognome) || empty($email)) {
            $response['message'] = 'Nome, cognome ed email sono obbligatori.';
            echo json_encode($response); exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Email non valida.';
            echo json_encode($response); exit();
        }
        // Verifica se l'email è già in uso da un altro utente
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $admin_id]);
        if ($stmt->fetch()) {
            $response['message'] = 'Email già in uso da un altro utente.';
            echo json_encode($response); exit();
        }
        // Aggiorna il profilo admin
        $sql = "UPDATE users SET nome = ?, cognome = ?, email = ?, telefono = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $cognome, $email, $telefono, $admin_id]);
        // Recupera i dati aggiornati per l'aggiornamento dinamico
        $stmt_updated = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt_updated->execute([$admin_id]);
        $updated_admin = $stmt_updated->fetch();
        $response['success'] = true;
        $response['message'] = 'Profilo amministratore aggiornato con successo!';
        $response['data'] = $updated_admin;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response['message'] = 'Errore durante l\'aggiornamento del profilo.';
    }
}
echo json_encode($response);
exit();
?>
