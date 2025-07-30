<?php
session_start();
require_once '../core/db_connection.php';

// Consenti accesso solo agli admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    die('Accesso negato.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('ID utente non valido.');
}

$user_id = (int)$_GET['id'];

// Formatta un intervallo di date
function formatDateRange($start, $end) {
    $start_date = date('M Y', strtotime($start));
    $end_date = $end ? date('M Y', strtotime($end)) : 'Presente';
    return $start_date . ' - ' . $end_date;
}

try {
    // Carica dati utente
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo '<p class="text-danger">Utente non trovato.</p>';
        exit;
    }

    // Aggiungi gli stili per le moderne card
    echo '<style>
        .modern-card {
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .modern-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
    </style>';

    echo '<div class="row">';
    echo '<div class="col-md-6">';
    echo '<div class="modern-card p-3 mb-3">';
    echo '<h5 class="text-primary"><i class="bi bi-person-badge me-2"></i>Informazioni Personali</h5>';
    echo '<table class="table table-borderless">';
    echo '<tr><td class="fw-semibold">Nome:</td><td>' . htmlspecialchars($user['nome']) . '</td></tr>';
    echo '<tr><td class="fw-semibold">Cognome:</td><td>' . htmlspecialchars($user['cognome']) . '</td></tr>';
    echo '<tr><td class="fw-semibold">Email:</td><td>' . htmlspecialchars($user['email']) . '</td></tr>';
    echo '<tr><td class="fw-semibold">Telefono:</td><td>' . htmlspecialchars($user['telefono'] ?: 'Non specificato') . '</td></tr>';
    echo '<tr><td class="fw-semibold">Data Nascita:</td><td>' . ($user['data_nascita'] ? date('d/m/Y', strtotime($user['data_nascita'])) : 'Non specificata') . '</td></tr>';
    echo '<tr><td class="fw-semibold">Indirizzo:</td><td>' . htmlspecialchars($user['indirizzo'] ?: 'Non specificato') . '</td></tr>';
    echo '<tr><td class="fw-semibold">Città:</td><td>' . htmlspecialchars($user['citta'] ?: 'Non specificata') . '</td></tr>';
    echo '<tr><td class="fw-semibold">CAP:</td><td>' . htmlspecialchars($user['cap'] ?: 'Non specificato') . '</td></tr>';
    echo '<tr><td class="fw-semibold">Sommario:</td><td>' . htmlspecialchars($user['sommario'] ? substr($user['sommario'], 0, 100) . '...' : 'Non specificato') . '</td></tr>';
    echo '<tr><td class="fw-semibold">Tipo Utente:</td><td><span class="badge bg-primary">' . ucfirst($user['user_type']) . '</span></td></tr>';
    echo '<tr><td class="fw-semibold">Registrato il:</td><td>' . date('d/m/Y H:i', strtotime($user['created_at'])) . '</td></tr>';
    if ($user['updated_at']) {
        echo '<tr><td class="fw-semibold">Ultimo aggiornamento:</td><td>' . date('d/m/Y H:i', strtotime($user['updated_at'])) . '</td></tr>';
    }
    echo '</table>';
    
    // Pulsanti di azione per l'utente
    $user_no_password = $user;
    unset($user_no_password['password']);
    echo '<div class="d-grid gap-2">';
    echo '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adminEditUserModal" data-user-data="' . htmlspecialchars(json_encode($user_no_password), ENT_QUOTES, 'UTF-8') . '">';
    echo '<i class="bi bi-pencil-square me-1"></i>Modifica profilo';
    echo '</button>';
    echo '<button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#adminChangePasswordModal" data-user-id="' . $user['id'] . '" data-user-name="' . htmlspecialchars($user['nome'] . ' ' . $user['cognome']) . '">';
    echo '<i class="bi bi-key me-1"></i>Cambia password';
    echo '</button>';
    echo '<button class="btn btn-danger admin-delete-btn" data-id="' . $user['id'] . '" data-type="user">';
    echo '<i class="bi bi-trash me-1"></i>Elimina utente';
    echo '</button>';
    echo '</div>';
    echo '</div>';

    // Foto profilo se presente
    if (!empty($user['profile_picture'])) {
        echo '<div class="modern-card p-3 text-center">';
        echo '<h6 class="text-primary"><i class="bi bi-image me-2"></i>Foto Profilo</h6>';
        echo '<img src="../uploads/profile_pics/' . htmlspecialchars($user['profile_picture']) . '" alt="Foto Profilo" class="img-thumbnail rounded-circle" style="max-width: 120px; max-height: 120px;">';
        echo '</div>';
    }

    echo '</div>';
    echo '<div class="col-md-6">';

        // CV caricati dall'utente
    $stmt = $pdo->prepare("SELECT * FROM curricula WHERE user_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$user_id]);
    $curricula = $stmt->fetchAll();

    echo '<div class="modern-card p-3">';
    echo '<h5 class="text-success"><i class="bi bi-file-earmark-pdf me-2"></i>Curricula</h5>';
    if (count($curricula) > 0) {
        echo '<div class="list-group list-group-flush">';
        foreach ($curricula as $cv) {
            $is_generated = $cv['tipo'] === 'generato' ? true : false;
            echo '<div class="list-group-item d-flex justify-content-between align-items-center border-0">';
            echo '<div class="d-flex align-items-center flex-grow-1">';
            if ($is_generated) {
                echo '<i class="bi bi-robot text-primary me-3 fs-5"></i>';
            } else {
                echo '<i class="bi bi-file-earmark-pdf text-danger me-3 fs-5"></i>';
            }
            echo '<div>';
            $cv_name = $cv['nome_originale'] ?: $cv['nome_file'] ?: 'CV senza nome';
            echo '<h6 class="mb-1">' . htmlspecialchars($cv_name) . '</h6>';
            if ($is_generated) {
                echo '<span class="badge bg-info">Generato</span>';
            } else {
                echo '<span class="badge bg-secondary">Caricato</span>';
            }
            echo '<div class="small text-muted mt-1">Caricato: ' . date('d/m/Y H:i', strtotime($cv['uploaded_at'])) . '</div>';
            if ($cv['file_path']) {
                echo '<div class="small text-muted">Dimensione: ' . (file_exists('../' . $cv['file_path']) ? number_format(filesize('../' . $cv['file_path']) / 1024, 1) . ' KB' : 'File non trovato') . '</div>';
            }
            echo '</div></div>';
            echo '<div class="btn-group" role="group">';
            echo '<a href="../api/cv/download.php?id=' . $cv['id'] . '" class="btn btn-info btn-sm" title="Scarica">';
            echo '<i class="bi bi-download"></i>';
            echo '</a>';
            echo '<button class="btn btn-danger btn-sm admin-delete-btn" data-id="' . $cv['id'] . '" data-type="cv" title="Elimina">';
            echo '<i class="bi bi-trash"></i>';
            echo '</button>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="text-center py-4">';
        echo '<i class="bi bi-file-earmark-pdf text-muted" style="font-size: 3rem;"></i>';
        echo '<p class="text-muted mt-3">Nessun curriculum caricato</p>';
        echo '</div>';
    }
    echo '</div>';

    echo '</div>';
    echo '</div>';

    // Sezione GDPR e Privacy
    echo '<div class="modern-card p-3 mt-3">';
    echo '<h5 class="text-danger"><i class="bi bi-shield-exclamation me-2"></i>Gestione GDPR e Privacy</h5>';
    echo '<div class="row">';
    echo '<div class="col-md-6">';
    echo '<table class="table table-borderless table-sm">';
    echo '<tr><td class="fw-semibold">Consenso dati:</td><td><span class="badge bg-success">Fornito alla registrazione</span></td></tr>';
    echo '</table>';
    echo '</div>';
    echo '<div class="col-md-6">';
    echo '<div class="alert alert-info mb-0">';
    echo '<small><i class="bi bi-info-circle me-1"></i>';
    echo 'I dati dell\'utente sono accessibili agli amministratori esclusivamente per scopi di supporto tecnico e gestione delle richieste in conformità al GDPR.';
    echo '</small>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="mt-3 d-flex gap-2">';
    echo '<button class="btn btn-warning btn-sm" onclick="exportUserData(' . $user_id . ')">';
    echo '<i class="bi bi-download me-1"></i>Esporta dati (JSON)';
    echo '</button>';
    echo '<button class="btn btn-danger btn-sm" onclick="requestDataDeletion(' . $user_id . ')">';
    echo '<i class="bi bi-trash me-1"></i>Elimina i dati dell\'utente';
    echo '</button>';
    echo '</div>';
    echo '</div>';

    // Esperienze lavorative
    $stmt = $pdo->prepare("SELECT * FROM esperienze_lavorative WHERE user_id = ? ORDER BY data_inizio DESC");
    $stmt->execute([$user_id]);
    $exp_lavorative = $stmt->fetchAll();

    if (count($exp_lavorative) > 0) {
        echo '<div class="modern-card p-3 mt-3">';
        echo '<h5 class="text-warning"><i class="bi bi-briefcase me-2"></i>Esperienze Lavorative (' . count($exp_lavorative) . ')</h5>';
        echo '<div class="list-group list-group-flush">';
        foreach ($exp_lavorative as $exp) {
            $exp_no_userid = $exp;
            unset($exp_no_userid['user_id']);
            echo '<div class="list-group-item border-0 border-bottom">';
            echo '<div class="d-flex justify-content-between align-items-start">';
            echo '<div class="flex-grow-1">';
            echo '<h6 class="mb-2 text-primary">' . htmlspecialchars($exp['posizione']) . ' - ' . htmlspecialchars($exp['azienda']) . '</h6>';
            echo '<p class="mb-2 text-muted">' . htmlspecialchars($exp['descrizione'] ?: 'Nessuna descrizione') . '</p>';
            echo '<small class="text-muted"><i class="bi bi-calendar3 me-1"></i>' . formatDateRange($exp['data_inizio'], $exp['data_fine']) . '</small>';
            echo '</div>';
            echo '<div class="btn-group ms-2" role="group">';
            echo '<button class="btn btn-danger btn-sm admin-delete-btn" data-id="' . $exp['id'] . '" data-type="lavorativa" title="Elimina">';
            echo '<i class="bi bi-trash"></i>';
            echo '</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    // Esperienze formative
    $stmt = $pdo->prepare("SELECT * FROM esperienze_formative WHERE user_id = ? ORDER BY data_inizio DESC");
    $stmt->execute([$user_id]);
    $exp_formative = $stmt->fetchAll();

    if (count($exp_formative) > 0) {
        echo '<div class="modern-card p-3 mt-3">';
        echo '<h5 class="text-info"><i class="bi bi-mortarboard me-2"></i>Esperienze Formative (' . count($exp_formative) . ')</h5>';
        echo '<div class="list-group list-group-flush">';
        foreach ($exp_formative as $exp) {
            $exp_no_userid = $exp;
            unset($exp_no_userid['user_id']);
            echo '<div class="list-group-item border-0 border-bottom">';
            echo '<div class="d-flex justify-content-between align-items-start">';
            echo '<div class="flex-grow-1">';
            echo '<h6 class="mb-2 text-primary">' . htmlspecialchars($exp['titolo']) . ' - ' . htmlspecialchars($exp['istituto']) . '</h6>';
            echo '<p class="mb-2 text-muted">' . htmlspecialchars($exp['descrizione'] ?: 'Nessuna descrizione') . '</p>';
            echo '<small class="text-muted"><i class="bi bi-calendar3 me-1"></i>' . formatDateRange($exp['data_inizio'], $exp['data_fine']) . '</small>';
            echo '</div>';
            echo '<div class="btn-group ms-2" role="group">';
            echo '<button class="btn btn-danger btn-sm admin-delete-btn" data-id="' . $exp['id'] . '" data-type="formativa" title="Elimina">';
            echo '<i class="bi bi-trash"></i>';
            echo '</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

} catch (PDOException $e) {
    error_log("Errore PDO in get_user_details.php: " . $e->getMessage());
    echo '<p class="text-danger">Errore nel caricamento dei dettagli: ' . htmlspecialchars($e->getMessage()) . '</p>';
} catch (Exception $e) {
    error_log("Errore generico in get_user_details.php: " . $e->getMessage());
    echo '<p class="text-danger">Errore: ' . htmlspecialchars($e->getMessage()) . '</p>';
} catch (Exception $e) {
    error_log("Errore generico in get_user_details.php: " . $e->getMessage());
    echo '<p class="text-danger">Errore: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
