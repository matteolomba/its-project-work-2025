<?php
session_start();
require_once '../core/db_connection.php';
define('INTERNAL_ACCESS', true);

// Controlla che l'utente sia autenticato e sia di tipo 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../pages/auth/login.html');
    exit();
}

// Carica i dati dell'utente loggato
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ../pages/auth/login.html');
    exit();
}

// Carica esperienze lavorative dell'utente
$stmt = $pdo->prepare("SELECT * FROM esperienze_lavorative WHERE user_id = ? ORDER BY data_inizio DESC");
$stmt->execute([$_SESSION['user_id']]);
$esperienze_lavorative = $stmt->fetchAll();

// Carica esperienze formative dell'utente
$stmt = $pdo->prepare("SELECT * FROM esperienze_formative WHERE user_id = ? ORDER BY data_inizio DESC");
$stmt->execute([$_SESSION['user_id']]);
$esperienze_formative = $stmt->fetchAll();

// Formatta una data in formato italiano
function formatDate($date) {
    return $date ? date('d/m/Y', strtotime($date)) : 'N/A';
}
// Restituisce un intervallo di date formattato
function formatDateRange($start, $end) {
    $start_formatted = $start ? date('d/m/Y', strtotime($start)) : 'N/A';
    $end_formatted = $end ? date('d/m/Y', strtotime($end)) : 'In corso';
    return $start_formatted . ' - ' . $end_formatted;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utente - Gestione CV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .btn-primary { background-color: #0d6efd !important; border-color: #0d6efd !important; color: white !important; }
        .btn-success { background-color: #198754 !important; border-color: #198754 !important; color: white !important; }
        .btn-warning { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #000 !important; }
        .btn-danger { background-color: #dc3545 !important; border-color: #dc3545 !important; color: white !important; }
        .btn-info { background-color: #0dcaf0 !important; border-color: #0dcaf0 !important; color: #000 !important; }
        .btn-secondary { background-color: #6c757d !important; border-color: #6c757d !important; color: white !important; }
        
        .card-header { 
            background: #4a69bd; 
            color: white; 
            border-bottom: none; 
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        .feature-card { 
            border: none; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); 
            margin-bottom: 1.5rem;
        }
        
        .profile-picture-container { position: relative; display: inline-block; cursor: pointer; }
        .profile-picture-overlay { 
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s ease; border-radius: 50%;
        }
        .profile-picture-container:hover .profile-picture-overlay { opacity: 1; }
        
        .experience-item { 
            border-left: 4px solid #0d6efd; 
            background: #f8f9fa; 
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-radius: 6px;
        }
        .navbar { 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            padding: 1rem 0;
        }
        
        /* Migliori spaziature per i modal */
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        /* Allineamento bottoni */
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Consistenza nelle card */
        .card-body {
            padding: 1.5rem;
        }
        
        .list-group-item {
            padding: 1rem;
            border-color: rgba(0,0,0,0.08);
        }
        
        /* Miglioramenti input file */
        input[type="file"] {
            border: 2px dashed #dee2e6;
            border-radius: 0.375rem;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        input[type="file"]:hover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        
        input[type="file"]:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            background-color: #fff;
        }
        
        /* Validazione date */
        .alert-danger.d-none {
            display: none !important;
        }
        
        /* Animazioni per aggiornamenti dinamici */
        .experience-item {
            transition: all 0.3s ease;
        }
        
        .experience-item.updating {
            opacity: 0.7;
            transform: scale(0.98);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-person-workspace"></i> Dashboard CV</a>
            <div class="navbar-nav ms-auto">
                <span id="benvenuto" class="navbar-text me-3">Benvenuto, <?= htmlspecialchars($user['nome']) ?>!</span>
                <a href="../api/auth/logout.php" class="btn btn-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-4">
        <div id="message-container"></div>
        

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card feature-card h-100">
                    <div class="card-header text-center">
                        <h5><i class="bi bi-person-circle"></i> Il Mio Profilo</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="profile-picture-container mb-3" data-bs-toggle="modal" data-bs-target="#photoModal">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img id="profile-image" src="../uploads/profile_pics/<?= htmlspecialchars($user['profile_picture']) ?>" 
                                     alt="Foto Profilo" class="rounded-circle" width="120" height="120" style="object-fit: cover;">
                            <?php else: ?>
                                <div id="profile-image" class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                     style="width: 120px; height: 120px;">
                                    <i class="bi bi-person-fill text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="profile-picture-overlay rounded-circle">
                                <i class="bi bi-camera-fill text-white" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <h5><?= htmlspecialchars($user['nome'] . ' ' . $user['cognome']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                        <?php if ($user['telefono']): ?>
                            <p class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($user['telefono']) ?></p>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#userInfoModal">
                                <i class="bi bi-info-circle"></i> Visualizza Tutte le Informazioni
                            </button>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="bi bi-pencil-square"></i> Modifica Profilo
                            </button>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#emailModal">
                                <i class="bi bi-envelope-at"></i> Cambia Email
                            </button>
                            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#passwordModal">
                                <i class="bi bi-shield-lock"></i> Cambia Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            

            <div class="col-md-8 mb-3">
                <div class="card feature-card h-100">
                    <div class="card-header">
                        <h5><i class="bi bi-file-earmark-arrow-up"></i> Carica Curriculum</h5>
                    </div>
                    <div class="card-body">
                        <form id="cv-upload-form" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="cv_file" class="form-label fw-bold">Seleziona il tuo Curriculum (PDF)</label>
                                <input type="file" class="form-control" id="cv_file" name="cv_file" accept=".pdf" required style="padding: 0.375rem 0.75rem;">
                                <div class="form-text">Massimo 10MB, formato PDF</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100" id="upload-btn">
                                <i class="bi bi-upload"></i> Carica Curriculum
                            </button>
                        </form>
                        
                        <div id="cv-upload-results" class="mt-4"></div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card feature-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-briefcase"></i> Esperienze Lavorative</h5>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#experienceModal" 
                                data-type="lavorativa">
                            <i class="bi bi-plus-circle"></i> Aggiungi
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($esperienze_lavorative)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-briefcase" style="font-size: 3rem;"></i>
                                <p class="mt-2">Nessuna esperienza lavorativa aggiunta</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($esperienze_lavorative as $exp): ?>
                                    <div class="list-group-item experience-item p-3 mb-2 rounded" 
                                         data-exp-id="<?= $exp['id'] ?>" 
                                         data-exp-data='<?= json_encode($exp) ?>'>
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($exp['posizione']) ?></h6>
                                                <p class="mb-1 text-primary"><?= htmlspecialchars($exp['azienda']) ?></p>
                                                <p class="mb-1"><?= htmlspecialchars($exp['descrizione']) ?></p>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-range"></i> 
                                                    <?= formatDateRange($exp['data_inizio'], $exp['data_fine']) ?>
                                                </small>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-primary edit-experience-btn" 
                                                        data-bs-toggle="modal" data-bs-target="#experienceModal" 
                                                        data-type="lavorativa">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-danger delete-btn" 
                                                        data-type="experience" data-id="<?= $exp['id'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card feature-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-mortarboard"></i> Formazione</h5>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#experienceModal" 
                                data-type="formativa">
                            <i class="bi bi-plus-circle"></i> Aggiungi
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($esperienze_formative)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-mortarboard" style="font-size: 3rem;"></i>
                                <p class="mt-2">Nessuna formazione aggiunta</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($esperienze_formative as $exp): ?>
                                    <div class="list-group-item experience-item p-3 mb-2 rounded" 
                                         data-exp-id="<?= $exp['id'] ?>" 
                                         data-exp-data='<?= json_encode($exp) ?>'>
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($exp['titolo']) ?></h6>
                                                <p class="mb-1 text-success"><?= htmlspecialchars($exp['istituto']) ?></p>
                                                <p class="mb-1"><?= htmlspecialchars($exp['descrizione']) ?></p>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-range"></i> 
                                                    <?= formatDateRange($exp['data_inizio'], $exp['data_fine']) ?>
                                                </small>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-success edit-experience-btn" 
                                                        data-bs-toggle="modal" data-bs-target="#experienceModal" 
                                                        data-type="formativa">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-danger delete-btn" 
                                                        data-type="experience" data-id="<?= $exp['id'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        

        <div class="row mb-4">
            <div class="col-12">
                <div class="card feature-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-file-earmark-pdf"></i> I Miei Curriculum</h5>
                        <div class="btn-group" role="group">
                            <button id="generate-cv-btn" class="btn btn-primary btn-sm" type="button">
                                <i class="bi bi-file-earmark-pdf"></i> Genera CV dal profilo in PDF
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="curriculum-list">
                            <!-- La lista dei curriculum sarà gestita e aggiornata dinamicamente da JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        

        <div class="row mb-4">
            <div class="col-12">
                <div class="card feature-card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="bi bi-shield-check"></i> Privacy e Gestione Dati (GDPR)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="bi bi-info-circle"></i> I Tuoi Diritti</h6>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle text-success me-2"></i> Diritto di accesso ai tuoi dati</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i> Diritto di rettifica</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i> Diritto alla cancellazione</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i> Diritto alla portabilità</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-download"></i> Gestione Dati</h6>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-info" onclick="exportPersonalData()">
                                        <i class="bi bi-box-arrow-down"></i> Esporta i Miei Dati
                                    </button>
                                    <button class="btn btn-warning" onclick="requestDataDeletion()">
                                        <i class="bi bi-exclamation-triangle"></i> Richiedi Cancellazione Account
                                    </button>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="bi bi-shield-lock"></i> I tuoi dati sono protetti secondo il GDPR.
                                        <a href="../pages/legal/privacy.html" target="_blank">Privacy Policy</a>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="userInfoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-lines-fill"></i> Tutte le Informazioni Utente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <h6><i class="bi bi-person-badge"></i> Dati Anagrafici</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nome:</strong></td>
                                    <td id="info-nome"><?= htmlspecialchars($user['nome']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Cognome:</strong></td>
                                    <td id="info-cognome"><?= htmlspecialchars($user['cognome']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td id="info-email"><?= htmlspecialchars($user['email']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Telefono:</strong></td>
                                    <td id="info-telefono"><?= htmlspecialchars($user['telefono'] ?: 'Non specificato') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Data di Nascita:</strong></td>
                                    <td id="info-data-nascita"><?= $user['data_nascita'] ? date('d/m/Y', strtotime($user['data_nascita'])) : 'Non specificata' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Città:</strong></td>
                                    <td id="info-citta"><?= htmlspecialchars($user['citta'] ?: 'Non specificata') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>CAP:</strong></td>
                                    <td id="info-cap"><?= htmlspecialchars($user['cap'] ?: 'Non specificato') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Indirizzo:</strong></td>
                                    <td id="info-indirizzo"><?= htmlspecialchars($user['indirizzo'] ?: 'Non specificato') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Sommario:</strong></td>
                                    <td id="info-sommario"><?= htmlspecialchars($user['sommario'] ?: 'Non specificato') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-shield-check"></i> Informazioni Account</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Registrato il:</strong></td>
                                    <td><?= isset($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'N/A' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Ultimo Aggiornamento:</strong></td>
                                    <td id="info-ultimo-aggiornamento"><?= isset($user['updated_at']) ? date('d/m/Y H:i', strtotime($user['updated_at'])) : 'N/A' ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-gear"></i> Modifica Profilo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-error-container"></div>
                    <form id="profileForm" action="../api/users/update.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome *</label>
                                <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($user['nome']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cognome *</label>
                                <input type="text" name="cognome" class="form-control" value="<?= htmlspecialchars($user['cognome']) ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefono</label>
                                <input type="tel" name="telefono" class="form-control" value="<?= htmlspecialchars($user['telefono']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data di Nascita</label>
                                <input type="date" name="data_nascita" class="form-control" 
                                       value="<?= $user['data_nascita'] ?>" 
                                       max="<?= date('Y-m-d', strtotime('-16 years')) ?>"
                                       min="<?= date('Y-m-d', strtotime('-100 years')) ?>">
                                <div class="form-text">Età minima: 16 anni</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Città</label>
                                <input type="text" name="citta" class="form-control" value="<?= htmlspecialchars($user['citta']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CAP</label>
                                <input type="text" name="cap" class="form-control" pattern="[0-9]{5}" 
                                       value="<?= htmlspecialchars($user['cap'] ?? '') ?>" 
                                       title="Inserire 5 cifre">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Indirizzo</label>
                            <input type="text" name="indirizzo" class="form-control" value="<?= htmlspecialchars($user['indirizzo']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sommario Professionale</label>
                            <textarea name="sommario" class="form-control" rows="4"><?= htmlspecialchars($user['sommario']) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Salva Modifiche
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="emailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-envelope-at"></i> Cambia Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-error-container"></div>
                    <form id="emailForm" action="../api/users/email.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Password Attuale *</label>
                            <input type="password" name="current_password" class="form-control" required>
                            <div class="form-text">Inserisci la tua password per confermare l'identità</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nuova Email *</label>
                            <input type="email" name="new_email" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-envelope-check"></i> Aggiorna Email
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-shield-lock"></i> Cambia Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-error-container"></div>
                    <form id="passwordForm" action="../api/users/password.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Password Attuale *</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nuova Password *</label>
                            <input type="password" name="new_password" class="form-control" minlength="6" required>
                            <div class="form-text">Minimo 6 caratteri</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Conferma Nuova Password *</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                        </div>
                        <button type="submit" class="btn btn-info w-100">
                            <i class="bi bi-shield-check"></i> Aggiorna Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-camera"></i> Aggiorna Foto Profilo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-error-container"></div>
                    <form id="profile-picture-form" action="../api/users/avatar.php" method="POST" enctype="multipart/form-data">
                        <div class="text-center mb-3">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="../uploads/profile_pics/<?= htmlspecialchars($user['profile_picture']) ?>" 
                                     alt="Foto Attuale" class="rounded-circle" width="100" height="100" style="object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 100px; height: 100px;">
                                    <i class="bi bi-person-fill text-white" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Seleziona Nuova Foto</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/*" required>
                            <div class="form-text">Formati supportati: JPG, PNG, GIF. Massimo 5MB.</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-upload"></i> Carica Foto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="experienceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="experienceModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-error-container"></div>
                    <form id="experienceForm" method="POST">
                        <input type="hidden" id="experienceId" name="id">
                        <input type="hidden" id="experienceType" name="type">
                        

                        <div class="mb-3">
                            <label class="form-label">Descrizione</label>
                            <textarea name="descrizione" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data Inizio</label>
                                <input type="date" name="data_inizio" class="form-control" required 
                                       min="1950-01-01" max="<?= date('Y-m-d') ?>"
                                       onchange="validateExperienceDates()">
                                <div class="form-text">Non può essere futura</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data Fine</label>
                                <input type="date" name="data_fine" class="form-control"
                                       min="1950-01-01" max="<?= date('Y-m-d') ?>"
                                       onchange="validateExperienceDates()">
                                <div class="form-text">Lascia vuoto se in corso</div>
                            </div>
                        </div>
                        <div id="date-validation-error" class="alert alert-danger d-none">
                            La data di fine deve essere successiva alla data di inizio.
                        </div>
                        

                        <div id="form-lavorativa-fields" class="d-none">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Posizione</label>
                                    <input type="text" id="modal-posizione" name="posizione" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Azienda</label>
                                    <input type="text" id="modal-azienda" name="azienda" class="form-control">
                                </div>
                            </div>
                        </div>
                        

                        <div id="form-formativa-fields" class="d-none">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Titolo</label>
                                    <input type="text" id="modal-titolo" name="titolo" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Istituto</label>
                                    <input type="text" id="modal-istituto" name="istituto" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100" id="experienceSubmitBtn">
                            <i class="bi bi-check-circle"></i> Salva
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="../assets/js/common.js"></script>
    <script type="module">
        import { initUserArea, handleUrlMessages, loadCurriculumList, generatePersonalCV } from '../assets/js/user.js';
        document.addEventListener('DOMContentLoaded', function () {
            handleUrlMessages();
            initUserArea();
            loadCurriculumList(); // Carica la lista curriculum all'avvio
            // Collega il bottone di generazione CV
            const generateBtn = document.getElementById('generate-cv-btn');
            if (generateBtn) {
                generateBtn.addEventListener('click', generatePersonalCV);
            }
            // Validazione form profilo
            const profileForm = document.getElementById('profileForm');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    let valid = true;
                    let errorMsg = '';
                    const nome = profileForm.nome.value.trim();
                    const cognome = profileForm.cognome.value.trim();
                    const data_nascita = profileForm.data_nascita.value;
                    const cap = profileForm.cap.value.trim();
                    if (!nome) { valid = false; errorMsg += 'Il nome è obbligatorio.<br>'; }
                    if (!cognome) { valid = false; errorMsg += 'Il cognome è obbligatorio.<br>'; }
                    if (data_nascita && !/^\d{4}-\d{2}-\d{2}$/.test(data_nascita)) {
                        valid = false; errorMsg += 'La data di nascita non è valida.<br>';
                    }
                    if (cap && !/^\d{5}$/.test(cap)) {
                        valid = false; errorMsg += 'Il CAP deve essere di 5 cifre.<br>';
                    }
                    if (!valid) {
                        e.preventDefault();
                        const errorContainer = document.querySelector('.modal-error-container');
                        if (errorContainer) {
                            errorContainer.innerHTML = `<div class='alert alert-danger'>${errorMsg}</div>`;
                        }
                    }
                });
            }

            // Validazione form esperienza
            const experienceForm = document.getElementById('experienceForm');
            if (experienceForm) {
                experienceForm.addEventListener('submit', function(e) {
                    let valid = true;
                    let errorMsg = '';
                    const descrizione = experienceForm.descrizione.value.trim();
                    const data_inizio = experienceForm.data_inizio.value;
                    const data_fine = experienceForm.data_fine.value;
                    const type = experienceForm.type.value;
                    // Campi specifici
                    let posizione = '', azienda = '', titolo = '', istituto = '';
                    if (type === 'lavorativa') {
                        posizione = experienceForm.posizione.value.trim();
                        azienda = experienceForm.azienda.value.trim();
                        if (!posizione) { valid = false; errorMsg += 'La posizione è obbligatoria.<br>'; }
                        if (!azienda) { valid = false; errorMsg += 'L\'azienda è obbligatoria.<br>'; }
                    } else {
                        titolo = experienceForm.titolo.value.trim();
                        istituto = experienceForm.istituto.value.trim();
                        if (!titolo) { valid = false; errorMsg += 'Il titolo è obbligatorio.<br>'; }
                        if (!istituto) { valid = false; errorMsg += 'L\'istituto è obbligatorio.<br>'; }
                    }
                    if (data_inizio && data_fine) {
                        const inizio = new Date(data_inizio);
                        const fine = new Date(data_fine);
                        if (fine <= inizio) {
                            valid = false;
                            errorMsg += 'La data di fine deve essere successiva alla data di inizio.<br>';
                        }
                    }
                    if (!valid) {
                        e.preventDefault();
                        const errorContainer = experienceForm.closest('.modal-body').querySelector('.modal-error-container');
                        if (errorContainer) {
                            errorContainer.innerHTML = `<div class='alert alert-danger'>${errorMsg}</div>`;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
