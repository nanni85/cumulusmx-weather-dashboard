# ⛅ CumulusMX Modern Weather Dashboard

Una dashboard web moderna, reattiva e modulare per stazioni meteo basate su **CumulusMX**.  
Progettata per essere leggera, rapida e facile da configurare tramite un unico file di impostazioni, senza richiedere database esterni[cite: 1, 2].

---

## ✨ Caratteristiche

* **Dati in tempo reale**: lettura diretta dal file `realtime.txt` (temperatura, umidità, punto di rugiada, wet-bulb, pressione, intensità pioggia, vento)[cite: 1, 2].
* **Rosa dei venti reattiva**: indicatore vettoriale SVG animato con calcolo del punto cardinale e della raffica[cite: 2].
* **Radar precipitazioni Windy**: integrato e centrato automaticamente sulle coordinate GPS della stazione.
* **Archivio storico (`storico.php`)**: navigazione mese per mese e anno per anno con grafici interattivi (Chart.js) basati sui log JSON generati da CumulusMX[cite: 1, 2].
* **Supporto Webcam universale**: compatibile direttamente con immagini statiche `webcam.jpg` (locali o remote), thumbnail opzionali e visualizzazione ingrandita Lightbox con auto-refresh[cite: 1, 2].
* **Design Responsive & Dark/Light Mode**: interfaccia ad alte prestazioni ottimizzata per smartphone e desktop con salvataggio automatico del tema[cite: 2].

---

## 📁 Struttura della Cartella

```text
├── config.example.php      # File di configurazione di esempio
├── config.php              # Configurazione attiva 
├── index.php               # Dashboard principale in tempo reale
├── storico.php             # Archivio climatico mensile e annuale
├── .gitignore              # Esclude dati personali e file di cache
├── README.md               # Istruzioni d'uso
│
├── includes/
│   └── functions.php       # Funzioni di parsing e calcoli meteorologici
│
└── ico/                    # Icone e favicon del sito