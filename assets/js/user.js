/**
 * JavaScript specifico per l'area utente
 */
import { displayMessage, handleAjaxFormSubmit } from './common.js';

/**
 * Inizializza l'area utente con tutti i gestori eventi
 */
export const initUserArea = () => {
    // Gestione form modifica profilo
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', (e) => {
            handleProfileUpdate(e);
        });
    }

    // Gestione form cambio email
    const emailForm = document.getElementById('emailForm');
    if (emailForm) {
        emailForm.addEventListener('submit', (e) => {
            handleEmailUpdate(e);
        });
    }

    // Gestione form cambio password
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', (e) => {
            handleAjaxFormSubmit(e, '#passwordModal', true);
        });
    }

    // Gestione upload foto profilo
    const photoForm = document.getElementById('profile-picture-form');
    if (photoForm) {
        photoForm.addEventListener('submit', handlePhotoUpload);
    }

    // Gestione upload CV
    const cvForm = document.getElementById('cv-upload-form');
    if (cvForm) {
        cvForm.addEventListener('submit', handleCvUpload);
        
    }

    // Gestione form esperienze
    const expForm = document.getElementById('experienceForm');
    if (expForm) {
        expForm.addEventListener('submit', (e) => {
            handleExperienceFormSubmit(e);
        });
    }

    // Gestione bottoni elimina
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = e.currentTarget.dataset.id;
            let type = e.currentTarget.dataset.type;
            // Determina il tipo corretto per esperienze
            if (type === 'experience') {
                // Recupera il tipo dal dataset dell'elemento
                const expData = e.currentTarget.closest('.list-group-item')?.dataset.expData;
                if (expData) {
                    try {
                        const parsed = JSON.parse(expData);
                        type = parsed.tipo || parsed.type || '';
                    } catch {}
                }
                // Fallback: cerca nel titolo della card
                if (!['lavorativa', 'formativa'].includes(type)) {
                    const card = e.currentTarget.closest('.card');
                    const h5 = card ? card.querySelector('h5') : null;
                    const cardTitle = h5 ? h5.textContent : '';
                    if (cardTitle.includes('Formazione')) type = 'formativa';
                    if (cardTitle.includes('Lavorative')) type = 'lavorativa';
                }
                const listItem = e.currentTarget.closest('.list-group-item');
                handleExperienceDelete(id, type, listItem);
            } else {
                // Se il bottone è in una esperienza formativa, type deve essere 'formativa'
                if (!['lavorativa', 'formativa'].includes(type)) {
                    const card = e.currentTarget.closest('.card');
                    const h5 = card ? card.querySelector('h5') : null;
                    const cardTitle = h5 ? h5.textContent : '';
                    if (cardTitle.includes('Formazione')) type = 'formativa';
                    if (cardTitle.includes('Lavorative')) type = 'lavorativa';
                }
                const listItem = e.currentTarget.closest('.list-group-item');
                handleExperienceDelete(id, type, listItem);
            }
        });
    });

    // Gestori per edit esperienze
    document.querySelectorAll('.edit-experience-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const type = e.currentTarget.dataset.type;
            const experienceItem = e.currentTarget.closest('.list-group-item');
            const experienceData = JSON.parse(experienceItem.dataset.expData);
            populateExperienceForm(experienceData, type);
        });
    });

    // Gestori per apertura modal esperienze
    document.querySelectorAll('[data-bs-target="#experienceModal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const type = e.currentTarget.dataset.type;
            if (!e.currentTarget.classList.contains('edit-experience-btn')) {
                // Nuovo elemento
                clearExperienceForm();
                setupExperienceModal(type);
            }
        });
    });
};

/**
 * Gestisce l'upload della foto profilo
 */
