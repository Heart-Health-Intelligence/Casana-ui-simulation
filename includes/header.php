<?php
/**
 * Casana Shared Header
 * Include at the top of each page
 * 
 * Variables that can be set before including:
 * - $pageTitle: Page title (will be appended to "Casana - ")
 * - $currentPage: Current page identifier for nav highlighting
 * - $appName: Application name ('user', 'monitor', 'provider', or 'superuser')
 * - $bodyClass: Additional body classes
 * - $hideNav: Set to true to hide navigation
 */

$pageTitle = isset($pageTitle) ? $pageTitle . ' - Casana' : 'Casana Health Monitoring';
$currentPage = isset($currentPage) ? $currentPage : '';
$appName = isset($appName) ? $appName : '';
$bodyClass = isset($bodyClass) ? $bodyClass : '';
$hideNav = isset($hideNav) ? $hideNav : false;

// Base path calculation for assets
$basePath = '';
if (strpos($_SERVER['REQUEST_URI'], '/user/') !== false) {
    $basePath = '..';
} elseif (strpos($_SERVER['REQUEST_URI'], '/monitor/') !== false) {
    $basePath = '..';
} elseif (strpos($_SERVER['REQUEST_URI'], '/provider/') !== false) {
    $basePath = '..';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="Casana Smart Toilet Seat Health Monitoring">
    
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Preload critical assets -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Casana Styles -->
    <link href="<?php echo $basePath; ?>/assets/css/casana.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/assets/css/components.css" rel="stylesheet">
    
    <!-- Theme initialization (prevents flash) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('casana-theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            } else {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
            }
        })();
    </script>
    
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>" data-app="<?php echo htmlspecialchars($appName); ?>">

<?php if (!$hideNav): ?>
<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $basePath; ?>/index.php">
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="16" cy="16" r="14" fill="#6A6EFF"/>
                <path d="M16 8C16 8 10 14 10 18C10 21.3137 12.6863 24 16 24C19.3137 24 22 21.3137 22 18C22 14 16 8 16 8Z" fill="white"/>
            </svg>
            <span>Casana</span>
            <?php if ($appName): ?>
            <span class="badge bg-info-soft text-primary-brand ms-2"><?php echo ucfirst($appName); ?></span>
            <?php endif; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="mainNav">
            <?php if ($appName === 'superuser' || $appName === ''): ?>
            <!-- Superuser Navigation -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/index.php">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>
            </ul>
            <?php elseif ($appName === 'provider'): ?>
            <!-- Provider Navigation -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-grid-1x2 me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'patients' ? 'active' : ''; ?>" href="patients.php">
                        <i class="bi bi-people me-1"></i> Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'alerts' ? 'active' : ''; ?>" href="alerts.php">
                        <i class="bi bi-exclamation-triangle me-1"></i> Alerts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'analytics' ? 'active' : ''; ?>" href="analytics.php">
                        <i class="bi bi-graph-up me-1"></i> Analytics
                    </a>
                </li>
            </ul>
            <?php elseif ($appName === 'monitor'): ?>
            <!-- Monitor Navigation -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>
            </ul>
            <?php elseif ($appName === 'user'): ?>
            <!-- User Navigation -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'history' ? 'active' : ''; ?>" href="history.php">
                        <i class="bi bi-clock-history me-1"></i> History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'trends' ? 'active' : ''; ?>" href="trends.php">
                        <i class="bi bi-graph-up me-1"></i> Trends
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" href="settings.php">
                        <i class="bi bi-gear me-1"></i> Settings
                    </a>
                </li>
            </ul>
            <?php endif; ?>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Theme Toggle -->
                <div class="theme-toggle" role="button" aria-label="Toggle dark mode">
                    <span class="theme-toggle-icon sun-icon">
                        <i class="bi bi-sun"></i>
                    </span>
                    <span class="theme-toggle-icon moon-icon">
                        <i class="bi bi-moon"></i>
                    </span>
                </div>
                
                <?php if ($appName && $appName !== 'superuser'): ?>
                <!-- Back to Superuser -->
                <a href="<?php echo $basePath; ?>/index.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Switch Role
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="<?php echo $appName === 'provider' ? 'main-with-sidebar' : ''; ?>">
