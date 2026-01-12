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

<?php
// Get time-based greeting
$hour = date('H');
$greeting = 'Good evening';
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = 'Good afternoon';
}

// Get health summary
$healthSummary = 'Your vitals look good today';
if ($latestRecording) {
    if ($latestRecording['htn']) {
        $healthSummary = '1 reading needs your attention';
    } elseif ($latestRecording['blood_oxygenation'] < 95) {
        $healthSummary = 'Oxygen levels slightly below optimal';
    } else {
        $healthSummary = 'All vitals within normal range';
    }
}
?>
<div class="container py-4 dashboard-container">
    <!-- Dashboard Header with Controls -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 gap-3">
        <div class="text-center text-md-start">
            <h1 class="h2 fw-bold mb-1" style="letter-spacing: -0.02em;"><?php echo $greeting; ?>, <?php echo htmlspecialchars(explode(' ', $user['name'] ?? 'User')[0]); ?></h1>
            <p class="text-secondary mb-0 d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                <?php if ($latestRecording && !$latestRecording['htn']): ?>
                <span class="d-inline-block rounded-circle bg-success" style="width: 8px; height: 8px;"></span>
                <?php elseif ($latestRecording && $latestRecording['htn']): ?>
                <span class="d-inline-block rounded-circle bg-warning" style="width: 8px; height: 8px; animation: pulse 2s infinite;"></span>
                <?php endif; ?>
                <?php echo $healthSummary; ?>
            </p>
        </div>
        
        <!-- View Mode Toggle -->
        <div class="segmented-control">
            <input type="radio" name="viewMode" id="simpleMode" checked>
            <label for="simpleMode">
                <i class="bi bi-grid-fill"></i> Overview
            </label>
            
            <input type="radio" name="viewMode" id="detailedMode">
            <label for="detailedMode">
                <i class="bi bi-activity"></i> Detailed
            </label>
        </div>
    </div>
    
    <?php if ($latestRecording): ?>
    
    <!-- Simple View -->
    <div id="simpleView">
        <!-- Health Status Banner -->
        <?php
        $status = getHealthStatus($latestRecording);
        $statusMessages = [
            'good' => ['All vitals are within normal range', 'bg-success-soft', 'bi-check-circle-fill', 'text-success', 'Keep it up!'],
            'warning' => ['Some readings are outside normal range', 'bg-warning-soft', 'bi-exclamation-circle-fill', 'text-warning', 'Monitor closely'],
            'alert' => ['Please contact your provider regarding recent readings', 'bg-danger-soft', 'bi-exclamation-triangle-fill', 'text-danger', 'Action needed']
        ];
        $statusInfo = $statusMessages[$status];
        
        // Get specific concerning metric for alerts
        $concerningMetric = '';
        if ($status === 'alert' || $status === 'warning') {
            if ($latestRecording['htn']) {
                $concerningMetric = 'Blood pressure: ' . $latestRecording['bp_systolic'] . '/' . $latestRecording['bp_diastolic'] . ' mmHg';
            } elseif ($latestRecording['blood_oxygenation'] < 92) {
                $concerningMetric = 'Oxygen saturation: ' . round($latestRecording['blood_oxygenation'], 1) . '%';
            } elseif ($latestRecording['heart_rate'] > 100 || $latestRecording['heart_rate'] < 50) {
                $concerningMetric = 'Heart rate: ' . $latestRecording['heart_rate'] . ' BPM';
            }
        }
        ?>
        
        <div class="health-alert-banner <?php echo $status; ?> mb-4" id="healthAlertBanner">
            <div class="alert-badge">
                <i class="bi <?php echo $statusInfo[2]; ?>"></i>
            </div>
            <div class="alert-body">
                <div class="alert-header">
                    <h5 class="alert-title"><?php echo $statusInfo[0]; ?></h5>
                    <button type="button" class="alert-dismiss" aria-label="Dismiss" onclick="dismissAlert()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <?php if ($concerningMetric): ?>
                <div class="alert-detail">
                    <i class="bi bi-info-circle me-1"></i><?php echo $concerningMetric; ?>
                </div>
                <?php endif; ?>
                <div class="alert-footer">
                    <span class="alert-time">
                        <i class="bi bi-clock me-1"></i>Last checked: <?php echo formatRelativeTime($latestRecording['sit_time']); ?>
                    </span>
                    <?php if ($status !== 'good'): ?>
                    <div class="alert-actions">
                        <a href="history.php?id=<?php echo $userId; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-clock-history me-1"></i>View History
                        </a>
                        <button class="btn btn-sm btn-<?php echo $status === 'alert' ? 'danger' : 'warning'; ?>" onclick="contactProvider()">
                            <i class="bi bi-telephone me-1"></i>Contact Provider
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Primary Vitals Grid -->
        <div class="row g-4 mb-4 animate-stagger">
            <div class="col-md-6">
                <div class="hero-stat-card <?php echo $latestRecording['htn'] ? 'elevated' : ''; ?> h-100 d-flex flex-column align-items-center justify-content-center text-center ripple">
                    <div class="card-decoration"></div>
                    <div class="mb-3 text-secondary text-uppercase fw-medium tracking-wide small">Blood Pressure</div>
                    <div class="bp-fraction-lg mb-3" style="color: <?php echo $latestRecording['htn'] ? 'var(--status-danger)' : 'var(--text-primary)'; ?>">
                        <span class="bp-systolic"><?php echo $latestRecording['bp_systolic']; ?></span>
                        <span class="bp-divider"></span>
                        <span class="bp-diastolic"><?php echo $latestRecording['bp_diastolic']; ?></span>
                    </div>
                    <?php if ($latestRecording['htn']): ?>
                    <div class="badge bg-danger-soft rounded-pill px-3 py-2">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Elevated
                    </div>
                    <?php else: ?>
                    <div class="badge bg-success-soft rounded-pill px-3 py-2">
                        <i class="bi bi-check-circle-fill me-1"></i>Normal Range
                    </div>
                    <?php endif; ?>
                    <?php 
                    // Calculate comparison to yesterday's average if available
                    $bpChange = null;
                    if ($trends && count($trends) >= 2) {
                        $yesterdayAvg = $trends[count($trends)-2]['avg_bp_systolic'] ?? null;
                        if ($yesterdayAvg) {
                            $bpChange = $latestRecording['bp_systolic'] - $yesterdayAvg;
                        }
                    }
                    ?>
                    <?php if ($bpChange !== null): ?>
                    <div class="comparison <?php echo $bpChange <= 0 ? 'positive' : 'negative'; ?>">
                        <i class="bi bi-arrow-<?php echo $bpChange <= 0 ? 'down' : 'up'; ?>"></i>
                        <span><?php echo abs(round($bpChange)); ?> vs yesterday</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="hero-stat-card h-100 d-flex flex-column align-items-center justify-content-center text-center ripple">
                    <div class="card-decoration"></div>
                    <div class="mb-3 text-secondary text-uppercase fw-medium tracking-wide small">
                        Heart Rate
                        <span class="pulse-indicator"></span>
                    </div>
                    <div class="display-3 fw-bold mb-1" style="color: var(--casana-purple);">
                        <?php echo $latestRecording['heart_rate']; ?>
                    </div>
                    <div class="text-muted mb-3">BPM</div>
                    <?php 
                    $hrNormal = $latestRecording['heart_rate'] >= 60 && $latestRecording['heart_rate'] <= 100;
                    ?>
                    <div class="badge <?php echo $hrNormal ? 'bg-success-soft' : 'bg-warning-soft'; ?> rounded-pill px-3 py-2">
                        <i class="bi bi-<?php echo $hrNormal ? 'heart-pulse' : 'exclamation-circle'; ?> me-1"></i>
                        <?php echo $hrNormal ? 'Resting Heart Rate' : 'Check Heart Rate'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Secondary Vitals Grid -->
        <?php
        // Determine status for each metric
        $o2Value = $latestRecording['blood_oxygenation'];
        $o2Status = 'excellent';
        $o2Label = 'Excellent';
        if ($o2Value < 92) {
            $o2Status = 'low';
            $o2Label = 'Low';
        } elseif ($o2Value < 95) {
            $o2Status = 'normal';
            $o2Label = 'Normal';
        }
        
        $mobilityValue = $latestRecording['agility_score'];
        $mobilityStatus = 'excellent';
        $mobilityLabel = 'Great';
        if ($mobilityValue < 30) {
            $mobilityStatus = 'low';
            $mobilityLabel = 'Needs work';
        } elseif ($mobilityValue < 60) {
            $mobilityStatus = 'normal';
            $mobilityLabel = 'Good';
        }
        ?>
        <div class="row g-3 mb-4 animate-stagger">
            <div class="col-4">
                <div class="secondary-stat-card h-100 card-hover-lift">
                    <div class="stat-icon oxygen">
                        <i class="bi bi-lungs"></i>
                    </div>
                    <div class="text-secondary small text-uppercase mb-1 fw-medium">Oxygen</div>
                    <div class="stat-value"><?php echo round($o2Value, 1); ?><span class="stat-unit">%</span></div>
                    <div class="stat-status <?php echo $o2Status; ?>"><?php echo $o2Label; ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="secondary-stat-card h-100 card-hover-lift">
                    <div class="stat-icon mobility">
                        <i class="bi bi-person-walking"></i>
                    </div>
                    <div class="text-secondary small text-uppercase mb-1 fw-medium">Mobility</div>
                    <div class="stat-value"><?php echo round($mobilityValue); ?></div>
                    <div class="stat-status <?php echo $mobilityStatus; ?>"><?php echo $mobilityLabel; ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="secondary-stat-card h-100 card-hover-lift">
                    <div class="stat-icon weight">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div class="text-secondary small text-uppercase mb-1 fw-medium">Weight</div>
                    <div class="stat-value"><?php echo round($latestRecording['seated_weight'] * 1.67, 1); ?><span class="stat-unit">kg</span></div>
                    <div class="stat-status normal">Tracked</div>
                </div>
            </div>
        </div>
        
        <!-- Weekly Summary Widget -->
        <?php if ($trends && count($trends) > 0): 
            $totalReadings = array_sum(array_column($trends, 'recording_count'));
            $avgSystolic = round(array_sum(array_column($trends, 'avg_bp_systolic')) / count($trends));
            $avgHR = round(array_sum(array_column($trends, 'avg_heart_rate')) / count($trends));
            
            // Calculate goal progress (7 readings per week = 100%)
            $readingGoal = 7;
            $readingProgress = min(100, ($totalReadings / $readingGoal) * 100);
            
            // BP status (120 = optimal, 140 = stage 1 hypertension)
            $bpStatus = 'optimal';
            if ($avgSystolic >= 130) {
                $bpStatus = 'high';
            } elseif ($avgSystolic >= 120) {
                $bpStatus = 'elevated';
            }
            $bpProgress = max(0, min(100, ((180 - $avgSystolic) / 60) * 100));
            
            // HR status (60-100 is normal)
            $hrStatus = 'normal';
            if ($avgHR < 60) {
                $hrStatus = 'low';
            } elseif ($avgHR > 100) {
                $hrStatus = 'high';
            }
        ?>
        <div class="card weekly-summary-card border-0 shadow-sm overflow-hidden animate-in">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">This Week</h5>
                        <span class="text-muted small">Your 7-day health summary</span>
                    </div>
                    <span class="badge bg-primary-soft rounded-pill px-3 py-2">
                        <i class="bi bi-calendar-week me-1"></i>Last 7 Days
                    </span>
                </div>
                
                <!-- Weekly Stats with Progress Indicators -->
                <div class="weekly-stats-grid">
                    <!-- Readings -->
                    <div class="weekly-stat-item">
                        <div class="stat-header">
                            <span class="stat-label">
                                <i class="bi bi-clipboard2-check me-1 text-primary"></i>Readings
                            </span>
                            <span class="stat-value count-up"><?php echo $totalReadings; ?></span>
                        </div>
                        <div class="progress-container">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $readingProgress; ?>%"></div>
                            </div>
                            <span class="progress-label"><?php echo round($readingProgress); ?>% of goal</span>
                        </div>
                    </div>
                    
                    <!-- Avg BP -->
                    <div class="weekly-stat-item">
                        <div class="stat-header">
                            <span class="stat-label">
                                <i class="bi bi-heart-pulse me-1 text-danger"></i>Avg Systolic
                            </span>
                            <span class="stat-value count-up">
                                <?php echo $avgSystolic; ?>
                                <small class="text-muted">mmHg</small>
                            </span>
                        </div>
                        <div class="progress-container">
                            <div class="progress" style="height: 6px;">
                                <?php 
                                $bpBarClass = 'bg-success';
                                if ($bpStatus === 'elevated') {
                                    $bpBarClass = 'bg-warning';
                                } elseif ($bpStatus === 'high') {
                                    $bpBarClass = 'bg-danger';
                                }
                                ?>
                                <div class="progress-bar <?php echo $bpBarClass; ?>" role="progressbar" style="width: <?php echo $bpProgress; ?>%"></div>
                            </div>
                            <?php 
                            $bpTextClass = 'text-success';
                            if ($bpStatus === 'elevated') {
                                $bpTextClass = 'text-warning';
                            } elseif ($bpStatus === 'high') {
                                $bpTextClass = 'text-danger';
                            }
                            ?>
                            <span class="progress-label <?php echo $bpTextClass; ?>">
                                <?php echo ucfirst($bpStatus); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Avg HR -->
                    <div class="weekly-stat-item">
                        <div class="stat-header">
                            <span class="stat-label">
                                <i class="bi bi-activity me-1 text-primary"></i>Avg Heart Rate
                            </span>
                            <span class="stat-value count-up">
                                <?php echo $avgHR; ?>
                                <small class="text-muted">BPM</small>
                            </span>
                        </div>
                        <div class="progress-container">
                            <div class="progress" style="height: 6px;">
                                <?php 
                                $hrBarClass = 'bg-success';
                                $hrWidth = 75;
                                if ($hrStatus !== 'normal') {
                                    $hrBarClass = 'bg-warning';
                                    $hrWidth = 50;
                                }
                                ?>
                                <div class="progress-bar <?php echo $hrBarClass; ?>" role="progressbar" style="width: <?php echo $hrWidth; ?>%"></div>
                            </div>
                            <?php 
                            $hrTextClass = 'text-success';
                            if ($hrStatus !== 'normal') {
                                $hrTextClass = 'text-warning';
                            }
                            ?>
                            <span class="progress-label <?php echo $hrTextClass; ?>">
                                <?php echo ucfirst($hrStatus); ?> range
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Mini Week Chart -->
                <div class="mini-week-chart mt-4">
                    <div class="d-flex align-items-end justify-content-between gap-1" style="height: 50px;">
                        <?php 
                        $maxReadings = max(array_column($trends, 'recording_count'));
                        foreach ($trends as $day): 
                            $barHeight = 10;
                            if ($maxReadings > 0) {
                                $barHeight = ($day['recording_count'] / $maxReadings) * 100;
                            }
                            $barBg = 'var(--border-color)';
                            if ($day['recording_count'] > 0) {
                                $barBg = 'var(--casana-purple)';
                            }
                            $dayName = date('D', strtotime($day['date']));
                        ?>
                        <div class="text-center flex-fill">
                            <div class="mini-bar mx-auto" style="height: <?php echo max(10, $barHeight); ?>%; background: <?php echo $barBg; ?>;"></div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.65rem;"><?php echo $dayName; ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Detailed View -->
    <div id="detailedView" style="display: none;">
        <!-- Latest Detailed Grid -->
        <div class="row g-4 mb-4">
            <!-- BP Detail -->
            <div class="col-md-4">
                <div class="vital-card-lg h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="vital-label">Blood Pressure</div>
                        <i class="bi bi-heart-pulse text-primary"></i>
                    </div>
                    <div class="vital-value mb-2"><?php echo $latestRecording['bp_systolic']; ?>/<?php echo $latestRecording['bp_diastolic']; ?></div>
                    <div class="vital-unit">mmHg</div>
                </div>
            </div>
            
            <!-- HR Detail -->
            <div class="col-md-4">
                <div class="vital-card-lg h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="vital-label">Heart Rate</div>
                        <i class="bi bi-activity text-danger"></i>
                    </div>
                    <div class="vital-value mb-2"><?php echo $latestRecording['heart_rate']; ?></div>
                    <div class="vital-unit">bpm</div>
                </div>
            </div>
            
            <!-- SpO2 Detail -->
            <div class="col-md-4">
                <div class="vital-card-lg h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="vital-label">Blood Oxygen</div>
                        <i class="bi bi-lungs text-info"></i>
                    </div>
                    <div class="vital-value mb-2"><?php echo round($latestRecording['blood_oxygenation'], 1); ?>%</div>
                    <div class="vital-unit">SpO₂</div>
                </div>
            </div>
            
            <!-- HRV Detail -->
            <div class="col-md-4">
                <div class="vital-card-lg h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="vital-label">HRV</div>
                        <i class="bi bi-lightning text-warning"></i>
                    </div>
                    <div class="vital-value mb-2"><?php echo round($latestRecording['hrv'], 1); ?></div>
                    <div class="vital-unit">ms</div>
                </div>
            </div>
            
            <!-- Mobility Detail -->
            <div class="col-md-4">
                <div class="vital-card-lg h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="vital-label">Agility</div>
                        <i class="bi bi-person-walking text-success"></i>
                    </div>
                    <div class="vital-value mb-2"><?php echo round($latestRecording['agility_score']); ?></div>
                    <div class="vital-unit">Score</div>
                </div>
            </div>
            
            <!-- Duration Detail -->
            <div class="col-md-4">
                <div class="vital-card-lg h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="vital-label">Duration</div>
                        <i class="bi bi-stopwatch text-secondary"></i>
                    </div>
                    <div class="vital-value mb-2" style="font-size: 2rem;"><?php echo formatDuration($latestRecording['duration_seconds']); ?></div>
                    <div class="vital-unit">Time Seated</div>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <?php if ($trends && count($trends) > 0): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Blood Pressure Trend</h5>
                <div class="d-flex gap-3">
                    <span class="d-flex align-items-center gap-2 text-secondary small">
                        <span class="rounded-circle" style="width: 8px; height: 8px; background: var(--casana-purple);"></span> Diastolic
                    </span>
                    <span class="d-flex align-items-center gap-2 text-secondary small">
                        <span class="rounded-circle" style="width: 8px; height: 8px; background: var(--status-danger);"></span> Systolic
                    </span>
                </div>
            </div>
            <div class="card-body p-4">
                <div style="height: 300px;">
                    <canvas id="bpChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h5 class="mb-0 fw-bold">Heart Rate</h5>
                    </div>
                    <div class="card-body p-4">
                        <div style="height: 200px;">
                            <canvas id="hrChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h5 class="mb-0 fw-bold">Oxygen Saturation</h5>
                    </div>
                    <div class="card-body p-4">
                        <div style="height: 200px;">
                            <canvas id="spo2Chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Recordings Table -->
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Recent Readings</h5>
                <a href="history.php?id=<?php echo $userId; ?>" class="btn btn-sm btn-outline-primary rounded-pill">View History</a>
            </div>
            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-clickable">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 ps-4">Time</th>
                                <th class="border-0">Blood Pressure</th>
                                <th class="border-0">Heart Rate</th>
                                <th class="border-0">SpO₂</th>
                                <th class="border-0 pe-4 text-end">Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recordings['recordings'] as $rec): ?>
                            <tr onclick="window.location='recording.php?id=<?php echo $rec['id']; ?>&user=<?php echo $userId; ?>'">
                                <td class="ps-4 fw-medium text-secondary"><?php echo formatDateTime($rec['sit_time']); ?></td>
                                <td class="<?php echo $rec['htn'] ? 'text-danger fw-bold' : 'fw-bold'; ?>">
                                    <?php echo $rec['bp_systolic']; ?>/<?php echo $rec['bp_diastolic']; ?>
                                </td>
                                <td class="fw-medium"><?php echo $rec['heart_rate']; ?> <span class="text-muted small fw-normal">bpm</span></td>
                                <td class="fw-medium"><?php echo round($rec['blood_oxygenation'], 1); ?>%</td>
                                <td class="pe-4 text-end text-secondary"><?php echo formatDuration($rec['duration_seconds']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-clipboard2-pulse empty-icon text-muted opacity-50"></i>
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
                <i class="bi bi-grid-fill"></i>
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

// Alert banner functions
function dismissAlert() {
    const banner = document.getElementById('healthAlertBanner');
    if (banner) {
        banner.classList.add('dismissed');
        // Store dismissal in session storage (resets on next visit)
        sessionStorage.setItem('alertDismissed-<?php echo $userId; ?>', Date.now());
        setTimeout(() => banner.remove(), 300);
    }
}

function contactProvider() {
    // In a real app, this would open a modal or redirect to provider contact
    alert('Contacting your healthcare provider...\n\nIn a production app, this would:\n- Open a messaging interface\n- Initiate a call\n- Send an automated notification');
}

// Check if alert was dismissed in this session
document.addEventListener('DOMContentLoaded', function() {
    const dismissed = sessionStorage.getItem('alertDismissed-<?php echo $userId; ?>');
    if (dismissed) {
        const banner = document.getElementById('healthAlertBanner');
        if (banner) {
            banner.style.display = 'none';
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // View mode toggle
    const simpleMode = document.getElementById('simpleMode');
    const detailedMode = document.getElementById('detailedMode');
    
    if (simpleMode && detailedMode) {
        simpleMode.addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('simpleView').style.display = 'block';
                document.getElementById('detailedView').style.display = 'none';
            }
        });
        
        detailedMode.addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('simpleView').style.display = 'none';
                document.getElementById('detailedView').style.display = 'block';
                // Small delay to ensure display:block has rendered before chart init
                setTimeout(initDetailedCharts, 50);
            }
        });

        // Re-init charts on theme change
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === "attributes" && mutation.attributeName === "data-theme") {
                    chartsInitialized = false; // Force re-init
                    if (detailedMode.checked) {
                         // Clear existing charts if possible or just re-init which might need chart destruction
                         // For simplicity, we'll just reload the page or re-draw.
                         // Better: destroy old charts. But we didn't save references.
                         // Let's just reload for now as theme switch is rare, OR just re-call init
                         // Ideally we should destroy charts.
                         location.reload(); 
                    }
                }
            });
        });
        
        observer.observe(document.documentElement, {
            attributes: true //configure it to listen to attribute changes
        });
    }
});