const handlePhotoUpload = async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const btn = form.querySelector('button[type="submit"]');
    const btnHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Caricamento...`;

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            // Aggiorna l'immagine del profilo
            const profileImage = document.getElementById('profile-image');
            if (profileImage) {
                profileImage.innerHTML = `<img src="../api/users/profile_pic.php?user_id=${userId}" alt="Foto Profilo" class="rounded-circle" width="120" height="120" style="object-fit: cover;">`;
            }
            
            // Chiudi modal e mostra messaggio
            const modal = bootstrap.Modal.getInstance(document.getElementById('photoModal'));
            modal.hide();
            displayMessage(result.message, 'success');
            
            // Reset form
            form.reset();
        } else {
            const container = form.closest('.modal-body').querySelector('.modal-error-container');
            container.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    } catch (error) {
        const container = form.closest('.modal-body').querySelector('.modal-error-container');
        container.innerHTML = `<div class="alert alert-danger">Errore di rete durante l'upload</div>`;
    }

    btn.disabled = false;
    btn.innerHTML = btnHtml;
};

/**
 * Crea una card per un curriculum
 */
const createCurriculumCard = (cv) => {
    const card = document.createElement('div');
    card.className = 'col-md-6 col-lg-4 mb-3';
    const tipoIcon = cv.tipo === 'generato' ? 'bi-robot text-primary' : 'bi-file-earmark-pdf text-danger';
    card.innerHTML = `
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi ${tipoIcon} me-2"></i>
                </div>
                <h6 class="card-title">${cv.nome_display || cv.nome_originale}</h6>
                <p class="card-text text-muted mb-1">
                    <small>${cv.data_caricamento || cv.uploaded_at}
                        <span class="badge bg-${cv.tipo === 'generato' ? 'info' : 'secondary'} ms-2">${cv.tipo === 'generato' ? 'Generato' : 'Caricato'}</span>
                    </small>
                </p>
                <div class="btn-group w-100" role="group">
                    <a href="../api/cv/download.php?id=${cv.id}" class="btn btn-info btn-sm">
                        <i class="bi bi-download"></i> Scarica
                    </a>
                    <button class="btn btn-danger btn-sm" onclick="deleteCurriculum(${cv.id}, this)">
                        <i class="bi bi-trash"></i> Elimina
                    </button>
                </div>
            </div>
        </div>
    `;
    return card;
};

/**
 * Carica l'elenco dei curriculum dell'utente
 */
export const loadCurriculumList = async () => {
    try {
        const response = await fetch('../api/cv/list.php');
        const result = await response.json();
        const container = document.getElementById('curriculum-list');
        if (!container) return;
        container.innerHTML = '';
        if (!result.success || !result.curricula || result.curricula.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-file-earmark-pdf" style="font-size: 3rem;"></i><p class="mt-2">Nessun curriculum caricato</p><button class="btn btn-primary" id="first-cv-btn"><i class="bi bi-magic"></i> Genera il tuo primo CV</button></div>';
            const firstBtn = document.getElementById('first-cv-btn');
            if (firstBtn) firstBtn.addEventListener('click', generatePersonalCV);
            return;
        }
        const row = document.createElement('div');
        row.className = 'row';
        result.curricula.forEach(cv => {
            const cvCard = createCurriculumCard(cv);
            row.appendChild(cvCard);
        });
        container.appendChild(row);
    } catch (error) {
        const container = document.getElementById('curriculum-list');
        if (container) {
            container.innerHTML = '<div class="alert alert-danger">Errore di rete nel caricamento curriculum</div>';
        }
    }
};

/**
 * Gestisce l'eliminazione di esperienze con aggiornamento dinamico
 */
