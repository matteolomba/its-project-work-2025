<?php
session_start();
require_once '../../core/db_connection.php';
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Richiesta non valida.'];

// Verifica autenticazione utente
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Accesso negato.';
    echo json_encode($response); exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $id = $_POST['id'];
    $type = $_POST['type'];
    $data_inizio = !empty($_POST['data_inizio']) ? $_POST['data_inizio'] : null;
    $data_fine = !empty($_POST['data_fine']) ? $_POST['data_fine'] : null;
    $descrizione = trim($_POST['descrizione']) ?: null;

    try {
        if ($type == 'formativa' && !empty($_POST['istituto']) && !empty($_POST['titolo']) && !empty($_POST['id'])) {
            $descrizione = isset($_POST['descrizione']) ? $_POST['descrizione'] : "";
            $sql = "UPDATE esperienze_formative SET istituto = ?, titolo = ?, descrizione = ?, data_inizio = ?, data_fine = ? WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['istituto'], $_POST['titolo'], $descrizione, $data_inizio, $data_fine, $_POST['id'], $user_id]);
            $sql = "SELECT * FROM esperienze_formative WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                foreach ($row as $key => $value) {
                    if ($value === null) $row[$key] = "";
                }
            }
            $response['data'] = $row;
            $response['success'] = true;
            $response['message'] = 'Esperienza formativa aggiornata!';
            $response['redirect'] = 'user/home.php';
        } elseif ($type == 'lavorativa' && !empty($_POST['azienda']) && !empty($_POST['posizione']) && !empty($_POST['id'])) {
            $descrizione = isset($_POST['descrizione']) ? $_POST['descrizione'] : "";
            $sql = "UPDATE esperienze_lavorative SET azienda = ?, posizione = ?, descrizione = ?, data_inizio = ?, data_fine = ? WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['azienda'], $_POST['posizione'], $descrizione, $data_inizio, $data_fine, $_POST['id'], $user_id]);
            $sql = "SELECT * FROM esperienze_lavorative WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                foreach ($row as $key => $value) {
                    if ($value === null) $row[$key] = "";
                }
            }
            $response['data'] = $row;
            $response['success'] = true;
            $response['message'] = 'Esperienza lavorativa aggiornata!';
            $response['redirect'] = 'user/home.php';
        } else {
            $response['message'] = 'Dati mancanti per aggiornare l\'esperienza.';
            echo json_encode($response); exit();
        }
    } catch (PDOException $e) {
        $response['message'] = 'Errore del database.';
        error_log($e->getMessage());
    }
    echo json_encode($response);
}
?>
