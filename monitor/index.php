<?php
/**
 * Family Monitor Dashboard
 * At-a-glance health status for monitored loved ones
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get monitor ID
$monitorId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch monitor data
$monitor = $api->getMonitor($monitorId);

// Fetch metadata for relationship labels and avatars
$monitorMetadata = $api->getEntityMetadata('monitor', $monitorId);
$relationshipLabels = [];
$userAvatars = [];

if ($monitorMetadata && isset($monitorMetadata['metadata'])) {
    foreach ($monitorMetadata['metadata'] as $item) {
        if (strpos($item['key'], 'relationship_') === 0) {
            $userId = str_replace('relationship_', '', $item['key']);
            $relationshipLabels[$userId] = $item['data'];
        }
    }
}

// Count alerts for badge using centralized health status
$alertCount = 0;
$familyCount = 0;
if ($monitor && !empty($monitor['monitored_users'])) {
    $familyCount = count($monitor['monitored_users']);
    foreach ($monitor['monitored_users'] as $u) {
        $uData = $api->getMonitoredUserData($monitorId, $u['user_id']);
        if ($uData && isset($uData['data'])) {
            $status = getHealthStatus($uData['data']);
            if ($status === STATUS_WARNING || $status === STATUS_ALERT) {
                $alertCount++;
            }
        }
    }
}

// Get time of day for greeting
$hour = (int)date('H');
if ($hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour < 17) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

// Page setup
$pageTitle = 'Family Dashboard';
$currentPage = 'dashboard';
$appName = 'monitor';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width: 900px;">
    <!-- Header -->
    <div class="text-center mb-5 page-header-animate">
        <h1 class="mb-2 display-greeting"><?php echo $greeting; ?></h1>
        <p class="text-muted lead-subtitle">
            <?php if ($alertCount > 0): ?>
                <span class="alert-summary">
                    <i class="bi bi-exclamation-circle text-warning me-1"></i>
                    <?php echo $alertCount; ?> family member<?php echo $alertCount > 1 ? 's need' : ' needs'; ?> your attention
                </span>
            <?php else: ?>
                <?php echo $familyCount; ?> family member<?php echo $familyCount !== 1 ? 's' : ''; ?> &mdash; everyone is doing well
            <?php endif; ?>
        </p>
    </div>
    
    <!-- Filter Chips -->
    <?php if ($monitor && !empty($monitor['monitored_users']) && $familyCount > 1): ?>
    <div class="filter-chips mb-4 d-flex flex-wrap gap-2 justify-content-center">
        <button class="filter-chip active" data-filter="all" onclick="filterFamily('all', this)">
            <i class="bi bi-people"></i> All
        </button>
        <button class="filter-chip" data-filter="alert" onclick="filterFamily('alert', this)">
            <i class="bi bi-exclamation-triangle"></i> Needs Care
        </button>
        <button class="filter-chip" data-filter="warning" onclick="filterFamily('warning', this)">
            <i class="bi bi-exclamation-circle"></i> Needs Attention
        </button>
        <button class="filter-chip" data-filter="good" onclick="filterFamily('good', this)">
            <i class="bi bi-check-circle"></i> Doing Well
        </button>
    </div>
    <?php endif; ?>
    
    <!-- Monitored Users Grid -->
    <?php if ($monitor && !empty($monitor['monitored_users'])): ?>
    <div class="family-grid" id="familyGrid">
        <?php $cardIndex = 0; ?>
        <?php foreach ($monitor['monitored_users'] as $user): ?>
        <?php $cardIndex++; ?>
        <?php
        // Get user's latest data
        $userData = $api->getMonitoredUserData($monitorId, $user['user_id']);
        
        // Determine health status using centralized function
        $status = 'good';
        $statusMessage = 'is doing well';
        $statusClass = 'status-good';
        $statusIcon = 'check-circle-fill';
        
        if ($userData && isset($userData['data'])) {
            $latest = $userData['data'];
            $status = getHealthStatus($latest);
            
            if ($status === STATUS_WARNING) {
                $statusMessage = 'needs attention';
                $statusClass = 'status-warning';
                $statusIcon = 'exclamation-circle-fill';
            } else if ($status === STATUS_ALERT) {
                $statusMessage = 'may need care';
                $statusClass = 'status-alert';
                $statusIcon = 'exclamation-triangle-fill';
            }
        }
        
        $firstName = explode(' ', $user['user_name'])[0];
        
        // Get relationship label for this user
        $relationshipData = isset($relationshipLabels[$user['user_id']]) ? $relationshipLabels[$user['user_id']] : null;
        $relationshipLabel = $relationshipData ? ($relationshipData['label'] ?? null) : null;
        
        // Get avatar URL if available
        $avatarUrl = null;
        $userMetadata = $api->getMetadata('user', $user['user_id'], 'avatar');
        if ($userMetadata && isset($userMetadata['data']['url'])) {
            $avatarUrl = $userMetadata['data']['url'];
        }
        ?>
        <article class="family-member-card <?php echo $statusClass; ?> animate-card animate-delay-<?php echo min($cardIndex, 5); ?>" 
                 data-status="<?php echo $status; ?>"
                 data-href="user.php?monitor=<?php echo $monitorId; ?>&id=<?php echo $user['user_id']; ?>"
                 onclick="navigateToCard(this)"
                 onkeydown="handleCardKeydown(event, this)"
                 role="button"
                 tabindex="0"
                 aria-label="View details for <?php echo htmlspecialchars($user['user_name']); ?>. Status: <?php echo $statusMessage; ?>">
            
            <!-- Status indicator bar -->
            <div class="status-bar"></div>
            
            <!-- Card Header -->
            <header class="member-header">
                <?php if ($avatarUrl): ?>
                <div class="member-avatar avatar-<?php echo $status; ?>" style="background-image: url('<?php echo htmlspecialchars($avatarUrl); ?>');">
                </div>
                <?php else: ?>
                <div class="member-avatar avatar-<?php echo $status; ?>">
                    <?php echo getInitials($user['user_name']); ?>
                </div>
                <?php endif; ?>
                
                <div class="member-info">
                    <?php if ($relationshipLabel): ?>
                    <span class="relationship-tag"><?php echo htmlspecialchars($relationshipLabel); ?></span>
                    <?php endif; ?>
                    <h3 class="member-name"><?php echo htmlspecialchars($user['user_name']); ?></h3>
                    <div class="status-badge status-<?php echo $status; ?>">
                        <i class="bi bi-<?php echo $statusIcon; ?>"></i>
                        <span><?php echo ucfirst($statusMessage); ?></span>
                    </div>
                </div>
            </header>
            
            <?php if ($userData && isset($userData['data'])): ?>
            <?php $latest = $userData['data']; ?>
            
            <!-- Vitals Grid -->
            <div class="vitals-summary">
                <?php if (in_array('blood_pressure', $user['shared_data_types'])): ?>
                <div class="vital-item <?php echo (isset($latest['htn']) && $latest['htn']) ? 'vital-elevated' : ''; ?>">
                    <span class="vital-label">Blood Pressure</span>
                    <span class="vital-value"><?php echo $latest['bp_systolic'] ?? '--'; ?>/<small><?php echo $latest['bp_diastolic'] ?? '--'; ?></small></span>
                </div>
                <?php endif; ?>
                
                <?php if (in_array('heart_rate', $user['shared_data_types'])): ?>
                <div class="vital-item">
                    <span class="vital-label">Heart Rate</span>
                    <span class="vital-value"><?php echo $latest['heart_rate'] ?? '--'; ?> <small>bpm</small></span>
                </div>
                <?php endif; ?>
                
                <?php if (in_array('blood_oxygenation', $user['shared_data_types'])): ?>
                <div class="vital-item <?php echo (isset($latest['blood_oxygenation']) && $latest['blood_oxygenation'] < SPO2_NORMAL_MIN) ? 'vital-warning' : ''; ?>">
                    <span class="vital-label">Oxygen</span>
                    <span class="vital-value"><?php echo isset($latest['blood_oxygenation']) ? round($latest['blood_oxygenation'], 1) : '--'; ?><small>%</small></span>
                </div>
                <?php endif; ?>
                
                <?php if (in_array('agility_score', $user['shared_data_types'])): ?>
                <div class="vital-item">
                    <span class="vital-label">Mobility</span>
                    <span class="vital-value"><?php echo isset($latest['agility_score']) ? round($latest['agility_score']) : '--'; ?><small>/100</small></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Card Footer -->
            <footer class="member-footer">
                <span class="last-reading">
                    <i class="bi bi-clock"></i>
                    <?php echo formatRelativeTime($latest['recorded_at']); ?>
                </span>
                <span class="view-link">
                    View Details <i class="bi bi-arrow-right"></i>
                </span>
            </footer>
            <?php else: ?>
            <div class="no-data-state">
                <i class="bi bi-hourglass-split"></i>
                <p>Waiting for first reading</p>
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>
    
    <?php else: ?>
    <div class="empty-dashboard">
        <div class="empty-illustration">
            <div class="empty-icon-wrapper">
                <i class="bi bi-people"></i>
            </div>
        </div>
        <h2>Your Family Dashboard is Ready</h2>
        <p>Once family members are connected to your account, their health status will appear here so you can keep an eye on the people who matter most.</p>
        <a href="../index.php" class="btn btn-primary btn-lg">
            <i class="bi bi-house me-2"></i>Back to Home
        </a>
    </div>
    <?php endif; ?>
    
    <!-- Info Card - Collapsible -->
    <details class="info-panel mt-5" open>
        <summary class="info-panel-header">
            <span class="info-panel-title">
                <i class="bi bi-info-circle"></i>
                Understanding Health Status
            </span>
            <i class="bi bi-chevron-down toggle-icon"></i>
        </summary>
        <div class="info-panel-content">
            <div class="status-legend">
                <div class="legend-item">
                    <div class="legend-indicator good">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="legend-text">
                        <strong>Doing Well</strong>
                        <span>All vitals within healthy ranges</span>
                    </div>
                </div>
                <div class="legend-item">
                    <div class="legend-indicator warning">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </div>
                    <div class="legend-text">
                        <strong>Needs Attention</strong>
                        <span>Some readings are slightly elevated</span>
                    </div>
                </div>
                <div class="legend-item">
                    <div class="legend-indicator alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div class="legend-text">
                        <strong>May Need Care</strong>
                        <span>Consider contacting their provider</span>
                    </div>
                </div>
            </div>
        </div>
    </details>
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-nav" aria-label="Mobile navigation">
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link active" href="index.php?id=<?php echo $monitorId; ?>" aria-current="page">
                <span class="nav-icon-wrapper">
                    <i class="bi bi-house-fill"></i>
                    <?php if ($alertCount > 0): ?>
                    <span class="nav-badge" aria-label="<?php echo $alertCount; ?> alerts"><?php echo $alertCount; ?></span>
                    <?php endif; ?>
                </span>
                <span>Family</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../index.php">
                <i class="bi bi-grid"></i>
                <span>Switch App</span>
            </a>
        </li>
    </ul>
</nav>

<style>
/* ==========================================================================
   Monitor Dashboard - Premium Styles
   ========================================================================== */