const handleExperienceDelete = async (id, type, listItem) => {
    if (!confirm('Sei sicuro di voler eliminare questa esperienza?')) return;
    const url = '../api/experiences/delete.php';
    try {
        const payload = { id: Number(id), type };
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            console.error('[ERROR] Risposta non valida dal server:', jsonError);
            displayMessage('Risposta non valida dal server', 'danger');
            return;
        }
        if (result.success) {
            let domError = false;
            try {
                const container = listItem.closest('.card-body');
                const listGroup = container ? container.querySelector('.list-group') : null;
                listItem.remove();
                displayMessage(result.message, 'success');
                // Controlla se la sezione è vuota e aggiungi messaggio
                if (container && listGroup && listGroup.parentNode && listGroup.children.length === 0) {
                    const emptyMessage = document.createElement('div');
                    emptyMessage.className = 'text-center text-muted py-4';
                    const card = container.closest('.card');
                    const h5 = card ? card.querySelector('h5') : null;
                    const cardTitle = h5 ? h5.textContent : '';
                    if (cardTitle.includes('Lavorative')) {
                        emptyMessage.innerHTML = `<i class=\"bi bi-briefcase\" style=\"font-size: 3rem;\"></i><p class=\"mt-2\">Nessuna esperienza lavorativa aggiunta</p>`;
                    } else {
                        emptyMessage.innerHTML = `<i class=\"bi bi-mortarboard\" style=\"font-size: 3rem;\"></i><p class=\"mt-2\">Nessuna formazione aggiunta</p>`;
                    }
                    container.appendChild(emptyMessage);
                    listGroup.remove();
                }
            } catch (err) {
                domError = true;
                console.error('[ERROR] Errore DOM post-eliminazione esperienza:', err);
            }
            return;
        } else {
            console.error('[ERROR] Errore backend eliminazione esperienza:', result.message);
            displayMessage(result.message || 'Errore durante l\'eliminazione', 'danger');
        }
    } catch (error) {
        console.error('[ERROR] Errore di rete/fetch eliminazione esperienza:', error);
        displayMessage('Errore di rete: impossibile contattare il server', 'danger');
    }
};

/**
 * Elimina un curriculum con aggiornamento dinamico
 */
window.deleteCurriculum = async (cvId, buttonElement) => {
    if (!confirm('Sei sicuro di voler eliminare questo curriculum?')) {
        return;
    }
    buttonElement.disabled = true;
    const originalContent = buttonElement.innerHTML;
    buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    try {
        const response = await fetch('../api/cv/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: Number(cvId) })
        });
        const result = await response.json();
        if (result.success) {
            displayMessage('Curriculum eliminato con successo', 'success');
            await loadCurriculumList();
        } else {
            displayMessage(result.error || 'Errore durante l\'eliminazione', 'danger');
        }
    } catch (error) {
        console.error('Errore eliminazione curriculum:', error);
        displayMessage('Errore di rete durante l\'eliminazione', 'danger');
    } finally {
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalContent;
    }
};

/**
 * Genera un CV dai dati del profilo
 */
export const generatePersonalCV = async () => {
    const button = document.getElementById('generate-cv-btn');
    if (!button) return;
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generazione in corso...';
    try {
        const response = await fetch('../api/cv/export.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'generate' })
        });
        const result = await response.json();
        if (result.success) {
            displayMessage('CV generato con successo!', 'success');
            // Aggiorna dinamicamente la lista dei curriculum
            await loadCurriculumList();
            // Avvia il download del file generato
            if (result.curriculum.download_url) {
                window.open(result.curriculum.download_url, '_blank');
            }
        } else {
            displayMessage(result.error || result.message || 'Errore durante la generazione del CV', 'danger');
        }
    } catch (error) {
        console.error('Errore generazione CV:', error);
        displayMessage('Errore di rete durante la generazione', 'danger');
    } finally {
        button.disabled = false;
        button.innerHTML = originalContent;
    }
};


/**
 * Gestisce l'upload del curriculum
 */
