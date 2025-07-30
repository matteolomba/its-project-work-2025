import { displayMessage } from './common.js';

// Mostra eventuali messaggi provenienti dai parametri URL
const handleUrlMessages = () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('success') === 'registered') displayMessage('Registrazione completata! Ora puoi accedere.', 'success');
    if (params.get('error') === 'login') displayMessage('Credenziali non valide. Riprova.', 'danger');
    if (params.get('error') === 'missing') displayMessage('Tutti i campi sono obbligatori.', 'danger');
    if (params.get('error') === 'email') displayMessage('L\'indirizzo email inserito non è valido.', 'danger');
    if (params.get('error') === 'server') displayMessage('Errore del server. Riprova più tardi.', 'danger');
    if (params.get('error') === 'access') displayMessage('Devi effettuare il login per accedere.', 'warning');
};

document.addEventListener('DOMContentLoaded', function () {
    handleUrlMessages();
    // Validazione lato client per i form di login e registrazione
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            // Validazione Bootstrap
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    // Cancella i messaggi di errore quando l'utente inizia a digitare
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                messageContainer.innerHTML = '';
            }
        });
    });
});
