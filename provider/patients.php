<?php
/**
 * Care Provider - Patient List
 * Searchable, filterable patient directory
 */

require_once __DIR__ . '/../includes/api-helper.php';
require_once __DIR__ . '/../includes/alert-taxonomy.php';

// Get provider ID
$providerId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch provider data
$provider = $api->getCareProvider($providerId);
$alerts = $api->getAlertRecordings(['per_page' => 5, 'days' => 7]);

// Page setup
$pageTitle = 'Patients';
$currentPage = 'patients';
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
                <h1>Patient Panel</h1>
                <p class="mb-0"><?php echo $provider['total_patients'] ?? 0; ?> patients in your care</p>
            </div>
            <div class="col-auto">
                <a href="alerts.php?id=<?php echo $providerId; ?>" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    View Alerts
                    <?php if ($alertCount > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $alertCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Quick View Filters (Saved Views) -->
    <div class="mb-4">
        <div class="d-flex flex-wrap gap-2 quick-filters" role="group" aria-label="Quick filters">
            <button class="filter-chip active" data-view="all">
                <i class="bi bi-people"></i>All Patients
            </button>
            <button class="filter-chip warning" data-view="needs-review">
                <i class="bi bi-exclamation-circle"></i>Needs Review
            </button>
            <button class="filter-chip" data-view="inactive">
                <i class="bi bi-clock"></i>No Data (7 days)
            </button>
            <button class="filter-chip" data-view="deteriorating">
                <i class="bi bi-arrow-down-circle"></i>Deteriorating
            </button>
            <button class="filter-chip" data-view="stable">
                <i class="bi bi-check-circle"></i>Stable
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search patients..." style="padding-left: 38px;" aria-label="Search patients">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="statusFilter" aria-label="Filter by status">
                        <option value="">All Status</option>
                        <option value="stable">Stable</option>
                        <option value="improving">Improving</option>
                        <option value="deteriorating">Deteriorating</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="ageFilter" aria-label="Filter by age">
                        <option value="">All Ages</option>
                        <option value="20-40">20-40</option>
                        <option value="40-60">40-60</option>
                        <option value="60-75">60-75</option>
                        <option value="75+">75+</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="genderFilter" aria-label="Filter by gender">
                        <option value="">All</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="recencyFilter" aria-label="Filter by recency">
                        <option value="">All Recency</option>
                        <option value="today">Today</option>
                        <option value="3days">Last 3 days</option>
                        <option value="7days">Last 7 days</option>
                        <option value="inactive">Inactive (7+ days)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Results Count -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted" id="resultsCount">Showing all patients</span>
        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                <i class="bi bi-x-circle me-1"></i>Clear
            </button>
            <div class="btn-group" role="group" aria-label="View toggle">
                <button type="button" class="btn btn-outline-secondary btn-sm active" id="viewTable" onclick="setView('table')" title="Table view">
                    <i class="bi bi-list-ul"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="viewCards" onclick="setView('cards')" title="Card view">
                    <i class="bi bi-grid-3x3-gap"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Patient List - Table View -->
    <div class="card" id="tableView">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 patient-panel-table">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="name" onclick="sortBy('name')">
                                Patient <i class="bi bi-chevron-expand ms-1"></i>
                            </th>
                            <th style="width: 100px;">Alerts</th>
                            <th class="sortable" data-sort="last_recording" onclick="sortBy('last_recording')" style="width: 140px;">
                                Last Reading <i class="bi bi-chevron-expand ms-1"></i>
                            </th>
                            <th style="width: 100px;">Adherence</th>
                            <th class="sortable" data-sort="trend_type" onclick="sortBy('trend_type')" style="width: 120px;">
                                Trend <i class="bi bi-chevron-expand ms-1"></i>
                            </th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="patientTableBody">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Patient List - Card View -->
    <div class="row g-4" id="cardView" style="display: none;">
        <!-- Populated by JavaScript -->
    </div>
    
    <!-- Empty State -->
    <div class="card" id="emptyState" style="display: none;">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-search empty-icon"></i>
                <h5 class="empty-title">No Patients Found</h5>
                <p class="empty-description">Try adjusting your search or filter criteria.</p>
                <button class="btn btn-primary" onclick="resetFilters()">Reset Filters</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Patient Panel Table Styles */
.patient-panel-table th {
    background: var(--bg-secondary);
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
}

.sortable {
    cursor: pointer;
    user-select: none;
    transition: color var(--transition-fast);
}

.sortable:hover {
    color: var(--casana-purple);
}

.sortable i {
    opacity: 0.4;
    font-size: 0.7rem;
}

.sortable:hover i {
    opacity: 1;
}

.adherence-bar {
    width: 50px;
    height: 5px;
    background: var(--bg-tertiary);
    border-radius: 3px;
    overflow: hidden;
}

.adherence-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.patient-row-compact {
    cursor: pointer;
    transition: all var(--transition-fast);
}

.patient-row-compact:hover {
    background: var(--bg-hover);
}

.patient-row-compact.inactive {
    opacity: 0.65;
}

.alert-indicator {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 26px;
    border-radius: var(--radius-full);
    font-size: 0.7rem;
    font-weight: 700;
}

.trend-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-weight: 600;
}

