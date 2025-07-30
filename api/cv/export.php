<?php
session_start();
require_once '../../core/db_connection.php';

// Verifica presenza libreria FPDF
if (!file_exists('../../libraries/fpdf/fpdf.php')) {
    die('Errore: Libreria FPDF non trovata in /libraries/fpdf/');
}
require_once '../../libraries/fpdf/fpdf.php';

if (!isset($_SESSION['user_id'])) { 
    die('Accesso negato.'); 
}

$user_id = $_SESSION['user_id'];

// Recupera dati utente e relative esperienze
try {
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    $exp_f_stmt = $pdo->prepare("SELECT * FROM esperienze_formative WHERE user_id = ? ORDER BY data_inizio DESC");
    $exp_f_stmt->execute([$user_id]);
    $esperienze_formative = $exp_f_stmt->fetchAll(PDO::FETCH_ASSOC);

    $exp_l_stmt = $pdo->prepare("SELECT * FROM esperienze_lavorative WHERE user_id = ? ORDER BY data_inizio DESC");
    $exp_l_stmt->execute([$user_id]);
    $esperienze_lavorative = $exp_l_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Errore nel recupero dei dati.');
}

if (!$user) { 
    die('Utente non trovato.'); 
}

// Classe per generare CV stile Europass
class EuropassCV extends FPDF
{
    private $user;
    private $expLavorative;
    private $expFormative;
    private $leftColumnWidth = 70;
    private $rightColumnWidth = 120;

    function __construct($user, $lavorative, $formative) {
        parent::__construct();
        $this->user = $user;
        $this->expLavorative = $lavorative;
        $this->expFormative = $formative;
    }

    // Intestazione: colonna blu, foto profilo, nome
    function Header() {
        if ($this->PageNo() === 1) {
            $this->SetFillColor(0, 51, 153);
            $this->Rect(0, 0, $this->leftColumnWidth, 297, 'F');
            $imgHeight = 30;
            $imgWidth = 25;
            $imgY = 10;
            if (!empty($this->user['profile_picture'])) {
                $picturePath = '../../uploads/profile_pics/' . $this->user['profile_picture'];
                if (file_exists($picturePath)) {
                    try {
                        list($origWidth, $origHeight) = getimagesize($picturePath);
                        $ratio = min($imgWidth / $origWidth, $imgHeight / $origHeight);
                        $finalWidth = $origWidth * $ratio;
                        $finalHeight = $origHeight * $ratio;
                        $this->Image($picturePath, 15, $imgY, $finalWidth, $finalHeight, '', '', '', false, 300, '', false, false, 0, true, false, false, false);
                        $imgY += $finalHeight + 5;
                    } catch (Exception $e) {
                        error_log('Errore caricamento immagine profilo: ' . $e->getMessage());
                    }
                }
            }
            $nameY = $imgY;
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 16);
            $this->SetXY(10, $nameY);
            $this->Cell($this->leftColumnWidth - 20, 12, $this->fix_text($this->user['nome'] . ' ' . $this->user['cognome']), 0, 1, 'L');
            $this->SetTextColor(0, 0, 0);
        }
    }

    // Piè di pagina: data generazione e privacy
    function Footer() {
        $pageHeight = $this->GetPageHeight();
        $footerY = $pageHeight - 15;
        $this->SetY($footerY);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(255, 255, 255); // bianco su blu
        $this->SetXY(0, $footerY);
        $this->Cell($this->leftColumnWidth, 10, 'Curriculum generato il ' . date('d/m/Y'), 0, 0, 'C');
        $this->SetTextColor(128, 128, 128); // grigio su bianco
        $this->SetXY($this->leftColumnWidth, $footerY);
        $this->SetFont('Arial', 'I', 7);
        $privacy = "Autorizzo il trattamento dei miei dati personali ai sensi del D.Lgs. 196/2003\ne del GDPR (Regolamento UE 2016/679) ai fini di selezione del personale.";
        $this->MultiCell($this->rightColumnWidth, 4, $privacy, 0, 'C');
    }

    // Sezione colonna sinistra (contatti, profilo)
    function AddLeftSection($title, $content) {
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        $this->SetX(10);
        $this->Cell($this->leftColumnWidth - 20, 8, $this->fix_text($title), 0, 1, 'L');
        $this->SetFont('Arial', '', 9);
        $this->SetX(10);
        if (is_array($content)) {
            foreach ($content as $item) {
                $this->SetX(10);
                $this->Cell($this->leftColumnWidth - 20, 5, $this->fix_text($item), 0, 1, 'L');
            }
        } else {
            $this->MultiCell($this->leftColumnWidth - 20, 5, $this->fix_text($content), 0, 'L');
        }
        $this->Ln(4);
    }

    // Sezione colonna destra (esperienze)
    function AddRightSection($title, $items = []) {
        $this->SetTextColor(0, 51, 153);
        $this->SetFont('Arial', 'B', 13);
        $this->SetX($this->leftColumnWidth + 10);
        $this->Cell($this->rightColumnWidth - 20, 8, $this->fix_text($title), 0, 1, 'L');
        $this->SetTextColor(0, 0, 0);
        foreach ($items as $item) {
            $this->AddExperienceItem($item);
        }
        $this->Ln(6);
    }

    // Singola esperienza (lavorativa o formativa)
    function AddExperienceItem($exp) {
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(0, 51, 153);
        $this->SetX($this->leftColumnWidth + 10);
        $date_start = isset($exp['data_inizio']) ? date('m/Y', strtotime($exp['data_inizio'])) : '';
        $date_end = isset($exp['data_fine']) && $exp['data_fine'] ? date('m/Y', strtotime($exp['data_fine'])) : 'Presente';
        $date_range = $date_start . ' - ' . $date_end;
        $this->Cell($this->rightColumnWidth - 20, 5, $date_range, 0, 1, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(0, 0, 0);
        $this->SetX($this->leftColumnWidth + 10);
        $title = isset($exp['posizione']) ? $exp['posizione'] : (isset($exp['titolo']) ? $exp['titolo'] : '');
        $this->Cell($this->rightColumnWidth - 20, 5, $this->fix_text($title), 0, 1, 'L');
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(80, 80, 80);
        $this->SetX($this->leftColumnWidth + 10);
        $company = isset($exp['azienda']) ? $exp['azienda'] : (isset($exp['istituto']) ? $exp['istituto'] : (isset($exp['istituzione']) ? $exp['istituzione'] : ''));
        if ($company) {
            $this->Cell($this->rightColumnWidth - 20, 5, $this->fix_text($company), 0, 1, 'L');
        }
        if (isset($exp['descrizione']) && $exp['descrizione']) {
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(60, 60, 60);
            $this->SetX($this->leftColumnWidth + 10);
            $this->MultiCell($this->rightColumnWidth - 20, 4, $this->fix_text($exp['descrizione']), 0, 'L');
        }
        $this->Ln(4);
    }

    // Crea la pagina del CV
    function CreateCV() {
        $this->AddPage();
        $this->SetY(60);
        $personalInfo = [];
        if ($this->user['email']) $personalInfo[] = 'Email: ' . $this->user['email'];
        if ($this->user['telefono']) $personalInfo[] = 'Tel: ' . $this->user['telefono'];
        if ($this->user['data_nascita']) $personalInfo[] = 'Nato il: ' . date('d/m/Y', strtotime($this->user['data_nascita']));
        if ($this->user['indirizzo']) $personalInfo[] = 'Indirizzo: ' . $this->user['indirizzo'];
        if ($this->user['citta'] || $this->user['cap']) {
            $location = trim($this->user['cap'] . ' ' . $this->user['citta']);
            if ($location) $personalInfo[] = $location;
        }
        $this->AddLeftSection('CONTATTI', $personalInfo);
        if ($this->user['sommario']) {
            $this->AddLeftSection('PROFILO', $this->user['sommario']);
        }
        $this->SetY(20); // Inizio colonna destra
        if (!empty($this->expLavorative)) {
            $this->AddRightSection('ESPERIENZA LAVORATIVA', $this->expLavorative);
        }
        if (!empty($this->expFormative)) {
            $this->AddRightSection('ISTRUZIONE E FORMAZIONE', $this->expFormative);
        }
    }

    // Decodifica testo in UTF-8 per FPDF
    private function fix_text($text) {
        return utf8_decode($text);
    }
}

