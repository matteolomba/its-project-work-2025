<?php
session_start();
require_once '../../core/db_connection.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Richiesta non valida.'];

// Verifica autenticazione utente
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Accesso negato.';
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['type'])) {
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];
    $data_inizio = !empty($_POST['data_inizio']) ? $_POST['data_inizio'] : null;
    $data_fine = !empty($_POST['data_fine']) ? $_POST['data_fine'] : null;

    try {
        if ($type == 'formativa' && !empty($_POST['istituto']) && !empty($_POST['titolo'])) {
            $descrizione = isset($_POST['descrizione']) ? $_POST['descrizione'] : "";
            $sql = "INSERT INTO esperienze_formative (user_id, istituto, titolo, descrizione, data_inizio, data_fine) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $_POST['istituto'], $_POST['titolo'], $descrizione, $data_inizio, $data_fine]);
            $lastId = $pdo->lastInsertId();
            $sql = "SELECT * FROM esperienze_formative WHERE id = ?";
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
            $response['message'] = 'Esperienza formativa aggiunta!';
            $response['redirect'] = 'user/home.php';
        } elseif ($type == 'lavorativa' && !empty($_POST['azienda']) && !empty($_POST['posizione'])) {
            $descrizione = isset($_POST['descrizione']) ? $_POST['descrizione'] : "";
            $sql = "INSERT INTO esperienze_lavorative (user_id, azienda, posizione, descrizione, data_inizio, data_fine) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $_POST['azienda'], $_POST['posizione'], $descrizione, $data_inizio, $data_fine]);
            $lastId = $pdo->lastInsertId();
            $sql = "SELECT * FROM esperienze_lavorative WHERE id = ?";
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
            $response['message'] = 'Esperienza lavorativa aggiunta!';
            $response['redirect'] = 'user/home.php';
        } else {
            $response['message'] = 'Dati mancanti per aggiungere l\'esperienza.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Errore del database.';
        error_log($e->getMessage());
    }
    echo json_encode($response);
}
?>
