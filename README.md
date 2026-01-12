# Casana Health Monitoring App

A PHP web application for Casana's smart toilet seat health monitoring platform. This application provides interfaces for healthcare providers, family monitors, and patients to view and manage health data captured effortlessly through Casana's life-integrated monitoring technology.

## Overview

Casana is a leader in smart toilet seat heart health monitoring. The platform passively captures vital health trends, providing meaningful insights for better care while saving valuable time for care teams.

## Application Structure

The application provides three distinct interfaces:

### 👨‍⚕️ Provider Interface (`/provider/`)

Healthcare provider dashboard for managing and monitoring patients:

| Page | Description |
|------|-------------|
| `index.php` | Main dashboard with alerts overview, population health stats, and patient list |
| `patients.php` | Searchable, filterable patient panel with table/card views |
| `patient.php` | Individual patient detail with vitals, trends, recordings, and clinical notes |
| `alerts.php` | Alert queue with severity-based sorting, filtering, and clinician actions |
| `analytics.php` | Population-level health analytics and insights |

### 👨‍👩‍👧 Monitor Interface (`/monitor/`)

Family monitor dashboard for tracking loved ones:

| Page | Description |
|------|-------------|
| `index.php` | Family dashboard with health status cards for all monitored users |
| `user.php` | Detailed health view for a specific family member |
| `add-note.php` | Add personal notes about a family member |
| `add-reminder.php` | Create reminders for follow-ups or check-ins |

### 🩺 User Interface (`/user/`)

Patient-facing interface for personal health data:

| Page | Description |
|------|-------------|
| `index.php` | Personal health dashboard with simple/detailed view toggle |
| `history.php` | Recording history with quick filters and date range selection |
| `recording.php` | Individual recording detail with ECG trace visualization |
| `trends.php` | Health trend visualizations over time |
| `settings.php` | Account and notification settings |

### 🔧 Superuser Mode (`/index.php`)

Development and testing portal for switching between different user roles and entities. Displays system statistics and allows impersonation of any provider, monitor, or user.

## Directory Structure

```
casana-app.mcchord.net/
├── assets/
│   ├── css/
│   │   ├── casana.css         # Core styles, themes, typography
│   │   └── components.css     # UI component styles
│   ├── img/                   # Images and icons
│   └── js/
│       ├── api.js             # Client-side API wrapper
│       ├── charts.js          # Chart.js helpers with Casana branding
│       ├── provider.js        # Provider-specific functionality
│       └── theme.js           # Dark/light mode management
├── docs/
│   ├── casana_brand_guidelines_llm_summary.md
│   ├── openapi.json           # OpenAPI specification
│   └── swagger.json           # Swagger documentation
├── includes/
│   ├── alert-taxonomy.php     # Centralized alert type definitions
│   ├── api-helper.php         # PHP API client class and utilities
│   ├── api-proxy.php          # CORS-free API proxy for frontend
│   ├── footer.php             # Shared page footer with scripts
│   ├── header.php             # Shared page header with navigation
│   └── provider-sidebar.php   # Provider navigation sidebar
├── monitor/                   # Family monitor interface
├── provider/                  # Healthcare provider interface
├── user/                      # Patient/user interface
├── index.php                  # Superuser/test portal
└── README.md                  # This file
```

## Key Features

### 🎨 User Interface

- **Dark/Light Theme** - User-selectable theme with system preference detection and localStorage persistence
- **Responsive Design** - Mobile-friendly Bootstrap 5 layout with dedicated mobile navigation
- **Accessible** - Skip links, ARIA labels, keyboard navigation support

### 📊 Health Visualizations

- **Chart.js Integration** - Branded charts for blood pressure, heart rate, SpO₂, and more
- **ECG Viewer** - Interactive ECG trace visualization with zoom and pan support
- **Sparklines** - Mini charts for quick trend visualization
- **Trend Indicators** - Visual indicators for improving/declining health metrics

### 🔔 Alert System

- **Alert Taxonomy** - Centralized alert type definitions with severity levels
- **Clinician Actions** - Acknowledge, add notes, schedule follow-ups
- **Severity Sorting** - Alerts automatically sorted by clinical priority
- **Real-time Indicators** - Visual indicators for recent alerts

### 📝 Clinical Features (Provider Interface)

- **Clinical Notes** - Add and manage patient notes with type categorization
- **Follow-up Scheduling** - Schedule and track patient follow-ups
- **Patient Search** - Global patient search in navigation bar
- **Population Stats** - Aggregate health statistics across patient panel

