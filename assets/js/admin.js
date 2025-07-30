import { displayMessage, handleAjaxFormSubmit } from './common.js';

// Gestisce l'eliminazione di un item da parte dell'admin
export const handleAdminDelete = async (id, type, elementToRemove) => {
    if (!confirm('Confermi l\'eliminazione? Questa azione è irreversibile.')) return;
    try {
        // Determina il percorso corretto in base alla posizione attuale
        const isAdminArea = window.location.pathname.includes('/admin/');
        const url = isAdminArea ? 'delete_item.php' : '../admin/delete_item.php';
        const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, type }) });
        const result = await response.json();
        if (result.success) { elementToRemove.remove(); displayMessage(result.message, 'success'); } 
        else { displayMessage(result.message, 'danger'); }
    } catch (error) { 
        console.error('Errore eliminazione admin:', error);
        displayMessage('Errore di rete: ' + error.message, 'danger'); 
    }
};

// Inizializzazione area admin
export const initAdminArea = () => {
    // Event delegation per i click sui bottoni di eliminazione
    document.body.addEventListener('click', (e) => {
        const target = e.target;
        const adminDeleteBtn = target.closest('.admin-delete-btn');
        if (adminDeleteBtn) {
            const id = adminDeleteBtn.dataset.id;
            const type = adminDeleteBtn.dataset.type;
            let elementToRemove = null;
            if (type === 'user') {
                elementToRemove = adminDeleteBtn.closest('.user-item');
            } else if (type === 'cv') {
                // Trova la card curriculum più vicina
                elementToRemove = adminDeleteBtn.closest('.modern-card, .cv-card, .list-group-item, .row, .col-md-6, .col-lg-4');
                if (!elementToRemove) {
                    elementToRemove = document.getElementById('curriculum-list');
                }
            } else {
                // Fallback per altri tipi
                elementToRemove = adminDeleteBtn.closest('li, .list-group-item');
            }
            if (!elementToRemove) {
                console.error('[ADMIN] Errore: elementToRemove è null per type', type, 'id', id);
                displayMessage('Errore interno: impossibile rimuovere l’elemento dalla UI', 'danger');
                return;
            }
            handleAdminDelete(id, type, elementToRemove);
            return;
        }
    });

    // Gestione modali modifica utente
    const adminEditUserModal = document.getElementById('adminEditUserModal');
    if (adminEditUserModal) {
        adminEditUserModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            let data = {};
            try {
                data = JSON.parse(button.dataset.userData);
            } catch (e) {
                console.error('[ADMIN] Errore parsing JSON userData:', button.dataset.userData, e);
                displayMessage('Errore interno: dati utente non validi (JSON)', 'danger');
                return;
            }
            const form = document.getElementById('adminEditUserForm');
            form.reset();
            for (const key in data) {
                if (form.elements[key]) form.elements[key].value = data[key];
            }
        });
        // Aggiorna i dati utente in tempo reale dopo submit
        const form = document.getElementById('adminEditUserForm');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(form);
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData
                    });
                    let result;
                    try { result = await response.json(); } catch { result = { success: false }; }
                    if (result.success && result.data) {
                        const userId = formData.get('id');
                        // Ricarica la sezione dettagli utente
                        const userItem = document.querySelector(`[data-user-id="${userId}"]`);
                        if (userItem) {
                            const accordionBody = userItem.querySelector('.accordion-body');
                            if (accordionBody) {
                                accordionBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p>Ricaricamento...</p></div>';
                                try {
                                    const isAdminArea = window.location.pathname.includes('/admin/');
                                    const url = isAdminArea ? `get_user_details.php?id=${userId}` : `../admin/get_user_details.php?id=${userId}`;
                                    const detailsResp = await fetch(url);
                                    if (detailsResp.ok) {
                                        accordionBody.innerHTML = await detailsResp.text();
                                        accordionBody.dataset.loaded = 'true';
                                    } else {
                                        accordionBody.innerHTML = '<p class="text-danger">Errore nel ricaricamento dettagli</p>';
                                    }
                                } catch (err) {
                                    accordionBody.innerHTML = '<p class="text-danger">Errore nel ricaricamento dettagli</p>';
                                }
                            }
                        }
                        const bsModal = bootstrap.Modal.getInstance(adminEditUserModal);
                        bsModal.hide();
                    }
                } catch (err) {
                    // Errore silenzioso
                }
            });
        }
    }
    // Gestione modale cambio password admin
    const adminChangePasswordModal = document.getElementById('adminChangePasswordModal');
    if (adminChangePasswordModal) {
        adminChangePasswordModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const userId = button.dataset.userId;
            const userName = button.dataset.userName;
            const form = document.getElementById('adminChangePasswordForm');
            form.reset();
            form.elements['user_id'].value = userId;
            // Aggiorna il titolo del modal
            const modalTitle = adminChangePasswordModal.querySelector('.modal-title');
            modalTitle.innerHTML = `<i class="bi bi-key me-2"></i>Modifica Password - ${userName}`;
        });
    }
    // Ricerca e caricamento dettagli nel pannello admin
    const adminSearchInput = document.getElementById('admin-search-input');
    if (adminSearchInput) {
        const adminFilterHasCv = document.getElementById('admin-filter-has-cv');
        const userItems = document.querySelectorAll('.user-item');
        const filterAdminView = () => {
            const searchTerm = adminSearchInput.value.toLowerCase();
            const mustHaveCv = adminFilterHasCv.checked;
            userItems.forEach(item => {
                const name = item.dataset.name;
                const email = item.dataset.email;
                const hasCv = item.dataset.hasCv === 'true';
                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                const matchesFilter = !mustHaveCv || hasCv;
                item.style.display = (matchesSearch && matchesFilter) ? 'block' : 'none';
            });
        };
        adminSearchInput.addEventListener('input', filterAdminView);
        adminFilterHasCv.addEventListener('change', filterAdminView);
    }
    document.querySelectorAll('#adminUserAccordion .accordion-button').forEach(button => {
        button.addEventListener('click', async () => {
            const accordionBody = document.querySelector(button.dataset.bsTarget).querySelector('.accordion-body');
            if (accordionBody && accordionBody.dataset.loaded !== 'true') {
                const userItem = button.closest('.user-item');
                const userId = userItem.dataset.userId;
                if (!userId) return;
                try {
                    const isAdminArea = window.location.pathname.includes('/admin/');
                    const url = isAdminArea ? `get_user_details.php?id=${userId}` : `../admin/get_user_details.php?id=${userId}`;
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    accordionBody.innerHTML = await response.text();
                    accordionBody.dataset.loaded = 'true';
                } catch (error) {
                    console.error('Errore nel caricamento dettagli utente:', error);
                    accordionBody.innerHTML = '<p class="text-danger">Impossibile caricare i dettagli. Errore: ' + error.message + '</p>';
                }
            }
        });
    });
    // Gestione form profilo admin (self-profile)
    const adminSelfProfileForm = document.getElementById('adminSelfProfileForm');
    if (adminSelfProfileForm) {
        adminSelfProfileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success && result.data) {
                    displayMessage(result.message || 'Profilo aggiornato con successo!', 'success');
                    // Aggiorna nome/cognome/email/telefono nella modale
                    document.querySelectorAll('.modal-title, h6.mt-2').forEach(el => {
                        if (el.textContent.includes('Gestione Profilo Amministratore') || el.textContent.includes('Modifica Profilo Utente')) return;
                        el.textContent = result.data.nome + ' ' + result.data.cognome;
                    });
                    // Aggiorna i valori dei campi del form con i nuovi dati
                    if (result.data.nome) adminSelfProfileForm.elements['nome'].value = result.data.nome;
                    if (result.data.cognome) adminSelfProfileForm.elements['cognome'].value = result.data.cognome;
                    if (result.data.email) adminSelfProfileForm.elements['email'].value = result.data.email;
                    if (typeof result.data.telefono !== 'undefined') adminSelfProfileForm.elements['telefono'].value = result.data.telefono || '';
                    // Aggiorna anche nella navbar se presente
                    const navbarName = document.querySelector('.navbar .navbar-brand strong');
                    if (navbarName) navbarName.textContent = result.data.nome + ' ' + result.data.cognome;
                    // Aggiorna anche il dataset userData del bottone Modifica Profilo
                    const editBtn = document.querySelector('button[data-bs-target="#adminProfileModal"]');
                    if (editBtn) {
                        editBtn.dataset.userData = JSON.stringify(result.data);
                    }
                    // Chiudi il modal dopo il salvataggio
                    const adminProfileModal = document.getElementById('adminProfileModal');
                    if (adminProfileModal) {
                        const bsModal = bootstrap.Modal.getInstance(adminProfileModal);
                        if (bsModal) bsModal.hide();
                    }
                } else {
                    displayMessage(result.message || 'Errore nell\'aggiornamento del profilo', 'danger');
                }
            } catch (error) {
                console.error('Errore form profilo admin:', error);
                displayMessage('Errore di rete durante l\'aggiornamento', 'danger');
            }
        });
    }
    // Aggiorna i campi del form con i dati aggiornati quando si apre il modal profilo admin
    const adminProfileModal = document.getElementById('adminProfileModal');
    if (adminProfileModal) {
        adminProfileModal.addEventListener('show.bs.modal', (event) => {
            const button = document.querySelector('button[data-bs-target="#adminProfileModal"]');
            if (button && button.dataset.userData) {
                let data = {};
                try {
                    data = JSON.parse(button.dataset.userData);
                } catch (e) {}
                if (adminSelfProfileForm) {
                    for (const key in data) {
                        if (adminSelfProfileForm.elements[key]) adminSelfProfileForm.elements[key].value = data[key];
                    }
                }
            }
            // Aggiorna anche le statistiche
            loadAdminStats();
        });
    }
};

