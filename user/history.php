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

<div class="container py-4 history-container">
    <!-- Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="mb-1 h2 fw-bold" style="letter-spacing: -0.02em;">Your History</h1>
            <p class="text-muted mb-0 d-flex align-items-center gap-2">
                <span class="badge bg-primary-soft text-primary fw-semibold"><?php echo number_format($recordings['pagination']['total'] ?? 0); ?></span>
                <span>total readings</span>
            </p>
        </div>
        <a href="index.php?id=<?php echo $userId; ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>
    
    <!-- Quick Filters -->
    <div class="quick-filters mb-4">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small me-2">Quick filters:</span>
            <button class="filter-chip active" onclick="setQuickFilter('all')">
                <i class="bi bi-list-ul"></i> All
            </button>
            <button class="filter-chip" onclick="setQuickFilter('today')">
                <i class="bi bi-calendar-day"></i> Today
            </button>
            <button class="filter-chip" onclick="setQuickFilter('week')">
                <i class="bi bi-calendar-week"></i> This Week
            </button>
            <button class="filter-chip" onclick="setQuickFilter('month')">
                <i class="bi bi-calendar-month"></i> This Month
            </button>
            <button class="filter-chip warning" onclick="setQuickFilter('elevated')">
                <i class="bi bi-exclamation-triangle"></i> Elevated BP
            </button>
        </div>
    </div>
    
    <!-- Advanced Date Filter (Collapsible) -->
    <div class="card mb-4 filter-card">
        <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-funnel me-2"></i>Custom Date Range
                </h6>
                <button class="btn btn-link btn-sm p-0 text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
            <div class="collapse show" id="advancedFilters">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Start Date</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">End Date</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" onclick="applyDateFilter()">
                            <i class="bi bi-filter me-1"></i> Apply Filter
                        </button>
                    </div>
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
                        <div class="fw-semibold"><?php echo formatDateTime($rec['sit_time'], true, true); ?></div>
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
                        <div class="<?php echo $rec['htn'] ? 'text-danger' : ''; ?>">
                            <?php echo formatBloodPressureStyled($rec['bp_systolic'], $rec['bp_diastolic'], $rec['htn']); ?>
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

function setQuickFilter(filter) {
    // Update active state visually
    document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.classList.remove('active');
    });
    event.target.closest('.filter-chip').classList.add('active');
    
    const today = new Date();
    let startDate = '';
    let endDate = today.toISOString().split('T')[0];
    let filterType = '';
    
    switch(filter) {
        case 'today':
            startDate = endDate;
            break;
        case 'week':
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);
            startDate = weekAgo.toISOString().split('T')[0];
            break;
        case 'month':
            const monthAgo = new Date(today);
            monthAgo.setMonth(monthAgo.getMonth() - 1);
            startDate = monthAgo.toISOString().split('T')[0];
            break;
        case 'elevated':
            filterType = 'elevated';
            break;
        case 'all':
        default:
            // No date filter for "all"
            window.location.href = `history.php?id=${userId}`;
            return;
    }
    
    let url = `history.php?id=${userId}`;
    if (startDate) url += `&start_date=${startDate}`;
    if (endDate) url += `&end_date=${endDate}`;
    if (filterType) url += `&filter=${filterType}`;
    
    window.location.href = url;
}

// Highlight current filter based on URL params
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const startDate = urlParams.get('start_date');
    const filter = urlParams.get('filter');
    
    if (startDate) {
        document.getElementById('startDate').value = startDate;
    }
    if (urlParams.get('end_date')) {
        document.getElementById('endDate').value = urlParams.get('end_date');
    }
    
    // Update filter chip active state based on current filter
    if (filter === 'elevated') {
        document.querySelectorAll('.filter-chip').forEach(chip => chip.classList.remove('active'));
        document.querySelector('.filter-chip.warning').classList.add('active');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
