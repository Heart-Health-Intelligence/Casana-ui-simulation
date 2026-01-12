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
$providerId = isset($providerId) ? $providerId : (isset($_GET['id']) ? intval($_GET['id']) : 1);

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
    <meta name="theme-color" content="#5b5fef">
    <meta name="description" content="Casana Smart Toilet Seat Health Monitoring">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Preconnect to external resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    
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

<!-- Skip Navigation Link (Accessibility) -->
<a href="#main-content" class="skip-link">Skip to main content</a>

<?php if (!$hideNav): ?>
<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-md sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $basePath; ?>/index.php">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="16" cy="16" r="14" fill="#5b5fef"/>
                <path d="M16 8C16 8 10 14 10 18C10 21.3137 12.6863 24 16 24C19.3137 24 22 21.3137 22 18C22 14 16 8 16 8Z" fill="white"/>
            </svg>
            <span class="fw-bold tracking-tight">Casana</span>
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
            <!-- Provider: Global patient search (sidebar handles main nav) -->
            <div class="navbar-nav me-auto">
                <form class="provider-search-form d-none d-md-flex position-relative" role="search" onsubmit="return handlePatientSearch(event);">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="search" 
                               class="form-control border-start-0 ps-0" 
                               id="globalPatientSearch"
                               placeholder="Search patients..." 
                               aria-label="Search patients"
                               autocomplete="off">
                    </div>
                    <!-- Search results dropdown (populated by JavaScript) -->
                    <div class="global-search-results" id="globalSearchResults" role="listbox" aria-label="Search results"></div>
                </form>
            </div>
            <?php elseif ($appName === 'monitor'): ?>
            <!-- Monitor Navigation -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-people-fill me-1"></i> Family
                    </a>
                </li>
            </ul>
            <?php elseif ($appName === 'user'): ?>
            <!-- User Navigation -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php?id=<?php echo $userId; ?>">
                        <i class="bi bi-house me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'history' ? 'active' : ''; ?>" href="history.php?id=<?php echo $userId; ?>">
                        <i class="bi bi-clock-history me-1"></i> History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'trends' ? 'active' : ''; ?>" href="trends.php?id=<?php echo $userId; ?>">
                        <i class="bi bi-graph-up me-1"></i> Trends
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" href="settings.php?id=<?php echo $userId; ?>">
                        <i class="bi bi-gear me-1"></i> Settings
                    </a>
                </li>
            </ul>
            <?php endif; ?>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Theme Toggle -->
                <button class="theme-toggle" type="button" aria-label="Toggle dark mode" title="Toggle dark mode">
                    <span class="theme-toggle-icon sun-icon">
                        <i class="bi bi-sun"></i>
                    </span>
                    <span class="theme-toggle-icon moon-icon">
                        <i class="bi bi-moon"></i>
                    </span>
                </button>
                
                <?php if ($appName && $appName !== 'superuser' && $appName !== 'monitor' && $appName !== 'provider'): ?>
                <!-- Back to Superuser -->
                <a href="<?php echo $basePath; ?>/index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    Switch Role
                </a>
                <?php endif; ?>
                
                <?php if ($appName === 'provider'): ?>
                <!-- Mobile menu toggle for sidebar -->
                <button class="btn btn-outline-secondary d-md-none" type="button" onclick="toggleProviderSidebar()" aria-label="Toggle navigation menu">
                    <i class="bi bi-list"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

<main id="main-content" class="<?php echo $appName === 'provider' ? 'main-with-sidebar' : ''; ?>" role="main">
