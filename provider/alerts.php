<?php
/**
 * Care Provider - Alerts
 * Patients with concerning readings
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get provider ID
$providerId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch data
$provider = $api->getCareProvider($providerId);
$alerts = $api->getAlertRecordings(['per_page' => 50, 'days' => 7]);
$extendedSits = $api->getExtendedRecordings(['per_page' => 20]);

// Page setup
$pageTitle = 'Alerts';
$currentPage = 'alerts';
$appName = 'provider';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Provider Sidebar -->
<aside class="sidebar hide-mobile">
    <div class="sidebar-header">
        <div class="d-flex align-items-center gap-3">
            <div class="entity-avatar" style="width: 48px; height: 48px; font-size: 1.25rem;">
                <?php echo getInitials($provider['name'] ?? 'Dr'); ?>
            </div>
            <div>
                <div class="fw-semibold"><?php echo htmlspecialchars($provider['name'] ?? 'Provider'); ?></div>
                <div class="small text-muted"><?php echo htmlspecialchars($provider['practice_name'] ?? ''); ?></div>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="index.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-grid-1x2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="patients.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-people"></i>
                    Patients
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="alerts.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-exclamation-triangle"></i>
                    Alerts
                    <?php if ($alerts && $alerts['pagination']['total'] > 0): ?>
                    <span class="badge bg-danger-soft ms-auto"><?php echo $alerts['pagination']['total']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="analytics.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-graph-up"></i>
                    Analytics
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../index.php" class="btn btn-outline-primary w-100">
            <i class="bi bi-arrow-left me-2"></i>
            Switch Role
        </a>
    </div>
</aside>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1>Patient Alerts</h1>
                <p class="mb-0">Patients with readings that may require attention</p>
            </div>
            <div class="col-auto">
                <div class="time-selector">
                    <button class="time-option" data-days="1">24h</button>
                    <button class="time-option active" data-days="7">7D</button>
                    <button class="time-option" data-days="30">30D</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alert Summary Cards -->
    <div class="row g-4 mb-4">
        <?php
        $htnCount = 0;
        $lowO2Count = 0;
        $extendedCount = 0;
        
        if ($alerts && !empty($alerts['recordings'])) {
            foreach ($alerts['recordings'] as $alert) {
                if (in_array('hypertension', $alert['alert_reasons'])) $htnCount++;
                if (in_array('low_spo2', $alert['alert_reasons'])) $lowO2Count++;
                if (in_array('extended_sit', $alert['alert_reasons'])) $extendedCount++;
            }
        }
        ?>
        <div class="col-md-4">
            <div class="card h-100" style="border-left: 4px solid var(--status-danger);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-danger-soft p-3">
                                <i class="bi bi-heart-pulse fs-4 text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0"><?php echo $htnCount; ?></h3>
                            <p class="text-muted mb-0">Hypertension Alerts</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100" style="border-left: 4px solid var(--status-warning);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-warning-soft p-3">
                                <i class="bi bi-lungs fs-4" style="color: var(--status-warning);"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0"><?php echo $lowO2Count; ?></h3>
                            <p class="text-muted mb-0">Low Oxygen Alerts</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100" style="border-left: 4px solid var(--casana-purple);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-info-soft p-3">
                                <i class="bi bi-clock-history fs-4 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0"><?php echo $extendedCount; ?></h3>
                            <p class="text-muted mb-0">Extended Sit Alerts</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alert Filters -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-primary btn-sm active" data-filter="all">
                    All Alerts <span class="badge bg-primary ms-1"><?php echo $alerts ? $alerts['pagination']['total'] : 0; ?></span>
                </button>
                <button class="btn btn-outline-danger btn-sm" data-filter="hypertension">
                    <i class="bi bi-heart-pulse me-1"></i>Hypertension
                </button>
                <button class="btn btn-outline-warning btn-sm" data-filter="low_spo2">
                    <i class="bi bi-lungs me-1"></i>Low O₂
                </button>
                <button class="btn btn-outline-secondary btn-sm" data-filter="extended_sit">
                    <i class="bi bi-clock me-1"></i>Extended Sit
                </button>
            </div>
        </div>
    </div>
    
    <!-- Alert List -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($alerts && !empty($alerts['recordings'])): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="alertsTable">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Alert Type</th>
                            <th>Blood Pressure</th>
                            <th>SpO₂</th>
                            <th>HR</th>
                            <th>Duration</th>
                            <th>Time</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts['recordings'] as $alert): ?>
                        <tr class="table-clickable alert-row" 
                            data-reasons="<?php echo implode(',', $alert['alert_reasons']); ?>"
                            onclick="window.location='patient.php?provider=<?php echo $providerId; ?>&id=<?php echo $alert['user_id']; ?>'">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="entity-avatar" style="width: 36px; height: 36px; font-size: 0.8rem;">
                                        <?php echo getInitials($alert['user_name']); ?>
                                    </div>
                                    <div>
                                        <div class="fw-medium"><?php echo htmlspecialchars($alert['user_name']); ?></div>
                                        <div class="small text-muted">Age <?php echo $alert['user_age']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($alert['alert_reasons'] as $reason): ?>
                                    <?php
                                    $badgeClass = 'bg-secondary-soft';
                                    $label = $reason;
                                    if ($reason === 'hypertension') {
                                        $badgeClass = 'bg-danger-soft';
                                        $label = 'HTN';
                                    } elseif ($reason === 'low_spo2') {
                                        $badgeClass = 'bg-warning-soft';
                                        $label = 'Low O₂';
                                    } elseif ($reason === 'extended_sit') {
                                        $badgeClass = 'bg-info-soft';
                                        $label = 'Long Sit';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $label; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="<?php echo $alert['htn'] ? 'text-danger fw-semibold' : ''; ?>">
                                <?php echo $alert['bp_systolic']; ?>/<?php echo $alert['bp_diastolic']; ?>
                            </td>
                            <td class="<?php echo $alert['blood_oxygenation'] < 92 ? 'text-danger fw-semibold' : ($alert['blood_oxygenation'] < 95 ? 'text-warning' : ''); ?>">
                                <?php echo round($alert['blood_oxygenation'], 1); ?>%
                            </td>
                            <td><?php echo $alert['heart_rate']; ?></td>
                            <td class="<?php echo $alert['duration_seconds'] > 1800 ? 'text-warning' : ''; ?>">
                                <?php echo formatDuration($alert['duration_seconds']); ?>
                            </td>
                            <td>
                                <span class="small text-muted"><?php echo formatRelativeTime($alert['sit_time']); ?></span>
                            </td>
                            <td>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($alerts['pagination']['pages'] > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li class="page-item <?php echo $alerts['pagination']['page'] <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $providerId; ?>&page=<?php echo $alerts['pagination']['page'] - 1; ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= min(5, $alerts['pagination']['pages']); $i++): ?>
                        <li class="page-item <?php echo $i === $alerts['pagination']['page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $providerId; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $alerts['pagination']['page'] >= $alerts['pagination']['pages'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $providerId; ?>&page=<?php echo $alerts['pagination']['page'] + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-check-circle empty-icon" style="color: var(--status-success);"></i>
                <h5 class="empty-title">No Active Alerts</h5>
                <p class="empty-description">All patients are within normal parameters. Great news!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter buttons
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            filterAlerts(filter);
        });
    });
    
    // Time selector
    document.querySelectorAll('.time-option').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.time-option').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            // In production, would reload data
            console.log('Loading alerts for', this.dataset.days, 'days');
        });
    });
});

function filterAlerts(filter) {
    const rows = document.querySelectorAll('.alert-row');
    
    rows.forEach(row => {
        const reasons = row.dataset.reasons.split(',');
        
        if (filter === 'all') {
            row.style.display = '';
        } else if (reasons.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
