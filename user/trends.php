<?php
/**
 * User - Health Trends
 * Long-term health trend visualizations
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get user ID
$userId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch user and trends
$user = $api->getUser($userId);
$trends30 = $api->getUserTrends($userId, ['days' => 30, 'group_by' => 'day']);
$trends90 = $api->getUserTrends($userId, ['days' => 90, 'group_by' => 'week']);

// Page setup
$pageTitle = 'Health Trends';
$currentPage = 'trends';
$appName = 'user';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width: 1000px;">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Your Health Trends</h1>
            <p class="text-muted mb-0">See how your health has changed over time</p>
        </div>
        <div class="time-selector">
            <button class="time-option active" data-days="30" onclick="setPeriod(30)">30 Days</button>
            <button class="time-option" data-days="90" onclick="setPeriod(90)">90 Days</button>
        </div>
    </div>
    
    <?php if ($trends30 && count($trends30) > 0): ?>
    
    <!-- Summary Cards -->
    <?php
    $avgBP = round(array_sum(array_column($trends30, 'avg_bp_systolic')) / count($trends30));
    $avgHR = round(array_sum(array_column($trends30, 'avg_heart_rate')) / count($trends30));
    $avgO2 = round(array_sum(array_column($trends30, 'avg_blood_oxygenation')) / count($trends30), 1);
    $avgAgility = round(array_sum(array_column($trends30, 'avg_agility_score')) / count($trends30));
    $totalReadings = array_sum(array_column($trends30, 'recording_count'));
    
    // Calculate trends (comparing first half to second half)
    $halfPoint = floor(count($trends30) / 2);
    $firstHalf = array_slice($trends30, 0, $halfPoint);
    $secondHalf = array_slice($trends30, $halfPoint);
    
    $bpTrend = 'stable';
    if (count($firstHalf) > 0 && count($secondHalf) > 0) {
        $firstAvgBP = array_sum(array_column($firstHalf, 'avg_bp_systolic')) / count($firstHalf);
        $secondAvgBP = array_sum(array_column($secondHalf, 'avg_bp_systolic')) / count($secondHalf);
        $bpChange = (($secondAvgBP - $firstAvgBP) / $firstAvgBP) * 100;
        if ($bpChange > 5) $bpTrend = 'up';
        if ($bpChange < -5) $bpTrend = 'down';
    }
    ?>
    
    <div class="row g-4 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card text-center py-4 h-100">
                <div class="small text-muted mb-1">Avg Blood Pressure</div>
                <div class="fs-2 fw-bold"><?php echo $avgBP; ?></div>
                <div class="small">
                    <?php if ($bpTrend === 'up'): ?>
                    <span class="text-danger"><i class="bi bi-arrow-up"></i> Trending up</span>
                    <?php elseif ($bpTrend === 'down'): ?>
                    <span class="text-success"><i class="bi bi-arrow-down"></i> Trending down</span>
                    <?php else: ?>
                    <span class="text-muted"><i class="bi bi-dash"></i> Stable</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card text-center py-4 h-100">
                <div class="small text-muted mb-1">Avg Heart Rate</div>
                <div class="fs-2 fw-bold"><?php echo $avgHR; ?></div>
                <div class="small text-muted">bpm</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card text-center py-4 h-100">
                <div class="small text-muted mb-1">Avg Oxygen</div>
                <div class="fs-2 fw-bold"><?php echo $avgO2; ?>%</div>
                <div class="small text-success">
                    <?php echo $avgO2 >= 95 ? 'Normal' : 'Monitor'; ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card text-center py-4 h-100">
                <div class="small text-muted mb-1">Total Readings</div>
                <div class="fs-2 fw-bold"><?php echo $totalReadings; ?></div>
                <div class="small text-muted">in 30 days</div>
            </div>
        </div>
    </div>
    
    <!-- Blood Pressure Trend -->
    <div class="chart-container mb-4">
        <div class="chart-header">
            <h5 class="chart-title">Blood Pressure Over Time</h5>
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
            <canvas id="bpTrendChart"></canvas>
        </div>
    </div>
    
    <!-- Heart Rate & SpO2 -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="chart-container h-100">
                <div class="chart-header">
                    <h5 class="chart-title">Heart Rate Trend</h5>
                </div>
                <div style="height: 220px;">
                    <canvas id="hrTrendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container h-100">
                <div class="chart-header">
                    <h5 class="chart-title">Oxygen Saturation</h5>
                </div>
                <div style="height: 220px;">
                    <canvas id="spo2TrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Agility Score -->
    <div class="chart-container mb-4">
        <div class="chart-header">
            <h5 class="chart-title">Mobility/Agility Score</h5>
            <span class="small text-muted">Higher is better (0-100 scale)</span>
        </div>
        <div style="height: 250px;">
            <canvas id="agilityTrendChart"></canvas>
        </div>
    </div>
    
    <!-- HTN Rate -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-bar-chart me-2"></i>Hypertensive Readings
        </div>
        <div class="card-body">
            <?php
            $totalHTN = 0;
            foreach ($trends30 as $day) {
                $totalHTN += ($day['htn_percentage'] / 100) * $day['recording_count'];
            }
            $htnPercent = $totalReadings > 0 ? ($totalHTN / $totalReadings) * 100 : 0;
            ?>
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <div class="display-4 fw-bold <?php echo $htnPercent > 30 ? 'text-danger' : ($htnPercent > 15 ? 'text-warning' : 'text-success'); ?>">
                        <?php echo round($htnPercent, 1); ?>%
                    </div>
                    <div class="text-muted">of readings were elevated</div>
                </div>
                <div class="col-md-8">
                    <div class="progress mb-3" style="height: 24px;">
                        <div class="progress-bar bg-success" style="width: <?php echo 100 - $htnPercent; ?>%;">
                            Normal
                        </div>
                        <div class="progress-bar bg-danger" style="width: <?php echo $htnPercent; ?>%;">
                            Elevated
                        </div>
                    </div>
                    <p class="small text-muted mb-0">
                        <?php if ($htnPercent < 15): ?>
                        <i class="bi bi-check-circle text-success me-1"></i>
                        Your blood pressure has been well controlled this month.
                        <?php elseif ($htnPercent < 30): ?>
                        <i class="bi bi-exclamation-circle text-warning me-1"></i>
                        Some readings are elevated. Consider lifestyle adjustments.
                        <?php else: ?>
                        <i class="bi bi-exclamation-triangle text-danger me-1"></i>
                        Many readings are elevated. Please consult your healthcare provider.
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
                <i class="bi bi-graph-up empty-icon"></i>
                <h5 class="empty-title">Not Enough Data</h5>
                <p class="empty-description">Keep using your Heart Seat to see trends over time.</p>
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
            <a class="nav-link" href="history.php?id=<?php echo $userId; ?>">
                <i class="bi bi-clock-history"></i>
                History
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="trends.php?id=<?php echo $userId; ?>">
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
const trends30 = <?php echo json_encode($trends30 ?? []); ?>;
const trends90 = <?php echo json_encode($trends90 ?? []); ?>;
const userId = <?php echo $userId; ?>;
let currentPeriod = 30;
let charts = {};

document.addEventListener('DOMContentLoaded', function() {
    if (trends30.length > 0) {
        initCharts(trends30);
    }
});

function setPeriod(days) {
    currentPeriod = days;
    
    // Update buttons
    document.querySelectorAll('.time-option').forEach(btn => {
        btn.classList.toggle('active', parseInt(btn.dataset.days) === days);
    });
    
    // Reinitialize charts
    const data = days === 30 ? trends30 : trends90;
    
    Object.values(charts).forEach(chart => chart.destroy());
    charts = {};
    
    initCharts(data);
}

function initCharts(trendsData) {
    const colors = CasanaCharts.getColors();
    const labels = trendsData.map(t => {
        const date = new Date(t.date);
        return currentPeriod === 30 
            ? date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
            : date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    
    // BP Chart
    charts.bp = CasanaCharts.createBPChart(
        document.getElementById('bpTrendChart'),
        trendsData.map(t => ({
            date: new Date(t.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
            systolic: t.avg_bp_systolic,
            diastolic: t.avg_bp_diastolic
        }))
    );
    
    // HR Chart
    charts.hr = CasanaCharts.createLineChart(document.getElementById('hrTrendChart'), {
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
    charts.spo2 = CasanaCharts.createLineChart(document.getElementById('spo2TrendChart'), {
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
    
    // Agility Chart
    charts.agility = CasanaCharts.createLineChart(document.getElementById('agilityTrendChart'), {
        data: {
            labels: labels,
            datasets: [{
                label: 'Agility Score',
                data: trendsData.map(t => t.avg_agility_score),
                borderColor: colors.success,
                backgroundColor: colors.success + '20',
                fill: true,
            }]
        },
        options: {
            scales: {
                y: { min: 0, max: 100 }
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