/* Greeting Header */
.display-greeting {
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    background: linear-gradient(135deg, var(--text-primary) 0%, var(--casana-purple) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.lead-subtitle {
    font-size: 1.1rem;
    color: var(--text-secondary);
}

.alert-summary {
    color: var(--status-warning);
    font-weight: 500;
}

/* Family Grid - Responsive Layout */
.family-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

@media (min-width: 700px) {
    .family-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
}

@media (max-width: 699px) {
    .family-member-card {
        padding: 1.25rem;
    }
    
    .member-avatar {
        width: 56px;
        height: 56px;
        min-width: 56px;
        font-size: 1.25rem;
    }
    
    .member-name {
        font-size: 1.1rem;
    }
    
    .display-greeting {
        font-size: 1.5rem;
    }
    
    .lead-subtitle {
        font-size: 0.95rem;
    }
    
    .page-header-animate {
        text-align: left;
        padding-left: 0.5rem;
    }
}

/* Family Member Card */
.family-member-card {
    position: relative;
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    padding: 1.5rem;
    border: 1px solid var(--border-color);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.family-member-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
    border-color: transparent;
}

.family-member-card:focus {
    outline: 2px solid var(--casana-purple);
    outline-offset: 2px;
}

/* Status Bar */
.status-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--status-success);
    transition: background 0.3s ease;
}

