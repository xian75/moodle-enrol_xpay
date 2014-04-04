<?php

$string['pluginname'] = 'POS virtuale X-Pay di CartaSi';
$string['pluginname_desc'] = 'Questo plugin permette di configurare le iscrizioni ai corsi attraverso il pagamento con carta di credito sul POS virtuale X-Pay di CartaSi';

$string['key'] = 'Chiave xPay';
$string['key_desc'] = 'Chiave privata xPay consegnata insieme all\'ID Utente.';
$string['userid'] = 'ID Utente xPay';
$string['userid_desc'] = 'ID Utente xPay associato al conto CartaSi destinatario delle transazioni.';
$string['unavailabletoguest'] = 'Questo corso richiede un costo di iscrizione e non &egrave; disponibile ad ospiti.';
$string['status'] = 'Consente l\'iscrizione tramite POS virtuale X-Pay';
$string['status_desc'] = 'Consenti per default l\'uso del POS virtuale X-Pay per le iscrizioni ai corsi.';
$string['cost'] = 'Costo di iscrizione';
$string['currency'] = 'Valuta';
$string['defaultrole'] = 'Ruolo assegnato per default al momento dell\'iscrizione al corso';
$string['defaultrole_desc'] = 'Seleziona il ruolo da assegnare agli utenti che si iscrivono al corso attraverso il pagamento online su POS virtuale X-Pay';
$string['enrolperiod'] = 'Durata iscrizione';
$string['enrolperiod_desc'] = 'Valore di default per la durata dell\'iscrizione (in secondi). Se zero, la durata &egrave; illimitata per default.';
$string['nocost'] = 'Non c\'&egrave; alcun costo da sostenere per iscriversi al corso!';
$string['assignrole'] = 'Assegna il ruolo';
$string['enrolstartdate'] = 'Data di inizio';
$string['enrolenddate'] = 'Data di fine';
$string['enrolenddate_help'] = 'Se abilitata, gli utenti possono iscriversi solo entro questa data.';
$string['enrolperiod_help'] = 'Finestra temporale in cui l\'iscrizione &egrave; valida. Se disabilitata, non ci sono limiti temporali per iscriversi.';
$string['enrolstartdate_help'] = 'Se abilitata, gli utenti possono iscriversi solo a partire da questa data.';
$string['xpay:config'] = 'Configura le istanze di iscrizione attraverso xPay';
$string['xpay:manage'] = 'Gestisci gli utenti iscritti';
$string['xpay:unenrol'] = 'Disiscrivi gli utenti dal corso';
$string['xpay:unenrolself'] = 'Disiscrivimi dal corso';
$string['coursenotfound'] = 'Corso non trovato';


// Error messages
$string['error_curlrequired'] = 'L\'estensione PHP Curl &egrave; necessaria per il plugin di iscrizione xPay.';
$string['error_xpaycurrency'] = 'Il costo di iscrizione al corso non &egrave; impostato su di una valuta riconosciuta da xPay.';
$string['error_xpayinitiate'] = 'Non &egrave; possibile avviare la transazione con il server xPay - si prega di riprovare più tardi.';
$string['error_enrolmentkey'] = 'La chiave di iscrizione non &egrave; corretta, si prega di riprovare.';
$string['error_paymentfailure'] = 'Il pagamento non &egrave; andato a buon fine. xPay ha risposto con esito KO.';
$string['error_paymentunsucessful'] = 'Il pagamento non &egrave; andato a buon fine, si prega di riprovare più tardi.';
$string['error_txalreadyprocessed'] = 'POS virtuale X-Pay: questa transazione &egrave; gi&agrave; stata processata.';
$string['error_txdatabase'] = 'Errore fatale: non &egrave; stato possibile aggiungere la transazione nel database di Moodle.';
$string['error_txinvalid'] = 'POS virtuale X-Pay: transazione non valida, si prega di riprovare.';
$string['error_txnotfound'] = 'POS virtuale X-Pay: non &egrave; possibile trovare la corrispondente transazione Moodle nel database.';
$string['error_usercourseempty'] = 'utente o corso vuoti';

