<?php
/**
 * Casana Superuser Mode
 * Role selection and entity impersonation dashboard
 */

require_once __DIR__ . '/includes/api-helper.php';

// Fetch overview stats
$overview = $api->getOverview();

// Page setup
$pageTitle = 'Superuser Mode';
$currentPage = 'home';
$appName = 'superuser';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1>Casana Test Portal</h1>
                <p class="mb-0">Select a role to impersonate and explore the healthcare monitoring interfaces.</p>
            </div>
            <div class="col-auto">
                <span class="badge bg-info-soft">Development Mode</span>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <?php if ($overview): ?>
    <div class="row g-4 mb-5">
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="stat-value"><?php echo number_format($overview['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="stat-value"><?php echo number_format($overview['total_recordings']); ?></div>
                <div class="stat-label">Recordings</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="stat-value"><?php echo number_format($overview['total_monitors']); ?></div>
                <div class="stat-label">Family Monitors</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="stat-value"><?php echo number_format($overview['total_care_providers']); ?></div>
                <div class="stat-label">Care Providers</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Role Selection Tabs -->
    <div class="card">
        <div class="card-header p-0">
            <ul class="nav nav-tabs nav-fill" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3" id="provider-tab" data-bs-toggle="tab" data-bs-target="#provider-panel" type="button" role="tab" onclick="loadRole('provider')">
                        <i class="bi bi-hospital me-2"></i>
                        <span class="d-none d-sm-inline">Care Providers</span>
                        <span class="d-sm-none">Providers</span>
                        <span class="badge bg-secondary ms-2"><?php echo $overview ? $overview['total_care_providers'] : 0; ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3" id="monitor-tab" data-bs-toggle="tab" data-bs-target="#monitor-panel" type="button" role="tab" onclick="loadRole('monitor')">
                        <i class="bi bi-people me-2"></i>
                        <span class="d-none d-sm-inline">Family Monitors</span>
                        <span class="d-sm-none">Monitors</span>
                        <span class="badge bg-secondary ms-2"><?php echo $overview ? $overview['total_monitors'] : 0; ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3" id="user-tab" data-bs-toggle="tab" data-bs-target="#user-panel" type="button" role="tab" onclick="loadRole('user')">
                        <i class="bi bi-person-heart me-2"></i>
                        <span class="d-none d-sm-inline">Patients / Users</span>
                        <span class="d-sm-none">Users</span>
                        <span class="badge bg-secondary ms-2"><?php echo $overview ? $overview['total_users'] : 0; ?></span>
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <!-- Search Box -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" id="entitySearch" placeholder="Search by name or ID..." oninput="filterEntities()">
                    </div>
                </div>
                <div class="col-md-6 col-lg-8 d-flex align-items-center">
                    <span class="text-muted" id="resultCount"></span>
                </div>
            </div>
            
            <!-- Entity Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="entityTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;"></th>
                            <th>Name</th>
                            <th class="d-none d-md-table-cell">Details</th>
                            <th class="text-end" style="width: 80px;">ID</th>
                            <th style="width: 120px;"></th>
                        </tr>
                    </thead>
                    <tbody id="entityList">
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="mt-2 text-muted">Loading...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- API Info (Collapsible) -->
    <div class="card mt-4">
        <div class="card-header" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#apiInfo">
            <i class="bi bi-info-circle me-2"></i>
            API Information
            <i class="bi bi-chevron-down float-end"></i>
        </div>
        <div class="collapse" id="apiInfo">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Base URL</h5>
                        <code>https://casana.mcchord.net/api</code>
                        
                        <h5 class="mt-3">API Key</h5>
                        <code>dev-key-12345</code>
                    </div>
                    <div class="col-md-6">
                        <h5>Documentation</h5>
                        <a href="https://casana.mcchord.net/docs" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-book me-2"></i>
                            View API Docs
                        </a>
                        
                        <h5 class="mt-3">Test Data</h5>
                        <p class="text-secondary mb-0">
                            <?php echo $overview ? number_format($overview['recordings_today']) : 0; ?> recordings today from 
                            <?php echo $overview ? number_format($overview['active_users_today']) : 0; ?> active users
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentRole = 'provider';
let entities = [];
let loadedRoles = {};

// Load providers on page load
document.addEventListener('DOMContentLoaded', () => {
    loadRole('provider');
});

async function loadRole(role) {
    currentRole = role;
    const entityList = document.getElementById('entityList');
    
    // Check if already loaded
    if (loadedRoles[role]) {
        entities = loadedRoles[role];
        renderEntities(entities);
        return;
    }
    
    // Show loading
    entityList.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Loading...</div>
            </td>
        </tr>
    `;
    document.getElementById('resultCount').textContent = '';
    
    try {
        let data;
        if (role === 'provider') {
            data = await CasanaAPI.getCareProviders({ per_page: 100 });
            entities = data.providers || [];
        } else if (role === 'monitor') {
            data = await CasanaAPI.getMonitors({ per_page: 100 });
            entities = data.monitors || [];
        } else {
            data = await CasanaAPI.getUsers({ per_page: 100 });
            entities = data.users || [];
        }
        
        // Cache the results
        loadedRoles[role] = entities;
        
        renderEntities(entities);
    } catch (error) {
        entityList.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-5">
                    <div class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load data</div>
                    <button class="btn btn-outline-primary btn-sm mt-2" onclick="loadRole('${role}')">
                        <i class="bi bi-arrow-clockwise me-1"></i> Retry
                    </button>
                </td>
            </tr>
        `;
        console.error(error);
    }
}

function renderEntities(list) {
    const entityList = document.getElementById('entityList');
    const resultCount = document.getElementById('resultCount');
    
    if (list.length === 0) {
        entityList.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-search me-2"></i>No results found
                </td>
            </tr>
        `;
        resultCount.textContent = '0 results';
        return;
    }
    
    resultCount.textContent = `${list.length} result${list.length !== 1 ? 's' : ''}`;
    
    let html = '';
    list.forEach(entity => {
        const name = entity.name || entity.practice_name || 'Unknown';
        const initials = getInitials(name);
        let details = '';
        let url = '';
        
        if (currentRole === 'provider') {
            details = `<span class="text-muted">${escapeHtml(entity.practice_name || '')}</span> · ${entity.total_patients || 0} patients`;
            url = `/provider/index.php?id=${entity.id}`;
        } else if (currentRole === 'monitor') {
            details = `Monitoring <strong>${entity.total_monitored || 0}</strong> user${entity.total_monitored !== 1 ? 's' : ''}`;
            url = `/monitor/index.php?id=${entity.id}`;
        } else {
            const genderIcon = entity.gender === 'Male' ? 'bi-gender-male' : entity.gender === 'Female' ? 'bi-gender-female' : 'bi-person';
            const memberSince = entity.created_at ? new Date(entity.created_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) : 'N/A';
            details = `<i class="bi ${genderIcon} me-1"></i> ${entity.age || 'N/A'} years · Member since ${memberSince}`;
            url = `/user/index.php?id=${entity.id}`;
        }
        
        html += `
            <tr class="entity-row" data-id="${entity.id}">
                <td>
                    <div class="entity-avatar">${initials}</div>
                </td>
                <td>
                    <div class="fw-medium">${escapeHtml(name)}</div>
                </td>
                <td class="d-none d-md-table-cell">
                    <small>${details}</small>
                </td>
                <td class="text-end text-muted">
                    <small>#${entity.id}</small>
                </td>
                <td>
                    <a href="${url}" class="btn btn-primary btn-sm w-100" onclick="saveSelection(${entity.id})">
                        <i class="bi bi-box-arrow-up-right me-1"></i>
                        Launch
                    </a>
                </td>
            </tr>
        `;
    });
    
    entityList.innerHTML = html;
}

function filterEntities() {
    const searchTerm = document.getElementById('entitySearch').value.toLowerCase().trim();
    
    if (!searchTerm) {
        renderEntities(entities);
        return;
    }
    
    const filtered = entities.filter(entity => {
        const name = (entity.name || entity.practice_name || '').toLowerCase();
        const id = String(entity.id);
        const practice = (entity.practice_name || '').toLowerCase();
        return name.includes(searchTerm) || id.includes(searchTerm) || practice.includes(searchTerm);
    });
    
    renderEntities(filtered);
}

function saveSelection(entityId) {
    const entity = entities.find(e => e.id === entityId);
    if (entity) {
        sessionStorage.setItem('casana_role', currentRole);
        sessionStorage.setItem('casana_entity_id', entityId);
        sessionStorage.setItem('casana_entity', JSON.stringify(entity));
    }
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
</script>

<style>
/* Tab Navigation */
.nav-tabs {
    border-bottom: 1px solid var(--casana-border);
    background: var(--casana-card-bg);
}

.nav-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    font-weight: 500;
    transition: all 0.2s ease;
    padding: 1rem 1.5rem;
}

/* Dark mode tab colors */
[data-theme="dark"] .nav-tabs .nav-link {
    color: rgba(255, 255, 255, 0.65);
}

/* Light mode tab colors */
[data-theme="light"] .nav-tabs .nav-link,
.nav-tabs .nav-link {
    color: #555 !important;
}

.nav-tabs .nav-link:hover {
    border-color: transparent;
    color: var(--casana-purple);
    background: rgba(106, 110, 255, 0.08);
}

.nav-tabs .nav-link.active {
    border-bottom-color: var(--casana-purple);
    color: var(--casana-purple) !important;
    background: transparent;
}

.nav-tabs .nav-link i {
    opacity: 0.8;
}

.nav-tabs .nav-link.active i {
    opacity: 1;
}

.nav-tabs .nav-link .badge {
    font-size: 0.7rem;
    font-weight: 600;
}

/* Dark mode badges */
[data-theme="dark"] .nav-tabs .nav-link .badge {
    background: rgba(255, 255, 255, 0.15) !important;
    color: rgba(255, 255, 255, 0.8) !important;
}

/* Light mode badges */
[data-theme="light"] .nav-tabs .nav-link .badge,
.nav-tabs .nav-link .badge {
    background: rgba(0, 0, 0, 0.08) !important;
    color: #666 !important;
}

.nav-tabs .nav-link.active .badge {
    background: var(--casana-purple) !important;
    color: white !important;
}

/* Entity Table */
.entity-row {
    transition: background-color 0.15s ease;
}

.entity-row:hover {
    background-color: var(--bg-hover);
}

/* Dark mode: ensure text remains readable on hover */
[data-theme="dark"] .entity-row:hover td,
[data-theme="dark"] .entity-row:hover .fw-medium,
[data-theme="dark"] .entity-row:hover small {
    color: var(--text-primary);
}

[data-theme="dark"] .entity-row:hover .text-muted {
    color: var(--text-secondary) !important;
}

.entity-row .btn {
    opacity: 0.85;
    transition: all 0.15s ease;
}

.entity-row:hover .btn {
    opacity: 1;
    transform: translateX(2px);
}

.entity-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(106, 110, 255, 0.15), rgba(106, 110, 255, 0.25));
    color: var(--casana-purple);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    letter-spacing: -0.5px;
}

#entityTable {
    margin-bottom: 0;
}

#entityTable thead th {
    border-top: none;
    border-bottom: 1px solid var(--casana-border);
    font-weight: 600;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--bs-body-color);
    opacity: 0.5;
    padding: 0.75rem 1rem;
}

#entityTable tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--casana-border);
}

#entityTable tbody tr:last-child td {
    border-bottom: none;
}

/* Search box enhancement */
.search-box {
    position: relative;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.5;
}

.search-box input {
    padding-left: 2.5rem;
}

/* Stats cards on this page */
.stat-card {
    text-align: center;
    padding: 1.5rem 1rem;
}

.stat-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--casana-purple);
    line-height: 1.2;
}

.stat-card .stat-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    opacity: 0.7;
    margin-top: 0.5rem;
}

/* Result count */
#resultCount {
    font-size: 0.875rem;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
