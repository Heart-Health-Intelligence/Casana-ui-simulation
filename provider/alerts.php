<?php
/**
 * Care Provider - Alerts
 * Patients with concerning readings
 */

require_once __DIR__ . '/../includes/api-helper.php';
require_once __DIR__ . '/../includes/alert-taxonomy.php';

// Get provider ID and time range
$providerId = isset($_GET['id']) ? intval($_GET['id']) : 1;
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;

// Validate days parameter
$validDays = [1, 7, 30, 90];
if (!in_array($days, $validDays)) {
    $days = 7;
}

// Fetch data with selected time range
$provider = $api->getCareProvider($providerId);
$alerts = $api->getAlertRecordings(['per_page' => 50, 'days' => $days]);
$extendedSits = $api->getExtendedRecordings(['per_page' => 20, 'days' => $days]);

// Page setup
$pageTitle = 'Alerts';
$currentPage = 'alerts';
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
                <h1>Patient Alerts</h1>
                <p class="mb-0">Patients with readings that may require attention</p>
            </div>
            <div class="col-auto">
                <div class="time-selector" role="group" aria-label="Time range">
                    <button class="time-option <?php echo $days === 1 ? 'active' : ''; ?>" 
                            data-days="1" 
                            aria-pressed="<?php echo $days === 1 ? 'true' : 'false'; ?>">24h</button>
                    <button class="time-option <?php echo $days === 7 ? 'active' : ''; ?>" 
                            data-days="7"
                            aria-pressed="<?php echo $days === 7 ? 'true' : 'false'; ?>">7D</button>
                    <button class="time-option <?php echo $days === 30 ? 'active' : ''; ?>" 
                            data-days="30"
                            aria-pressed="<?php echo $days === 30 ? 'true' : 'false'; ?>">30D</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alert Summary Cards -->
    <div class="row g-4 mb-4">
        <?php
        // Count alerts using centralized taxonomy
        $alertCounts = countAlertsByReason($alerts ? $alerts['recordings'] : []);
        
        // Define which alert types to show in summary (sorted by clinical priority)
        $summaryTypes = ['hypertension', 'low_spo2', 'extended_sit'];
        
        foreach ($summaryTypes as $alertType):
            $info = getAlertInfo($alertType);
            $count = $alertCounts[$alertType] ?? 0;
            $colorVar = $info['color'] === 'danger' ? 'var(--status-danger)' : 
                       ($info['color'] === 'warning' ? 'var(--status-warning)' : 'var(--casana-purple)');
        ?>
        <div class="col-md-4">
            <div class="card h-100 alert-summary-card">
                <div class="alert-card-accent" style="background: <?php echo $colorVar; ?>;"></div>
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="alert-icon-wrapper <?php echo $info['color_class']; ?>">
                            <i class="bi <?php echo $info['icon']; ?> <?php echo $info['text_class']; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="alert-count"><?php echo $count; ?></div>
                            <div class="alert-type-label"><?php echo htmlspecialchars($info['label']); ?></div>
                            <div class="alert-threshold"><?php echo htmlspecialchars($info['threshold']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Alert Filters -->
    <div class="mb-4">
        <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filter alerts by type">
            <button class="filter-chip active" data-filter="all">
                All Alerts <span class="filter-count"><?php echo $alerts ? $alerts['pagination']['total'] : 0; ?></span>
            </button>
            <?php foreach ($summaryTypes as $alertType): 
                $info = getAlertInfo($alertType);
                $count = $alertCounts[$alertType] ?? 0;
            ?>
            <button class="filter-chip" data-filter="<?php echo $alertType; ?>">
                <i class="bi <?php echo $info['icon']; ?>"></i><?php echo htmlspecialchars($info['label']); ?>
                <?php if ($count > 0): ?>
                <span class="filter-count"><?php echo $count; ?></span>
                <?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Alert List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-inbox me-2"></i>Alert Queue</span>
            <span class="text-muted small">
                Showing <?php echo count($alerts['recordings'] ?? []); ?> of <?php echo $alerts['pagination']['total'] ?? 0; ?> alerts
            </span>
        </div>
        <div class="card-body p-0">
            <?php if ($alerts && !empty($alerts['recordings'])): ?>
            <?php 
            // Sort alerts by severity (highest first)
            $sortedAlerts = sortAlertsBySeverity($alerts['recordings']);
            ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 alert-queue-table" id="alertsTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Severity</th>
                            <th>Patient</th>
                            <th>Alert Type</th>
                            <th>Vitals</th>
                            <th>Time</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sortedAlerts as $alert): 
                            $severity = getMaxSeverity($alert['alert_reasons']);
                            $severityInfo = getSeverityInfo($severity);
                            $isRecent = strtotime($alert['sit_time']) > strtotime('-2 hours');
                        ?>
                        <tr class="alert-row <?php echo $isRecent ? 'alert-recent' : ''; ?>" 
                            data-reasons="<?php echo implode(',', $alert['alert_reasons']); ?>"
                            data-severity="<?php echo $severity; ?>"
                            data-alert-id="<?php echo $alert['id']; ?>">
                            <td>
                                <span class="badge bg-<?php echo $severityInfo['class']; ?> severity-badge" title="<?php echo htmlspecialchars($severityInfo['description']); ?>">
                                    <?php echo htmlspecialchars($severityInfo['label']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="patient.php?provider=<?php echo $providerId; ?>&id=<?php echo $alert['user_id']; ?>" class="text-decoration-none">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="entity-avatar" style="width: 36px; height: 36px; font-size: 0.8rem;">
                                            <?php echo getInitials($alert['user_name']); ?>
                                        </div>
                                        <div>
                                            <div class="fw-medium text-body"><?php echo htmlspecialchars($alert['user_name']); ?></div>
                                            <div class="small text-muted">Age <?php echo $alert['user_age']; ?> • <?php echo $alert['user_gender'] ?? ''; ?></div>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <?php foreach ($alert['alert_reasons'] as $reason): ?>
                                    <?php echo renderAlertBadge($reason, true, true); ?>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <div class="vitals-compact">
                                    <div class="vital-item <?php echo $alert['htn'] ? 'text-danger fw-semibold' : ''; ?>">
                                        <span class="vital-label">BP</span>
                                        <span class="vital-value"><?php echo $alert['bp_systolic']; ?>/<?php echo $alert['bp_diastolic']; ?></span>
                                    </div>
                                    <div class="vital-item <?php echo $alert['blood_oxygenation'] < 94 ? 'text-danger fw-semibold' : ''; ?>">
                                        <span class="vital-label">O₂</span>
                                        <span class="vital-value"><?php echo round($alert['blood_oxygenation'], 1); ?>%</span>
                                    </div>
                                    <div class="vital-item">
                                        <span class="vital-label">HR</span>
                                        <span class="vital-value"><?php echo $alert['heart_rate']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="<?php echo $isRecent ? 'text-danger fw-semibold' : 'text-muted'; ?>" 
                                      title="<?php echo date('M j, Y \a\t g:i A', strtotime($alert['sit_time'])); ?>">
                                    <?php echo $isRecent ? '<i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>' : ''; ?>
                                    <?php echo formatRelativeTime($alert['sit_time']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="alert-actions d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-success action-btn" 
                                            onclick="event.stopPropagation(); acknowledgeAlert(<?php echo $alert['id']; ?>)" 
                                            title="Acknowledge - mark as reviewed"
                                            aria-label="Acknowledge alert">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary action-btn" 
                                            onclick="event.stopPropagation(); addAlertNote(<?php echo $alert['id']; ?>, '<?php echo htmlspecialchars($alert['user_name'], ENT_QUOTES); ?>')" 
                                            title="Add note"
                                            aria-label="Add note to alert">
                                        <i class="bi bi-chat-text"></i>
                                    </button>
                                    <a href="patient.php?provider=<?php echo $providerId; ?>&id=<?php echo $alert['user_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary action-btn"
                                       title="View patient details"
                                       aria-label="View patient details">
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($alerts['pagination']['pages'] > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li class="page-item <?php echo $alerts['pagination']['page'] <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $providerId; ?>&page=<?php echo $alerts['pagination']['page'] - 1; ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= min(5, $alerts['pagination']['pages']); $i++): ?>
                        <li class="page-item <?php echo $i === $alerts['pagination']['page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $providerId; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $alerts['pagination']['page'] >= $alerts['pagination']['pages'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $providerId; ?>&page=<?php echo $alerts['pagination']['page'] + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-check-circle empty-icon success"></i>
                <h5 class="empty-title">No Active Alerts</h5>
                <p class="empty-description">All patients are within normal parameters for the selected time range.</p>
                <div class="empty-action">
                    <a href="patients.php?id=<?php echo $providerId; ?>" class="btn btn-outline-primary">
                        <i class="bi bi-people me-2"></i>View All Patients
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="noteModalLabel">Add Clinical Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">
                    Adding note for alert on patient: <strong id="notePatientName"></strong>
                </p>
                <div class="mb-3">
                    <label for="noteType" class="form-label">Note Type</label>
                    <select class="form-select" id="noteType">
                        <option value="review">Reviewed - No Action Needed</option>
                        <option value="follow_up">Follow-up Required</option>
                        <option value="contacted">Patient Contacted</option>
                        <option value="referred">Referred to Specialist</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="noteText" class="form-label">Notes (optional)</label>
                    <textarea class="form-control" id="noteText" rows="3" placeholder="Add any relevant clinical notes..."></textarea>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="scheduleFollowUp">
                    <label class="form-check-label" for="scheduleFollowUp">Schedule follow-up reminder</label>
                </div>
                <div id="followUpDate" class="mb-3" style="display: none;">
                    <label for="followUpDateInput" class="form-label">Follow-up Date</label>
                    <input type="date" class="form-control" id="followUpDateInput">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveNote()">
                    <i class="bi bi-check-lg me-1"></i>Save Note
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Alert Summary Card Styles */
.alert-summary-card {
    position: relative;
    overflow: hidden;
}

.alert-card-accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    border-radius: var(--radius-xl) var(--radius-xl) 0 0;
}

.alert-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.alert-count {
    font-family: var(--font-family-numbers);
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.02em;
}

.alert-type-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-top: 0.25rem;
}

.alert-threshold {
    font-size: 0.7rem;
    color: var(--text-muted);
    margin-top: 0.125rem;
}

/* Alert Queue Table Styles */
.alert-queue-table th {
    background: var(--bg-secondary);
    position: sticky;
    top: 0;
    z-index: 10;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
}

.alert-row {
    transition: all var(--transition-fast);
}

.alert-row.alert-recent {
    background: rgba(239, 68, 68, 0.04);
}

.alert-row:hover {
    background: var(--bg-hover);
}

.severity-badge {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    min-width: 64px;
    text-align: center;
    padding: 0.35rem 0.6rem;
}

.vitals-compact {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.vital-item {
    display: flex;
    flex-direction: column;
    min-width: 44px;
}

.vital-item .vital-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.04em;
    font-weight: 600;
}

.vital-item .vital-value {
    font-family: var(--font-family-numbers);
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    font-size: 0.875rem;
}

.alert-actions {
    opacity: 0.5;
    transition: opacity var(--transition-fast);
}

.alert-row:hover .alert-actions {
    opacity: 1;
}

.action-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    transition: all var(--transition-fast);
}