export const handleCvUpload = async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const btn = form.querySelector('button[type="submit"]');
    const btnHtml = btn.innerHTML;
    // Validazione
    const fileInput = form.querySelector('#cv_file');
    if (!fileInput.files[0]) {
        displayMessage('Seleziona un file PDF', 'warning');
        return;
    }
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Caricamento...`;
    try {
        const formData = new FormData();
        formData.append('cv_file', fileInput.files[0]);
        const response = await fetch('../api/cv/upload.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            displayMessage('Curriculum caricato con successo!', 'success');
            // Aggiorna la lista dei CV in modo dinamico
            loadCurriculumList();
            // Reset form
            form.reset();
        } else {
            displayMessage(result.message, 'danger');
        }
    } catch (error) {
        console.error('Errore upload CV:', error);
        displayMessage('Errore durante il caricamento del curriculum', 'danger');
    }
    btn.disabled = false;
    btn.innerHTML = btnHtml;
};

/**
 * Gestisce il submit del form esperienze
 */
const handleExperienceFormSubmit = async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    
    // Valida le date prima dell'invio
    if (!validateExperienceDates()) {
        return;
    }
    
    const formData = new FormData(form);
    const type = formData.get('type');
    const id = formData.get('id');
    
    // Validazione campi specifici per tipo
    let valid = true;
    let errorMsg = '';
    const errorContainer = form.closest('.modal-body').querySelector('.modal-error-container');
    
    if (type === 'lavorativa') {
        const azienda = formData.get('azienda');
        const posizione = formData.get('posizione');
        if (!azienda || !posizione) {
            valid = false;
            errorMsg = 'Azienda e posizione sono obbligatorie per le esperienze lavorative.';
        }
    } else if (type === 'formativa') {
        const istituto = formData.get('istituto');
        const titolo = formData.get('titolo');
        if (!istituto || !titolo) {
            valid = false;
            errorMsg = 'Istituto e titolo sono obbligatori per le esperienze formative.';
        }
    }
    
    if (!valid) {
        if (errorContainer) {
            errorContainer.innerHTML = `<div class="alert alert-danger">${errorMsg}</div>`;
        }
        return;
    }
    
    const url = id ? '../api/experiences/update.php' : '../api/experiences/create.php';
    const btn = form.querySelector('button[type="submit"]');
    const btnHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvataggio...';
    
    try {
        const response = await fetch(url, { method: 'POST', body: formData });
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            throw new Error('Risposta non valida dal server');
        }
        if (response.ok && result && result.success) {
            // Aggiornamento dinamico invece di reload
            if (id) {
                updateExperienceInUI(result.data, type);
            } else {
                addExperienceToUI(result.data, type);
            }
            const modal = bootstrap.Modal.getInstance(document.getElementById('experienceModal'));
            modal.hide();
            displayMessage(result.message, 'success');
            clearExperienceForm();
        } else {
            if (errorContainer) {
                errorContainer.innerHTML = `<div class="alert alert-danger">${result && result.message ? result.message : 'Errore durante il salvataggio'}</div>`;
            }
        }
    } catch (error) {
        if (errorContainer) {
            errorContainer.innerHTML = `<div class="alert alert-danger">${error.message || 'Errore di rete'}</div>`;
        }
    }
    
    btn.disabled = false;
    btn.innerHTML = btnHtml;
};

/**
 * Configura il modal esperienze per un nuovo elemento
 */
const setupExperienceModal = (type) => {
    const modal = document.getElementById('experienceModal');
    const title = modal.querySelector('#experienceModalTitle');
    const typeInput = modal.querySelector('#experienceType');
    const idInput = modal.querySelector('#experienceId');
    const submitBtn = modal.querySelector('#experienceSubmitBtn');
    
    // Reset form
    clearExperienceForm();
    
    // Configura per il tipo
    typeInput.value = type;
    idInput.value = '';
    
    if (type === 'lavorativa') {
        title.innerHTML = '<i class="bi bi-briefcase"></i> Nuova Esperienza Lavorativa';
        document.getElementById('form-lavorativa-fields').classList.remove('d-none');
        document.getElementById('form-formativa-fields').classList.add('d-none');
        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Aggiungi Esperienza';
    } else {
        title.innerHTML = '<i class="bi bi-mortarboard"></i> Nuova Formazione';
        document.getElementById('form-lavorativa-fields').classList.add('d-none');
        document.getElementById('form-formativa-fields').classList.remove('d-none');
        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Aggiungi Formazione';
    }
};

/**
 * Popola il form esperienze per la modifica
 */
const populateExperienceForm = (data, type) => {
    const modal = document.getElementById('experienceModal');
    const title = modal.querySelector('#experienceModalTitle');
    const typeInput = modal.querySelector('#experienceType');
    const idInput = modal.querySelector('#experienceId');
    const submitBtn = modal.querySelector('#experienceSubmitBtn');
    
    // Configura per il tipo
    typeInput.value = type;
    idInput.value = data.id;
    
    if (type === 'lavorativa') {
        title.innerHTML = '<i class="bi bi-briefcase"></i> Modifica Esperienza Lavorativa';
        document.getElementById('form-lavorativa-fields').classList.remove('d-none');
        document.getElementById('form-formativa-fields').classList.add('d-none');
        
        // Popola campi specifici
        modal.querySelector('[name="posizione"]').value = data.posizione || '';
        modal.querySelector('[name="azienda"]').value = data.azienda || '';
        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Aggiorna Esperienza';
    } else {
        title.innerHTML = '<i class="bi bi-mortarboard"></i> Modifica Formazione';
        document.getElementById('form-lavorativa-fields').classList.add('d-none');
        document.getElementById('form-formativa-fields').classList.remove('d-none');
        
        // Popola campi specifici
        modal.querySelector('[name="titolo"]').value = data.titolo || '';
        modal.querySelector('[name="istituto"]').value = data.istituto || '';
        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Aggiorna Formazione';
    }
    
    // Popola campi comuni
    modal.querySelector('[name="descrizione"]').value = data.descrizione || '';
    modal.querySelector('[name="data_inizio"]').value = data.data_inizio || '';
    modal.querySelector('[name="data_fine"]').value = data.data_fine || '';
};

/**
 * Pulisce il form esperienze
 */
const clearExperienceForm = () => {
    const form = document.getElementById('experienceForm');
    if (form) {
        form.reset();
        // Pulisci anche i messaggi di errore
        const errorContainer = form.closest('.modal-body').querySelector('.modal-error-container');
        if (errorContainer) {
            errorContainer.innerHTML = '';
        }
    }
};

/**
 * Gestisce i messaggi da URL
 */
export const handleUrlMessages = () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        displayMessage(urlParams.get('success'), 'success');
    }
    if (urlParams.has('error')) {
        displayMessage(urlParams.get('error'), 'danger');
    }
    
    // Pulisce l'URL dai parametri
    if (urlParams.has('success') || urlParams.has('error')) {
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
};

/**
 * Genera CV dal profilo utente
 */
window.generatePersonalCV = async () => {
    if (!confirm('Vuoi generare un nuovo CV basato sui dati del tuo profilo?')) return;
    
    try {
        const response = await fetch('../api/cv/export.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: 'current' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayMessage('CV generato con successo! Ricarica la pagina per vederlo.', 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            displayMessage(result.message, 'danger');
        }
    } catch (error) {
        displayMessage('Errore durante la generazione del CV', 'danger');
    }
};

/**
 * Genera CV per un utente specifico (admin)
 */
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
                accordion.innerHTML = '';
            }
        } else {
            displayMessage(result.message, 'danger');
        }
    } catch (error) {
        displayMessage('Errore durante la generazione del CV', 'danger');
    }
};

/**
 * Esporta tutti i dati personali (GDPR)
 */
window.exportPersonalData = async () => {
    try {
        const response = await fetch('../api/users/export.php', {
            method: 'POST'
        });
        
        if (response.ok) {
            // Scarica il file
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `dati_personali_${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            window.URL.revokeObjectURL(url);
            
            displayMessage('Dati esportati con successo!', 'success');
        } else {
            displayMessage('Errore nell\'esportazione dei dati', 'danger');
        }
    } catch (error) {
        displayMessage('Errore di rete durante l\'esportazione', 'danger');
    }
};

