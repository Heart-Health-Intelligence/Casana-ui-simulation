<?php
/**
 * Care Provider - Analytics
 * Population health statistics and visualizations
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get provider ID
$providerId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch data
$provider = $api->getCareProvider($providerId);
$populationStats = $api->getPopulationStats($providerId);
$healthOverview = $api->getHealthOverview(30);
$ageDistribution = $api->getAgeDistribution();
$durationAnalysis = $api->getDurationAnalysis(30);
$hourlyUsageResponse = $api->getHourlyUsage(7);
$hourlyUsage = isset($hourlyUsageResponse['hourly_usage']) ? $hourlyUsageResponse['hourly_usage'] : [];
$alerts = $api->getAlertRecordings(['per_page' => 5, 'days' => 7]);

// Page setup
$pageTitle = 'Analytics';
$currentPage = 'analytics';
$appName = 'provider';
$alertCount = $alerts ? $alerts['pagination']['total'] : 0;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/provider-sidebar.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1>Population Analytics</h1>
                <p class="mb-0">
                    Health trends and usage patterns across your patient panel
                    <span class="badge bg-info-soft ms-2"><?php echo $provider['total_patients'] ?? 0; ?> patients</span>
                </p>
            </div>
            <div class="col-auto">
                <span class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i>Last 30 days
                </span>
            </div>
        </div>
    </div>
    
    <!-- Health Overview -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-value"><?php echo $healthOverview ? round($healthOverview['avg_heart_rate']) : '--'; ?></div>
                <div class="stat-label">Mean Heart Rate</div>
                <div class="stat-sublabel">bpm (all readings)</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card stat-success h-100">
                <div class="stat-value"><?php echo $healthOverview ? round($healthOverview['avg_blood_oxygenation'], 1) : '--'; ?>%</div>
                <div class="stat-label">Mean SpO₂</div>
                <div class="stat-sublabel">Normal ≥95%</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a href="patients.php?id=<?php echo $providerId; ?>" class="text-decoration-none">
                <?php $htnHigh = $healthOverview && $healthOverview['htn_rate'] > 30; ?>
                <div class="card stat-card h-100 stat-card-clickable <?php echo $htnHigh ? 'stat-danger' : 'stat-warning'; ?>">
                    <div class="stat-value">
                        <?php echo $healthOverview ? round($healthOverview['htn_rate'], 1) : '--'; ?>%
                    </div>
                    <div class="stat-label">Hypertension Rate</div>
                    <div class="stat-sublabel">
                        % of readings ≥140/90 
                        <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-value"><?php echo $healthOverview ? round($healthOverview['avg_agility_score']) : '--'; ?></div>
                <div class="stat-label">Mean Agility Score</div>
                <div class="stat-sublabel">Higher = better mobility</div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Age Distribution -->
        <div class="col-lg-6">
            <div class="chart-container h-100">
                <div class="chart-header">
                    <h5 class="chart-title">Patient Age Distribution</h5>
                </div>
                <div style="height: 300px;">
                    <canvas id="ageChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gender Distribution -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-pie-chart me-2"></i>Population Breakdown
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Gender</h6>
                            <?php if ($populationStats && isset($populationStats['gender_distribution'])): ?>
                            <div style="height: 150px;">
                                <canvas id="genderChart"></canvas>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">BP Trends (30-Day)</h6>
                            <?php if ($populationStats && isset($populationStats['trend_breakdown'])): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($populationStats['trend_breakdown'] as $trend => $count): ?>
                                <?php
                                $total = array_sum($populationStats['trend_breakdown']);
                                $percent = $total > 0 ? ($count / $total) * 100 : 0;
                                $color = $trend === 'stable' ? 'var(--status-success)' : ($trend === 'improving' ? 'var(--casana-purple)' : 'var(--status-danger)');
                                ?>
                                <a href="patients.php?id=<?php echo $providerId; ?>&status=<?php echo $trend; ?>" class="text-decoration-none text-body trend-drill-down">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-capitalize"><?php echo $trend; ?></span>
                                        <span class="fw-semibold"><?php echo $count; ?> <i class="bi bi-chevron-right small"></i></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" style="width: <?php echo $percent; ?>%; background-color: <?php echo $color; ?>"></div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <p class="small text-muted mt-3 mb-0">Click to view patients by trend</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Usage Patterns -->
    <div class="row g-4 mt-2">
        <div class="col-lg-8">
            <div class="chart-container h-100">
                <div class="chart-header">
                    <h5 class="chart-title">Reading Time Distribution</h5>
                    <span class="small text-muted">Average readings by hour (last 7 days)</span>
                </div>
                <div style="height: 300px;">
                    <canvas id="hourlyChart"></canvas>
                </div>
                <p class="small text-muted mt-2 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Shows when patients typically take readings. Useful for identifying adherence patterns.
                </p>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-stopwatch me-2"></i>Reading Duration Metrics
                </div>
                <div class="card-body">
                    <?php if ($durationAnalysis): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Mean Session Duration</span>
                            <span class="fw-semibold"><?php echo round($durationAnalysis['avg_duration_seconds'] / 60, 1); ?> min</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Extended Sessions (≥20 min)</span>
                            <span class="fw-semibold"><?php echo round($durationAnalysis['extended_sits_percentage'], 1); ?>%</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Prolonged Sessions (≥30 min)</span>
                            <span class="fw-semibold text-warning"><?php echo round($durationAnalysis['very_long_sits_percentage'], 1); ?>%</span>
                        </div>
                    </div>
                    
                    <p class="small text-muted mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Prolonged durations may indicate mobility issues or GI concerns.
                    </p>
                    
                    <?php if (isset($durationAnalysis['by_age_group'])): ?>
                    <hr>
                    <h6 class="text-muted mb-3">Mean Duration by Age</h6>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($durationAnalysis['by_age_group'] as $group => $data): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><?php echo $group; ?></span>
                            <span class="fw-semibold"><?php echo round($data['avg_minutes'], 1); ?> min</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <p>No duration data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Patient Status Overview -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-activity me-2"></i>Patient Health Overview</span>
                    <a href="patients.php?id=<?php echo $providerId; ?>" class="btn btn-outline-primary btn-sm">View All Patients</a>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="text-center p-4 rounded" style="background: rgba(0, 89, 75, 0.1);">
                                <h2 class="mb-2" style="color: var(--status-success);">
                                    <?php echo $healthOverview ? $healthOverview['users_stable'] : 0; ?>
                                </h2>
                                <p class="mb-0 text-muted">Stable Patients</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-4 rounded" style="background: rgba(106, 110, 255, 0.1);">
                                <h2 class="mb-2" style="color: var(--casana-purple);">
                                    <?php echo $healthOverview ? $healthOverview['users_with_improving_trends'] : 0; ?>
                                </h2>
                                <p class="mb-0 text-muted">Improving</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-4 rounded" style="background: rgba(194, 77, 112, 0.1);">
                                <h2 class="mb-2" style="color: var(--status-danger);">
                                    <?php echo $healthOverview ? $healthOverview['users_with_deteriorating_trends'] : 0; ?>
                                </h2>
                                <p class="mb-0 text-muted">Need Attention</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart data from PHP
const ageDistribution = <?php echo json_encode($ageDistribution ?? []); ?>;
const genderDistribution = <?php echo json_encode($populationStats['gender_distribution'] ?? []); ?>;
const hourlyUsage = <?php echo json_encode($hourlyUsage ?? []); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const colors = CasanaCharts.getColors();
    
    // Age Distribution Chart
    if (ageDistribution.length > 0) {
        CasanaCharts.createBarChart(document.getElementById('ageChart'), {
            data: {
                labels: ageDistribution.map(d => d.age_group),
                datasets: [{
                    label: 'Patients',
                    data: ageDistribution.map(d => d.count),
                    backgroundColor: colors.primary,
                    borderRadius: 4,
                }]
            }
        });
    }
    
    // Gender Distribution Chart
    if (Object.keys(genderDistribution).length > 0) {
        CasanaCharts.createDoughnutChart(document.getElementById('genderChart'), {
            data: {
                labels: Object.keys(genderDistribution),
                datasets: [{
                    data: Object.values(genderDistribution),
                    backgroundColor: [colors.primary, colors.danger, colors.warning],
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            usePointStyle: true,
                        }
                    }
                }
            }
        });
    }
    
    // Hourly Usage Chart
    if (hourlyUsage && hourlyUsage.length > 0) {
        const labels = hourlyUsage.map(h => {
            const hour = h.hour;
            if (hour === 0) return '12 AM';
            if (hour === 12) return '12 PM';
            if (hour < 12) return hour + ' AM';
            return (hour - 12) + ' PM';
        });
        
        CasanaCharts.createBarChart(document.getElementById('hourlyChart'), {
            data: {
                labels: labels,
                datasets: [{
                    label: 'Recordings',
                    data: hourlyUsage.map(h => h.recording_count),
                    backgroundColor: hourlyUsage.map(h => {
                        // Peak hours in purple, others in gray
                        if (h.hour >= 6 && h.hour <= 9) return colors.primary;
                        if (h.hour >= 18 && h.hour <= 21) return colors.primary;
                        return colors.primary + '60';
                    }),
                    borderRadius: 4,
                }]
            },
            options: {
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                        }
                    }
                }
            }
        });
    }
});

// Expose data for global search
window.providerId = <?php echo $providerId; ?>;
window.providerPatients = <?php echo json_encode($provider['patients'] ?? []); ?>;
</script>

<style>
/* Analytics Page Styles */
.stat-card-clickable {
    cursor: pointer;
    transition: transform var(--transition-fast), box-shadow var(--transition-fast), border-color var(--transition-fast);
}

.stat-card-clickable:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--casana-purple);
}

.trend-drill-down {
    display: block;
    padding: var(--spacing-sm);
    margin: calc(-1 * var(--spacing-sm));
    border-radius: var(--radius-md);
    transition: background-color var(--transition-fast);
}

.trend-drill-down:hover {
    background: var(--bg-hover);
}

.trend-drill-down .bi-chevron-right {
    opacity: 0;
    transition: opacity var(--transition-fast);
}

.trend-drill-down:hover .bi-chevron-right {
    opacity: 1;
}

.stat-context {
    font-size: 0.75rem;
}

/* Status Overview Cards */
.status-overview-card {
    cursor: pointer;
    transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.status-overview-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
