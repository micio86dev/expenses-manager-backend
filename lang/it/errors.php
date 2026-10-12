<?php

declare(strict_types=1);

/*
 * Titles and details of RFC 9457 problem responses, keyed by HTTP status.
 */
return [
    '400' => ['title' => 'Richiesta non valida', 'detail' => 'Non è stato possibile interpretare la richiesta.'],
    '401' => ['title' => 'Non autenticato', 'detail' => 'Per accedere a questa risorsa è necessaria l\'autenticazione.'],
    '403' => ['title' => 'Accesso negato', 'detail' => 'Non hai il permesso di eseguire questa azione.'],
    '404' => ['title' => 'Non trovato', 'detail' => 'La risorsa richiesta non esiste.'],
    '405' => ['title' => 'Metodo non consentito', 'detail' => 'Questo metodo non è supportato per la risorsa richiesta.'],
    '409' => ['title' => 'Conflitto', 'detail' => 'La richiesta è in conflitto con lo stato attuale della risorsa.'],
    '422' => ['title' => 'Validazione non riuscita', 'detail' => 'I dati inviati non sono validi.'],
    '429' => ['title' => 'Troppe richieste', 'detail' => 'Limite di richieste superato. Riprova dopo il tempo indicato dall\'intestazione Retry-After.'],
    '500' => ['title' => 'Errore del server', 'detail' => 'Si è verificato un errore imprevisto. Riprova più tardi.'],
    '503' => ['title' => 'Servizio non disponibile', 'detail' => 'Il servizio non è temporaneamente disponibile.'],
    'default' => ['title' => 'Richiesta non riuscita', 'detail' => 'Non è stato possibile completare la richiesta.'],
];
