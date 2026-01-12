<?php
/**
 * Care Provider - Patient Detail
 * Individual patient health record and vitals
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get IDs
$providerId = isset($_GET['provider']) ? intval($_GET['provider']) : 1;
$userId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch data
$provider = $api->getCareProvider($providerId);
$user = $api->getUser($userId);
$trends = $api->getUserTrends($userId, ['days' => 30]);
$recordings = $api->getUserRecordings($userId, ['per_page' => 10]);
$alerts = $api->getAlertRecordings(['per_page' => 5, 'days' => 7]);

// Page setup
$pageTitle = ($user['name'] ?? 'Patient') . ' - Patient Detail';
$currentPage = 'patients';
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
                <a class="nav-link active" href="patients.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-people"></i>
                    Patients
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="alerts.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-exclamation-triangle"></i>
                    Alerts
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
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php?id=<?php echo $providerId; ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="patients.php?id=<?php echo $providerId; ?>">Patients</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($user['name'] ?? 'Patient'); ?></li>
        </ol>
    </nav>
    
    <!-- Patient Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="entity-avatar" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?php echo getInitials($user['name'] ?? ''); ?>
                    </div>
                </div>
                <div class="col">
                    <h1 class="mb-1"><?php echo htmlspecialchars($user['name'] ?? 'Unknown Patient'); ?></h1>
                    <p class="text-muted mb-2">
                        <span class="me-3"><i class="bi bi-person me-1"></i><?php echo $user['age'] ?? 'N/A'; ?> years old</span>
                        <span class="me-3"><i class="bi bi-gender-ambiguous me-1"></i><?php echo $user['gender'] ?? 'N/A'; ?></span>
                        <span><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span>
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-info-soft"><?php echo number_format($user['total_recordings'] ?? 0); ?> Recordings</span>
                        <?php if (isset($user['has_care_provider']) && $user['has_care_provider']): ?>
                        <span class="badge bg-success-soft">Under Care</span>
                        <?php endif; ?>
                        <?php if (isset($user['has_monitor']) && $user['has_monitor']): ?>
                        <span class="badge bg-warning-soft">Family Monitored</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="time-selector">
                        <button class="time-option active" data-days="7">7D</button>
                        <button class="time-option" data-days="30">30D</button>
                        <button class="time-option" data-days="90">90D</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Latest Vitals -->
    <?php 
    $latestRecording = null;
    if ($recordings && !empty($recordings['recordings'])) {
        $latestRecording = $recordings['recordings'][0];
    }
    ?>
    
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100">
                <div class="vital-label mb-2">Heart Rate</div>
                <div class="vital-value <?php echo ($latestRecording && ($latestRecording['heart_rate'] < 50 || $latestRecording['heart_rate'] > 100)) ? 'text-warning' : ''; ?>">
                    <?php echo $latestRecording ? $latestRecording['heart_rate'] : '--'; ?>
                    <span class="vital-unit">bpm</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100">
                <div class="vital-label mb-2">Blood Pressure</div>
                <div class="vital-value <?php echo ($latestRecording && $latestRecording['htn']) ? 'text-danger' : ''; ?>">
                    <?php echo $latestRecording ? $latestRecording['bp_systolic'] . '/' . $latestRecording['bp_diastolic'] : '--/--'; ?>
                    <span class="vital-unit">mmHg</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100">
                <div class="vital-label mb-2">Oxygen (SpO₂)</div>
                <div class="vital-value <?php echo ($latestRecording && $latestRecording['blood_oxygenation'] < 95) ? 'text-warning' : ''; ?>">
                    <?php echo $latestRecording ? round($latestRecording['blood_oxygenation'], 1) : '--'; ?>
                    <span class="vital-unit">%</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100">
                <div class="vital-label mb-2">HRV</div>
                <div class="vital-value">
                    <?php echo $latestRecording ? round($latestRecording['hrv'], 1) : '--'; ?>
                    <span class="vital-unit">ms</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100">
                <div class="vital-label mb-2">Agility</div>
                <div class="vital-value">
                    <?php echo $latestRecording ? round($latestRecording['agility_score']) : '--'; ?>
                    <span class="vital-unit">/100</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100">
                <div class="vital-label mb-2">Seated Weight</div>
                <div class="vital-value">
                    <?php echo $latestRecording ? round($latestRecording['seated_weight'], 1) : '--'; ?>
                    <span class="vital-unit">kg</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Blood Pressure Chart -->
        <div class="col-lg-8">
            <div class="chart-container h-100">
                <div class="chart-header">
                    <h5 class="chart-title">Blood Pressure Trend</h5>
                    <div class="chart-legend">
                        <span class="chart-legend-item">
                            <span class="chart-legend-dot" style="background: var(--status-danger);"></span> Systolic
                        </span>
                        <span class="chart-legend-item">
                            <span class="chart-legend-dot" style="background: var(--casana-purple);"></span> Diastolic
                        </span>
                    </div>
                </div>
                <div style="height: 300px;">
                    <canvas id="bpChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Health Summary -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-clipboard2-pulse me-2"></i>Health Summary
                </div>
                <div class="card-body">
                    <?php if ($trends && count($trends) > 0): ?>
                    <?php
                    $avgSystolic = array_sum(array_column($trends, 'avg_bp_systolic')) / count($trends);
                    $avgDiastolic = array_sum(array_column($trends, 'avg_bp_diastolic')) / count($trends);
                    $avgHR = array_sum(array_column($trends, 'avg_heart_rate')) / count($trends);
                    $avgSpO2 = array_sum(array_column($trends, 'avg_blood_oxygenation')) / count($trends);
                    $avgHTN = array_sum(array_column($trends, 'htn_percentage')) / count($trends);
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Avg. Blood Pressure</span>
                            <span class="fw-semibold"><?php echo round($avgSystolic); ?>/<?php echo round($avgDiastolic); ?> mmHg</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Avg. Heart Rate</span>
                            <span class="fw-semibold"><?php echo round($avgHR); ?> bpm</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Avg. SpO₂</span>
                            <span class="fw-semibold"><?php echo round($avgSpO2, 1); ?>%</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">HTN Rate</span>
                            <span class="fw-semibold <?php echo $avgHTN > 30 ? 'text-danger' : ''; ?>"><?php echo round($avgHTN, 1); ?>%</span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="text-muted mb-2">30-Day Overview</h6>
                        <div class="d-flex justify-content-between">
                            <span>Total Recordings</span>
                            <span class="fw-semibold"><?php echo array_sum(array_column($trends, 'recording_count')); ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-graph-up-arrow fs-1 mb-2 d-block"></i>
                        <p>No trend data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Heart Rate & SpO2 Charts -->
    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="chart-container">
                <div class="chart-header">
                    <h5 class="chart-title">Heart Rate Trend</h5>
                </div>
                <div style="height: 200px;">
                    <canvas id="hrChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <div class="chart-header">
                    <h5 class="chart-title">Blood Oxygen Trend</h5>
                </div>
                <div style="height: 200px;">
                    <canvas id="spo2Chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Recordings -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-2"></i>Recent Recordings</span>
                </div>
                <div class="card-body p-0">
                    <?php if ($recordings && !empty($recordings['recordings'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Duration</th>
                                    <th>BP</th>
                                    <th>HR</th>
                                    <th>SpO₂</th>
                                    <th>HRV</th>
                                    <th>Agility</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recordings['recordings'] as $rec): ?>
                                <tr class="table-clickable" onclick="viewRecording(<?php echo $rec['id']; ?>)">
                                    <td>
                                        <div class="fw-medium"><?php echo formatDateTime($rec['sit_time']); ?></div>
                                    </td>
                                    <td><?php echo formatDuration($rec['duration_seconds']); ?></td>
                                    <td class="<?php echo $rec['htn'] ? 'text-danger fw-semibold' : ''; ?>">
                                        <?php echo $rec['bp_systolic']; ?>/<?php echo $rec['bp_diastolic']; ?>
                                    </td>
                                    <td><?php echo $rec['heart_rate']; ?></td>
                                    <td class="<?php echo $rec['blood_oxygenation'] < 95 ? 'text-warning' : ''; ?>">
                                        <?php echo round($rec['blood_oxygenation'], 1); ?>%
                                    </td>
                                    <td><?php echo round($rec['hrv'], 1); ?></td>
                                    <td><?php echo round($rec['agility_score']); ?></td>
                                    <td>
                                        <?php if ($rec['htn']): ?>
                                        <span class="badge bg-danger-soft">HTN</span>
                                        <?php else: ?>
                                        <span class="badge bg-success-soft">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); viewRecording(<?php echo $rec['id']; ?>);">
                                            <i class="bi bi-activity"></i> ECG
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-clock-history empty-icon"></i>
                        <h5 class="empty-title">No Recordings</h5>
                        <p class="empty-description">This patient doesn't have any recordings yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ECG Modal -->
<div class="modal fade" id="ecgModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-activity me-2"></i>ECG Recording</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ecgLoading" class="text-center py-5">
                    <div class="spinner-border" role="status"></div>
                    <p class="mt-3">Loading ECG data...</p>
                </div>
                <div id="ecgContent" style="display: none;">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="text-muted small">Recording Time</div>
                            <div class="fw-semibold" id="ecgTime">--</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Heart Rate</div>
                            <div class="fw-semibold" id="ecgHR">-- bpm</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Blood Pressure</div>
                            <div class="fw-semibold" id="ecgBP">--/--</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Duration</div>
                            <div class="fw-semibold" id="ecgDuration">--</div>
                        </div>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="ecgChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Trend data from PHP
const trendsData = <?php echo json_encode($trends ?? []); ?>;
let ecgChart = null;

document.addEventListener('DOMContentLoaded', function() {
    if (trendsData && trendsData.length > 0) {
        initCharts();
    }
    
    // Time selector
    document.querySelectorAll('.time-option').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.time-option').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            loadTrends(this.dataset.days);
        });
    });
});

function initCharts() {
    const colors = CasanaCharts.getColors();
    const labels = trendsData.map(t => t.date);
    
    // Blood Pressure Chart
    CasanaCharts.createBPChart(
        document.getElementById('bpChart'),
        trendsData.map(t => ({
            date: t.date,
            systolic: t.avg_bp_systolic,
            diastolic: t.avg_bp_diastolic
        }))
    );
    
    // Heart Rate Chart
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
                y: {
                    min: 85,
                    max: 100,
                }
            }
        }
    });
}

async function loadTrends(days) {
    // In production, this would reload data for different time periods
    console.log('Loading trends for', days, 'days');
}

async function viewRecording(recordingId) {
    const modal = new bootstrap.Modal(document.getElementById('ecgModal'));
    modal.show();
    
    document.getElementById('ecgLoading').style.display = 'block';
    document.getElementById('ecgContent').style.display = 'none';
    
    try {
        const recording = await CasanaAPI.getRecording(recordingId);
        
        document.getElementById('ecgTime').textContent = formatDateTime(recording.sit_time);
        document.getElementById('ecgHR').textContent = recording.heart_rate + ' bpm';
        document.getElementById('ecgBP').textContent = recording.bp_systolic + '/' + recording.bp_diastolic + ' mmHg';
        document.getElementById('ecgDuration').textContent = formatDuration(recording.duration_seconds);
        
        document.getElementById('ecgLoading').style.display = 'none';
        document.getElementById('ecgContent').style.display = 'block';
        
        // Destroy previous chart if exists
        if (ecgChart) {
            ecgChart.destroy();
        }
        
        // Create ECG chart
        if (recording.ecg_trace && recording.ecg_trace.length > 0) {
            ecgChart = CasanaCharts.createECGChart(
                document.getElementById('ecgChart'),
                recording.ecg_trace
            );
        }
    } catch (error) {
        console.error('Failed to load recording:', error);
        document.getElementById('ecgLoading').innerHTML = '<div class="alert alert-danger">Failed to load ECG data</div>';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
