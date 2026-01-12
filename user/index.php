<?php
/**
 * User Dashboard
 * Personal health overview with customizable widgets
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get user ID
$userId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch user data
$user = $api->getUser($userId);
$trends = $api->getUserTrends($userId, ['days' => 7]);
$recordings = $api->getUserRecordings($userId, ['per_page' => 5]);

// Get latest recording
$latestRecording = null;
if ($recordings && !empty($recordings['recordings'])) {
    $latestRecording = $recordings['recordings'][0];
}

// Page setup
$pageTitle = 'My Health';
$currentPage = 'dashboard';
$appName = 'user';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width: 1000px;">
    <!-- Welcome Header -->
    <div class="text-center mb-4">
        <h1 class="mb-2">Welcome, <?php echo htmlspecialchars(explode(' ', $user['name'] ?? 'User')[0]); ?></h1>
        <p class="text-muted">Here's your health at a glance</p>
    </div>
    
    <!-- View Mode Toggle -->
    <div class="d-flex justify-content-center mb-4">
        <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="viewMode" id="simpleMode" checked>
            <label class="btn btn-outline-primary" for="simpleMode">
                <i class="bi bi-emoji-smile me-1"></i> Simple
            </label>
            <input type="radio" class="btn-check" name="viewMode" id="detailedMode">
            <label class="btn btn-outline-primary" for="detailedMode">
                <i class="bi bi-graph-up me-1"></i> Detailed
            </label>
        </div>
    </div>
    
    <?php if ($latestRecording): ?>
    
    <!-- Simple View -->
    <div id="simpleView">
        <!-- Health Status Card -->
        <?php
        $status = getHealthStatus($latestRecording);
        $statusMessages = [
            'good' => ['Your health looks great!', 'bg-success-soft', 'bi-check-circle', 'text-success'],
            'warning' => ['Some readings need attention', 'bg-warning-soft', 'bi-exclamation-circle', 'text-warning'],
            'alert' => ['Please contact your provider', 'bg-danger-soft', 'bi-exclamation-triangle', 'text-danger']
        ];
        $statusInfo = $statusMessages[$status];
        ?>
        <div class="card mb-4 <?php echo $statusInfo[1]; ?>" style="border: none;">
            <div class="card-body text-center py-5">
                <i class="bi <?php echo $statusInfo[2]; ?> fs-1 <?php echo $statusInfo[3]; ?> mb-3"></i>
                <h2 class="mb-2"><?php echo $statusInfo[0]; ?></h2>
                <p class="text-muted mb-0">Last checked: <?php echo formatRelativeTime($latestRecording['sit_time']); ?></p>
            </div>
        </div>
        
        <!-- Big Number Vitals -->
        <div class="row g-4 mb-4">
            <div class="col-6">
                <div class="card text-center py-4">
                    <div class="text-muted small mb-2">
                        <i class="bi bi-heart-pulse me-1"></i>Blood Pressure
                    </div>
                    <div class="bp-fraction-lg mx-auto" style="color: <?php echo $latestRecording['htn'] ? 'var(--status-danger)' : 'var(--casana-purple)'; ?>">
                        <span class="bp-systolic"><?php echo $latestRecording['bp_systolic']; ?></span>
                        <span class="bp-divider"></span>
                        <span class="bp-diastolic"><?php echo $latestRecording['bp_diastolic']; ?></span>
                    </div>
                    <?php if ($latestRecording['htn']): ?>
                    <span class="badge bg-danger-soft mt-3">Elevated</span>
                    <?php else: ?>
                    <span class="badge bg-success-soft mt-3">Normal</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-6">
                <div class="card text-center py-4">
                    <div class="text-muted small mb-1">
                        <i class="bi bi-activity me-1"></i>Heart Rate
                    </div>
                    <div class="display-4 fw-bold" style="color: var(--casana-purple);">
                        <?php echo $latestRecording['heart_rate']; ?>
                    </div>
                    <div class="text-muted">beats per minute</div>
                    <?php 
                    $hrNormal = $latestRecording['heart_rate'] >= 60 && $latestRecording['heart_rate'] <= 100;
                    ?>
                    <span class="badge <?php echo $hrNormal ? 'bg-success-soft' : 'bg-warning-soft'; ?> mt-2">
                        <?php echo $hrNormal ? 'Normal' : 'Review'; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Secondary Vitals -->
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="card text-center py-3">
                    <div class="small text-muted">Oxygen</div>
                    <div class="fs-3 fw-bold"><?php echo round($latestRecording['blood_oxygenation'], 1); ?>%</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center py-3">
                    <div class="small text-muted">Mobility</div>
                    <div class="fs-3 fw-bold"><?php echo round($latestRecording['agility_score']); ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center py-3">
                    <div class="small text-muted">Weight</div>
                    <div class="fs-3 fw-bold"><?php echo round($latestRecording['seated_weight'] * 1.67, 1); ?></div>
                    <div class="small text-muted">kg est.</div>
                </div>
            </div>
        </div>
        
        <!-- Weekly Summary -->
        <?php if ($trends && count($trends) > 0): ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-week me-2"></i>This Week
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="text-muted small">Readings</div>
                        <div class="fs-4 fw-bold"><?php echo array_sum(array_column($trends, 'recording_count')); ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Avg BP</div>
                        <div class="fs-4 fw-bold">
                            <?php echo round(array_sum(array_column($trends, 'avg_bp_systolic')) / count($trends)); ?>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Avg HR</div>
                        <div class="fs-4 fw-bold">
                            <?php echo round(array_sum(array_column($trends, 'avg_heart_rate')) / count($trends)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Detailed View -->
    <div id="detailedView" style="display: none;">
        <!-- Latest Vitals Grid -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clipboard2-pulse me-2"></i>Latest Reading</span>
                <span class="small text-muted"><?php echo formatDateTime($latestRecording['sit_time']); ?></span>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="vital-display">
                            <div class="vital-icon"><i class="bi bi-heart-pulse"></i></div>
                            <div class="vital-value"><?php echo $latestRecording['bp_systolic']; ?>/<?php echo $latestRecording['bp_diastolic']; ?></div>
                            <div class="vital-label">Blood Pressure (mmHg)</div>
                            <span class="badge <?php echo $latestRecording['htn'] ? 'bg-danger-soft' : 'bg-success-soft'; ?>">
                                <?php echo $latestRecording['htn'] ? 'Hypertensive' : 'Normal'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="vital-display">
                            <div class="vital-icon"><i class="bi bi-activity"></i></div>
                            <div class="vital-value"><?php echo $latestRecording['heart_rate']; ?></div>
                            <div class="vital-label">Heart Rate (bpm)</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="vital-display">
                            <div class="vital-icon"><i class="bi bi-lungs"></i></div>
                            <div class="vital-value"><?php echo round($latestRecording['blood_oxygenation'], 1); ?>%</div>
                            <div class="vital-label">Blood Oxygen (SpO₂)</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="vital-display">
                            <div class="vital-icon"><i class="bi bi-lightning"></i></div>
                            <div class="vital-value"><?php echo round($latestRecording['hrv'], 1); ?></div>
                            <div class="vital-label">HRV (ms)</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="vital-display">
                            <div class="vital-icon"><i class="bi bi-person-walking"></i></div>
                            <div class="vital-value"><?php echo round($latestRecording['agility_score']); ?></div>
                            <div class="vital-label">Agility Score</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="vital-display">
                            <div class="vital-icon"><i class="bi bi-stopwatch"></i></div>
                            <div class="vital-value"><?php echo formatDuration($latestRecording['duration_seconds']); ?></div>
                            <div class="vital-label">Sit Duration</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Blood Pressure Trend Chart -->
        <?php if ($trends && count($trends) > 0): ?>
        <div class="chart-container mb-4">
            <div class="chart-header">
                <h5 class="chart-title">Blood Pressure Trend (7 Days)</h5>
                <div class="chart-legend">
                    <span class="chart-legend-item">
                        <span class="chart-legend-dot" style="background: var(--status-danger);"></span> Systolic
                    </span>
                    <span class="chart-legend-item">
                        <span class="chart-legend-dot" style="background: var(--casana-purple);"></span> Diastolic
                    </span>
                </div>
            </div>
            <div style="height: 250px;">
                <canvas id="bpChart"></canvas>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="chart-container h-100">
                    <div class="chart-header">
                        <h5 class="chart-title">Heart Rate</h5>
                    </div>
                    <div style="height: 180px;">
                        <canvas id="hrChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container h-100">
                    <div class="chart-header">
                        <h5 class="chart-title">Oxygen Saturation</h5>
                    </div>
                    <div style="height: 180px;">
                        <canvas id="spo2Chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Recordings Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Recent Readings</span>
                <a href="history.php?id=<?php echo $userId; ?>" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>BP</th>
                                <th>HR</th>
                                <th>SpO₂</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recordings['recordings'] as $rec): ?>
                            <tr onclick="window.location='recording.php?id=<?php echo $rec['id']; ?>&user=<?php echo $userId; ?>'" style="cursor: pointer;">
                                <td><?php echo formatDateTime($rec['sit_time']); ?></td>
                                <td class="<?php echo $rec['htn'] ? 'text-danger fw-semibold' : ''; ?>">
                                    <?php echo $rec['bp_systolic']; ?>/<?php echo $rec['bp_diastolic']; ?>
                                </td>
                                <td><?php echo $rec['heart_rate']; ?></td>
                                <td><?php echo round($rec['blood_oxygenation'], 1); ?>%</td>
                                <td><?php echo formatDuration($rec['duration_seconds']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-clipboard2-pulse empty-icon"></i>
                <h5 class="empty-title">No Health Data Yet</h5>
                <p class="empty-description">Start using your Heart Seat to see your health data here.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-nav">
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link active" href="index.php?id=<?php echo $userId; ?>">
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
            <a class="nav-link" href="settings.php?id=<?php echo $userId; ?>">
                <i class="bi bi-gear"></i>
                Settings
            </a>
        </li>
    </ul>
</nav>

<script>
const trendsData = <?php echo json_encode($trends ?? []); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // View mode toggle
    document.getElementById('simpleMode').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('simpleView').style.display = 'block';
            document.getElementById('detailedView').style.display = 'none';
        }
    });
    
    document.getElementById('detailedMode').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('simpleView').style.display = 'none';
            document.getElementById('detailedView').style.display = 'block';
            initDetailedCharts();
        }
    });
});

let chartsInitialized = false;

function initDetailedCharts() {
    if (chartsInitialized || !trendsData || trendsData.length === 0) return;
    chartsInitialized = true;
    
    const colors = CasanaCharts.getColors();
    const labels = trendsData.map(t => new Date(t.date).toLocaleDateString('en-US', { weekday: 'short' }));
    
    // BP Chart
    CasanaCharts.createBPChart(
        document.getElementById('bpChart'),
        trendsData.map(t => ({
            date: new Date(t.date).toLocaleDateString('en-US', { weekday: 'short' }),
            systolic: t.avg_bp_systolic,
            diastolic: t.avg_bp_diastolic
        }))
    );
    
    // HR Chart
    CasanaCharts.createLineChart(document.getElementById('hrChart'), {
        data: {
            labels: labels,
            datasets: [{
                label: 'Heart Rate',
                data: trendsData.map(t => t.avg_heart_rate),
                borderColor: colors.danger,
                backgroundColor: colors.danger + '20',
                fill: true,
            }]
        }
    });
    
    // SpO2 Chart
    CasanaCharts.createLineChart(document.getElementById('spo2Chart'), {
        data: {
            labels: labels,
            datasets: [{
                label: 'SpO₂',
                data: trendsData.map(t => t.avg_blood_oxygenation),
                borderColor: colors.primary,
                backgroundColor: colors.primary + '20',
                fill: true,
            }]
        },
        options: {
            scales: {
                y: { min: 90, max: 100 }
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
