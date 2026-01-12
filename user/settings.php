<?php
/**
 * User - Settings
 * User preferences and account settings
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get user ID
$userId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch user data
$user = $api->getUser($userId);

// Get seats
$seats = [];
if (isset($user['seats']) && !empty($user['seats'])) {
    foreach ($user['seats'] as $serial) {
        $seatData = $api->getSeat($serial);
        if ($seatData) {
            $seats[] = $seatData;
        }
    }
}

// Page setup
$pageTitle = 'Settings';
$currentPage = 'settings';
$appName = 'user';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width: 800px;">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="mb-1 h2 fw-bold" style="letter-spacing: -0.02em;">Settings</h1>
        <p class="text-muted mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-gear"></i>
            Manage your preferences and account
        </p>
    </div>
    
    <!-- Profile -->
    <div class="settings-group">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="settings-title mb-0"><i class="bi bi-person me-2"></i>Profile</h5>
            <button class="btn btn-sm btn-outline-secondary" disabled title="Profile editing coming soon">
                <i class="bi bi-pencil me-1"></i>Edit
            </button>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Name</div>
                <div class="settings-description"><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></div>
            </div>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Email</div>
                <div class="settings-description"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></div>
            </div>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Phone</div>
                <div class="settings-description"><?php echo htmlspecialchars($user['cell_phone'] ?? 'Not set'); ?></div>
            </div>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Biometric ID</div>
                <div class="settings-description"><?php echo htmlspecialchars($user['bio_id'] ?? 'N/A'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Display Preferences -->
    <div class="settings-group">
        <h5 class="settings-title"><i class="bi bi-display me-2"></i>Display</h5>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Theme</div>
                <div class="settings-description">Choose light or dark mode</div>
            </div>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="themeChoice" id="themeLight" autocomplete="off">
                <label class="btn btn-outline-secondary btn-sm" for="themeLight">
                    <i class="bi bi-sun"></i> Light
                </label>
                <input type="radio" class="btn-check" name="themeChoice" id="themeDark" autocomplete="off">
                <label class="btn btn-outline-secondary btn-sm" for="themeDark">
                    <i class="bi bi-moon"></i> Dark
                </label>
                <input type="radio" class="btn-check" name="themeChoice" id="themeAuto" autocomplete="off" checked>
                <label class="btn btn-outline-secondary btn-sm" for="themeAuto">
                    <i class="bi bi-circle-half"></i> Auto
                </label>
            </div>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Default View</div>
                <div class="settings-description">Simple or detailed dashboard</div>
            </div>
            <select class="form-select form-select-sm" style="width: auto;" id="defaultView">
                <option value="simple">Simple</option>
                <option value="detailed">Detailed</option>
            </select>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Large Text</div>
                <div class="settings-description">Increase text size for readability</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="largeText">
                <span class="slider"></span>
            </label>
        </div>
    </div>
    
    <!-- Notifications -->
    <div class="settings-group">
        <h5 class="settings-title"><i class="bi bi-bell me-2"></i>Notifications</h5>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Health Alerts</div>
                <div class="settings-description">Get notified about concerning readings</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="healthAlerts" checked>
                <span class="slider"></span>
            </label>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Daily Summary</div>
                <div class="settings-description">Receive a daily health summary</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="dailySummary">
                <span class="slider"></span>
            </label>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Weekly Report</div>
                <div class="settings-description">Get weekly trend analysis</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="weeklyReport" checked>
                <span class="slider"></span>
            </label>
        </div>
    </div>
    
    <!-- Device -->
    <div class="settings-group">
        <h5 class="settings-title"><i class="bi bi-cpu me-2"></i>Your Heart Seat</h5>
        
        <?php if (!empty($seats)): ?>
        <?php foreach ($seats as $seat): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1"><?php echo htmlspecialchars($seat['serial_number']); ?></h6>
                        <div class="small text-muted">
                            Firmware: <?php echo htmlspecialchars($seat['firmware_version'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <span class="badge bg-success-soft">Connected</span>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-4">
                        <div class="small text-muted">Battery</div>
                        <div class="fw-semibold device-stat-value">
                            <?php 
                            $voltage = $seat['battery_voltage'] ?? 3.7;
                            $percent = min(100, max(0, (($voltage - 3.3) / 0.9) * 100));
                            echo round($percent) . '%';
                            ?>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">Recordings</div>
                        <div class="fw-semibold device-stat-value"><?php echo number_format($seat['total_recordings'] ?? 0); ?></div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">Last Used</div>
                        <div class="fw-semibold device-stat-value">
                            <?php echo isset($seat['last_used']) ? formatRelativeTime($seat['last_used']) : 'N/A'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="text-center py-4 text-muted">
            <i class="bi bi-cpu fs-1 mb-2 d-block"></i>
            <p>No Heart Seat devices linked to your account.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Data & Privacy -->
    <div class="settings-group">
        <h5 class="settings-title"><i class="bi bi-shield-check me-2"></i>Data & Privacy</h5>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Share with Care Provider</div>
                <div class="settings-description">
                    <?php echo isset($user['has_care_provider']) && $user['has_care_provider'] ? 'Your data is shared with your care provider' : 'No care provider linked'; ?>
                </div>
            </div>
            <?php if (isset($user['has_care_provider']) && $user['has_care_provider']): ?>
            <span class="badge bg-success-soft">Active</span>
            <?php endif; ?>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Family Monitor Access</div>
                <div class="settings-description">
                    <?php echo isset($user['has_monitor']) && $user['has_monitor'] ? 'Family members can view your health data' : 'No family monitors'; ?>
                </div>
            </div>
            <?php if (isset($user['has_monitor']) && $user['has_monitor']): ?>
            <span class="badge bg-info-soft">Active</span>
            <?php endif; ?>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Export My Data</div>
                <div class="settings-description">Download all your health records</div>
            </div>
            <button class="btn btn-outline-primary btn-sm">
                <i class="bi bi-download me-1"></i> Export
            </button>
        </div>
    </div>
    
    <!-- About -->
    <div class="settings-group">
        <h5 class="settings-title"><i class="bi bi-info-circle me-2"></i>About</h5>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Account Created</div>
            </div>
            <span class="text-muted"><?php echo isset($user['created_at']) ? formatDateTime($user['created_at'], false) : 'N/A'; ?></span>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">Total Recordings</div>
            </div>
            <span class="text-muted"><?php echo number_format($user['total_recordings'] ?? 0); ?></span>
        </div>
        
        <div class="settings-item">
            <div>
                <div class="settings-label">App Version</div>
            </div>
            <span class="text-muted">1.0.0 (Test Portal)</span>
        </div>
    </div>
    
    <!-- Help -->
    <div class="card text-center">
        <div class="card-body py-4">
            <i class="bi bi-question-circle fs-1 text-primary mb-3 d-block"></i>
            <h5>Need Help?</h5>
            <p class="text-muted mb-3">Contact our support team for assistance with your Heart Seat.</p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="https://casana.mcchord.net/docs" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-book me-1"></i> Documentation
                </a>
                <button class="btn btn-primary">
                    <i class="bi bi-chat-dots me-1"></i> Contact Support
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-nav">
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link" href="index.php?id=<?php echo $userId; ?>">
                <i class="bi bi-house"></i>
                Home
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="history.php?id=<?php echo $userId; ?>">
                <i class="bi bi-clock-history"></i>
                History
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="trends.php?id=<?php echo $userId; ?>">
                <i class="bi bi-graph-up"></i>
                Trends
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="settings.php?id=<?php echo $userId; ?>">
                <i class="bi bi-gear"></i>
                Settings
            </a>
        </li>
    </ul>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Theme selection
    const currentTheme = localStorage.getItem('casana-theme') || 'auto';
    
    if (currentTheme === 'light') {
        document.getElementById('themeLight').checked = true;
    } else if (currentTheme === 'dark') {
        document.getElementById('themeDark').checked = true;
    } else {
        document.getElementById('themeAuto').checked = true;
    }
    
    document.getElementById('themeLight').addEventListener('change', function() {
        if (this.checked) {
            ThemeManager.setTheme('light');
            localStorage.setItem('casana-theme', 'light');
        }
    });
    
    document.getElementById('themeDark').addEventListener('change', function() {
        if (this.checked) {
            ThemeManager.setTheme('dark');
            localStorage.setItem('casana-theme', 'dark');
        }
    });
    
    document.getElementById('themeAuto').addEventListener('change', function() {
        if (this.checked) {
            localStorage.removeItem('casana-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            ThemeManager.setTheme(prefersDark ? 'dark' : 'light');
        }
    });
    
    // Load saved preferences
    const savedView = localStorage.getItem('casana-default-view') || 'simple';
    document.getElementById('defaultView').value = savedView;
    
    const largeText = localStorage.getItem('casana-large-text') === 'true';
    document.getElementById('largeText').checked = largeText;
    if (largeText) {
        document.documentElement.style.fontSize = '18px';
    }
    
    // Save preference changes
    document.getElementById('defaultView').addEventListener('change', function() {
        localStorage.setItem('casana-default-view', this.value);
    });
    
    document.getElementById('largeText').addEventListener('change', function() {
        localStorage.setItem('casana-large-text', this.checked);
        document.documentElement.style.fontSize = this.checked ? '18px' : '16px';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
