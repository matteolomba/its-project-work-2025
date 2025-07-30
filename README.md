# CV Manager

Project Work per il corso Cybersecurity Specialist di ITS Olivetti

## Struttura del progetto

- `index.html` — Homepage pubblica
- `admin/` — Pagine e API per amministratori (gestione utenti, log, statistiche)
- `api/` — Endpoint per autenticazione, gestione CV, esperienze, utenti
    - `auth/` — Login, logout, registrazione
    - `cv/` — Upload, generazione, download, lista, eliminazione CV
    - `experiences/` — Gestione esperienze lavorative e formative
    - `users/` — Gestione utenti, avatar, password, esportazione dati
- `assets/` — Risorse statiche
    - `css/` — Stili globali e specifici
    - `js/` — Script JS modulari (admin, auth, user, common)
    - `img/` — Immagini (sfondo)
- `core/` — Componenti core lato server
    - `db_connection.php` — Connessione al database (usa variabili da `.env`)
    - `audit_logger.php` — Log di sicurezza e audit
    - `account_delete_manager.php` — Gestione cancellazione account
- `libraries/` — Librerie esterne (es. FPDF per PDF)
- `migrations/` — Script SQL per setup database
- `pages/` — Pagine HTML per autenticazione e legal
- `uploads/` — File caricati dagli utenti (CV, foto profilo)
- `user/` — Home utente loggato
- `report.pdf` — Il report richiesto sulle funzionalità implementate
- `presentazione.pdf` — Presentazione introduttiva del progetto
- `.env.example` — Esempio di file di configurazione ambiente (da copiare in `.env`)
- `.htaccess` — Regole per sicurezza e accesso ai file sensibili


## Librerie esterne usate

- **FPDF** (`libraries/fpdf/`): generazione PDF dei CV
- **Bootstrap 5**: layout e componenti UI
- **FontAwesome**: icone

## Configurazione ambiente

1. Copia `.env.example` in `.env` e inserisci le tue credenziali database (omesso dal repository con .gitignore per sicurezza):

```
DB_HOST=localhost
DB_NAME=nome_database
DB_USER=nome_utente
DB_PASSWORD=la_tua_password
```

2. Assicurati che la cartella `uploads/` sia scrivibile dal webserver.

## Migrazione database

Esegui lo script SQL in `migrations/database_setup.sql` per creare le tabelle necessarie.