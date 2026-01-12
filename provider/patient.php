<?php
/**
 * Care Provider - Patient Detail
 * Individual patient health record and vitals
 */

require_once __DIR__ . '/../includes/api-helper.php';
require_once __DIR__ . '/../includes/alert-taxonomy.php';

// Get IDs and time range
$providerId = isset($_GET['provider']) ? intval($_GET['provider']) : 1;
$userId = isset($_GET['id']) ? intval($_GET['id']) : 1;
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'vitals';

// Validate active tab (trends is now the default/main tab)
$validTabs = ['trends', 'recordings', 'notes'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'trends';
}

// Validate days parameter
$validDays = [7, 30, 90];
if (!in_array($days, $validDays)) {
    $days = 7;
}

// Fetch data with selected time range
$provider = $api->getCareProvider($providerId);
$user = $api->getUser($userId);
$trends = $api->getUserTrends($userId, ['days' => $days]);
$recordings = $api->getUserRecordings($userId, ['per_page' => 10]);
$alerts = $api->getAlertRecordings(['per_page' => 5, 'days' => 7]);

// Get the latest recording for vitals display
$latestRecording = null;
if ($recordings && !empty($recordings['recordings'])) {
    $latestRecording = $recordings['recordings'][0]; // First one is most recent
}

// Page setup
$pageTitle = ($user['name'] ?? 'Patient') . ' - Patient Detail';
$currentPage = 'patients';
$appName = 'provider';
$alertCount = $alerts ? $alerts['pagination']['total'] : 0;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/provider-sidebar.php';
?>

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
    <div class="card mb-4 patient-header-card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="entity-avatar" style="width: 72px; height: 72px; font-size: 1.75rem;">
                        <?php echo getInitials($user['name'] ?? ''); ?>
                    </div>
                </div>
                <div class="col">
                    <h1 class="mb-1 h3"><?php echo htmlspecialchars($user['name'] ?? 'Unknown Patient'); ?></h1>
                    <div class="text-muted mb-2 d-flex flex-wrap gap-3">
                        <span><i class="bi bi-person me-1"></i><?php echo $user['age'] ?? 'N/A'; ?>y</span>
                        <span><i class="bi bi-gender-ambiguous me-1"></i><?php echo $user['gender'] ?? 'N/A'; ?></span>
                        <span><i class="bi bi-calendar-check me-1"></i><?php echo number_format($user['total_recordings'] ?? 0); ?> readings</span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (isset($user['has_care_provider']) && $user['has_care_provider']): ?>
                        <span class="badge bg-success-soft">Under Care</span>
                        <?php endif; ?>
                        <?php if (isset($user['has_monitor']) && $user['has_monitor']): ?>
                        <span class="badge bg-warning-soft">Family Monitored</span>
                        <?php endif; ?>
                        <?php if ($latestRecording && isset($latestRecording['htn']) && $latestRecording['htn']): ?>
                        <span class="badge bg-danger-soft"><i class="bi bi-exclamation-triangle me-1"></i>HTN Flag</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-auto d-flex flex-column align-items-end gap-2">
                    <div class="time-selector" role="group" aria-label="Trend time range">
                        <button class="time-option <?php echo $days === 7 ? 'active' : ''; ?>" 
                                data-days="7"
                                aria-pressed="<?php echo $days === 7 ? 'true' : 'false'; ?>">7D</button>
                        <button class="time-option <?php echo $days === 30 ? 'active' : ''; ?>" 
                                data-days="30"
                                aria-pressed="<?php echo $days === 30 ? 'true' : 'false'; ?>">30D</button>
                        <button class="time-option <?php echo $days === 90 ? 'active' : ''; ?>" 
                                data-days="90"
                                aria-pressed="<?php echo $days === 90 ? 'true' : 'false'; ?>">90D</button>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" onclick="showAddNoteModal()">
                        <i class="bi bi-chat-text me-1"></i>Add Note
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Clinical Summary Card -->
    <div class="card mb-4 clinical-summary-card">
        <div class="card-body">
            <div class="row g-4">
                <!-- Last Reading Info -->
                <div class="col-md-4">
                    <h6 class="text-muted mb-2"><i class="bi bi-clock-history me-1"></i>Last Reading</h6>
                    <?php if ($latestRecording): ?>
                    <p class="mb-1 fw-semibold">
                        <?php echo date('M j, Y \a\t g:i A', strtotime($latestRecording['sit_time'])); ?>
                    </p>
                    <p class="small text-muted mb-0">
                        <?php echo formatRelativeTime($latestRecording['sit_time']); ?> • Duration: <?php echo formatDuration($latestRecording['duration_seconds']); ?>
                    </p>
                    <?php else: ?>
                    <p class="text-muted mb-0">No readings available</p>
                    <?php endif; ?>
                </div>
                
                <!-- Active Concerns -->
                <div class="col-md-4">
                    <h6 class="text-muted mb-2"><i class="bi bi-exclamation-circle me-1"></i>Active Concerns</h6>
                    <?php 
                    $concerns = [];
                    if ($latestRecording) {
                        if ($latestRecording['htn']) $concerns[] = ['label' => 'Elevated BP', 'class' => 'danger'];
                        if ($latestRecording['blood_oxygenation'] < 94) $concerns[] = ['label' => 'Low SpO₂', 'class' => 'warning'];
                        if ($latestRecording['heart_rate'] < 50 || $latestRecording['heart_rate'] > 100) $concerns[] = ['label' => 'HR Abnormal', 'class' => 'warning'];
                    }
                    if (empty($concerns)):
                    ?>
                    <p class="mb-0"><span class="badge bg-success-soft text-success"><i class="bi bi-check-circle me-1"></i>No active concerns</span></p>
                    <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($concerns as $concern): ?>
                        <span class="badge bg-<?php echo $concern['class']; ?>-soft text-<?php echo $concern['class']; ?>">
                            <?php echo $concern['label']; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Trend Summary -->
                <div class="col-md-4">
                    <h6 class="text-muted mb-2"><i class="bi bi-graph-up me-1"></i><?php echo $days; ?>-Day Trend</h6>
                    <?php
                    $trendStatus = 'stable';
                    $trendIcon = 'bi-dash';
                    $trendColor = 'secondary';
                    if ($trends && count($trends) >= 2) {
                        $firstBP = $trends[0]['avg_bp_systolic'] ?? 0;
                        $lastBP = $trends[count($trends) - 1]['avg_bp_systolic'] ?? 0;
                        if ($lastBP < $firstBP - 5) {
                            $trendStatus = 'improving';
                            $trendIcon = 'bi-arrow-down';
                            $trendColor = 'success';
                        } elseif ($lastBP > $firstBP + 5) {
                            $trendStatus = 'worsening';
                            $trendIcon = 'bi-arrow-up';
                            $trendColor = 'danger';
                        }
                    }
                    ?>
                    <p class="mb-0">
                        <span class="badge bg-<?php echo $trendColor; ?>-soft text-<?php echo $trendColor; ?>">
                            <i class="bi <?php echo $trendIcon; ?> me-1"></i>BP <?php echo ucfirst($trendStatus); ?>
                        </span>
                    </p>
                    <p class="small text-muted mb-0 mt-1">
                        Based on <?php echo count($trends ?? []); ?> data points
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Latest Vitals Cards (shown above tabs) -->
    <?php if ($latestRecording): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100 vital-tooltip" 
                 data-bs-toggle="tooltip" 
                 data-bs-placement="bottom"
                 title="Heart rate. Normal resting range: 60-100 bpm.">
                <div class="vital-label mb-2">
                    <i class="bi bi-heart text-danger me-1"></i>Heart Rate
                </div>
                <div class="vital-value <?php echo ($latestRecording['heart_rate'] < 50 || $latestRecording['heart_rate'] > 100) ? 'text-warning' : ''; ?>">
                    <?php echo $latestRecording['heart_rate']; ?>
                    <span class="vital-unit">bpm</span>
                </div>
                <div class="vital-range">Normal: 60-100</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100 vital-tooltip"
                 data-bs-toggle="tooltip"
                 data-bs-placement="bottom"
                 title="Blood pressure. Normal: <120/80. HTN: ≥140/90.">
                <div class="vital-label mb-2">
                    <i class="bi bi-activity text-primary me-1"></i>Blood Pressure
                </div>
                <div class="vital-value <?php echo $latestRecording['htn'] ? 'text-danger' : ''; ?>">
                    <?php echo $latestRecording['bp_systolic'] . '/' . $latestRecording['bp_diastolic']; ?>
                    <span class="vital-unit">mmHg</span>
                </div>
                <div class="vital-range">Normal: &lt;120/80</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100 vital-tooltip"
                 data-bs-toggle="tooltip"
                 data-bs-placement="bottom"
                 title="Blood oxygen saturation. Normal: 95-100%.">
                <div class="vital-label mb-2">
                    <i class="bi bi-lungs text-info me-1"></i>Oxygen (SpO₂)
                </div>
                <div class="vital-value <?php echo $latestRecording['blood_oxygenation'] < 94 ? 'text-danger' : ($latestRecording['blood_oxygenation'] < 95 ? 'text-warning' : ''); ?>">
                    <?php echo round($latestRecording['blood_oxygenation'], 1); ?>
                    <span class="vital-unit">%</span>
                </div>
                <div class="vital-range">Normal: 95-100%</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100 vital-tooltip"
                 data-bs-toggle="tooltip"
                 data-bs-placement="bottom"
                 title="Heart Rate Variability (RMSSD). Higher = better cardiac health.">
                <div class="vital-label mb-2">
                    <i class="bi bi-bar-chart text-success me-1"></i>HRV
                </div>
                <div class="vital-value">
                    <?php echo round($latestRecording['hrv'], 1); ?>
                    <span class="vital-unit">ms</span>
                </div>
                <div class="vital-range">Individual baseline</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100 vital-tooltip"
                 data-bs-toggle="tooltip"
                 data-bs-placement="bottom"
                 title="Agility Score from sit-to-stand time. Higher = better mobility.">
                <div class="vital-label mb-2">
                    <i class="bi bi-person-walking text-warning me-1"></i>Agility
                </div>
                <div class="vital-value">
                    <?php echo round($latestRecording['agility_score']); ?>
                    <span class="vital-unit">/100</span>
                </div>
                <div class="vital-range">Higher = better</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vital-card-lg h-100 vital-tooltip"
                 data-bs-toggle="tooltip"
                 data-bs-placement="bottom"
                 title="Seated weight measurement. Track for fluid retention.">
                <div class="vital-label mb-2">
                    <i class="bi bi-speedometer2 text-secondary me-1"></i>Weight
                </div>
                <div class="vital-value">
                    <?php echo round($latestRecording['seated_weight'], 1); ?>
                    <span class="vital-unit">kg</span>
                </div>
                <div class="vital-range">Track trend</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Page Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $activeTab === 'trends' ? 'active' : ''; ?>" id="trends-tab" data-bs-toggle="tab" data-bs-target="#trends" type="button" role="tab">
                <i class="bi bi-graph-up me-1"></i>Trends
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $activeTab === 'recordings' ? 'active' : ''; ?>" id="recordings-tab" data-bs-toggle="tab" data-bs-target="#recordings" type="button" role="tab">
                <i class="bi bi-list-ul me-1"></i>Recordings
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $activeTab === 'notes' ? 'active' : ''; ?>" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab">
                <i class="bi bi-chat-text me-1"></i>Notes
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content">
    
    <!-- Trends Tab (Main Tab) -->
    <div class="tab-pane fade <?php echo $activeTab === 'trends' ? 'show active' : ''; ?>" id="trends" role="tabpanel">
    
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
    </div><!-- End Trends Tab -->
    
    <!-- Recordings Tab -->
    <div class="tab-pane fade <?php echo $activeTab === 'recordings' ? 'show active' : ''; ?>" id="recordings" role="tabpanel">
    
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
    </div><!-- End Recordings Tab -->
    
    <!-- Notes Tab -->
    <div class="tab-pane fade <?php echo $activeTab === 'notes' ? 'show active' : ''; ?>" id="notes" role="tabpanel">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-chat-text me-2"></i>Clinical Notes</span>
                    <button class="btn btn-primary btn-sm" onclick="showAddNoteModal()">
                        <i class="bi bi-plus me-1"></i>Add Note
                    </button>
                </div>
                <div class="card-body">
                    <!-- Notes list (would be populated from API) -->
                    <div id="notesList">
                        <div class="note-item mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-info-soft text-info me-2">Follow-up</span>
                                    <small class="text-muted">Demo note</small>
                                </div>
                                <small class="text-muted">Just now</small>
                            </div>
                            <p class="mb-0 small">This is a placeholder for clinical notes. In production, notes would be saved to and loaded from the API.</p>
                        </div>
                    </div>
                    
                    <div class="text-center text-muted py-4" id="noNotesMessage" style="display: none;">
                        <i class="bi bi-chat-text fs-1 mb-2 d-block opacity-50"></i>
                        <p>No clinical notes yet</p>
                        <button class="btn btn-outline-primary btn-sm" onclick="showAddNoteModal()">
                            <i class="bi bi-plus me-1"></i>Add First Note
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Follow-up Reminders -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-bell me-2"></i>Follow-up Reminders
                </div>
                <div class="card-body">
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-calendar-check fs-2 mb-2 d-block opacity-50"></i>
                        <p class="small mb-2">No scheduled follow-ups</p>
                        <button class="btn btn-outline-secondary btn-sm" onclick="showScheduleFollowUpModal()">
                            <i class="bi bi-plus me-1"></i>Schedule
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="showAddNoteModal()">
                            <i class="bi bi-chat-text me-2"></i>Add Note
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="showScheduleFollowUpModal()">
                            <i class="bi bi-calendar-plus me-2"></i>Schedule Follow-up
                        </button>
                        <a href="alerts.php?id=<?php echo $providerId; ?>&patient=<?php echo $userId; ?>" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-exclamation-triangle me-2"></i>View Alerts
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div><!-- End Notes Tab -->
    
    </div><!-- End Tab Content -->
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
    
    // Time selector - navigate to update data
    document.querySelectorAll('.time-option').forEach(btn => {
        btn.addEventListener('click', function() {
            const days = this.dataset.days;
            const url = new URL(window.location.href);
            url.searchParams.set('days', days);
            window.location.href = url.toString();
        });
    });
    
    // Update URL when tabs are clicked (without page refresh)
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const tabId = e.target.getAttribute('data-bs-target').replace('#', '');
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url.toString());
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