// Genera e salva il PDF, aggiorna DB e restituisce info curriculum
try {
    $pdf = new EuropassCV($user, $esperienze_lavorative, $esperienze_formative);
    $pdf->CreateCV();
    $real_user_id = $user['id'];
    $original_filename = 'CV_' . $user['nome'] . '_' . $user['cognome'] . '_' . date('Y-m-d') . '.pdf';
    $saved_filename = 'cv_' . $real_user_id . '_' . time() . '.pdf';
    $curricula_dir = 'uploads/cvs/';
    if (!file_exists($curricula_dir)) {
        mkdir($curricula_dir, 0755, true);
    }
    $file_path = $curricula_dir . $saved_filename;
    $pdf->Output('F', "../../" . $file_path);
    if (!file_exists("../../" . $file_path)) {
        error_log('Errore: PDF non creato in ' . $file_path);
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Errore nella generazione del PDF. File non creato.']);
        exit;
    }
    $insert_stmt = $pdo->prepare(
        "INSERT INTO curricula (user_id, nome_originale, nome_file, file_path, tipo, uploaded_at) 
         VALUES (?, ?, ?, ?, 'generato', NOW())"
    );
    $insert_stmt->execute([$real_user_id, $original_filename, $saved_filename, $file_path]);
    $cv_id = $pdo->lastInsertId();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'curriculum' => [
            'id' => $cv_id,
            'nome_originale' => $original_filename,
            'nome_file' => $saved_filename,
            'file_path' => $file_path,
            'tipo' => 'generato',
            'uploaded_at' => date('Y-m-d H:i:s'),
            'download_url' => '../api/cv/download.php?id=' . $cv_id
        ]
    ]);
    exit;
} catch (Exception $e) {
    error_log("Errore generazione PDF: " . $e->getMessage());
    die('Errore nella generazione del PDF.');
}
?>
