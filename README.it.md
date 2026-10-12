# ⛅ CumulusMX Modern Responsive Weather Dashboard

[![Licenza: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Versione PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)

Un template dashboard web moderno, leggero e ottimizzato per dispositivi mobili, progettato per stazioni meteorologiche basate su **CumulusMX**.  
Nessun database esterno richiesto — funziona interamente tramite i file di log standard in formato testo e JSON generati da CumulusMX.

[🇬🇧 Read documentation in English](README.md)

---

## ✨ Funzionalità

- **Dati Meteo in Tempo Reale**: Lettura diretta del file `realtime.txt` (temperatura, umidità, punto di rugiada, wet-bulb, pressione, intensità pioggia, velocità/raffica del vento).
- **Rosa dei Venti e Bussola Reattiva**: Quadrante bussola SVG animato con ago dinamico della direzione e rilevamento dei punti cardinali.
- **Radar Windy Integrato**: Centrato automaticamente sulle coordinate GPS della tua stazione meteo.
- **Dati Storici e Grafici Interattivi (`storico.php`)**: Archivio completo con andamenti mensili e annuali basato su Chart.js.
- **Supporto Webcam Singola**: Supporto diretto a immagini statiche `.jpg` (percorso locale o URL remoto), miniatura leggera opzionale e visualizzatore Lightbox ad alta risoluzione con aggiornamento automatico.
- **Modalità Scura / Chiara**: Cambio tema fluido con preferenza utente salvata in `localStorage`.
- **Architettura Modulare e Pulita**: Abilita o disabilita le sezioni tramite il singolo file di impostazioni `config.php`.

---

## 📁 Struttura della Repository

```text
├── config.example.php      # File di configurazione di esempio
├── config.php              # La tua configurazione attiva della stazione
├── index.php               # Dashboard live in tempo reale
├── storico.php             # Archivio storico climatico
├── .gitignore              # Esclude dati locali, cache e file riservati
├── README.md               # Documentazione in inglese
├── README.it.md            # Documentazione in italiano
│
├── includes/
│   ├── functions.php       # Algoritmi di lettura e formule meteorologiche
│   └── lang.php            # Stringhe di localizzazione (EN / IT)
│
└── ico/                    # Favicon e web manifest della stazione
