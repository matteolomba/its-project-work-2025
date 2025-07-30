<?php
// Gestore per la cancellazione degli account
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/audit_logger.php';

class AccountDeleteManager {
    private $pdo;
    private $audit;
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->audit = new AuditLogger();
    }
    /**
     * Cancella definitivamente un account utente
     * @param int $user_id ID dell'utente da cancellare
     * @param string $reason Motivo della cancellazione
     * @param int|null $admin_id ID dell'admin che effettua la cancellazione (se admin)
     * @param string|null $ip_address IP da cui viene fatta la richiesta
     * @return array Risultato dell'operazione
     */
    public function deleteAccount($user_id, $reason = 'user_request', $admin_id = null, $ip_address = null) {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new Exception('Utente non trovato');
            }
            if ($admin_id && $admin_id == $user_id) {
                throw new Exception('Un amministratore non può cancellare il proprio account');
            }
            $this->deleteUserFiles($user_id);
            $this->audit->log('account_permanent_delete', $admin_id ?: $user_id, 'users', $user_id, [
                'deleted_user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'nome' => $user['nome'],
                    'cognome' => $user['cognome'],
                    'user_type' => $user['user_type']
                ],
                'reason' => $reason,
                'deleted_by_admin' => $admin_id !== null,
                'admin_id' => $admin_id,
                'ip_address' => $ip_address
            ]);
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $this->pdo->commit();
            return [
                'success' => true,
                'message' => 'Account eliminato definitivamente. Tutti i dati sono stati rimossi.'
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->audit->log('account_delete_failed', $admin_id ?: $user_id, 'users', $user_id, [
                'error' => $e->getMessage(),
                'reason' => $reason
            ], 'failed');
            return [
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ];
        }
    }
    /**
     * Elimina tutti i file fisici dell'utente
     */
    private function deleteUserFiles($user_id) {
        $stmt = $this->pdo->prepare("SELECT nome_file FROM curricula WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cvs = $stmt->fetchAll();
        foreach ($cvs as $cv) {
            $file_path = __DIR__ . '/../uploads/cvs/' . $cv['nome_file'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $stmt = $this->pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user && $user['profile_picture']) {
            $profile_path = __DIR__ . '/../uploads/profile_pics/' . $user['profile_picture'];
            if (file_exists($profile_path)) {
                unlink($profile_path);
            }
        }
    }
    /**
     * Verifica se un utente può essere cancellato
     */
    public function canDeleteUser($user_id, $requesting_user_id, $is_admin = false) {
        if (!$is_admin && $user_id != $requesting_user_id) {
            return [
                'can_delete' => false,
                'reason' => 'Puoi eliminare solo il tuo account'
            ];
        }
        if ($is_admin && $user_id == $requesting_user_id) {
            return [
                'can_delete' => false,
                'reason' => 'Un amministratore non può eliminare il proprio account'
            ];
        }
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            return [
                'can_delete' => false,
                'reason' => 'Utente non trovato'
            ];
        }
        return [
            'can_delete' => true,
            'reason' => null
        ];
    }
}
?>