/**
 * Richiede la cancellazione dell'account (GDPR) - CANCELLAZIONE ISTANTANEA
 */
window.requestDataDeletion = async () => {
    const confirmed = confirm(
        'ATTENZIONE: Questa azione eliminerà IMMEDIATAMENTE e PERMANENTEMENTE il tuo account e tutti i dati associati.\n\n' +
        '• Tutti i tuoi CV verranno eliminati\n' +
        '• Tutte le esperienze lavorative e formative verranno eliminate\n' +
        '• La foto profilo verrà eliminata\n' +
        '• NON È POSSIBILE RECUPERARE I DATI dopo questa operazione\n\n' +
        'Vuoi davvero continuare?'
    );
    
    if (!confirmed) return;
    
    const secondConfirm = confirm(
        'ULTIMA CONFERMA: Sei SICURO di voler eliminare definitivamente il tuo account?\n\n' +
        'Questa operazione NON può essere annullata.'
    );
    
    if (!secondConfirm) return;
    
    const password = prompt('Inserisci la tua password per confermare la cancellazione:');
    if (!password) return;
    
    try {
        const response = await fetch('../api/users/delete_account.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: password })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Account eliminato definitivamente. Verrai reindirizzato alla homepage.');
            window.location.href = '../index.html';
        } else {
            displayMessage(result.message, 'danger');
        }
    } catch (error) {
        displayMessage('Errore durante la cancellazione dell\'account', 'danger');
    }
};