.family-member-card.status-warning .status-bar {
    background: var(--status-warning);
}

.family-member-card.status-alert .status-bar {
    background: var(--status-danger);
    animation: pulse-bar 2s ease-in-out infinite;
}

@keyframes pulse-bar {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Card Header */
.member-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.member-avatar {
    width: 64px;
    height: 64px;
    min-width: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 12px -2px rgba(16, 185, 129, 0.4);
    background-size: cover;
    background-position: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.family-member-card:hover .member-avatar {
    transform: scale(1.05);
}

.member-avatar.avatar-warning {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    box-shadow: 0 4px 12px -2px rgba(245, 158, 11, 0.4);
}

.member-avatar.avatar-alert {
    background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
    box-shadow: 0 4px 12px -2px rgba(239, 68, 68, 0.4);
}

.member-info {
    flex: 1;
    min-width: 0;
}

.relationship-tag {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 0.25rem;
}

.member-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: var(--text-primary);
    line-height: 1.2;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 100px;
    font-size: 0.8rem;
    font-weight: 600;
    white-space: nowrap;
}

.status-badge.status-good {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}

.status-badge.status-warning {
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
}

.status-badge.status-alert {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
    animation: pulse-badge 2s ease-in-out infinite;
}

[data-theme="dark"] .status-badge.status-good {
    background: rgba(52, 211, 153, 0.15);
    color: #34d399;
}

[data-theme="dark"] .status-badge.status-warning {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
}

[data-theme="dark"] .status-badge.status-alert {
    background: rgba(248, 113, 113, 0.15);
    color: #f87171;
}

@keyframes pulse-badge {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.85; transform: scale(1.02); }
}

