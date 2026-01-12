<?php
/**
 * User - Recording History
 * All health recordings with filtering
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get user ID
$userId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Fetch user and recordings
$user = $api->getUser($userId);
$recordings = $api->getUserRecordings($userId, ['per_page' => 20, 'page' => $page]);

// Page setup
$pageTitle = 'History';
$currentPage = 'history';
$appName = 'user';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width: 900px;">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Your History</h1>
            <p class="text-muted mb-0"><?php echo number_format($recordings['pagination']['total'] ?? 0); ?> total readings</p>
        </div>
        <a href="index.php?id=<?php echo $userId; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
    
    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Start Date</label>
                    <input type="date" class="form-control" id="startDate">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">End Date</label>
                    <input type="date" class="form-control" id="endDate">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100" onclick="applyDateFilter()">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recordings List -->
    <?php if ($recordings && !empty($recordings['recordings'])): ?>
    
    <!-- Mobile Card View -->
    <div class="d-md-none">
        <?php foreach ($recordings['recordings'] as $rec): ?>
        <div class="card mb-3" onclick="window.location='recording.php?id=<?php echo $rec['id']; ?>&user=<?php echo $userId; ?>'" style="cursor: pointer;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-semibold"><?php echo formatDateTime($rec['sit_time']); ?></div>
                        <div class="small text-muted">Duration: <?php echo formatDuration($rec['duration_seconds']); ?></div>
                    </div>
                    <?php if ($rec['htn']): ?>
                    <span class="badge bg-danger-soft">HTN</span>
                    <?php else: ?>
                    <span class="badge bg-success-soft">Normal</span>
                    <?php endif; ?>
                </div>
                <div class="row g-2 text-center">
                    <div class="col-3">
                        <div class="small text-muted">BP</div>
                        <div class="fw-semibold <?php echo $rec['htn'] ? 'text-danger' : ''; ?>">
                            <?php echo $rec['bp_systolic']; ?>/<?php echo $rec['bp_diastolic']; ?>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted">HR</div>
                        <div class="fw-semibold"><?php echo $rec['heart_rate']; ?></div>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted">O₂</div>
                        <div class="fw-semibold"><?php echo round($rec['blood_oxygenation'], 1); ?>%</div>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted">Agility</div>
                        <div class="fw-semibold"><?php echo round($rec['agility_score']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Desktop Table View -->
    <div class="card d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Blood Pressure</th>
                            <th>Heart Rate</th>
                            <th>SpO₂</th>
                            <th>HRV</th>
                            <th>Agility</th>
                            <th>Duration</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recordings['recordings'] as $rec): ?>
                        <tr class="table-clickable" onclick="window.location='recording.php?id=<?php echo $rec['id']; ?>&user=<?php echo $userId; ?>'">
                            <td>
                                <div class="fw-medium text-nowrap"><?php echo formatDateTime($rec['sit_time'], true, true); ?></div>
                            </td>
                            <td>
                                <?php echo formatBloodPressureStyled($rec['bp_systolic'], $rec['bp_diastolic'], $rec['htn']); ?>
                                <?php if ($rec['htn']): ?>
                                <span class="badge bg-danger-soft ms-2">HTN</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $rec['heart_rate']; ?> bpm</td>
                            <td><?php echo round($rec['blood_oxygenation'], 1); ?>%</td>
                            <td><?php echo round($rec['hrv'], 1); ?> ms</td>
                            <td><?php echo round($rec['agility_score']); ?></td>
                            <td class="text-nowrap"><?php echo formatDuration($rec['duration_seconds']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-activity"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($recordings['pagination']['pages'] > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?id=<?php echo $userId; ?>&page=<?php echo $page - 1; ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            
            <?php 
            $startPage = max(1, $page - 2);
            $endPage = min($recordings['pagination']['pages'], $page + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++): 
            ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?id=<?php echo $userId; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            
            <li class="page-item <?php echo $page >= $recordings['pagination']['pages'] ? 'disabled' : ''; ?>">
                <a class="page-link" href="?id=<?php echo $userId; ?>&page=<?php echo $page + 1; ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-clock-history empty-icon"></i>
                <h5 class="empty-title">No Recordings Found</h5>
                <p class="empty-description">Your health readings will appear here.</p>
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

<script>
const userId = <?php echo $userId; ?>;

function applyDateFilter() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    let url = `history.php?id=${userId}`;
    if (startDate) url += `&start_date=${startDate}`;
    if (endDate) url += `&end_date=${endDate}`;
    
    window.location.href = url;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
