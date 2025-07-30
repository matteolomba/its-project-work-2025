<?php
session_start();
require_once '../../core/db_connection.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Richiesta non valida.'];

// Solo admin può creare utenti
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $response['message'] = 'Accesso negato.';
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['nome']) || empty($_POST['cognome']) || empty($_POST['email']) || empty($_POST['password'])) {
        $response['message'] = 'Tutti i campi sono obbligatori.';
    } else {
        $nome = $_POST['nome'];
        $cognome = $_POST['cognome'];
        $email = $_POST['email'];
        $password_hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO users (nome, cognome, email, password, user_type) VALUES (?, ?, ?, ?, 'user')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $cognome, $email, $password_hashed]);
            $response['success'] = true;
            $response['message'] = 'Utente creato con successo!';
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $response['message'] = 'Email già in uso.';
            } else {
                $response['message'] = 'Errore del database.';
                error_log($e->getMessage());
            }
        }
    }
}

echo json_encode($response);
?>