let chartsInitialized = false;

function initDetailedCharts() {
    if (chartsInitialized || !trendsData || trendsData.length === 0) return;
    chartsInitialized = true;
    
    // Check if CasanaCharts is defined
    if (typeof CasanaCharts === 'undefined') {
        console.error('CasanaCharts not loaded');
        return;
    }
    
    const colors = CasanaCharts.getColors();
    const labels = trendsData.map(t => new Date(t.date).toLocaleDateString('en-US', { weekday: 'short' }));
    
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.04)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    // Common Chart Options for polished look
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 800,
            easing: 'easeOutQuart',
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: isDark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.98)',
                titleColor: isDark ? '#f8fafc' : '#0f172a',
                bodyColor: isDark ? '#cbd5e1' : '#475569',
                borderColor: isDark ? 'rgba(148, 163, 184, 0.2)' : 'rgba(0, 0, 0, 0.08)',
                borderWidth: 1,
                padding: 14,
                cornerRadius: 10,
                titleFont: { family: 'Plus Jakarta Sans', size: 13, weight: 600 },
                bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                displayColors: true,
                boxPadding: 6,
                usePointStyle: true,
                caretSize: 6,
            }
        },
        scales: {
            x: {
                grid: { display: false, drawBorder: false },
                ticks: { 
                    font: { family: 'Plus Jakarta Sans', size: 11, weight: 500 }, 
                    color: textColor,
                    padding: 8,
                }
            },
            y: {
                grid: { 
                    color: gridColor, 
                    drawBorder: false,
                    lineWidth: 1,
                },
                ticks: { 
                    font: { family: 'Plus Jakarta Sans', size: 11 }, 
                    color: textColor,
                    padding: 10,
                },
                beginAtZero: false,
            }
        },
        elements: {
            point: { 
                radius: 0, 
                hoverRadius: 6, 
                hitRadius: 30,
                hoverBorderWidth: 2,
                hoverBackgroundColor: isDark ? '#0f172a' : '#ffffff',
            },
            line: { 
                tension: 0.35, 
                borderWidth: 2.5,
                borderCapStyle: 'round',
                borderJoinStyle: 'round',
            }
        },
        interaction: {
            mode: 'index',
            intersect: false,
        }
    };
    
    // BP Chart with gradient fills and reference lines
    const bpCtx = document.getElementById('bpChart');
    if (bpCtx) {
        const ctx = bpCtx.getContext('2d');
        
        // Create gradients
        const systolicGradient = ctx.createLinearGradient(0, 0, 0, 300);
        systolicGradient.addColorStop(0, 'rgba(239, 68, 68, 0.15)');
        systolicGradient.addColorStop(1, 'rgba(239, 68, 68, 0)');
        
        const diastolicGradient = ctx.createLinearGradient(0, 0, 0, 300);
        diastolicGradient.addColorStop(0, 'rgba(91, 95, 239, 0.15)');
        diastolicGradient.addColorStop(1, 'rgba(91, 95, 239, 0)');
        
        new Chart(bpCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Systolic',
                        data: trendsData.map(t => t.avg_bp_systolic),
                        borderColor: '#ef4444',
                        backgroundColor: systolicGradient,
                        fill: true,
                        pointBackgroundColor: '#ef4444',
                    },
                    {
                        label: 'Diastolic',
                        data: trendsData.map(t => t.avg_bp_diastolic),
                        borderColor: '#5b5fef',
                        backgroundColor: diastolicGradient,
                        fill: true,
                        pointBackgroundColor: '#5b5fef',
                    }
                ]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + Math.round(context.raw) + ' mmHg';
                            }
                        }
                    },
                    annotation: {
                        annotations: {
                            normalSystolic: {
                                type: 'line',
                                yMin: 120,
                                yMax: 120,
                                borderColor: 'rgba(16, 185, 129, 0.4)',
                                borderWidth: 1,
                                borderDash: [6, 4],
                            },
                            normalDiastolic: {
                                type: 'line',
                                yMin: 80,
                                yMax: 80,
                                borderColor: 'rgba(16, 185, 129, 0.4)',
                                borderWidth: 1,
                                borderDash: [6, 4],
                            }
                        }
                    }
                },
                scales: {
                    ...commonOptions.scales,
                    y: {
                        ...commonOptions.scales.y,
                        min: 60,
                        max: 180,
                    }
                }
            }
        });
    }
    
    // HR Chart with gradient fill
    const hrCtx = document.getElementById('hrChart');
    if (hrCtx) {
        const ctx = hrCtx.getContext('2d');
        const hrGradient = ctx.createLinearGradient(0, 0, 0, 200);
        hrGradient.addColorStop(0, 'rgba(239, 68, 68, 0.2)');
        hrGradient.addColorStop(1, 'rgba(239, 68, 68, 0)');
        
        new Chart(hrCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Heart Rate',
                    data: trendsData.map(t => t.avg_heart_rate),
                    borderColor: '#ef4444',
                    backgroundColor: hrGradient,
                    fill: true,
                    pointBackgroundColor: '#ef4444',
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                return Math.round(context.raw) + ' BPM';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // SpO2 Chart with gradient fill
    const spo2Ctx = document.getElementById('spo2Chart');
    if (spo2Ctx) {
        const ctx = spo2Ctx.getContext('2d');
        const spo2Gradient = ctx.createLinearGradient(0, 0, 0, 200);
        spo2Gradient.addColorStop(0, 'rgba(56, 189, 248, 0.2)');
        spo2Gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');
        
        new Chart(spo2Ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'SpO₂',
                    data: trendsData.map(t => t.avg_blood_oxygenation),
                    borderColor: '#38bdf8',
                    backgroundColor: spo2Gradient,
                    fill: true,
                    pointBackgroundColor: '#38bdf8',
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonOptions.scales,
                    y: { 
                        ...commonOptions.scales.y, 
                        min: 90, 
                        max: 100,
                        ticks: {
                            ...commonOptions.scales.y.ticks,
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                return context.raw.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