/* Vitals Summary Grid */
.vitals-summary {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.vital-item {
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    padding: 0.75rem;
    transition: background 0.2s ease;
}

.vital-item .vital-label {
    display: block;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 0.25rem;
}

.vital-item .vital-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}

.vital-item .vital-value small {
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--text-muted);
    margin-left: 1px;
}

.vital-item.vital-elevated .vital-value {
    color: var(--status-danger);
}

.vital-item.vital-warning .vital-value {
    color: var(--status-warning);
}

/* Card Footer */
.member-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    margin-top: 0.5rem;
}

.last-reading {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8rem;
    color: var(--text-muted);
}

.last-reading i {
    font-size: 0.9rem;
}

.view-link {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--casana-purple);
    transition: gap 0.2s ease;
}

.family-member-card:hover .view-link {
    gap: 0.5rem;
}

/* No Data State */
.no-data-state {
    text-align: center;
    padding: 1.5rem 0;
    color: var(--text-muted);
}

.no-data-state i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.no-data-state p {
    margin: 0;
    font-size: 0.9rem;
}

/* Empty Dashboard */
.empty-dashboard {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-color);
}

.empty-icon-wrapper {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--casana-purple) 0%, var(--casana-purple-light) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.9;
}

.empty-icon-wrapper i {
    font-size: 3rem;
    color: white;
}

.empty-dashboard h2 {
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
}

.empty-dashboard p {
    max-width: 400px;
    margin: 0 auto 1.5rem;
    color: var(--text-secondary);
}

