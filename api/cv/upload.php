<?php
session_start();
require_once '../../core/db_connection.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Richiesta non valida.'];

// Verifica autenticazione utente
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Accesso negato. Effettua il login.';
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['cv_file'])) {
    if ($_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        $user_id = $_SESSION['user_id'];
        $nome_originale = basename($_FILES['cv_file']['name']);
        
        // Verifica tipo file
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['cv_file']['tmp_name']);
        finfo_close($file_info);
        
        if ($mime_type !== 'application/pdf') {
            $response['message'] = 'Formato file non supportato. Carica solo file PDF.';
            echo json_encode($response);
            exit();
        }
        
        // Verifica dimensione file (max 10MB)
        if ($_FILES['cv_file']['size'] > 10 * 1024 * 1024) {
            $response['message'] = 'File troppo grande. Dimensione massima 10MB.';
            echo json_encode($response);
            exit();
        }
        
        $upload_dir = dirname(__DIR__, 2) . '/uploads/cvs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $nome_file = uniqid($user_id . '_', true) . '.pdf';
        $file_path = $upload_dir . $nome_file;

        if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $file_path)) {
            try {
                $sql = "INSERT INTO curricula (user_id, nome_originale, nome_file, file_path, tipo, uploaded_at) VALUES (?, ?, ?, ?, 'caricato', NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $nome_originale, $nome_file, 'uploads/cvs/' . $nome_file]);
                $lastId = $pdo->lastInsertId();
                $sql = "SELECT * FROM curricula WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$lastId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    foreach ($row as $key => $value) {
                        if ($value === null) $row[$key] = "";
                    }
                }
                $response['data'] = $row;
                $response['success'] = true;
                $response['message'] = 'CV caricato con successo!';
            } catch (PDOException $e) {
                $response['message'] = 'Errore del database.';
                error_log($e->getMessage());
            }
        } else {
            $response['message'] = 'Errore durante il caricamento del file.';
        }
    } else {
        $response['message'] = 'Errore nell\'upload del file.';
    }
    echo json_encode($response);
}
?>
