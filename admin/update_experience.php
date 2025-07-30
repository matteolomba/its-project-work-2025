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
        $experience_id = $_POST['id'];
        $type = $_POST['type'];
        $descrizione = trim($_POST['descrizione']);
        $data_inizio = $_POST['data_inizio'];
        $data_fine = $_POST['data_fine'];

        // Validazione base
        if (empty($descrizione) || empty($data_inizio)) {
            $response['message'] = 'Descrizione e data di inizio sono obbligatorie.';
            echo json_encode($response); exit();
        }

        if ($type === 'lavorativa') {
            $azienda = trim($_POST['azienda']);
            $posizione = trim($_POST['posizione']);
            
            if (empty($azienda) || empty($posizione)) {
                $response['message'] = 'Azienda e posizione sono obbligatorie per le esperienze lavorative.';
                echo json_encode($response); exit();
            }

            $sql = "UPDATE esperienze_lavorative SET descrizione = ?, data_inizio = ?, data_fine = ?, azienda = ?, posizione = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$descrizione, $data_inizio, $data_fine, $azienda, $posizione, $experience_id]);

        } elseif ($type === 'formativa') {
            $istituto = trim($_POST['istituto']);
            $titolo = trim($_POST['titolo']);
            
            if (empty($istituto) || empty($titolo)) {
                $response['message'] = 'Istituto e titolo sono obbligatori per le esperienze formative.';
                echo json_encode($response); exit();
            }

            $sql = "UPDATE esperienze_formative SET descrizione = ?, data_inizio = ?, data_fine = ?, istituto = ?, titolo = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$descrizione, $data_inizio, $data_fine, $istituto, $titolo, $experience_id]);
        }

        // Recupera i dati aggiornati per l'aggiornamento dinamico
        if ($type === 'lavorativa') {
            $stmt_updated = $pdo->prepare("SELECT * FROM esperienze_lavorative WHERE id = ?");
        } else {
            $stmt_updated = $pdo->prepare("SELECT * FROM esperienze_formative WHERE id = ?");
        }
        $stmt_updated->execute([$experience_id]);
        $updated_exp = $stmt_updated->fetch();
        
        $response['success'] = true;
        $response['message'] = 'Esperienza aggiornata con successo!';
        $response['data'] = $updated_exp;

    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response['message'] = 'Errore durante l\'aggiornamento.';
    }
}

echo json_encode($response);
?>
