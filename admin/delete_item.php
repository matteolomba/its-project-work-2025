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

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];
$type = $data['type'];
$table = '';
$file_column = '';

if ($type === 'user') $table = 'users';
if ($type === 'cv') { $table = 'curricula'; $file_column = 'file_path'; }
if ($type === 'formativa') $table = 'esperienze_formative';
if ($type === 'lavorativa') $table = 'esperienze_lavorative';

if (empty($table)) {
    $response['message'] = 'Tipo non riconosciuto.';
    echo json_encode($response); exit();
}

try {
    if (!empty($file_column)) {
        $stmt = $pdo->prepare("SELECT $file_column FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && !empty($row[$file_column])) {
            $file_path = $row[$file_column];
            if (file_exists($file_path)) unlink($file_path);
        }
    }
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        $response = ['success' => true, 'message' => 'Elemento eliminato con successo.'];
    } else {
        $response['message'] = 'Elemento non trovato.';
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $response['message'] = 'Errore durante l\'eliminazione.';
}
echo json_encode($response);
?>