.action-btn:hover {
    transform: translateY(-1px);
}

.alert-row.acknowledged {
    opacity: 0.5;
    background: var(--bg-tertiary);
}

.alert-row.acknowledged td:first-child::before {
    content: '✓';
    margin-right: 0.5rem;
    color: var(--status-success);
}

/* Filter chip active states for this page */
.filter-chip[data-filter]:not(.active):hover {
    border-color: var(--casana-purple);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter buttons (filter chips)
    document.querySelectorAll('.filter-chip[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            document.querySelectorAll('.filter-chip[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            filterAlerts(filter);
        });
    });
    
    // Time selector - navigate to update data
    document.querySelectorAll('.time-option').forEach(btn => {
        btn.addEventListener('click', function() {
            const days = this.dataset.days;
            const url = new URL(window.location.href);
            url.searchParams.set('days', days);
            url.searchParams.delete('page'); // Reset pagination when changing time range
            window.location.href = url.toString();
        });
    });
    
    // Follow-up date toggle
    document.getElementById('scheduleFollowUp').addEventListener('change', function() {
        document.getElementById('followUpDate').style.display = this.checked ? 'block' : 'none';
        if (this.checked) {
            // Default to 7 days from now
            const date = new Date();
            date.setDate(date.getDate() + 7);
            document.getElementById('followUpDateInput').value = date.toISOString().split('T')[0];
        }
    });
});