// Carica le statistiche per il pannello admin
const loadAdminStats = async () => {
    try {
        const isAdminArea = window.location.pathname.includes('/admin/');
        const url = isAdminArea ? 'get_stats.php' : '../admin/get_stats.php';
        const response = await fetch(url);
        const result = await response.json();
        if (result.success) {
            const stats = result.stats;
            document.getElementById('admin-stats-users').textContent = `${stats.total_users} (${stats.today_users} oggi)`;
            document.getElementById('admin-stats-cvs').textContent = `${stats.total_cvs} (${stats.today_cvs} oggi)`;
        } else {
            document.getElementById('admin-stats-users').textContent = 'Errore caricamento';
            document.getElementById('admin-stats-cvs').textContent = 'Errore caricamento';
        }
    } catch (error) {
        console.error('Errore nel caricamento statistiche:', error);
        document.getElementById('admin-stats-users').textContent = 'Errore caricamento';
        document.getElementById('admin-stats-cvs').textContent = 'Errore caricamento';
    }
};

// Esporta i dati dell'utente per GDPR
window.exportUserData = async (userId) => {
    if (!confirm('Vuoi esportare tutti i dati dell\'utente in formato GDPR?')) return;
    try {
        const response = await fetch('../api/users/export.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `dati_utente_${userId}_${new Date().getTime()}.json`;
            a.click();
            displayMessage('Dati esportati con successo!', 'success');
        } else {
            const result = await response.json();
            displayMessage(result.message || 'Errore nell\'esportazione', 'danger');
        }
    } catch (error) {
        displayMessage('Errore durante l\'esportazione dei dati', 'danger');
    }
};

