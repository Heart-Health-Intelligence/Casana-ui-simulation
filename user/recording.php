<?php
/**
 * User - Recording Detail
 * Single recording with full ECG trace
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get IDs
$recordingId = isset($_GET['id']) ? intval($_GET['id']) : 1;
$userId = isset($_GET['user']) ? intval($_GET['user']) : 1;

// Fetch recording (includes ECG)
$recording = $api->getRecording($recordingId);

// Page setup
$pageTitle = 'Recording Detail';
$currentPage = 'history';
$appName = 'user';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width: 900px;">
    <!-- Back Button -->
    <a href="history.php?id=<?php echo $userId; ?>" class="btn btn-outline-secondary mb-4 rounded-pill px-3">
        <i class="bi bi-arrow-left me-2"></i>Back to History
    </a>
    
    <?php if ($recording): ?>
    
    <!-- Recording Header -->
    <div class="card mb-4 recording-header-card">
        <div class="card-body py-4">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="mb-1 fw-bold" style="letter-spacing: -0.02em;"><?php echo formatDateTime($recording['sit_time']); ?></h2>
                    <p class="text-muted mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-stopwatch"></i>
                        <span>Duration: <?php echo formatDuration($recording['duration_seconds']); ?></span>
                    </p>
                </div>
                <div class="col-auto">
                    <?php if ($recording['htn']): ?>
                    <span class="badge bg-danger-soft text-danger fs-6 px-4 py-2 rounded-pill fw-semibold">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Hypertensive
                    </span>
                    <?php else: ?>
                    <span class="badge bg-success-soft text-success fs-6 px-4 py-2 rounded-pill fw-semibold">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Normal
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Vitals Grid -->
    <div class="row g-4 mb-4 animate-stagger">
        <div class="col-6 col-md-4">
            <div class="card recording-vital-card h-100">
                <i class="bi bi-heart-pulse fs-2 text-danger mb-3"></i>
                <div class="vital-label">Blood Pressure</div>
                <div class="vital-value-bp" style="color: <?php echo $recording['htn'] ? 'var(--status-danger)' : 'var(--text-primary)'; ?>">
                    <span class="bp-fraction-lg">
                        <span class="bp-systolic"><?php echo $recording['bp_systolic']; ?></span>
                        <span class="bp-divider"></span>
                        <span class="bp-diastolic"><?php echo $recording['bp_diastolic']; ?></span>
                    </span>
                </div>
                <div class="vital-unit">mmHg</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card recording-vital-card h-100">
                <i class="bi bi-activity fs-2 mb-3" style="color: var(--casana-maroon);"></i>
                <div class="vital-label">Heart Rate</div>
                <div class="vital-value"><?php echo $recording['heart_rate']; ?></div>
                <div class="vital-unit">bpm</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card recording-vital-card h-100">
                <i class="bi bi-lungs fs-2 mb-3" style="color: var(--casana-baby-blue);"></i>
                <div class="vital-label">Blood Oxygen</div>
                <div class="vital-value"><?php echo round($recording['blood_oxygenation'], 1); ?></div>
                <div class="vital-unit">%</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card recording-vital-card h-100">
                <i class="bi bi-lightning fs-2 mb-3" style="color: var(--casana-orange);"></i>
                <div class="vital-label">HRV</div>
                <div class="vital-value"><?php echo round($recording['hrv'], 1); ?></div>
                <div class="vital-unit">ms</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card recording-vital-card h-100">
                <i class="bi bi-person-walking fs-2 mb-3" style="color: var(--casana-green);"></i>
                <div class="vital-label">Agility Score</div>
                <div class="vital-value"><?php echo round($recording['agility_score']); ?></div>
                <div class="vital-unit">/100</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card recording-vital-card h-100">
                <i class="bi bi-speedometer2 fs-2 mb-3" style="color: var(--casana-purple);"></i>
                <div class="vital-label">Seated Weight</div>
                <div class="vital-value"><?php echo round($recording['seated_weight'], 1); ?></div>
                <div class="vital-unit">kg</div>
            </div>
        </div>
    </div>
    
    <!-- ECG Trace -->
    <?php if (isset($recording['ecg_trace']) && !empty($recording['ecg_trace'])): ?>
    <div class="ecg-viewer">
        <div class="ecg-header">
            <div>
                <h5 class="mb-1"><i class="bi bi-activity me-2"></i>ECG Trace</h5>
                <p class="small text-muted mb-0">10-second recording at 100Hz</p>
            </div>
            <div class="ecg-controls">
                <button class="btn btn-outline-secondary btn-sm" onclick="zoomIn()" title="Zoom In">
                    <i class="bi bi-zoom-in"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="zoomOut()" title="Zoom Out">
                    <i class="bi bi-zoom-out"></i>
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="resetZoom()" title="Reset View">
                    <i class="bi bi-arrows-angle-expand me-1"></i>Reset
                </button>
            </div>
        </div>
        <div class="ecg-canvas" style="height: 300px; cursor: crosshair;">
            <canvas id="ecgChart"></canvas>
        </div>
        <div class="ecg-zoom-hint small text-muted mt-2 text-center">
            <i class="bi bi-info-circle me-1"></i>
            <span class="d-none d-md-inline">Scroll to zoom • Click and drag to select area • Drag to pan when zoomed</span>
            <span class="d-md-none">Pinch to zoom • Drag to pan</span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Additional Info -->
    <div class="card mt-4">
        <div class="card-header">
            <i class="bi bi-info-circle me-2"></i>Recording Information
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td class="text-muted">Recording ID</td>
                            <td class="fw-medium"><?php echo $recording['id']; ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Biometric ID</td>
                            <td class="fw-medium"><?php echo htmlspecialchars($recording['bio_id']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Seat Serial</td>
                            <td class="fw-medium"><?php echo htmlspecialchars($recording['seat_serial']); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td class="text-muted">User</td>
                            <td class="fw-medium"><?php echo htmlspecialchars($recording['user_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Age</td>
                            <td class="fw-medium"><?php echo $recording['user_age'] ?? 'N/A'; ?> years</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Hypertensive</td>
                            <td class="fw-medium"><?php echo $recording['htn'] ? 'Yes' : 'No'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- What This Means -->
    <div class="card mt-4">
        <div class="card-header">
            <i class="bi bi-lightbulb me-2"></i>Understanding Your Results
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="text-primary">Blood Pressure</h6>
                    <p class="small text-muted mb-0">
                        <?php if ($recording['bp_systolic'] < 120 && $recording['bp_diastolic'] < 80): ?>
                        Your blood pressure is in the normal range. Keep up the good work!
                        <?php elseif ($recording['bp_systolic'] < 130 && $recording['bp_diastolic'] < 85): ?>
                        Your blood pressure is slightly elevated. Monitor regularly.
                        <?php else: ?>
                        Your blood pressure reading is high. Consider discussing with your healthcare provider.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">Heart Rate</h6>
                    <p class="small text-muted mb-0">
                        <?php if ($recording['heart_rate'] >= 60 && $recording['heart_rate'] <= 100): ?>
                        Your resting heart rate is within the normal range of 60-100 bpm.
                        <?php elseif ($recording['heart_rate'] < 60): ?>
                        Your heart rate is below 60 bpm, which may be normal for athletes.
                        <?php else: ?>
                        Your heart rate is above 100 bpm. This could be due to activity, caffeine, or stress.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-exclamation-circle empty-icon"></i>
                <h5 class="empty-title">Recording Not Found</h5>
                <p class="empty-description">This recording could not be loaded.</p>
                <a href="history.php?id=<?php echo $userId; ?>" class="btn btn-primary">Back to History</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
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
            <a class="nav-link active" href="history.php?id=<?php echo $userId; ?>">
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

<?php if (isset($recording['ecg_trace']) && !empty($recording['ecg_trace'])): ?>
<script>
const ecgData = <?php echo json_encode($recording['ecg_trace']); ?>;
let ecgChart = null;

document.addEventListener('DOMContentLoaded', function() {
    ecgChart = CasanaCharts.createECGChart(
        document.getElementById('ecgChart'),
        ecgData
    );
});

function zoomIn() {
    if (ecgChart) {
        ecgChart.zoom(1.5);
    }
}

function zoomOut() {
    if (ecgChart) {
        ecgChart.zoom(0.67);
    }
}

function resetZoom() {
    if (ecgChart) {
        ecgChart.resetZoom();
    }
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
