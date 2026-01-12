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

// Get filter parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$filter = isset($_GET['filter']) ? $_GET['filter'] : null;

// Build API parameters
$apiParams = ['per_page' => 20, 'page' => $page];

// Add date filters if provided
if ($startDate) {
    $apiParams['start_date'] = $startDate;
}
if ($endDate) {
    $apiParams['end_date'] = $endDate;
}

// Add HTN-only filter for "elevated" - the API supports htn_only parameter
if ($filter === 'elevated') {
    $apiParams['htn_only'] = true;
}

// Fetch user and recordings
$user = $api->getUser($userId);
$recordings = $api->getUserRecordings($userId, $apiParams);

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
            <button class="filter-chip active" onclick="setQuickFilter('all', event)">
                <i class="bi bi-list-ul"></i> All
            </button>
            <button class="filter-chip" onclick="setQuickFilter('today', event)">
                <i class="bi bi-calendar-day"></i> Today
            </button>
            <button class="filter-chip" onclick="setQuickFilter('week', event)">
                <i class="bi bi-calendar-week"></i> This Week
            </button>
            <button class="filter-chip" onclick="setQuickFilter('month', event)">
                <i class="bi bi-calendar-month"></i> This Month
            </button>
            <button class="filter-chip warning" onclick="setQuickFilter('elevated', event)">
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
                                <button class="btn btn-sm btn-outline-primary" onclick="openRecordingDetail(<?php echo $rec['id']; ?>, event)" title="View ECG &amp; Details">
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
var userId = <?php echo $userId; ?>;

function applyDateFilter() {
    var startDate = document.getElementById('startDate').value;
    var endDate = document.getElementById('endDate').value;
    
    var url = 'history.php?id=' + userId;
    if (startDate) url += '&start_date=' + startDate;
    if (endDate) url += '&end_date=' + endDate;
    
    window.location.href = url;
}

function setQuickFilter(filter, evt) {
    // Prevent double-firing from event bubbling
    if (evt) {
        evt.preventDefault();
    }
    
    // Update active state visually
    document.querySelectorAll('.filter-chip').forEach(function(chip) {
        chip.classList.remove('active');
    });
    
    // Find the clicked chip and mark it active
    var targetChip = evt ? evt.currentTarget : document.querySelector('.filter-chip');
    if (targetChip) {
        targetChip.classList.add('active');
    }
    
    var today = new Date();
    var startDate = '';
    var endDate = today.toISOString().split('T')[0];
    var filterType = '';
    
    if (filter === 'today') {
        startDate = endDate;
    } else if (filter === 'week') {
        var weekAgo = new Date(today);
        weekAgo.setDate(weekAgo.getDate() - 7);
        startDate = weekAgo.toISOString().split('T')[0];
    } else if (filter === 'month') {
        var monthAgo = new Date(today);
        monthAgo.setMonth(monthAgo.getMonth() - 1);
        startDate = monthAgo.toISOString().split('T')[0];
    } else if (filter === 'elevated') {
        filterType = 'elevated';
    } else {
        // "all" - no date filter
        window.location.href = 'history.php?id=' + userId;
        return;
    }
    
    var url = 'history.php?id=' + userId;
    if (startDate) url += '&start_date=' + startDate;
    if (endDate) url += '&end_date=' + endDate;
    if (filterType) url += '&filter=' + filterType;
    
    window.location.href = url;
}

// Open recording detail (for ECG button in table)
function openRecordingDetail(recId, evt) {
    evt.stopPropagation(); // Prevent row click
    window.location.href = 'recording.php?id=' + recId + '&user=' + userId;
}

// Highlight current filter based on URL params
document.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    var startDate = urlParams.get('start_date');
    var endDateParam = urlParams.get('end_date');
    var filter = urlParams.get('filter');
    
    if (startDate) {
        document.getElementById('startDate').value = startDate;
    }
    if (endDateParam) {
        document.getElementById('endDate').value = endDateParam;
    }
    
    // Update filter chip active state based on current filter
    if (filter === 'elevated') {
        document.querySelectorAll('.filter-chip').forEach(function(chip) { 
            chip.classList.remove('active'); 
        });
        var warningChip = document.querySelector('.filter-chip.warning');
        if (warningChip) {
            warningChip.classList.add('active');
        }
    } else if (startDate) {
        // If there's a start date, deactivate "All" chip
        document.querySelectorAll('.filter-chip').forEach(function(chip) { 
            chip.classList.remove('active'); 
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