/**
 * Gestisce l'aggiornamento del profilo con aggiornamento dinamico UI
 */
const handleProfileUpdate = async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const btn = form.querySelector('button[type="submit"]');
    const btnHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Aggiornamento...';
    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            // Aggiorna dinamicamente la UI
            updateProfileUI(formData);
            // Chiudi modal e mostra messaggio
            const modalEl = document.getElementById('profileModal');
            if (!modalEl) {
                console.error('[USER] Errore: document.getElementById("profileModal") è null');
            } else {
                try {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (!modal) {
                        console.error('[USER] Errore: bootstrap.Modal.getInstance(profileModal) è null');
                    } else {
                        modal.hide();
                    }
                } catch (err) {
                    console.error('[USER] Errore durante la chiusura della modale:', err);
                }
            }
            displayMessage(result.message, 'success');
        } else {
            const container = form.closest('.modal-body')?.querySelector('.modal-error-container');
            if (!container) {
                console.error('[USER] Errore: .modal-error-container non trovato');
            } else {
                container.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        }
    } catch (error) {
        const container = form.closest('.modal-body')?.querySelector('.modal-error-container');
        if (!container) {
            console.error('[USER] Errore: .modal-error-container non trovato');
        } else {
            container.innerHTML = `<div class="alert alert-danger">Errore di rete durante l'aggiornamento</div>`;
        }
        console.error('[USER] Errore di rete/fetch:', error);
    }
    btn.disabled = false;
    btn.innerHTML = btnHtml;
};

/**
 * Gestisce l'aggiornamento email con aggiornamento dinamico UI
 */
const handleEmailUpdate = async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const btn = form.querySelector('button[type="submit"]');
    const btnHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Aggiornamento...';
    
    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            // Aggiorna dinamicamente la email nella UI
            const newEmail = formData.get('new_email');
            document.querySelector('p.text-muted').textContent = newEmail;
            document.getElementById('info-email').textContent = newEmail;
            
            // Chiudi modal e mostra messaggio
            const modal = bootstrap.Modal.getInstance(document.getElementById('emailModal'));
            modal.hide();
            displayMessage(result.message, 'success');
            
            // Reset form
            form.reset();
        } else {
            const container = form.closest('.modal-body').querySelector('.modal-error-container');
            container.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    } catch (error) {
        const container = form.closest('.modal-body').querySelector('.modal-error-container');
        container.innerHTML = `<div class="alert alert-danger">Errore di rete durante l'aggiornamento</div>`;
    }
    
    btn.disabled = false;
    btn.innerHTML = btnHtml;
};

/**
 * Aggiorna dinamicamente la UI del profilo dopo un aggiornamento
 */
