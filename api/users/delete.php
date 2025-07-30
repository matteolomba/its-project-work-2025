<?php
session_start();
require_once '../../core/db_connection.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Richiesta non valida.'];

// Solo admin può eliminare utenti diversi da sé
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $response['message'] = 'Accesso negato.';
    echo json_encode($response);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id']) && is_numeric($data['id'])) {
    $user_to_delete_id = $data['id'];

    // Impedisce all'admin di eliminare se stesso
    if ($user_to_delete_id == $_SESSION['user_id']) {
        $response['message'] = 'Non puoi eliminare il tuo stesso account.';
        echo json_encode($response);
        exit();
    }

    try {
        // Elimina i file dei CV associati
        $cvs = $pdo->prepare("SELECT file_path FROM curricula WHERE user_id = ?");
        $cvs->execute([$user_to_delete_id]);
        $files_to_delete = $cvs->fetchAll(PDO::FETCH_ASSOC);

        foreach ($files_to_delete as $file) {
            $path = dirname(__DIR__) . '/' . $file['file_path'];
            if (file_exists($path)) {
                unlink($path);
            }
        }

        // Elimina l'utente (ON DELETE CASCADE per tabelle collegate)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_to_delete_id]);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Utente eliminato con successo.';
        } else {
            $response['message'] = 'Utente non trovato.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Errore del database durante l\'eliminazione.';
        error_log($e->getMessage());
    }
}

echo json_encode($response);
?>
