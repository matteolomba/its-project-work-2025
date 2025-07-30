<?php
session_start();
require_once '../../core/db_connection.php';

// Verifica autenticazione utente
if (!isset($_SESSION['user_id'])) {
    die('Accesso negato. Effettua il login.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID del curriculum non valido.');
}

$cv_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM curricula WHERE id = ?");
    $stmt->execute([$cv_id]);
    $cv = $stmt->fetch();

    // Verifica che il CV esista e che appartenga all'utente loggato o ad un admin
    if ($cv && ($cv['user_id'] == $user_id || $_SESSION['user_type'] == 'admin')) {
        // Determina il percorso del file
        $file_path = null;
        if (!empty($cv['file_path'])) {
            $relative_path = ltrim($cv['file_path'], '/');
            $file_path = realpath(__DIR__ . '/../../' . $relative_path);
        } else if (!empty($cv['nome_file'])) {
            $file_path = realpath(__DIR__ . '/../../uploads/cvs/' . $cv['nome_file']);
        }
        if ($file_path && file_exists($file_path)) {
            // Prepara il download
            $download_name = $cv['nome_originale'] ?: $cv['nome_file'];
            
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($download_name) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            die('Errore: File non trovato sul server.');
        }
    } else {
        die('Accesso negato. Non hai i permessi per scaricare questo file.');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Errore del database.');
}
?>