### 👨‍👩‍👧 Family Monitoring

- **Relationship Labels** - Custom labels (Mom, Dad, etc.) via metadata API
- **Shared Data Types** - Configurable data sharing permissions
- **Status Indicators** - At-a-glance health status with color coding

## Brand Guidelines

The application follows Casana brand guidelines with a refined modern aesthetic:

- **Primary Colors:**
  - Purple: `#6366f1` (Indigo 500)
  - Green: `#10b981` (Emerald 500)
  - Accent gradient: Linear gradient from Indigo through Violet to Purple
- **Typography:** Plus Jakarta Sans for body text, DM Sans for numeric displays
- **UI Elements:** Modern rounded buttons, subtle shadows, smooth transitions
- **Light Theme:** Clean white backgrounds with slate gray text
- **Dark Theme:** Deep slate background `#0f172a` with refined contrast

See `docs/casana_brand_guidelines_llm_summary.md` for complete brand documentation.

## API Integration

The application connects to the Casana API backend at `https://casana.mcchord.net/api`.

### Server-Side (PHP)

The `includes/api-helper.php` provides a `CasanaAPI` class with methods for:

- **Users** - `getUsers()`, `getUser()`, `getUserRecordings()`, `getUserTrends()`
- **Monitors** - `getMonitors()`, `getMonitor()`, `getMonitoredUserData()`
- **Providers** - `getCareProviders()`, `getCareProvider()`, `getPatientDetail()`, `getPopulationStats()`
- **Recordings** - `getRecordings()`, `getRecording()`, `getAlertRecordings()`, `getExtendedRecordings()`
- **Metadata** - `getEntityMetadata()`, `setMetadata()`, `setBulkMetadata()`
- **Clinical** - `getNotes()`, `createNote()`, `getFollowups()`, `createFollowup()`

### Client-Side (JavaScript)

The `assets/js/api.js` provides the `CasanaAPI` object that proxies requests through `includes/api-proxy.php` to avoid CORS issues.

### Helper Functions

Both PHP and JavaScript include utility functions for:

- `formatRelativeTime()` - Human-readable relative timestamps
- `formatDateTime()` - Formatted date/time strings
- `formatDuration()` - Duration formatting (e.g., "2m 30s")
- `getHealthStatus()` - Determine health status from vitals
- `getInitials()` - Extract initials from names
- `formatBloodPressure()` - Blood pressure display formatting

## File Documentation

All files include documentation headers explaining their purpose:

```php
<?php
/**
 * File Name
 * Brief description of the file's purpose
 * 
 * Additional details about variables, dependencies, etc.
 */
```

PHP functions include PHPDoc comments:

```php
/**
 * Function description
 * 
 * @param string $param Description of parameter
 * @return array|null Description of return value
 */
```

JavaScript functions include JSDoc comments:

```javascript
/**
 * Function description
 * @param {string} param - Description of parameter
 * @returns {string} Description of return value
 */
```

## Requirements

- PHP 7.4+
- Web server (Apache/Nginx)
- Access to Casana API backend
- Modern web browser with JavaScript enabled

## Development

### Theme System

The theme system uses CSS custom properties defined in `casana.css`:

```css
:root, [data-theme="light"] {
    --bg-primary: #ffffff;
    --text-primary: #212529;
    /* ... */
}

[data-theme="dark"] {
    --bg-primary: #121420;
    --text-primary: #f1f3f5;
    /* ... */
}
```

Theme is toggled via `ThemeManager` in `theme.js` and persisted to localStorage.

### Adding New Alert Types

Alert types are defined in `includes/alert-taxonomy.php`:

```php
$ALERT_TAXONOMY = [
    'alert_type' => [
        'id' => 'alert_type',
        'label' => 'Display Label',
        'severity' => 3, // 1-4
        'icon' => 'bi-icon-name',
        'color' => 'danger',
        'threshold' => 'Description'
    ],
];
```

Use `getAlertInfo()`, `renderAlertBadge()`, and `sortAlertsBySeverity()` functions to work with alerts.

### Chart Customization

Charts are created via `CasanaCharts` helper in `charts.js`:

```javascript
CasanaCharts.createLineChart(canvas, config);
CasanaCharts.createBPChart(canvas, data);
CasanaCharts.createECGChart(canvas, ecgData);
CasanaCharts.createSparkline(canvas, data, color);
```

Charts automatically update colors on theme change.

## License

Proprietary - Casana Inc.
