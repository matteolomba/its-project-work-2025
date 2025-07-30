<?php
session_start();
require_once '../core/db_connection.php';
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Richiesta non valida.'];

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $response['message'] = 'Accesso negato.';
    echo json_encode($response); exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $user_id_to_update = $_POST['id'];
        $nome = trim($_POST['nome']);
        $cognome = trim($_POST['cognome']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $indirizzo = trim($_POST['indirizzo']);
        $citta = trim($_POST['citta']);
        $sommario = trim($_POST['sommario']);
        $password = trim($_POST['password']);

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
        $stmt->execute([$email, $user_id_to_update]);
        if ($stmt->fetch()) {
            $response['message'] = 'Email già in uso da un altro utente.';
            echo json_encode($response); exit();
        }

        // Aggiorna l'utente (con o senza password)
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET nome = ?, cognome = ?, email = ?, telefono = ?, indirizzo = ?, citta = ?, sommario = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $cognome, $email, $telefono, $indirizzo, $citta, $sommario, $hashed_password, $user_id_to_update]);
        } else {
            $sql = "UPDATE users SET nome = ?, cognome = ?, email = ?, telefono = ?, indirizzo = ?, citta = ?, sommario = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $cognome, $email, $telefono, $indirizzo, $citta, $sommario, $user_id_to_update]);
        }

        // Recupera i dati aggiornati per l'aggiornamento dinamico
        $stmt_updated = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt_updated->execute([$user_id_to_update]);
        $updated_user = $stmt_updated->fetch();

        $response['success'] = true;
        $response['message'] = 'Profilo utente aggiornato con successo!';
        $response['data'] = $updated_user;

    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response['message'] = 'Errore durante l\'aggiornamento.';
    }
}

echo json_encode($response);
?>