const updateProfileUI = (formData) => {
    const nome = formData.get('nome');
    const cognome = formData.get('cognome');
    const telefono = formData.get('telefono');
    const dataNascita = formData.get('data_nascita');
    const citta = formData.get('citta');
    const cap = formData.get('cap');
    const indirizzo = formData.get('indirizzo');
    const sommario = formData.get('sommario');

    const messBenvenuto = document.getElementById('benvenuto');
    if (messBenvenuto) {
        messBenvenuto.textContent = "Benvenuto, " + nome + "!";
    }

    // Aggiorna nome completo nell'header del profilo e messaggio di benvenuto
    const nomeCompleto = `${nome} ${cognome}`;

    // Aggiorna il messaggio di benvenuto
    const welcomeElement = document.querySelector('.card-body h5');
    if (welcomeElement) {
        welcomeElement.textContent = nomeCompleto;
    }
    
    // Aggiorna telefono se presente
    const telefonoElement = document.querySelector('.info-telefono');
    if (telefonoElement) {
        if (telefono) {
            telefonoElement.innerHTML = `<i class="bi bi-telephone"></i> ${telefono}`;
            telefonoElement.style.display = 'block';
        } else {
            telefonoElement.style.display = 'none';
        }
    }
    
    // Aggiorna tutte le informazioni nel modal delle informazioni complete
    const infoNome = document.getElementById('info-nome');
    if (infoNome) infoNome.textContent = nome;
    
    const infoCognome = document.getElementById('info-cognome');
    if (infoCognome) infoCognome.textContent = cognome;
    
    const infoTelefono = document.getElementById('info-telefono');
    if (infoTelefono) infoTelefono.textContent = telefono || 'Non specificato';
    
    const infoCitta = document.getElementById('info-citta');
    if (infoCitta) infoCitta.textContent = citta || 'Non specificata';
    
    const infoCap = document.getElementById('info-cap');
    if (infoCap) infoCap.textContent = cap || 'Non specificato';
    
    const infoIndirizzo = document.getElementById('info-indirizzo');
    if (infoIndirizzo) infoIndirizzo.textContent = indirizzo || 'Non specificato';
    
    const infoSommario = document.getElementById('info-sommario');
    if (infoSommario) infoSommario.textContent = sommario || 'Non specificato';

    if (dataNascita) {
        const dataFormatted = new Date(dataNascita).toLocaleDateString('it-IT');
        const infoDataNascita = document.getElementById('info-data-nascita');
        if (infoDataNascita) infoDataNascita.textContent = dataFormatted;
    }
    
    // Aggiorna timestamp ultimo aggiornamento
    const now = new Date().toLocaleDateString('it-IT') + ' ' + new Date().toLocaleTimeString('it-IT', {hour: '2-digit', minute: '2-digit'});
    const lastUpdateElement = document.getElementById('info-ultimo-aggiornamento');
    if (lastUpdateElement) {
        lastUpdateElement.textContent = now;
    }
};

/**
 * Valida le date nelle esperienze
 */
window.validateExperienceDates = () => {
    const dataInizio = document.querySelector('input[name="data_inizio"]').value;
    const dataFine = document.querySelector('input[name="data_fine"]').value;
    const errorDiv = document.getElementById('date-validation-error');
    const submitBtn = document.querySelector('#experienceForm button[type="submit"]');
    
    if (dataInizio && dataFine) {
        const inizio = new Date(dataInizio);
        const fine = new Date(dataFine);
        
        if (fine <= inizio) {
            errorDiv.classList.remove('d-none');
            submitBtn.disabled = true;
            return false;
        }
    }
    
    errorDiv.classList.add('d-none');
    submitBtn.disabled = false;
    return true;
};

/**
 * Aggiunge una nuova esperienza alla UI
 */
const addExperienceToUI = (data, type) => {
    // Trova il container corretto in base al tipo
    let container;
    if (type === 'lavorativa') {
        // Trova la card con "Esperienze Lavorative" nel titolo
        const cards = document.querySelectorAll('.card h5');
        for (let card of cards) {
            if (card.textContent.includes('Esperienze Lavorative')) {
                container = card.closest('.card').querySelector('.card-body');
                break;
            }
        }
    } else {
        // Trova la card con "Formazione" nel titolo
        const cards = document.querySelectorAll('.card h5');
        for (let card of cards) {
            if (card.textContent.includes('Formazione')) {
                container = card.closest('.card').querySelector('.card-body');
                break;
            }
        }
    }
    
    if (!container) return;
    const emptyMessage = container.querySelector('.text-center.text-muted');
    if (emptyMessage) {
        emptyMessage.remove();
    }
    
    // Crea o ottieni container lista
    let listContainer = container.querySelector('.list-group');
    if (!listContainer) {
        listContainer = document.createElement('div');
        listContainer.className = 'list-group list-group-flush';
        container.appendChild(listContainer);
    }
    
    // Crea elemento esperienza
    const expElement = createExperienceElement(data, type);
    
    // Inserisci all'inizio (più recente)
    listContainer.insertBefore(expElement, listContainer.firstChild);
};

