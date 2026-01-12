<?php
/**
 * Care Provider Dashboard
 * Main overview for healthcare providers
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get provider ID from URL or session
$providerId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch provider data
$provider = $api->getCareProvider($providerId);
$populationStats = $api->getPopulationStats($providerId);
$alerts = $api->getAlertRecordings(['per_page' => 10, 'days' => 7]);

// Page setup
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
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
                <a class="nav-link active" href="index.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-grid-1x2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="patients.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-people"></i>
                    Patients
                    <span class="badge bg-info-soft ms-auto"><?php echo $provider['total_patients'] ?? 0; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="alerts.php?id=<?php echo $providerId; ?>">
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
                <h1>Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($provider['name'] ?? 'Doctor'); ?></p>
            </div>
            <div class="col-auto">
                <span class="text-muted">
                    <i class="bi bi-clock me-1"></i>
                    <?php echo date('l, F j, Y'); ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-value"><?php echo $provider['total_patients'] ?? 0; ?></div>
                <div class="stat-label">Total Patients</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-value" style="color: var(--status-danger);">
                    <?php echo $alerts ? $alerts['pagination']['total'] : 0; ?>
                </div>
                <div class="stat-label">Active Alerts</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-value">
                    <?php echo $populationStats ? round($populationStats['htn_rate'], 1) : 0; ?>%
                </div>
                <div class="stat-label">HTN Rate</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-value" style="color: var(--status-success);">
                    <?php echo $populationStats ? ($populationStats['patients_with_trends'] ?? 0) : 0; ?>
                </div>
                <div class="stat-label">Patients with Trends</div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Recent Alerts -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-exclamation-triangle text-warning me-2"></i>Recent Alerts</span>
                    <a href="alerts.php?id=<?php echo $providerId; ?>" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($alerts && !empty($alerts['recordings'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Alert Type</th>
                                    <th>Vitals</th>
                                    <th>Time</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($alerts['recordings'], 0, 5) as $alert): ?>
                                <tr class="table-clickable" onclick="window.location='patient.php?provider=<?php echo $providerId; ?>&id=<?php echo $alert['user_id']; ?>'">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="entity-avatar" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                                <?php echo getInitials($alert['user_name']); ?>
                                            </div>
                                            <div>
                                                <div class="fw-medium"><?php echo htmlspecialchars($alert['user_name']); ?></div>
                                                <div class="small text-muted">Age <?php echo $alert['user_age']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php foreach ($alert['alert_reasons'] as $reason): ?>
                                        <span class="badge bg-danger-soft me-1">
                                            <?php 
                                            $labels = [
                                                'hypertension' => 'HTN',
                                                'extended_sit' => 'Long Sit',
                                                'low_spo2' => 'Low O₂'
                                            ];
                                            echo $labels[$reason] ?? $reason;
                                            ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <span class="text-muted">BP:</span> <?php echo $alert['bp_systolic']; ?>/<?php echo $alert['bp_diastolic']; ?>
                                            <span class="text-muted ms-2">SpO₂:</span> <?php echo $alert['blood_oxygenation']; ?>%
                                        </div>
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
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-check-circle empty-icon" style="color: var(--status-success);"></i>
                        <h5 class="empty-title">No Active Alerts</h5>
                        <p class="empty-description">All patients are within normal parameters.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Population Health -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-pie-chart me-2"></i>Population Health
                </div>
                <div class="card-body">
                    <?php if ($populationStats): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Average Age</span>
                            <span class="fw-semibold"><?php echo round($populationStats['avg_age']); ?> years</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Avg Recordings/Patient</span>
                            <span class="fw-semibold"><?php echo round($populationStats['avg_recordings_per_patient']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">HTN Rate</span>
                            <span class="fw-semibold"><?php echo round($populationStats['htn_rate'], 1); ?>%</span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Trend Breakdown</h6>
                    <?php if (isset($populationStats['trend_breakdown'])): ?>
                    <div class="d-flex flex-column gap-2">
                        <?php 
                        $trendColors = [
                            'stable' => 'success',
                            'improving' => 'info',
                            'deteriorating' => 'danger'
                        ];
                        foreach ($populationStats['trend_breakdown'] as $type => $count): 
                        ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="health-status-badge <?php echo $type; ?>">
                                <i class="bi bi-<?php echo $type === 'improving' ? 'arrow-up' : ($type === 'deteriorating' ? 'arrow-down' : 'dash'); ?>"></i>
                                <?php echo ucfirst($type); ?>
                            </span>
                            <span class="fw-semibold"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Gender Distribution</h6>
                    <?php if (isset($populationStats['gender_distribution'])): ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($populationStats['gender_distribution'] as $gender => $count): ?>
                        <div class="d-flex justify-content-between">
                            <span><?php echo ucfirst($gender); ?></span>
                            <span class="fw-semibold"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-graph-up-arrow fs-1 mb-2 d-block"></i>
                        <p>No population data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Patient List Preview -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people me-2"></i>Your Patients</span>
                    <a href="patients.php?id=<?php echo $providerId; ?>" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($provider && isset($provider['patients'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Recordings</th>
                                    <th>Last Activity</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($provider['patients'], 0, 8) as $patient): ?>
                                <tr class="table-clickable" onclick="window.location='patient.php?provider=<?php echo $providerId; ?>&id=<?php echo $patient['user_id']; ?>'">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="entity-avatar" style="width: 36px; height: 36px; font-size: 0.8rem;">
                                                <?php echo getInitials($patient['name']); ?>
                                            </div>
                                            <span class="fw-medium"><?php echo htmlspecialchars($patient['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $patient['age']; ?></td>
                                    <td><?php echo $patient['gender']; ?></td>
                                    <td><?php echo number_format($patient['total_recordings']); ?></td>
                                    <td>
                                        <span class="small text-muted">
                                            <?php echo $patient['last_recording'] ? formatRelativeTime($patient['last_recording']) : 'Never'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $statusClass = $patient['trend_type'] === 'stable' ? 'success' : ($patient['trend_type'] === 'improving' ? 'info' : 'danger');
                                        ?>
                                        <span class="health-status-badge <?php echo $patient['trend_type']; ?>">
                                            <?php echo ucfirst($patient['trend_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-people empty-icon"></i>
                        <h5 class="empty-title">No Patients Yet</h5>
                        <p class="empty-description">Patients will appear here once assigned.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