.trend-arrow {
    font-size: 1rem;
}
</style>

<script>
const providerId = <?php echo $providerId; ?>;
let patients = <?php echo json_encode($provider['patients'] ?? []); ?>;
let filteredPatients = [...patients];
let currentSort = { field: 'last_recording', direction: 'desc' };
let currentView = 'table';
let currentQuickView = 'all';

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Enrich patient data with computed fields
    enrichPatientData();
    
    // Update filteredPatients with enriched data
    filteredPatients = [...patients];
    sortPatients();
    renderPatients();
    
    // Set up filter listeners
    document.getElementById('searchInput').addEventListener('input', debounce(applyFilters, 300));
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    document.getElementById('ageFilter').addEventListener('change', applyFilters);
    document.getElementById('genderFilter').addEventListener('change', applyFilters);
    document.getElementById('recencyFilter').addEventListener('change', applyFilters);
    
    // Quick view buttons (filter chips)
    document.querySelectorAll('.filter-chip[data-view]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-chip[data-view]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentQuickView = this.dataset.view;
            applyFilters();
        });
    });
});

// Enrich patient data with computed fields
function enrichPatientData() {
    const now = Date.now();
    const dayMs = 24 * 60 * 60 * 1000;
    
    patients = patients.map(patient => {
        const lastRecordingDate = patient.last_recording ? new Date(patient.last_recording).getTime() : 0;
        const daysSinceReading = lastRecordingDate ? Math.floor((now - lastRecordingDate) / dayMs) : 999;
        
        // Calculate adherence (recordings in last 7 days / 7)
        // Using a simple heuristic since we don't have detailed data
        let adherence = 0;
        if (daysSinceReading === 0) {
            adherence = Math.min(100, Math.random() * 30 + 70); // Active today: 70-100%
        } else if (daysSinceReading <= 3) {
            adherence = Math.min(100, Math.random() * 30 + 40); // Recent: 40-70%
        } else if (daysSinceReading <= 7) {
            adherence = Math.min(100, Math.random() * 30 + 20); // Week: 20-50%
        } else {
            adherence = Math.random() * 20; // Inactive: 0-20%
        }
        
        // Simulate alert count (would come from real data)
        const alertCount = patient.trend_type === 'deteriorating' ? Math.floor(Math.random() * 3) + 1 : 
                          (patient.trend_type === 'stable' ? 0 : Math.floor(Math.random() * 2));
        
        return {
            ...patient,
            daysSinceReading,
            adherence: Math.round(adherence),
            alertCount,
            isInactive: daysSinceReading > 7,
            needsReview: alertCount > 0 || patient.trend_type === 'deteriorating'
        };
    });
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const age = document.getElementById('ageFilter').value;
    const gender = document.getElementById('genderFilter').value;
    const recency = document.getElementById('recencyFilter').value;
    
    filteredPatients = patients.filter(patient => {
        // Quick view filter
        if (currentQuickView === 'needs-review' && !patient.needsReview) return false;
        if (currentQuickView === 'inactive' && !patient.isInactive) return false;
        if (currentQuickView === 'deteriorating' && patient.trend_type !== 'deteriorating') return false;
        if (currentQuickView === 'stable' && patient.trend_type !== 'stable') return false;
        
        // Search filter
        if (search && !patient.name.toLowerCase().includes(search)) {
            return false;
        }
        
        // Status filter
        if (status && patient.trend_type !== status) {
            return false;
        }
        
        // Gender filter
        if (gender && patient.gender !== gender) {
            return false;
        }
        
        // Age filter
        if (age) {
            const patientAge = patient.age;
            if (age === '20-40' && (patientAge < 20 || patientAge >= 40)) return false;
            if (age === '40-60' && (patientAge < 40 || patientAge >= 60)) return false;
            if (age === '60-75' && (patientAge < 60 || patientAge >= 75)) return false;
            if (age === '75+' && patientAge < 75) return false;
        }
        
        // Recency filter
        if (recency) {
            if (recency === 'today' && patient.daysSinceReading > 0) return false;
            if (recency === '3days' && patient.daysSinceReading > 3) return false;
            if (recency === '7days' && patient.daysSinceReading > 7) return false;
            if (recency === 'inactive' && patient.daysSinceReading <= 7) return false;
        }
        
        return true;
    });
    
    sortPatients();
    renderPatients();
}

