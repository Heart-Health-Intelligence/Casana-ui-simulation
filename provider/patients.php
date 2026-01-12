<?php
/**
 * Care Provider - Patient List
 * Searchable, filterable patient directory
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get provider ID
$providerId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch provider data
$provider = $api->getCareProvider($providerId);
$alerts = $api->getAlertRecordings(['per_page' => 5, 'days' => 7]);

// Page setup
$pageTitle = 'Patients';
$currentPage = 'patients';
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
                <a class="nav-link active" href="patients.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-people"></i>
                    Patients
                    <span class="badge bg-info-soft ms-auto"><?php echo $provider['total_patients'] ?? 0; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="alerts.php?id=<?php echo $providerId; ?>">
                    <i class="bi bi-exclamation-triangle"></i>
                    Alerts
                    <?php if ($alerts && $alerts['pagination']['total'] > 0): ?>
                    <span class="badge bg-danger-soft ms-auto"><?php echo $alerts['pagination']['total']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="analytics.php?id=<?php echo $providerId; ?>">
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
                <h1>Patients</h1>
                <p class="mb-0"><?php echo $provider['total_patients'] ?? 0; ?> patients in your care</p>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name..." style="padding-left: 38px;">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="stable">Stable</option>
                        <option value="improving">Improving</option>
                        <option value="deteriorating">Deteriorating</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Age Range</label>
                    <select class="form-select" id="ageFilter">
                        <option value="">All Ages</option>
                        <option value="20-40">20-40</option>
                        <option value="40-60">40-60</option>
                        <option value="60-75">60-75</option>
                        <option value="75+">75+</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Gender</label>
                    <select class="form-select" id="genderFilter">
                        <option value="">All</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary w-100" onclick="resetFilters()">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Results Count -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted" id="resultsCount">Showing all patients</span>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary btn-sm active" id="viewTable" onclick="setView('table')">
                <i class="bi bi-list-ul"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="viewCards" onclick="setView('cards')">
                <i class="bi bi-grid-3x3-gap"></i>
            </button>
        </div>
    </div>
    
    <!-- Patient List - Table View -->
    <div class="card" id="tableView">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="name" onclick="sortBy('name')">
                                Patient <i class="bi bi-chevron-expand ms-1"></i>
                            </th>
                            <th class="sortable" data-sort="age" onclick="sortBy('age')">
                                Age <i class="bi bi-chevron-expand ms-1"></i>
                            </th>
                            <th>Gender</th>
                            <th class="sortable" data-sort="recordings" onclick="sortBy('recordings')">
                                Recordings <i class="bi bi-chevron-expand ms-1"></i>
                            </th>
                            <th class="sortable" data-sort="last_recording" onclick="sortBy('last_recording')">
                                Last Activity <i class="bi bi-chevron-expand ms-1"></i>
                            </th>
                            <th>Status</th>
                            <th></th>
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

<script>
const providerId = <?php echo $providerId; ?>;
let patients = <?php echo json_encode($provider['patients'] ?? []); ?>;
let filteredPatients = [...patients];
let currentSort = { field: 'name', direction: 'asc' };
let currentView = 'table';

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    renderPatients();
    
    // Set up filter listeners
    document.getElementById('searchInput').addEventListener('input', debounce(applyFilters, 300));
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    document.getElementById('ageFilter').addEventListener('change', applyFilters);
    document.getElementById('genderFilter').addEventListener('change', applyFilters);
});

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const age = document.getElementById('ageFilter').value;
    const gender = document.getElementById('genderFilter').value;
    
    filteredPatients = patients.filter(patient => {
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
        
        return true;
    });
    
    renderPatients();
}

function sortBy(field) {
    if (currentSort.field === field) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.field = field;
        currentSort.direction = 'asc';
    }
    
    filteredPatients.sort((a, b) => {
        let aVal = a[field];
        let bVal = b[field];
        
        if (field === 'recordings') {
            aVal = a.total_recordings;
            bVal = b.total_recordings;
        }
        
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        
        let comparison = 0;
        if (aVal > bVal) comparison = 1;
        if (aVal < bVal) comparison = -1;
        
        return currentSort.direction === 'desc' ? -comparison : comparison;
    });
    
    renderPatients();
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
    tbody.innerHTML = filteredPatients.map(patient => `
        <tr class="table-clickable" onclick="viewPatient(${patient.user_id})">
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="entity-avatar" style="width: 36px; height: 36px; font-size: 0.8rem;">
                        ${getInitials(patient.name)}
                    </div>
                    <span class="fw-medium">${escapeHtml(patient.name)}</span>
                </div>
            </td>
            <td>${patient.age}</td>
            <td>${patient.gender}</td>
            <td>${patient.total_recordings.toLocaleString()}</td>
            <td>
                <span class="small text-muted">
                    ${patient.last_recording ? formatRelativeTime(patient.last_recording) : 'Never'}
                </span>
            </td>
            <td>
                <span class="health-status-badge ${patient.trend_type}">
                    ${patient.trend_type.charAt(0).toUpperCase() + patient.trend_type.slice(1)}
                </span>
            </td>
            <td>
                <i class="bi bi-chevron-right text-muted"></i>
            </td>
        </tr>
    `).join('');
    
    // Render cards
    cardContainer.innerHTML = filteredPatients.map(patient => `
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="patient-card h-100" onclick="viewPatient(${patient.user_id})">
                <div class="patient-avatar">${getInitials(patient.name)}</div>
                <div class="patient-info">
                    <div class="patient-name">${escapeHtml(patient.name)}</div>
                    <div class="patient-meta">
                        Age ${patient.age} · ${patient.gender} · ${patient.total_recordings.toLocaleString()} recordings
                    </div>
                </div>
                <div class="patient-status">
                    <span class="health-status-badge ${patient.trend_type}">
                        ${patient.trend_type.charAt(0).toUpperCase() + patient.trend_type.slice(1)}
                    </span>
                    <div class="small text-muted mt-1">
                        ${patient.last_recording ? formatRelativeTime(patient.last_recording) : 'No activity'}
                    </div>
                </div>
            </div>
        </div>
    `).join('');
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
    filteredPatients = [...patients];
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

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
