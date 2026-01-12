<?php
/**
 * Provider Sidebar
 * Shared navigation sidebar for provider pages
 * 
 * Required variables:
 * - $providerId: Provider ID for links
 * - $provider: Provider data array (name, practice_name, total_patients)
 * - $currentPage: Current page identifier for active state
 * - $alertCount: Number of active alerts (optional)
 */

$providerId = isset($providerId) ? $providerId : 1;
$currentPage = isset($currentPage) ? $currentPage : '';
$alertCount = isset($alertCount) ? $alertCount : 0;
?>
<aside class="sidebar hide-mobile" id="providerSidebar">
    <div class="sidebar-header">
        <?php $sanitizedName = sanitizeProviderName($provider['name'] ?? 'Provider'); ?>
        <div class="d-flex align-items-center gap-3">
            <div class="entity-avatar provider-avatar" style="width: 48px; height: 48px; font-size: 1.25rem;">
                <?php echo getInitials($provider['name'] ?? 'Dr'); ?>
            </div>
            <div class="sidebar-identity">
                <div class="fw-semibold text-truncate" title="<?php echo htmlspecialchars($sanitizedName); ?>"><?php echo htmlspecialchars($sanitizedName); ?></div>
                <div class="small text-muted text-truncate" title="<?php echo htmlspecialchars($provider['practice_name'] ?? ''); ?>"><?php echo htmlspecialchars($provider['practice_name'] ?? ''); ?></div>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav" aria-label="Provider navigation">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" 
                   href="index.php?id=<?php echo $providerId; ?>"
                   aria-current="<?php echo $currentPage === 'dashboard' ? 'page' : 'false'; ?>">
                    <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'patients' ? 'active' : ''; ?>" 
                   href="patients.php?id=<?php echo $providerId; ?>"
                   aria-current="<?php echo $currentPage === 'patients' ? 'page' : 'false'; ?>">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    <span>Patients</span>
                    <?php if (isset($provider['total_patients']) && $provider['total_patients'] > 0): ?>
                    <span class="badge bg-info-soft ms-auto"><?php echo $provider['total_patients']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'alerts' ? 'active' : ''; ?>" 
                   href="alerts.php?id=<?php echo $providerId; ?>"
                   aria-current="<?php echo $currentPage === 'alerts' ? 'page' : 'false'; ?>">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    <span>Alerts</span>
                    <?php if ($alertCount > 0): ?>
                    <span class="badge bg-danger-soft ms-auto"><?php echo $alertCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'analytics' ? 'active' : ''; ?>" 
                   href="analytics.php?id=<?php echo $providerId; ?>"
                   aria-current="<?php echo $currentPage === 'analytics' ? 'page' : 'false'; ?>">
                    <i class="bi bi-graph-up" aria-hidden="true"></i>
                    <span>Analytics</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="dropdown w-100">
            <button class="btn btn-outline-secondary w-100 dropdown-toggle d-flex align-items-center justify-content-center gap-2" 
                    type="button" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false">
                <i class="bi bi-person-circle"></i>
                <span class="sidebar-footer-text">Account</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end w-100">
                <li>
                    <span class="dropdown-item-text">
                        <strong><?php echo htmlspecialchars(sanitizeProviderName($provider['name'] ?? 'Provider')); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($provider['email'] ?? ''); ?></small>
                    </span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="../index.php">
                        <i class="bi bi-arrow-left-right me-2"></i>Switch Role
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