/* Info Panel */
.info-panel {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.info-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    cursor: pointer;
    transition: background 0.2s ease;
    list-style: none;
}

.info-panel-header::-webkit-details-marker {
    display: none;
}

.info-panel-header:hover {
    background: var(--bg-hover);
}

.info-panel-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.info-panel-title i {
    color: var(--casana-purple);
}

.toggle-icon {
    color: var(--text-muted);
    transition: transform 0.3s ease;
}

.info-panel[open] .toggle-icon {
    transform: rotate(180deg);
}

.info-panel-content {
    padding: 1.25rem;
    border-top: 1px solid var(--border-color);
}

/* Status Legend */
.status-legend {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

@media (max-width: 900px) {
    .status-legend {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .legend-item {
        flex-direction: row;
        align-items: center;
    }
}

.legend-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.legend-indicator {
    width: 32px;
    height: 32px;
    min-width: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.legend-indicator.good {
    background: rgba(16, 185, 129, 0.12);
    color: var(--status-success);
}

.legend-indicator.warning {
    background: rgba(245, 158, 11, 0.12);
    color: var(--status-warning);
}

.legend-indicator.alert {
    background: rgba(239, 68, 68, 0.12);
    color: var(--status-danger);
}

.legend-text {
    display: flex;
    flex-direction: column;
}

.legend-text strong {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
}

.legend-text span {
    font-size: 0.8rem;
    color: var(--text-muted);
}

/* Animation Classes */
.animate-card {
    opacity: 0;
    transform: translateY(20px);
    animation: cardEntrance 0.5s ease-out forwards;
}

.animate-delay-1 { animation-delay: 0.1s; }
.animate-delay-2 { animation-delay: 0.2s; }
.animate-delay-3 { animation-delay: 0.3s; }
.animate-delay-4 { animation-delay: 0.4s; }
.animate-delay-5 { animation-delay: 0.5s; }

@keyframes cardEntrance {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.page-header-animate {
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Dark Mode Enhancements */
[data-theme="dark"] .family-member-card {
    background: linear-gradient(180deg, var(--bg-card) 0%, rgba(15, 23, 42, 0.95) 100%);
    border-color: rgba(148, 163, 184, 0.1);
}

[data-theme="dark"] .family-member-card:hover {
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.4);
    border-color: rgba(99, 102, 241, 0.3);
}

[data-theme="dark"] .vital-item {
    background: rgba(255, 255, 255, 0.03);
}

[data-theme="dark"] .info-panel {
    background: linear-gradient(180deg, var(--bg-card) 0%, rgba(15, 23, 42, 0.95) 100%);
}

[data-theme="dark"] .display-greeting {
    background: linear-gradient(135deg, #f8fafc 0%, #818cf8 100%);
    -webkit-background-clip: text;
    background-clip: text;
}
</style>

<script>
// Keyboard activation for cards
function handleCardKeydown(event, card) {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        navigateToCard(card);
    }
}

function navigateToCard(card) {
    var href = card.getAttribute('data-href');
    if (href) {
        window.location.href = href;
    }
}

// Filter family members by status
function filterFamily(status, button) {
    // Update active button
    document.querySelectorAll('.filter-chip').forEach(function(chip) {
        chip.classList.remove('active');
    });
    button.classList.add('active');
    
    // Filter cards
    var cards = document.querySelectorAll('.family-member-card');
    cards.forEach(function(card) {
        var cardStatus = card.getAttribute('data-status');
        if (status === 'all') {
            card.style.display = '';
        } else if (cardStatus === status) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update URL without reload (for bookmarking)
    var url = new URL(window.location.href);
    if (status === 'all') {
        url.searchParams.delete('filter');
    } else {
        url.searchParams.set('filter', status);
    }
    history.replaceState(null, '', url);
}

// Apply filter from URL on page load
document.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    var filter = urlParams.get('filter');
    if (filter) {
        var button = document.querySelector('.filter-chip[data-filter="' + filter + '"]');
        if (button) {
            filterFamily(filter, button);
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