// Time range is now handled via URL navigation - data is loaded server-side
const currentDays = <?php echo $days; ?>;

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

// Expose data for global search
window.providerId = <?php echo $providerId; ?>;
window.providerPatients = <?php echo json_encode($provider['patients'] ?? []); ?>;

// Initialize Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Notes functionality
function showAddNoteModal() {
    const modal = new bootstrap.Modal(document.getElementById('addNoteModal'));
    modal.show();
}

function savePatientNote() {
    const noteType = document.getElementById('patientNoteType').value;
    const noteText = document.getElementById('patientNoteText').value;
    
    if (!noteText.trim()) {
        alert('Please enter a note');
        return;
    }
    
    // In production, would save to API
    const noteHtml = `
        <div class="note-item mb-3 p-3 bg-light rounded">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="badge bg-info-soft text-info me-2">${noteType}</span>
                    <small class="text-muted"><?php echo htmlspecialchars($provider['name'] ?? 'Provider'); ?></small>
                </div>
                <small class="text-muted">Just now</small>
            </div>
            <p class="mb-0 small">${escapeHtml(noteText)}</p>
        </div>
    `;
    
    document.getElementById('notesList').insertAdjacentHTML('afterbegin', noteHtml);
    document.getElementById('noNotesMessage').style.display = 'none';
    
    // Close modal and reset
    bootstrap.Modal.getInstance(document.getElementById('addNoteModal')).hide();
    document.getElementById('patientNoteText').value = '';
    
    showToast('Note saved successfully', 'success');
}