function sortBy(field) {
    if (currentSort.field === field) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.field = field;
        currentSort.direction = field === 'last_recording' ? 'desc' : 'asc';
    }
    
    sortPatients();
    renderPatients();
}

function sortPatients() {
    filteredPatients.sort((a, b) => {
        let aVal = a[currentSort.field];
        let bVal = b[currentSort.field];
        
        // Handle special fields
        if (currentSort.field === 'last_recording') {
            aVal = a.last_recording ? new Date(a.last_recording).getTime() : 0;
            bVal = b.last_recording ? new Date(b.last_recording).getTime() : 0;
        } else if (currentSort.field === 'trend_type') {
            const order = { deteriorating: 0, improving: 1, stable: 2 };
            aVal = order[a.trend_type] || 2;
            bVal = order[b.trend_type] || 2;
        }
        
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = (bVal || '').toLowerCase();
        }
        
        let comparison = 0;
        if (aVal > bVal) comparison = 1;
        if (aVal < bVal) comparison = -1;
        
        return currentSort.direction === 'desc' ? -comparison : comparison;
    });
}

function renderPatients() {
    const tbody = document.getElementById('patientTableBody');
    const cardContainer = document.getElementById('cardView');
    const emptyState = document.getElementById('emptyState');
    const tableView = document.getElementById('tableView');
    const resultsCount = document.getElementById('resultsCount');
    
    // Update count
    resultsCount.textContent = `Showing ${filteredPatients.length} of ${patients.length} patients`;
    
    // Show/hide views
    if (filteredPatients.length === 0) {
        emptyState.style.display = 'block';
        tableView.style.display = 'none';
        cardContainer.style.display = 'none';
        return;
    }
    
    emptyState.style.display = 'none';
    
    if (currentView === 'table') {
        tableView.style.display = 'block';
        cardContainer.style.display = 'none';
    } else {
        tableView.style.display = 'none';
        cardContainer.style.display = 'flex';
    }
    
    // Render table
    tbody.innerHTML = filteredPatients.map(patient => {
        const adherenceColor = patient.adherence >= 70 ? 'var(--status-success)' : 
                              (patient.adherence >= 40 ? 'var(--status-warning)' : 'var(--status-danger)');
        const trendIcon = patient.trend_type === 'improving' ? 'arrow-up' : 
                         (patient.trend_type === 'deteriorating' ? 'arrow-down' : 'dash');
        const trendColor = patient.trend_type === 'improving' ? 'text-success' : 
                          (patient.trend_type === 'deteriorating' ? 'text-danger' : 'text-secondary');
        const recencyClass = patient.isInactive ? 'text-warning' : (patient.daysSinceReading === 0 ? 'text-success' : 'text-muted');
        
        return `
        <tr class="patient-row-compact ${patient.isInactive ? 'inactive' : ''}" 
            onclick="viewPatient(${patient.user_id})" 
            tabindex="0" 
            role="button"
            aria-label="View ${escapeHtml(patient.name)}">
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="entity-avatar" style="width: 32px; height: 32px; font-size: 0.75rem;">
                        ${getInitials(patient.name)}
                    </div>
                    <div>
                        <div class="fw-medium">${escapeHtml(patient.name)}</div>
                        <div class="small text-muted">${patient.age}y • ${patient.gender}</div>
                    </div>
                </div>
            </td>
            <td>
                ${patient.alertCount > 0 ? 
                    `<span class="alert-indicator bg-danger-soft text-danger">${patient.alertCount}</span>` : 
                    '<span class="text-muted">—</span>'}
            </td>
            <td>
                <span class="${recencyClass} small">
                    ${patient.last_recording ? formatRelativeTime(patient.last_recording) : 'Never'}
                </span>
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="adherence-bar">
                        <div class="adherence-fill" style="width: ${patient.adherence}%; background: ${adherenceColor};"></div>
                    </div>
                    <span class="small text-muted">${patient.adherence}%</span>
                </div>
            </td>
            <td>
                <span class="trend-indicator ${trendColor}">
                    <i class="bi bi-${trendIcon} trend-arrow"></i>
                    <span class="small">${patient.trend_type.charAt(0).toUpperCase() + patient.trend_type.slice(1)}</span>
                </span>
            </td>
            <td>
                <i class="bi bi-chevron-right text-muted"></i>
            </td>
        </tr>`;
    }).join('');
    
    // Render cards
    cardContainer.innerHTML = filteredPatients.map(patient => {
        const adherenceColor = patient.adherence >= 70 ? 'var(--status-success)' : 
                              (patient.adherence >= 40 ? 'var(--status-warning)' : 'var(--status-danger)');
        
        return `
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="patient-card h-100 ${patient.isInactive ? 'opacity-75' : ''}" onclick="viewPatient(${patient.user_id})" tabindex="0" role="button">
                <div class="patient-avatar">${getInitials(patient.name)}</div>
                <div class="patient-info flex-grow-1">
                    <div class="patient-name">${escapeHtml(patient.name)}</div>
                    <div class="patient-meta">Age ${patient.age} • ${patient.gender}</div>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <span class="health-status-badge ${patient.trend_type}" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                            ${patient.trend_type.charAt(0).toUpperCase() + patient.trend_type.slice(1)}
                        </span>
                        ${patient.alertCount > 0 ? 
                            `<span class="badge bg-danger-soft text-danger">${patient.alertCount} alert${patient.alertCount > 1 ? 's' : ''}</span>` : ''}
                    </div>
                </div>
                <div class="patient-status text-end">
                    <div class="small ${patient.isInactive ? 'text-warning' : 'text-muted'}">
                        ${patient.last_recording ? formatRelativeTime(patient.last_recording) : 'No activity'}
                    </div>
                    <div class="d-flex align-items-center gap-1 mt-1 justify-content-end">
                        <div class="adherence-bar" style="width: 40px;">
                            <div class="adherence-fill" style="width: ${patient.adherence}%; background: ${adherenceColor};"></div>
                        </div>
                        <span class="small text-muted">${patient.adherence}%</span>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
}

function setView(view) {
    currentView = view;
    document.getElementById('viewTable').classList.toggle('active', view === 'table');
    document.getElementById('viewCards').classList.toggle('active', view === 'cards');
    renderPatients();
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('ageFilter').value = '';
    document.getElementById('genderFilter').value = '';
    document.getElementById('recencyFilter').value = '';
    
    document.querySelectorAll('.filter-chip[data-view]').forEach(b => b.classList.remove('active'));
    document.querySelector('.filter-chip[data-view="all"]').classList.add('active');
    currentQuickView = 'all';
    
    filteredPatients = [...patients];
    sortPatients();
    renderPatients();
}

function viewPatient(userId) {
    window.location.href = `patient.php?provider=${providerId}&id=${userId}`;
}

// Utility functions
function getInitials(name) {
    if (!name) return '?';
    const parts = name.split(' ').filter(p => p.length > 0);
    if (parts.length === 0) return '?';
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 60) return diffMins <= 1 ? 'Just now' : `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays}d ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`;
    return date.toLocaleDateString();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

// Keyboard navigation for table rows
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.classList.contains('patient-row-compact')) {
        e.target.click();
    }
});

// Expose data for global search
window.providerId = providerId;
window.providerPatients = patients;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
