# Casana Health Monitoring App

A PHP web application for Casana's smart toilet seat health monitoring platform. This application provides interfaces for healthcare providers, family monitors, and patients to view and manage health data captured effortlessly through Casana's life-integrated monitoring technology.

## Overview

Casana is a leader in smart toilet seat heart health monitoring. The platform passively captures vital health trends, providing meaningful insights for better care while saving valuable time for care teams.

## Application Structure

The application provides three distinct interfaces:

### 👨‍⚕️ Provider Interface (`/provider/`)
Healthcare provider dashboard for managing and monitoring patients:
- **Dashboard** - Overview of patient population and alerts
- **Patients** - Patient list and management
- **Alerts** - Health alerts requiring attention
- **Analytics** - Population-level health analytics

### 👨‍👩‍👧 Monitor Interface (`/monitor/`)
Family monitor dashboard for tracking loved ones:
- View health status of monitored users
- Access to shared health insights

### 🩺 User Interface (`/user/`)
Patient-facing interface for personal health data:
- **Home** - Personal health dashboard
- **History** - Recording history
- **Trends** - Health trend visualizations
- **Settings** - Account and notification settings

### 🔧 Superuser Mode (`/index.php`)
Development and testing portal for switching between different user roles and entities.

## Directory Structure

```
casana-app.mcchord.net/
├── assets/
│   ├── css/           # Stylesheets (casana.css, components.css)
│   ├── img/           # Images and icons
│   └── js/            # JavaScript (api.js, charts.js, theme.js)
├── docs/              # Documentation
├── includes/          # Shared PHP includes
│   ├── api-helper.php # API client wrapper
│   ├── api-proxy.php  # API proxy for frontend calls
│   ├── header.php     # Shared page header
│   └── footer.php     # Shared page footer
├── monitor/           # Family monitor interface
├── provider/          # Healthcare provider interface
├── user/              # Patient/user interface
└── index.php          # Superuser/test portal
```

## Brand Guidelines

The application follows Casana brand guidelines:
- **Primary Color:** Purple `#6A6EFF`
- **Typography:** Arimo (Semibold for headings, Normal for body)
- **UI Elements:** Modern rounded buttons, simple single-color icons

See `docs/casana_brand_guidelines_llm_summary.md` for complete brand documentation.

## API Integration

The application connects to the Casana API backend at `https://casana.mcchord.net/api`. The `includes/api-helper.php` provides a PHP wrapper, while `assets/js/api.js` provides client-side API access.

## Features

- 🌓 **Dark/Light Theme** - User-selectable theme with system preference detection
- 📱 **Responsive Design** - Mobile-friendly Bootstrap 5 layout
- 📊 **Health Visualizations** - Chart.js powered health data graphs
- 🔔 **Alert System** - Health anomaly detection and notifications

## Requirements

- PHP 7.4+
- Web server (Apache/Nginx)
- Access to Casana API backend

## License

Proprietary - Casana Inc.
