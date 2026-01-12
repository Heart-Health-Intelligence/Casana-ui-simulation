<?php
/**
 * Family Monitor - User Detail View
 * Detailed health information for a monitored family member
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get IDs
$monitorId = isset($_GET['monitor']) ? intval($_GET['monitor']) : 1;
$userId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch data
$monitor = $api->getMonitor($monitorId);
$userData = $api->getMonitoredUserData($monitorId, $userId);
$trends = $api->getUserTrends($userId, ['days' => 14]);

// Find the monitored user info
$monitoredUser = null;
if ($monitor && isset($monitor['monitored_users'])) {
    foreach ($monitor['monitored_users'] as $u) {
        if ($u['user_id'] == $userId) {
            $monitoredUser = $u;
            break;
        }
    }
}

// Page setup
$userName = $monitoredUser ? $monitoredUser['user_name'] : 'Family Member';
$pageTitle = $userName;
$currentPage = 'user';
$appName = 'monitor';

require_once __DIR__ . '/../includes/header.php';

// Shared data types
$sharedTypes = $monitoredUser ? $monitoredUser['shared_data_types'] : [];
$firstName = explode(' ', $userName)[0];
?>

<div class="container py-4" style="max-width: 900px;">
    <!-- Back Button -->
    <a href="index.php?id=<?php echo $monitorId; ?>" class="btn btn-outline-secondary mb-4">
        <i class="bi bi-arrow-left me-2"></i>Back to Family
    </a>
    
    <!-- User Header -->
    <div class="card mb-4">
        <div class="card-body text-center py-5">
            <div class="entity-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                <?php echo getInitials($userName); ?>
            </div>
            <h1 class="mb-1"><?php echo htmlspecialchars($userName); ?></h1>
            <p class="text-muted mb-3">Age <?php echo $monitoredUser ? $monitoredUser['user_age'] : 'N/A'; ?></p>
            
            <?php if ($userData && isset($userData['data'])): ?>
            <?php 
            $latest = $userData['data'];
            $status = 'good';
            $statusText = 'is doing well';
            
            if (isset($latest['htn']) && $latest['htn']) {
                $status = 'warning';
                $statusText = 'needs attention';
            }
            if (isset($latest['blood_oxygenation']) && $latest['blood_oxygenation'] < 92) {
                $status = 'alert';
                $statusText = 'may need care';
            }
            ?>
            <div class="health-status-badge <?php echo $status === 'good' ? 'stable' : ($status === 'warning' ? 'stable' : 'deteriorating'); ?> fs-5 px-4 py-2">
                <i class="bi bi-<?php echo $status === 'good' ? 'check-circle' : ($status === 'warning' ? 'exclamation-circle' : 'exclamation-triangle'); ?>"></i>
                <?php echo $firstName; ?> <?php echo $statusText; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($userData && isset($userData['data'])): ?>
    <?php $latest = $userData['data']; ?>
    
    <!-- Latest Reading Time -->
    <div class="text-center mb-4">
        <span class="text-muted">
            <i class="bi bi-clock me-1"></i>
            Latest reading: <?php echo formatRelativeTime($latest['recorded_at']); ?>
        </span>
    </div>
    
    <!-- Vitals Grid -->
    <div class="row g-4 mb-4">
        <?php if (in_array('blood_pressure', $sharedTypes)): ?>
        <div class="col-6 col-md-4">
            <div class="card vital-card-lg h-100 text-center">
                <i class="bi bi-heart-pulse fs-3 text-danger mb-2"></i>
                <div class="vital-label mb-2">Blood Pressure</div>
                <div class="vital-value" style="color: <?php echo (isset($latest['htn']) && $latest['htn']) ? 'var(--status-danger)' : 'var(--text-primary)'; ?>">
                    <?php echo $latest['bp_systolic']; ?>/<?php echo $latest['bp_diastolic']; ?>
                </div>
                <div class="vital-unit">mmHg</div>
                <?php if (isset($latest['htn']) && $latest['htn']): ?>
                <span class="badge bg-danger-soft mt-2">Elevated</span>
                <?php else: ?>
                <span class="badge bg-success-soft mt-2">Normal</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('heart_rate', $sharedTypes)): ?>
        <div class="col-6 col-md-4">
            <div class="card vital-card-lg h-100 text-center">
                <i class="bi bi-activity fs-3 mb-2" style="color: var(--casana-maroon);"></i>
                <div class="vital-label mb-2">Heart Rate</div>
                <div class="vital-value"><?php echo $latest['heart_rate']; ?></div>
                <div class="vital-unit">beats/min</div>
                <?php 
                $hrStatus = 'Normal';
                $hrClass = 'bg-success-soft';
                if ($latest['heart_rate'] < 60) {
                    $hrStatus = 'Low';
                    $hrClass = 'bg-warning-soft';
                } elseif ($latest['heart_rate'] > 100) {
                    $hrStatus = 'High';
                    $hrClass = 'bg-warning-soft';
                }
                ?>
                <span class="badge <?php echo $hrClass; ?> mt-2"><?php echo $hrStatus; ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('blood_oxygenation', $sharedTypes)): ?>
        <div class="col-6 col-md-4">
            <div class="card vital-card-lg h-100 text-center">
                <i class="bi bi-lungs fs-3 mb-2" style="color: var(--casana-baby-blue);"></i>
                <div class="vital-label mb-2">Oxygen Level</div>
                <div class="vital-value" style="color: <?php echo ($latest['blood_oxygenation'] < 95) ? 'var(--status-warning)' : 'var(--text-primary)'; ?>">
                    <?php echo round($latest['blood_oxygenation'], 1); ?>
                </div>
                <div class="vital-unit">%</div>
                <?php 
                $o2Status = 'Normal';
                $o2Class = 'bg-success-soft';
                if ($latest['blood_oxygenation'] < 92) {
                    $o2Status = 'Low';
                    $o2Class = 'bg-danger-soft';
                } elseif ($latest['blood_oxygenation'] < 95) {
                    $o2Status = 'Slightly Low';
                    $o2Class = 'bg-warning-soft';
                }
                ?>
                <span class="badge <?php echo $o2Class; ?> mt-2"><?php echo $o2Status; ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('agility_score', $sharedTypes)): ?>
        <div class="col-6 col-md-4">
            <div class="card vital-card-lg h-100 text-center">
                <i class="bi bi-person-walking fs-3 mb-2" style="color: var(--casana-green);"></i>
                <div class="vital-label mb-2">Mobility Score</div>
                <div class="vital-value"><?php echo round($latest['agility_score']); ?></div>
                <div class="vital-unit">out of 100</div>
                <?php 
                $agilityStatus = 'Good';
                $agilityClass = 'bg-success-soft';
                if ($latest['agility_score'] < 40) {
                    $agilityStatus = 'Reduced';
                    $agilityClass = 'bg-warning-soft';
                } elseif ($latest['agility_score'] < 60) {
                    $agilityStatus = 'Fair';
                    $agilityClass = 'bg-info-soft';
                }
                ?>
                <span class="badge <?php echo $agilityClass; ?> mt-2"><?php echo $agilityStatus; ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Weekly Trend -->
    <?php if (in_array('blood_pressure', $sharedTypes) && $trends && count($trends) > 0): ?>
    <div class="chart-container mb-4">
        <div class="chart-header">
            <h5 class="chart-title">Blood Pressure This Week</h5>
            <span class="small text-muted">Daily averages</span>
        </div>
        <div style="height: 200px;">
            <canvas id="bpTrendChart"></canvas>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Usage Patterns -->
    <?php if (in_array('usage_patterns', $sharedTypes)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-calendar-check me-2"></i>Activity This Week
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted">Readings this week</span>
                </div>
                <div>
                    <span class="fs-4 fw-bold"><?php echo $userData['recordings_count'] ?? '--'; ?></span>
                </div>
            </div>
            <hr>
            <p class="small text-muted mb-0">
                <i class="bi bi-info-circle me-1"></i>
                <?php echo $firstName; ?> is using the Heart Seat regularly. This consistent data helps track health trends over time.
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Summary Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-clipboard-pulse me-2"></i>What This Means
        </div>
        <div class="card-body">
            <?php if ($status === 'good'): ?>
            <div class="d-flex align-items-start gap-3">
                <div class="rounded-circle bg-success-soft p-3">
                    <i class="bi bi-check-lg fs-4" style="color: var(--status-success);"></i>
                </div>
                <div>
                    <h5 class="mb-2">Everything looks good!</h5>
                    <p class="text-muted mb-0">
                        <?php echo $firstName; ?>'s vitals are within healthy ranges. Continue regular monitoring 
                        and consult their healthcare provider with any questions.
                    </p>
                </div>
            </div>
            <?php elseif ($status === 'warning'): ?>
            <div class="d-flex align-items-start gap-3">
                <div class="rounded-circle bg-warning-soft p-3">
                    <i class="bi bi-exclamation-lg fs-4" style="color: var(--status-warning);"></i>
                </div>
                <div>
                    <h5 class="mb-2">Some readings are elevated</h5>
                    <p class="text-muted mb-0">
                        <?php echo $firstName; ?>'s blood pressure is higher than normal. This could be temporary, 
                        but if it continues, consider discussing with their healthcare provider.
                    </p>
                </div>
            </div>
            <?php else: ?>
            <div class="d-flex align-items-start gap-3">
                <div class="rounded-circle bg-danger-soft p-3">
                    <i class="bi bi-exclamation-triangle fs-4" style="color: var(--status-danger);"></i>
                </div>
                <div>
                    <h5 class="mb-2">Please check in on <?php echo $firstName; ?></h5>
                    <p class="text-muted mb-0">
                        Some readings are concerning. Consider calling to check in, and if there are any symptoms 
                        or concerns, contact their healthcare provider.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-clock-history empty-icon"></i>
                <h5 class="empty-title">No Recent Data</h5>
                <p class="empty-description">We haven't received any readings from <?php echo $firstName; ?> recently.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-nav">
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link" href="index.php?id=<?php echo $monitorId; ?>">
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

<?php if ($trends && count($trends) > 0): ?>
<script>
const trendsData = <?php echo json_encode($trends); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const colors = CasanaCharts.getColors();
    
    // Only show last 7 days
    const recentTrends = trendsData.slice(-7);
    
    CasanaCharts.createBPChart(
        document.getElementById('bpTrendChart'),
        recentTrends.map(t => ({
            date: new Date(t.date).toLocaleDateString('en-US', { weekday: 'short' }),
            systolic: t.avg_bp_systolic,
            diastolic: t.avg_bp_diastolic
        }))
    );
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