/**
 * Aggiorna un'esperienza esistente nella UI
 */
const updateExperienceInUI = (data, type) => {
    const existingElement = document.querySelector(`[data-exp-id="${data.id}"]`);
    if (existingElement) {
        const newElement = createExperienceElement(data, type);
        existingElement.replaceWith(newElement);
    }
};

/**
 * Crea un elemento HTML per un'esperienza
 */
const createExperienceElement = (data, type) => {
    const div = document.createElement('div');
    div.className = 'list-group-item experience-item p-3 mb-2 rounded';
    div.setAttribute('data-exp-id', data.id);
    div.setAttribute('data-exp-data', JSON.stringify(data));
    
    const formatDateRange = (start, end) => {
        const startFormatted = new Date(start).toLocaleDateString('it-IT', {month: '2-digit', year: 'numeric'});
        const endFormatted = end ? new Date(end).toLocaleDateString('it-IT', {month: '2-digit', year: 'numeric'}) : 'Presente';
        return `${startFormatted} - ${endFormatted}`;
    };
    
    if (type === 'lavorativa') {
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 id="exp-${data.id}-posizione" class="mb-1 fw-bold">${data.posizione}</h6>
                    <p id="exp-${data.id}-azienda" class="mb-1 text-primary">${data.azienda}</p>
                    <p id="exp-${data.id}-descrizione" class="mb-1">${data.descrizione}</p>
                    <small id="exp-${data.id}-periodo" class="text-muted">
                        <i class="bi bi-calendar-range"></i> 
                        ${formatDateRange(data.data_inizio, data.data_fine)}
                    </small>
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-primary edit-experience-btn" 
                            data-bs-toggle="modal" data-bs-target="#experienceModal" 
                            data-type="lavorativa">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-danger delete-btn" 
                            data-type="experience" data-id="${data.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    } else {
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 id="exp-${data.id}-titolo" class="mb-1 fw-bold">${data.titolo}</h6>
                    <p id="exp-${data.id}-istituto" class="mb-1 text-success">${data.istituto}</p>
                    <p id="exp-${data.id}-descrizione" class="mb-1">${data.descrizione}</p>
                    <small id="exp-${data.id}-periodo" class="text-muted">
                        <i class="bi bi-calendar-range"></i> 
                        ${formatDateRange(data.data_inizio, data.data_fine)}
                    </small>
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-success edit-experience-btn" 
                            data-bs-toggle="modal" data-bs-target="#experienceModal" 
                            data-type="formativa">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-danger delete-btn" 
                            data-type="experience" data-id="${data.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    // Aggiungi event listeners ai nuovi elementi
    const editBtn = div.querySelector('.edit-experience-btn');
    editBtn.addEventListener('click', (e) => {
        const experienceData = JSON.parse(div.dataset.expData);
        populateExperienceForm(experienceData, type);
    });
    
    const deleteBtn = div.querySelector('.delete-btn');
    deleteBtn.addEventListener('click', (e) => {
        e.stopPropagation(); // Blocca bubbling per evitare doppie chiamate
        const id = e.currentTarget.dataset.id;
        handleExperienceDelete(id, type, div);
    });
    
    return div;
};
/**
 * Elimina un curriculum con aggiornamento dinamico
 */
window.deleteCurriculum = async (cvId, buttonElement) => {
    if (!confirm('Sei sicuro di voler eliminare questo curriculum?')) {
        return;
    }
    buttonElement.disabled = true;
    const originalContent = buttonElement.innerHTML;
    buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    try {
        const response = await fetch('../api/cv/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: Number(cvId) })
        });
        const result = await response.json();
        if (result.success) {
            displayMessage('Curriculum eliminato con successo', 'success');
            await loadCurriculumList();
        } else {
            displayMessage(result.error || 'Errore durante l\'eliminazione', 'danger');
        }
    } catch (error) {
        console.error('Errore eliminazione curriculum:', error);
        displayMessage('Errore di rete durante l\'eliminazione', 'danger');
    } finally {
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalContent;
    }
};
