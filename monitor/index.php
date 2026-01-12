<?php
/**
 * Family Monitor Dashboard
 * At-a-glance health status for monitored loved ones
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get monitor ID
$monitorId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch monitor data
$monitor = $api->getMonitor($monitorId);

// Page setup
$pageTitle = 'Family Dashboard';
$currentPage = 'dashboard';
$appName = 'monitor';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width: 900px;">
    <!-- Header -->
    <div class="text-center mb-5">
        <h1 class="mb-2">Your Family</h1>
        <p class="text-muted">Keep an eye on the people who matter most</p>
    </div>
    
    <!-- Monitored Users Grid -->
    <?php if ($monitor && !empty($monitor['monitored_users'])): ?>
    <div class="row g-4">
        <?php foreach ($monitor['monitored_users'] as $user): ?>
        <?php
        // Get user's latest data
        $userData = $api->getMonitoredUserData($monitorId, $user['user_id']);
        
        // Determine health status
        $status = 'good';
        $statusMessage = 'is doing well';
        $statusClass = 'status-good';
        
        if ($userData && isset($userData['data'])) {
            $latest = $userData['data'];
            if (isset($latest['htn']) && $latest['htn']) {
                $status = 'warning';
                $statusMessage = 'needs attention';
                $statusClass = 'status-warning';
            }
            if (isset($latest['blood_oxygenation']) && $latest['blood_oxygenation'] < 92) {
                $status = 'alert';
                $statusMessage = 'may need care';
                $statusClass = 'status-alert';
            }
        }
        
        $firstName = explode(' ', $user['user_name'])[0];
        ?>
        <div class="col-md-6">
            <div class="monitor-user-card health-card <?php echo $statusClass; ?>" 
                 onclick="window.location='user.php?monitor=<?php echo $monitorId; ?>&id=<?php echo $user['user_id']; ?>'"
                 style="cursor: pointer;">
                <div class="user-avatar">
                    <?php echo getInitials($user['user_name']); ?>
                </div>
                <h3 class="user-name"><?php echo htmlspecialchars($user['user_name']); ?></h3>
                <p class="status-message <?php echo $status; ?>">
                    <?php echo $firstName; ?> <?php echo $statusMessage; ?>
                </p>
                
                <?php if ($userData && isset($userData['data'])): ?>
                <?php $latest = $userData['data']; ?>
                
                <!-- Quick Vitals -->
                <div class="row g-3 mb-4 text-start">
                    <?php if (in_array('blood_pressure', $user['shared_data_types'])): ?>
                    <div class="col-6">
                        <div class="vital-label">Blood Pressure</div>
                        <div class="vital-value" style="font-size: 1.25rem; color: <?php echo (isset($latest['htn']) && $latest['htn']) ? 'var(--status-danger)' : 'var(--text-primary)'; ?>">
                            <?php echo $latest['bp_systolic'] ?? '--'; ?>/<?php echo $latest['bp_diastolic'] ?? '--'; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('heart_rate', $user['shared_data_types'])): ?>
                    <div class="col-6">
                        <div class="vital-label">Heart Rate</div>
                        <div class="vital-value" style="font-size: 1.25rem;">
                            <?php echo $latest['heart_rate'] ?? '--'; ?> <span class="vital-unit">bpm</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('blood_oxygenation', $user['shared_data_types'])): ?>
                    <div class="col-6">
                        <div class="vital-label">Oxygen</div>
                        <div class="vital-value" style="font-size: 1.25rem; color: <?php echo (isset($latest['blood_oxygenation']) && $latest['blood_oxygenation'] < 95) ? 'var(--status-warning)' : 'var(--text-primary)'; ?>">
                            <?php echo isset($latest['blood_oxygenation']) ? round($latest['blood_oxygenation'], 1) : '--'; ?>%
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('agility_score', $user['shared_data_types'])): ?>
                    <div class="col-6">
                        <div class="vital-label">Mobility</div>
                        <div class="vital-value" style="font-size: 1.25rem;">
                            <?php echo isset($latest['agility_score']) ? round($latest['agility_score']) : '--'; ?>/100
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <p class="last-activity mb-0">
                    <i class="bi bi-clock me-1"></i>
                    Last reading: <?php echo formatRelativeTime($latest['recorded_at']); ?>
                </p>
                <?php else: ?>
                <p class="text-muted mb-0">No recent data available</p>
                <?php endif; ?>
                
                <div class="mt-4">
                    <span class="btn btn-outline-primary btn-sm">
                        View Details <i class="bi bi-chevron-right ms-1"></i>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-people empty-icon"></i>
                <h5 class="empty-title">No One to Monitor</h5>
                <p class="empty-description">You're not currently monitoring any family members.</p>
                <a href="../index.php" class="btn btn-primary">Back to Home</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Info Card -->
    <div class="card mt-5">
        <div class="card-body text-center">
            <i class="bi bi-info-circle fs-4 text-primary mb-3 d-block"></i>
            <h5>Understanding Health Status</h5>
            <div class="row g-4 mt-3 text-start">
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="status-dot good"></div>
                        <strong>Doing Well</strong>
                    </div>
                    <p class="small text-muted mb-0">All vitals are within healthy ranges. No concerns at this time.</p>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="status-dot warning"></div>
                        <strong>Needs Attention</strong>
                    </div>
                    <p class="small text-muted mb-0">Some readings are slightly elevated. Worth keeping an eye on.</p>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="status-dot alert"></div>
                        <strong>May Need Care</strong>
                    </div>
                    <p class="small text-muted mb-0">Readings suggest contacting their healthcare provider may be wise.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-nav">
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link active" href="index.php?id=<?php echo $monitorId; ?>">
                <i class="bi bi-house"></i>
                Home
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../index.php">
                <i class="bi bi-arrow-left-circle"></i>
                Exit
            </a>
        </li>
    </ul>
</nav>

<style>
/* Monitor-specific styles */
.monitor-user-card {
    transition: all 0.3s ease;
}

.monitor-user-card:hover {
    transform: translateY(-8px);
}

.monitor-user-card .vital-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 4px;
}

@media (max-width: 768px) {
    .monitor-user-card {
        padding: var(--spacing-lg);
    }
    
    .user-avatar {
        width: 60px !important;
        height: 60px !important;
        font-size: 1.5rem !important;
    }
    
    .user-name {
        font-size: 1.1rem !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