// Richiesta cancellazione dati utente
window.requestDataDeletion = async (userId) => {
    if (!confirm('ATTENZIONE: Questa azione cancellerà TUTTI i dati dell\'utente in modo irreversibile. Continuare?')) return;
    if (!confirm('Sei sicuro? Questa azione non può essere annullata!')) return;
    try {
        const response = await fetch('../api/users/delete_account.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, admin_deletion: true })
        });
        const result = await response.json();
        if (result.success) {
            displayMessage('Account utente eliminato con successo!', 'success');
            // Rimuovi l'elemento dall'accordion
            const userItem = document.querySelector(`[data-user-id="${userId}"]`);
            if (userItem) {
                userItem.remove();
            }
        } else {
            displayMessage(result.message, 'danger');
        }
    } catch (error) {
        displayMessage('Errore durante l\'eliminazione dell\'account', 'danger');
    }
};

// Genera CV per un utente specifico (admin)
window.generateCvForUser = async (userId) => {
    if (!confirm('Vuoi generare un nuovo CV per questo utente?')) return;
    try {
        const response = await fetch('../api/cv/export.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        const result = await response.json();
        if (result.success) {
            displayMessage('CV generato con successo!', 'success');
            // Ricarica i dettagli utente
            const accordion = document.querySelector(`[data-user-id="${userId}"] .accordion-body`);
            if (accordion) {
                accordion.dataset.loaded = 'false';
                accordion.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p>Ricaricamento...</p></div>';
                // Ricarica i dati
                setTimeout(async () => {
                    try {
                        const isAdminArea = window.location.pathname.includes('/admin/');
                        const url = isAdminArea ? `get_user_details.php?id=${userId}` : `../admin/get_user_details.php?id=${userId}`;
                        const response = await fetch(url);
                        if (response.ok) {
                            accordion.innerHTML = await response.text();
                            accordion.dataset.loaded = 'true';
                        }
                    } catch (error) {
                        accordion.innerHTML = '<p class="text-danger">Errore nel ricaricamento</p>';
                    }
                }, 1000);
            }
        } else {
            displayMessage(result.message, 'danger');
        }
    } catch (error) {
        displayMessage('Errore durante la generazione del CV', 'danger');
    }
};
