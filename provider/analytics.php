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
                <a class="nav-link" href="alerts.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-exclamation-triangle"></i>
                    Alerts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="analytics.php?id=<?php echo $providerId; ?>">
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
                <h1>Analytics</h1>
                <p class="mb-0">Population health statistics and usage patterns</p>
            </div>
        </div>
    </div>
    
    <!-- Health Overview -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-value"><?php echo $healthOverview ? round($healthOverview['avg_heart_rate']) : '--'; ?></div>
                <div class="stat-label">Avg Heart Rate (bpm)</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-value"><?php echo $healthOverview ? round($healthOverview['avg_blood_oxygenation'], 1) : '--'; ?>%</div>
                <div class="stat-label">Avg SpO₂</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-value" style="color: <?php echo ($healthOverview && $healthOverview['htn_rate'] > 30) ? 'var(--status-danger)' : 'inherit'; ?>">
                    <?php echo $healthOverview ? round($healthOverview['htn_rate'], 1) : '--'; ?>%
                </div>
                <div class="stat-label">HTN Rate</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-value"><?php echo $healthOverview ? round($healthOverview['avg_agility_score']) : '--'; ?></div>
                <div class="stat-label">Avg Agility Score</div>
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
                            <h6 class="text-muted mb-3">Health Trends</h6>
                            <?php if ($populationStats && isset($populationStats['trend_breakdown'])): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($populationStats['trend_breakdown'] as $trend => $count): ?>
                                <div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-capitalize"><?php echo $trend; ?></span>
                                        <span class="fw-semibold"><?php echo $count; ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <?php
                                        $total = array_sum($populationStats['trend_breakdown']);
                                        $percent = $total > 0 ? ($count / $total) * 100 : 0;
                                        $color = $trend === 'stable' ? 'var(--status-success)' : ($trend === 'improving' ? 'var(--casana-purple)' : 'var(--status-danger)');
                                        ?>
                                        <div class="progress-bar" style="width: <?php echo $percent; ?>%; background-color: <?php echo $color; ?>"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
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
                    <h5 class="chart-title">Daily Usage Patterns</h5>
                    <span class="small text-muted">Average bathroom visits by hour</span>
                </div>
                <div style="height: 300px;">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-stopwatch me-2"></i>Sit Duration Analysis
                </div>
                <div class="card-body">
                    <?php if ($durationAnalysis): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Average Duration</span>
                            <span class="fw-semibold"><?php echo round($durationAnalysis['avg_duration_seconds'] / 60, 1); ?> min</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Extended Sits (20+ min)</span>
                            <span class="fw-semibold"><?php echo round($durationAnalysis['extended_sits_percentage'], 1); ?>%</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Very Long Sits (30+ min)</span>
                            <span class="fw-semibold text-warning"><?php echo round($durationAnalysis['very_long_sits_percentage'], 1); ?>%</span>
                        </div>
                    </div>
                    
                    <?php if (isset($durationAnalysis['by_age_group'])): ?>
                    <hr>
                    <h6 class="text-muted mb-3">By Age Group</h6>
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