function showScheduleFollowUpModal() {
    alert('Follow-up scheduling would open a date picker modal in production.');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'info'} position-fixed`;
    toast.style.cssText = 'bottom: 20px; right: 20px; z-index: 9999; animation: fadeIn 0.3s;';
    toast.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-chat-text me-2"></i>Add Clinical Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Patient</label>
                    <p class="form-control-static fw-semibold mb-0"><?php echo htmlspecialchars($user['name'] ?? 'Patient'); ?></p>
                </div>
                <div class="mb-3">
                    <label for="patientNoteType" class="form-label">Note Type</label>
                    <select class="form-select" id="patientNoteType">
                        <option value="General">General Note</option>
                        <option value="Follow-up">Follow-up Required</option>
                        <option value="Concern">Clinical Concern</option>
                        <option value="Medication">Medication Related</option>
                        <option value="Communication">Patient Communication</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="patientNoteText" class="form-label">Note</label>
                    <textarea class="form-control" id="patientNoteText" rows="4" placeholder="Enter clinical note..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePatientNote()">
                    <i class="bi bi-check-lg me-1"></i>Save Note
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Patient Detail Page Styles */
.patient-header-card {
    border-left: 4px solid var(--casana-purple);
}

.clinical-summary-card {
    background: var(--bg-secondary);
    border: none;
}

.vital-card-lg .vital-range {
    font-size: 0.65rem;
    color: var(--text-muted);
    margin-top: var(--spacing-sm);
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.vital-tooltip {
    cursor: help;
}

.nav-tabs .nav-link {
    color: var(--text-secondary);
    border: none;
    border-bottom: 2px solid transparent;
    padding: var(--spacing-md) var(--spacing-lg);
}

.nav-tabs .nav-link:hover {
    color: var(--casana-purple);
    border-color: transparent;
}

.nav-tabs .nav-link.active {
    color: var(--casana-purple);
    border-bottom-color: var(--casana-purple);
    background: transparent;
}

.note-item {
    border-left: 3px solid var(--casana-purple);
}

.note-item:hover {
    background: var(--bg-hover) !important;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
