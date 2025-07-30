/**
 * Utilities comuni per l'applicazione
 */

/**
 * Mostra un messaggio di feedback (alert Bootstrap) in un contenitore designato.
 * @param {string} message Il testo del messaggio.
 * @param {string} type Il tipo di alert ('success', 'danger', etc.).
 * @param {string} containerId L'ID del contenitore dove mostrare il messaggio.
 */
// Funzione per mostrare un messaggio di feedback (alert Bootstrap) in un contenitore specifico
export const displayMessage = (message, type = 'danger', containerId = 'message-container') => {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
};

/**
 * Controlla i parametri nell'URL per mostrare messaggi al caricamento della pagina (es. dopo un redirect).
 */
// Mostra messaggi in base ai parametri URL (es. dopo un redirect)
export const handleUrlMessages = () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('success') === 'registered') displayMessage('Registrazione completata! Ora puoi accedere.', 'success');
    if (params.get('success') === 'logout') displayMessage('Logout effettuato con successo.', 'success');
    if (params.get('error') === 'login') displayMessage('Credenziali non valide. Riprova.', 'danger');
    if (params.get('error') === 'access') displayMessage('Devi effettuare il login per accedere.', 'warning');
    if (params.get('error') === 'missing') displayMessage('Tutti i campi sono obbligatori.', 'danger');
    if (params.get('error') === 'email') displayMessage('L\'indirizzo email inserito non è valido.', 'danger');
    if (params.get('error') === 'server') displayMessage('Errore del server. Riprova più tardi.', 'danger');
};

/**
 * Gestisce l'invio di un form tramite AJAX, mostrando feedback e gestendo la risposta.
 * @param {Event} event L'evento di submit.
 * @param {string} modalSelector Selettore del modal da chiudere in caso di successo (opzionale).
 * @param {boolean} reload Se ricaricare la pagina dopo il successo (opzionale).
 */
// Gestisce l'invio di un form tramite AJAX, mostrando feedback e gestendo la risposta
export const handleAjaxFormSubmit = async (event, modalSelector = null, reload = false) => {
    event.preventDefault();
    const form = event.currentTarget;
    
    // Permetti invio normale se il form è marcato come no-ajax
    if (form.dataset.noAjax) {
        return true;
    }
    
    const btn = form.querySelector('button[type="submit"]');
    const btnHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Elaborazione...`;

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            // Chiudi modal se specificato
            if (modalSelector) {
                const modalElement = document.querySelector(modalSelector);
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                }
            }
            
            displayMessage(result.message, 'success');
            
            // Ricarica pagina se richiesto
            if (reload) {
                window.location.reload();
            }
            
            // Reset form
            form.reset();
        } else {
            displayMessage(result.message || 'Errore durante l\'operazione.', 'danger');
        }
    } catch (error) {
        displayMessage('Errore di rete: ' + error.message, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = btnHtml;
    }
};
