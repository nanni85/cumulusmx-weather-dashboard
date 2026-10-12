# ⛅ CumulusMX Modern Responsive Weather Dashboard

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)

A lightweight, modern, and mobile-ready web dashboard template designed for weather stations running **CumulusMX**.  
Zero external databases required — fully powered by standard CumulusMX text and JSON logs.

[🇮🇹 Leggi la documentazione in Italiano](README.it.md)

---

## ✨ Features

- **Realtime Weather Data**: Direct parsing of `realtime.txt` (temperature, humidity, dew point, wet-bulb, pressure, rain rate, wind speed/gust).
- **Responsive Compass & Wind Rose**: Animated SVG compass dial with dynamic bearing needle and cardinal direction detection.
- **Embedded Windy Radar**: Automatically centered on your station's GPS coordinates.
- **Historical Data & Interactive Charts (`storico.php`)**: Full archive with monthly and yearly trends powered by Chart.js.
- **Single Webcam Support**: Direct static `.jpg` support (local or remote URL), optional lightweight thumbnail, and full-resolution lightbox viewer with auto-refresh.
- **Dark / Light Mode**: Smooth theme toggling with persistent user preference via `localStorage`.
- **Modular & Clean Architecture**: Enable or disable modules via a single `config.php` file.

---

## 📁 Repository Structure

```text
├── config.example.php      # Sample configuration file
├── config.php              # Your active station configuration
├── index.php               # Realtime live dashboard
├── storico.php             # Historical climate archive
├── .gitignore              # Ignores local data, cache, and sensitive files
├── README.md               # English documentation
├── README.it.md            # Italian documentation
│
├── includes/
│   ├── functions.php       # Parsing algorithms and meteorological formulas
│   └── lang.php            # Localization strings (EN / IT)
│
└── ico/                    # Station favicons and web manifest