function filterAlerts(filter) {
    const rows = document.querySelectorAll('.alert-row');
    
    rows.forEach(row => {
        const reasons = row.dataset.reasons.split(',');
        
        if (filter === 'all') {
            row.style.display = '';
        } else if (reasons.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Clinician Actions
let currentAlertId = null;

function acknowledgeAlert(alertId) {
    const row = document.querySelector(`[data-alert-id="${alertId}"]`);
    if (row) {
        row.classList.add('acknowledged');
        
        // Show success toast
        showToast('Alert acknowledged', 'success');
        
        // In production, would call API:
        // CasanaAPI.acknowledgeAlert(alertId);
    }
}

function addAlertNote(alertId, patientName) {
    currentAlertId = alertId;
    document.getElementById('notePatientName').textContent = patientName;
    document.getElementById('noteText').value = '';
    document.getElementById('noteType').value = 'review';
    document.getElementById('scheduleFollowUp').checked = false;
    document.getElementById('followUpDate').style.display = 'none';
    
    const modal = new bootstrap.Modal(document.getElementById('noteModal'));
    modal.show();
}

function saveNote() {
    const noteType = document.getElementById('noteType').value;
    const noteText = document.getElementById('noteText').value;
    const hasFollowUp = document.getElementById('scheduleFollowUp').checked;
    const followUpDate = document.getElementById('followUpDateInput').value;
    
    // In production, would save to API
    console.log('Saving note:', { alertId: currentAlertId, noteType, noteText, hasFollowUp, followUpDate });
    
    // Close modal and show success
    bootstrap.Modal.getInstance(document.getElementById('noteModal')).hide();
    showToast('Note saved successfully', 'success');
    
    // Mark row as having a note
    const row = document.querySelector(`[data-alert-id="${currentAlertId}"]`);
    if (row) {
        const actionBtn = row.querySelector('.action-btn[title="Add note"]');
        if (actionBtn) {
            actionBtn.classList.remove('btn-outline-secondary');
            actionBtn.classList.add('btn-secondary');
        }
    }
}

function showToast(message, type = 'info') {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'info'} position-fixed`;
    toast.style.cssText = 'bottom: 20px; right: 20px; z-index: 9999; animation: fadeIn 0.3s;';
    toast.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Expose data for global search
window.providerId = <?php echo $providerId; ?>;
window.providerPatients = <?php echo json_encode($provider['patients'] ?? []); ?>;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
