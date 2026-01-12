<?php
/**
 * Family Monitor - User Detail View
 * Detailed health information for a monitored family member
 */

require_once __DIR__ . '/../includes/api-helper.php';

// Get IDs
$monitorId = isset($_GET['monitor']) ? intval($_GET['monitor']) : 1;
$userId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch data
$monitor = $api->getMonitor($monitorId);
$userData = $api->getMonitoredUserData($monitorId, $userId);
$trends = $api->getUserTrends($userId, ['days' => 14]);

// Find the monitored user info
$monitoredUser = null;
$userNotFound = true;
if ($monitor && isset($monitor['monitored_users'])) {
    foreach ($monitor['monitored_users'] as $u) {
        if ($u['user_id'] == $userId) {
            $monitoredUser = $u;
            $userNotFound = false;
            break;
        }
    }
}

// Fetch relationship label
$relationshipData = $api->getMetadata('monitor', $monitorId, 'relationship_' . $userId);
$relationshipLabel = null;
if ($relationshipData && isset($relationshipData['data']['label'])) {
    $relationshipLabel = $relationshipData['data']['label'];
}

// Fetch avatar
$avatarData = $api->getMetadata('user', $userId, 'avatar');
$avatarUrl = null;
if ($avatarData && isset($avatarData['data']['url'])) {
    $avatarUrl = $avatarData['data']['url'];
}

// Fetch user details for contact info
$userDetails = $api->getUser($userId);
$cellPhone = $userDetails ? ($userDetails['cell_phone'] ?? null) : null;

// Fetch notes for this user
$notes = $api->getNotes(['user_id' => $userId, 'per_page' => 5]);

// Fetch upcoming reminders
$followups = $api->getFollowups(['user_id' => $userId, 'status' => 'pending', 'per_page' => 5]);

// Calculate trends for vital indicators
$bpTrend = calculateTrend($trends, 'avg_bp_systolic');
$hrTrend = calculateTrend($trends, 'avg_heart_rate');
$o2Trend = calculateTrend($trends, 'avg_blood_oxygenation');
$agilityTrend = calculateTrend($trends, 'avg_agility_score');

// Page setup
$userName = $monitoredUser ? $monitoredUser['user_name'] : 'Family Member';
$pageTitle = $monitoredUser ? $userName : 'Member Not Found';
$currentPage = 'user';
$appName = 'monitor';

require_once __DIR__ . '/../includes/header.php';

// Shared data types
$sharedTypes = $monitoredUser ? $monitoredUser['shared_data_types'] : [];
$firstName = $monitoredUser ? explode(' ', $userName)[0] : 'this person';
?>

<div class="container py-4" style="max-width: 900px;">
    <!-- Back Button -->
    <a href="index.php?id=<?php echo $monitorId; ?>" class="back-link mb-4">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Family</span>
    </a>
    
    <?php if ($userNotFound): ?>
    <!-- User Not Found Error State -->
    <div class="error-card">
        <div class="error-icon-wrapper">
            <i class="bi bi-person-x"></i>
        </div>
        <h2>Family Member Not Found</h2>
        <p>We couldn't find this person in your family list. They may have been removed or the link may be outdated.</p>
        <a href="index.php?id=<?php echo $monitorId; ?>" class="btn btn-primary btn-lg">
            <i class="bi bi-house me-2"></i>Back to Family Dashboard
        </a>
    </div>
    <?php else: ?>
    
    <?php 
    // Calculate status early
    $status = 'good';
    $statusText = 'is doing well';
    $statusIcon = 'check-circle-fill';
    
    if ($userData && isset($userData['data'])) {
        $latest = $userData['data'];
        if (isset($latest['htn']) && $latest['htn']) {
            $status = 'warning';
            $statusText = 'needs attention';
            $statusIcon = 'exclamation-circle-fill';
        }
        if (isset($latest['blood_oxygenation']) && $latest['blood_oxygenation'] < 92) {
            $status = 'alert';
            $statusText = 'may need care';
            $statusIcon = 'exclamation-triangle-fill';
        }
    }
    ?>
    
    <!-- User Header Card -->
    <header class="profile-header animate-in animate-delay-1">
        <!-- Status Ring -->
        <div class="profile-avatar-container">
            <div class="avatar-ring avatar-ring-<?php echo $status; ?>">
                <?php if ($avatarUrl): ?>
                <div class="profile-avatar" style="background-image: url('<?php echo htmlspecialchars($avatarUrl); ?>');">
                </div>
                <?php else: ?>
                <div class="profile-avatar avatar-<?php echo $status; ?>">
                    <?php echo getInitials($userName); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-info">
            <?php if ($relationshipLabel): ?>
            <span class="relationship-label"><?php echo htmlspecialchars($relationshipLabel); ?></span>
            <button class="btn-edit-relationship" onclick="editRelationship()" title="Edit relationship label">
                <i class="bi bi-pencil"></i>
            </button>
            <?php else: ?>
            <button class="btn-add-relationship" onclick="editRelationship()" title="Add a relationship label (e.g., Mom, Dad, Grandma)">
                <i class="bi bi-plus-circle me-1"></i>Add relationship
            </button>
            <?php endif; ?>
            
            <h1 class="profile-name"><?php echo htmlspecialchars($userName); ?></h1>
            <p class="profile-meta">Age <?php echo $monitoredUser ? $monitoredUser['user_age'] : 'N/A'; ?></p>
            
            <!-- Contact Actions -->
            <?php if ($cellPhone): ?>
            <div class="contact-actions">
                <a href="tel:<?php echo htmlspecialchars($cellPhone); ?>" class="btn-contact-primary">
                    <i class="bi bi-telephone-fill"></i>
                    <span>Call <?php echo htmlspecialchars($firstName); ?></span>
                </a>
                <a href="sms:<?php echo htmlspecialchars($cellPhone); ?>" class="btn-contact-secondary">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span>Text</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Status Badge -->
        <?php if ($userData && isset($userData['data'])): ?>
        <div class="profile-status status-<?php echo $status; ?>">
            <i class="bi bi-<?php echo $statusIcon; ?>"></i>
            <span><?php echo $firstName; ?> <?php echo $statusText; ?></span>
        </div>
        <?php endif; ?>
    </header>
    
    <?php if ($userData && isset($userData['data'])): ?>
    <?php $latest = $userData['data']; ?>
    
    <!-- Latest Reading Time -->
    <div class="reading-timestamp animate-in animate-delay-2">
        <i class="bi bi-clock"></i>
        <span>Latest reading: <strong><?php echo formatRelativeTime($latest['recorded_at']); ?></strong></span>
    </div>
    
    <!-- Hero Vitals (BP & HR) -->
    <div class="hero-vitals animate-in animate-delay-2">
        <?php if (in_array('blood_pressure', $sharedTypes)): ?>
        <div class="hero-vital-card <?php echo (isset($latest['htn']) && $latest['htn']) ? 'vital-elevated' : ''; ?>">
            <div class="hero-vital-header">
                <div class="vital-icon bp-icon">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <span class="vital-title">Blood Pressure</span>
                <?php if (isset($latest['htn']) && $latest['htn']): ?>
                <span class="vital-alert-badge">
                    <i class="bi bi-exclamation-triangle-fill"></i> Elevated
                </span>
                <?php endif; ?>
            </div>
            <div class="hero-vital-value">
                <span class="bp-systolic"><?php echo $latest['bp_systolic']; ?></span>
                <span class="bp-separator">/</span>
                <span class="bp-diastolic"><?php echo $latest['bp_diastolic']; ?></span>
                <?php echo getTrendIndicator($bpTrend['direction'], true); ?>
            </div>
            <div class="vital-meta">
                <span class="vital-unit">mmHg</span>
                <span class="vital-range">Normal: &lt;120/80</span>
            </div>
            <?php if ($bpTrend['change'] != 0): ?>
            <div class="vital-trend <?php echo $bpTrend['direction'] === 'up' ? 'trend-bad' : 'trend-good'; ?>">
                <i class="bi bi-arrow-<?php echo $bpTrend['direction'] === 'up' ? 'up' : 'down'; ?>"></i>
                <?php echo abs($bpTrend['change']); ?>% vs last week
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('heart_rate', $sharedTypes)): ?>
        <div class="hero-vital-card">
            <div class="hero-vital-header">
                <div class="vital-icon hr-icon">
                    <i class="bi bi-activity"></i>
                    <span class="pulse-dot"></span>
                </div>
                <span class="vital-title">Heart Rate</span>
            </div>
            <div class="hero-vital-value">
                <span class="hr-value"><?php echo $latest['heart_rate']; ?></span>
                <?php echo getTrendIndicator($hrTrend['direction'], false); ?>
            </div>
            <div class="vital-meta">
                <span class="vital-unit">BPM</span>
                <span class="vital-range">Normal: 60-100</span>
            </div>
            <?php 
            $hrStatus = 'Normal';
            $hrStatusClass = 'status-good';
            if ($latest['heart_rate'] < 60) {
                $hrStatus = 'Low';
                $hrStatusClass = 'status-warning';
            } elseif ($latest['heart_rate'] > 100) {
                $hrStatus = 'High';
                $hrStatusClass = 'status-warning';
            }
            ?>
            <span class="vital-status-badge <?php echo $hrStatusClass; ?>"><?php echo $hrStatus; ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Secondary Vitals -->
    <div class="secondary-vitals animate-in animate-delay-3">
        <?php if (in_array('blood_oxygenation', $sharedTypes)): ?>
        <div class="secondary-vital-card <?php echo ($latest['blood_oxygenation'] < 95) ? 'vital-warning' : ''; ?>">
            <div class="secondary-vital-icon oxygen">
                <i class="bi bi-lungs"></i>
            </div>
            <span class="secondary-vital-label">Oxygen</span>
            <span class="secondary-vital-value"><?php echo round($latest['blood_oxygenation'], 1); ?><small>%</small></span>
            <?php 
            $o2Status = 'Normal';
            $o2Class = 'status-good';
            if ($latest['blood_oxygenation'] < 92) {
                $o2Status = 'Low';
                $o2Class = 'status-alert';
            } elseif ($latest['blood_oxygenation'] < 95) {
                $o2Status = 'Low';
                $o2Class = 'status-warning';
            }
            ?>
            <span class="secondary-vital-status <?php echo $o2Class; ?>"><?php echo $o2Status; ?></span>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('agility_score', $sharedTypes)): ?>
        <div class="secondary-vital-card">
            <div class="secondary-vital-icon mobility">
                <i class="bi bi-person-walking"></i>
            </div>
            <span class="secondary-vital-label">Mobility</span>
            <span class="secondary-vital-value"><?php echo round($latest['agility_score']); ?><small>/100</small></span>
            <?php 
            $agilityStatus = 'Good';
            $agilityClass = 'status-good';
            if ($latest['agility_score'] < 40) {
                $agilityStatus = 'Reduced';
                $agilityClass = 'status-warning';
            } elseif ($latest['agility_score'] < 60) {
                $agilityStatus = 'Fair';
                $agilityClass = 'status-info';
            }
            ?>
            <span class="secondary-vital-status <?php echo $agilityClass; ?>"><?php echo $agilityStatus; ?></span>
        </div>
        <?php endif; ?>
        
        <?php if (isset($latest['weight'])): ?>
        <div class="secondary-vital-card">
            <div class="secondary-vital-icon weight">
                <i class="bi bi-speedometer2"></i>
            </div>
            <span class="secondary-vital-label">Weight</span>
            <span class="secondary-vital-value"><?php echo round($latest['weight'], 1); ?><small>kg</small></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Weekly Summary -->
    <?php 
    // Calculate weekly stats
    $weekReadings = [];
    if (!empty($history)) {
        $weekReadings = array_filter($history, function($r) {
            return strtotime($r['recorded_at']) > strtotime('-7 days');
        });
    }
    $weekCount = count($weekReadings);
    $avgBP = $weekCount > 0 ? round(array_sum(array_column($weekReadings, 'bp_systolic')) / $weekCount) : null;
    $avgHR = $weekCount > 0 ? round(array_sum(array_column($weekReadings, 'heart_rate')) / $weekCount) : null;
    ?>
    <div class="weekly-summary animate-in animate-delay-4">
        <h3 class="section-title">
            <i class="bi bi-calendar-week"></i>
            This Week's Summary
        </h3>
        <div class="weekly-stats">
            <div class="weekly-stat">
                <span class="weekly-stat-value"><?php echo $weekCount > 0 ? $weekCount : '--'; ?></span>
                <span class="weekly-stat-label">Readings</span>
            </div>
            <div class="weekly-stat-divider"></div>
            <div class="weekly-stat">
                <span class="weekly-stat-value"><?php echo $avgBP ? $avgBP : '--'; ?></span>
                <span class="weekly-stat-label">Avg Systolic BP</span>
            </div>
            <div class="weekly-stat-divider"></div>
            <div class="weekly-stat">
                <span class="weekly-stat-value"><?php echo $avgHR ? $avgHR : '--'; ?></span>
                <span class="weekly-stat-label">Avg Heart Rate</span>
            </div>
        </div>
    </div>

    <!-- Weekly Trend Chart -->
    <?php if (in_array('blood_pressure', $sharedTypes) && $trends && count($trends) > 0): ?>
    <div class="chart-section animate-in animate-delay-5">
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">
                    <i class="bi bi-graph-up"></i>
                    Blood Pressure Trend
                </h3>
                <span class="chart-period">Last 7 days</span>
            </div>
            <div class="chart-body">
                <canvas id="bpTrendChart"></canvas>
            </div>
            <div class="chart-footer">
                <div class="chart-legend">
                    <span class="legend-item legend-systolic">
                        <span class="legend-dot"></span> Systolic
                    </span>
                    <span class="legend-item legend-diastolic">
                        <span class="legend-dot"></span> Diastolic
                    </span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Activity Summary -->
    <?php if (in_array('usage_patterns', $sharedTypes)): ?>
    <div class="activity-card animate-in">
        <div class="activity-header">
            <i class="bi bi-heart-pulse-fill"></i>
            <span>Daily Check-ins</span>
        </div>
        <div class="activity-content">
            <div class="activity-stat">
                <span class="activity-value"><?php echo $userData['recordings_count'] ?? $weekCount; ?></span>
                <span class="activity-label">readings this week</span>
            </div>
            <p class="activity-note">
                <i class="bi bi-check-circle"></i>
                <?php echo $firstName; ?> is using the Heart Seat regularly, helping track health trends over time.
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Reminders Section -->
    <section class="section-card animate-in">
        <div class="section-header">
            <h3 class="section-title">
                <i class="bi bi-bell"></i>
                Reminders
            </h3>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addReminderModal">
                <i class="bi bi-plus"></i>
                <span>Add</span>
            </button>
        </div>
        <div class="section-body">
            <?php if ($followups && !empty($followups['follow_ups'])): ?>
            <ul class="reminder-list">
                <?php foreach (array_slice($followups['follow_ups'], 0, 3) as $followup): ?>
                <li class="reminder-item">
                    <div class="reminder-icon <?php echo $followup['followup_type'] === 'call' ? 'icon-call' : 'icon-event'; ?>">
                        <i class="bi bi-<?php echo $followup['followup_type'] === 'call' ? 'telephone' : 'calendar-check'; ?>"></i>
                    </div>
                    <div class="reminder-content">
                        <span class="reminder-title"><?php echo htmlspecialchars($followup['title']); ?></span>
                        <span class="reminder-due">Due <?php echo date('M j', strtotime($followup['due_date'])); ?></span>
                    </div>
                    <i class="bi bi-chevron-right reminder-arrow"></i>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="empty-section">
                <div class="empty-icon">
                    <i class="bi bi-bell"></i>
                </div>
                <p>Stay connected with <?php echo htmlspecialchars($firstName); ?> by setting reminders</p>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addReminderModal">
                    <i class="bi bi-plus"></i> Add Your First Reminder
                </button>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Notes Section -->
    <section class="section-card animate-in">
        <div class="section-header">
            <h3 class="section-title">
                <i class="bi bi-journal-text"></i>
                Notes
            </h3>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                <i class="bi bi-plus"></i>
                <span>Add Note</span>
            </button>
        </div>
        <div class="section-body">
            <?php if ($notes && !empty($notes['notes'])): ?>
            <ul class="notes-list">
                <?php foreach (array_slice($notes['notes'], 0, 3) as $note): ?>
                <li class="note-item">
                    <p class="note-content"><?php echo htmlspecialchars($note['content']); ?></p>
                    <div class="note-meta">
                        <span class="note-author"><?php echo htmlspecialchars($note['author']); ?></span>
                        <span class="note-time"><?php echo formatRelativeTime($note['created_at']); ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="empty-section">
                <div class="empty-icon">
                    <i class="bi bi-journal-text"></i>
                </div>
                <p>Keep track of how <?php echo htmlspecialchars($firstName); ?> is doing by adding notes</p>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                    <i class="bi bi-plus"></i> Add Your First Note
                </button>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- What This Means - Contextual Guidance -->
    <section class="guidance-card animate-in guidance-<?php echo $status; ?>">
        <div class="guidance-icon">
            <?php if ($status === 'good'): ?>
            <i class="bi bi-check-circle-fill"></i>
            <?php elseif ($status === 'warning'): ?>
            <i class="bi bi-exclamation-circle-fill"></i>
            <?php else: ?>
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php endif; ?>
        </div>
        <div class="guidance-content">
            <?php if ($status === 'good'): ?>
            <h3>Everything looks good!</h3>
            <p><?php echo $firstName; ?>'s vitals are within healthy ranges. Continue regular monitoring and consult their healthcare provider with any questions.</p>
            <div class="guidance-actions">
                <button class="btn-action secondary" data-bs-toggle="modal" data-bs-target="#addReminderModal">
                    <i class="bi bi-bell"></i> Schedule Check-in
                </button>
                <button class="btn-action secondary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                    <i class="bi bi-journal-plus"></i> Add Note
                </button>
            </div>
            <?php elseif ($status === 'warning'): ?>
            <h3>Some readings are elevated</h3>
            <p><?php echo $firstName; ?>'s blood pressure is higher than normal. This could be temporary, but if it continues, consider discussing with their healthcare provider.</p>
            <div class="guidance-actions">
                <?php if ($cellPhone): ?>
                <a href="tel:<?php echo htmlspecialchars($cellPhone); ?>" class="btn-action primary">
                    <i class="bi bi-telephone-fill"></i> Call <?php echo $firstName; ?>
                </a>
                <?php endif; ?>
                <button class="btn-action secondary" data-bs-toggle="modal" data-bs-target="#addReminderModal">
                    <i class="bi bi-bell"></i> Set Reminder
                </button>
            </div>
            <?php else: ?>
            <h3>Please check in on <?php echo $firstName; ?></h3>
            <p>Some readings are concerning. Consider calling to check in, and if there are any symptoms or concerns, contact their healthcare provider.</p>
            <div class="guidance-actions">
                <?php if ($cellPhone): ?>
                <a href="tel:<?php echo htmlspecialchars($cellPhone); ?>" class="btn-action danger">
                    <i class="bi bi-telephone-fill"></i> Call Now
                </a>
                <a href="sms:<?php echo htmlspecialchars($cellPhone); ?>" class="btn-action danger-outline">
                    <i class="bi bi-chat-dots"></i> Send Text
                </a>
                <?php endif; ?>
                <button class="btn-action secondary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                    <i class="bi bi-journal-plus"></i> Add Note
                </button>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-illustration mb-4">
                    <i class="bi bi-heart-pulse" style="font-size: 4rem; color: var(--casana-purple); opacity: 0.6;"></i>
                </div>
                <h5 class="empty-title">Waiting for Health Data</h5>
                <p class="empty-description" style="max-width: 400px; margin: 0 auto;">
                    We haven't received any readings from <?php echo htmlspecialchars($firstName); ?> yet. 
                    Once they start using their Heart Seat, their health data will appear here.
                </p>
                <div class="empty-actions mt-4 d-flex justify-content-center gap-3 flex-wrap">
                    <?php if ($cellPhone): ?>
                    <a href="tel:<?php echo htmlspecialchars($cellPhone); ?>" class="btn btn-primary">
                        <i class="bi bi-telephone me-2"></i>Call to Check In
                    </a>
                    <a href="sms:<?php echo htmlspecialchars($cellPhone); ?>" class="btn btn-outline-primary">
                        <i class="bi bi-chat-dots me-2"></i>Send a Message
                    </a>
                    <?php else: ?>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addReminderModal">
                        <i class="bi bi-bell me-2"></i>Set a Reminder
                    </button>
                    <?php endif; ?>
                </div>
                <p class="text-muted small mt-4 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Don't worry — they may just need a gentle reminder to use the device.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; // End userNotFound check ?>
</div>

<?php if (!$userNotFound): ?>
<!-- Add Reminder Modal -->
<div class="modal fade" id="addReminderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Reminder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add-reminder.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <input type="hidden" name="monitor_id" value="<?php echo $monitorId; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Reminder Type</label>
                        <select name="followup_type" class="form-select">
                            <option value="call">Call <?php echo $firstName; ?></option>
                            <option value="visit">Visit <?php echo $firstName; ?></option>
                            <option value="review">Review health data</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Weekly check-in call" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Any details to remember..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Set Reminder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Note about <?php echo $firstName; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add-note.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <input type="hidden" name="monitor_id" value="<?php echo $monitorId; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Your Observation</label>
                        <textarea name="content" class="form-control" rows="4" placeholder="How is <?php echo $firstName; ?> doing? Any observations to remember..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Note</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; // End modals condition ?>

<!-- Mobile Bottom Nav -->
<nav class="mobile-nav" aria-label="Mobile navigation">
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link" href="index.php?id=<?php echo $monitorId; ?>">
                <i class="bi bi-house"></i>
                <span>Family</span>
            </a>
        </li>
        <?php if (!$userNotFound && $cellPhone): ?>
        <li class="nav-item">
            <a class="nav-link call-action" href="tel:<?php echo htmlspecialchars($cellPhone); ?>">
                <i class="bi bi-telephone-fill"></i>
                <span>Call</span>
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" href="../index.php">
                <i class="bi bi-grid"></i>
                <span>Switch</span>
            </a>
        </li>
    </ul>
</nav>

<style>
/* ==========================================================================
   User Detail Page - Premium Styles
   ========================================================================== */

/* Back Link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-weight: 500;
    padding: 0.5rem 0;
    transition: all 0.2s ease;
    text-decoration: none;
}

.back-link:hover {
    color: var(--casana-purple);
    gap: 0.75rem;
}

.back-link i {
    transition: transform 0.2s ease;
}

.back-link:hover i {
    transform: translateX(-3px);
}

/* Error Card */
.error-card {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-color);
}

.error-icon-wrapper {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    background: rgba(245, 158, 11, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
}

.error-icon-wrapper i {
    font-size: 2.5rem;
    color: var(--status-warning);
}

.error-card h2 {
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
}

.error-card p {
    max-width: 400px;
    margin: 0 auto 1.5rem;
    color: var(--text-secondary);
}

/* Profile Header Card */
.profile-header {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    padding: 2rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.profile-avatar-container {
    margin-bottom: 1rem;
}

.avatar-ring {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    padding: 4px;
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    animation: ring-pulse 3s ease-in-out infinite;
}

.avatar-ring-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
}

.avatar-ring-alert {
    background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
    animation: ring-pulse-alert 1.5s ease-in-out infinite;
}

@keyframes ring-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.2); }
    50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
}

@keyframes ring-pulse-alert {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3); }
    50% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
}

.profile-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    background-size: cover;
    background-position: center;
}

.profile-avatar.avatar-warning {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
}

.profile-avatar.avatar-alert {
    background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
}

.profile-info {
    flex: 1;
}

.relationship-label {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 0.25rem;
}

.btn-edit-relationship {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.65rem;
    cursor: pointer;
    border-radius: 50%;
    transition: all var(--transition-fast);
    vertical-align: middle;
    margin-left: 4px;
}

.btn-edit-relationship:hover {
    background: var(--bg-hover);
    color: var(--casana-purple);
}

.btn-add-relationship {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--casana-purple);
    background: transparent;
    border: 1px dashed var(--border-color);
    border-radius: var(--radius-full);
    padding: 0.25rem 0.75rem;
    cursor: pointer;
    transition: all var(--transition-fast);
    margin-bottom: 0.5rem;
}

.btn-add-relationship:hover {
    background: rgba(99, 102, 241, 0.08);
    border-color: var(--casana-purple);
}

.profile-name {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: var(--text-primary);
}

.profile-meta {
    color: var(--text-secondary);
    margin: 0 0 1rem 0;
    font-size: 1rem;
}

/* Contact Actions */
.contact-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    margin-bottom: 1rem;
}

.btn-contact-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: var(--casana-purple);
    color: white;
    border-radius: 100px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-contact-primary:hover {
    background: var(--casana-purple-dark);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px -2px rgba(99, 102, 241, 0.4);
}

.btn-contact-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: transparent;
    color: var(--casana-purple);
    border: 2px solid var(--casana-purple);
    border-radius: 100px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-contact-secondary:hover {
    background: rgba(99, 102, 241, 0.1);
    color: var(--casana-purple);
}

/* Profile Status Badge */
.profile-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 100px;
    font-size: 1rem;
    font-weight: 600;
}

.profile-status.status-good {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}

.profile-status.status-warning {
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
}

.profile-status.status-alert {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
}

[data-theme="dark"] .profile-status.status-good {
    background: rgba(52, 211, 153, 0.15);
    color: #34d399;
}

[data-theme="dark"] .profile-status.status-warning {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
}

[data-theme="dark"] .profile-status.status-alert {
    background: rgba(248, 113, 113, 0.15);
    color: #f87171;
}

/* Reading Timestamp */
.reading-timestamp {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    margin-bottom: 1.5rem;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.reading-timestamp strong {
    color: var(--text-primary);
}

/* Hero Vitals (BP & HR) */
.hero-vitals {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

@media (max-width: 600px) {
    .hero-vitals {
        grid-template-columns: 1fr;
    }
}

.hero-vital-card {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    padding: 1.5rem;
    border: 1px solid var(--border-color);
    position: relative;
    transition: all 0.3s ease;
}

.hero-vital-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.hero-vital-card.vital-elevated {
    border-color: var(--status-danger);
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.03) 0%, var(--bg-card) 100%);
}

.hero-vital-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.vital-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    position: relative;
}

.vital-icon.bp-icon {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
}

.vital-icon.hr-icon {
    background: rgba(244, 63, 94, 0.12);
    color: #e11d48;
}

.pulse-dot {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 8px;
    height: 8px;
    background: #e11d48;
    border-radius: 50%;
    animation: pulse-dot 1s ease-in-out infinite;
}

@keyframes pulse-dot {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.7; }
}

.vital-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-secondary);
    flex: 1;
}

.vital-alert-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.625rem;
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
    border-radius: 100px;
    font-size: 0.7rem;
    font-weight: 600;
}

.hero-vital-value {
    display: flex;
    align-items: baseline;
    gap: 2px;
    margin-bottom: 0.5rem;
}

.bp-systolic {
    font-size: 3rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.bp-separator {
    font-size: 2rem;
    font-weight: 400;
    color: var(--text-muted);
    margin: 0 2px;
}

.bp-diastolic {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text-secondary);
    font-variant-numeric: tabular-nums;
}

.hr-value {
    font-size: 3rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.vital-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.vital-unit {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.vital-range {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.vital-trend {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    font-weight: 500;
}

.vital-trend.trend-good {
    color: var(--status-success);
}

.vital-trend.trend-bad {
    color: var(--status-danger);
}

.vital-status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 100px;
    font-size: 0.75rem;
    font-weight: 600;
}

.vital-status-badge.status-good {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}

.vital-status-badge.status-warning {
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
}

/* Secondary Vitals */
.secondary-vitals {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 600px) {
    .secondary-vitals {
        grid-template-columns: repeat(2, 1fr);
    }
}

.secondary-vital-card {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    padding: 1rem;
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: all 0.2s ease;
}

.secondary-vital-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.secondary-vital-card.vital-warning {
    border-color: var(--status-warning);
}

.secondary-vital-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.secondary-vital-icon.oxygen {
    background: rgba(14, 165, 233, 0.12);
    color: #0284c7;
}

.secondary-vital-icon.mobility {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}

.secondary-vital-icon.weight {
    background: rgba(168, 85, 247, 0.12);
    color: #9333ea;
}

.secondary-vital-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted);
    margin-bottom: 0.25rem;
}

.secondary-vital-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}

.secondary-vital-value small {
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--text-muted);
}

.secondary-vital-status {
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 0.375rem;
    padding: 0.125rem 0.5rem;
    border-radius: 100px;
}

.secondary-vital-status.status-good {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}

.secondary-vital-status.status-warning {
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
}

.secondary-vital-status.status-alert {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
}

.secondary-vital-status.status-info {
    background: rgba(14, 165, 233, 0.12);
    color: #0284c7;
}

/* Weekly Summary */
.weekly-summary {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1rem 0;
}

.section-title i {
    color: var(--casana-purple);
}

.weekly-stats {
    display: flex;
    align-items: center;
    justify-content: space-around;
}

.weekly-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0 1rem;
}

.weekly-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.weekly-stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.weekly-stat-divider {
    width: 1px;
    height: 40px;
    background: var(--border-color);
}

@media (max-width: 600px) {
    .weekly-stats {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .weekly-stat-divider {
        display: none;
    }
    
    .weekly-stat {
        flex: 1;
        min-width: 80px;
    }
    
    .weekly-stat-value {
        font-size: 1.5rem;
    }
}

/* Chart Section */
.chart-section {
    margin-bottom: 1.5rem;
}

.chart-card {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.chart-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: var(--text-primary);
}

.chart-title i {
    color: var(--casana-purple);
}

.chart-period {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.chart-body {
    padding: 1rem 1.5rem;
    height: 200px;
}

.chart-footer {
    padding: 0.75rem 1.5rem;
    border-top: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.chart-legend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8rem;
    color: var(--text-secondary);
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 2px;
}

.legend-systolic .legend-dot {
    background: var(--casana-purple);
}

.legend-diastolic .legend-dot {
    background: var(--casana-purple-light);
}

/* Activity Card */
.activity-card {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-color);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.activity-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.08) 100%);
    border-bottom: 1px solid var(--border-color);
    color: var(--casana-purple);
    font-weight: 600;
}

.activity-content {
    padding: 1.25rem;
}

.activity-stat {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.activity-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}

.activity-label {
    font-size: 1rem;
    color: var(--text-secondary);
}

.activity-note {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-muted);
    margin: 0;
    padding: 0.75rem;
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
}

.activity-note i {
    color: var(--status-success);
    flex-shrink: 0;
    margin-top: 2px;
}

/* Section Cards (Reminders, Notes) */
.section-card {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-color);
    margin-bottom: 1rem;
    overflow: hidden;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
}

.section-body {
    padding: 1rem 1.25rem;
}

.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    background: rgba(99, 102, 241, 0.1);
    color: var(--casana-purple);
    border: none;
    border-radius: 100px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-add:hover {
    background: var(--casana-purple);
    color: white;
}

/* Reminder List */
.reminder-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.reminder-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: background 0.2s ease;
}

.reminder-item:last-child {
    border-bottom: none;
}

.reminder-item:hover {
    background: var(--bg-hover);
    margin: 0 -1.25rem;
    padding: 0.75rem 1.25rem;
}

.reminder-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.reminder-icon.icon-call {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}

.reminder-icon.icon-event {
    background: rgba(99, 102, 241, 0.12);
    color: var(--casana-purple);
}

.reminder-content {
    flex: 1;
    min-width: 0;
}

.reminder-title {
    display: block;
    font-weight: 500;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.reminder-due {
    display: block;
    font-size: 0.8rem;
    color: var(--text-muted);
}

.reminder-arrow {
    color: var(--text-muted);
    font-size: 0.9rem;
}

/* Notes List */
.notes-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.note-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.note-item:last-child {
    border-bottom: none;
}

.note-content {
    margin: 0 0 0.375rem 0;
    color: var(--text-primary);
    line-height: 1.5;
}

.note-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--text-muted);
}

.note-author {
    font-weight: 500;
}

.note-time::before {
    content: '•';
    margin-right: 0.5rem;
}

/* Empty Section */
.empty-section {
    text-align: center;
    padding: 1.5rem 0;
}

.empty-section .empty-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 0.75rem;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--casana-purple);
    font-size: 1.25rem;
}

.empty-section p {
    color: var(--text-muted);
    margin: 0 0 1rem 0;
    font-size: 0.9rem;
}

/* Guidance Card */
.guidance-card {
    display: flex;
    gap: 1.25rem;
    padding: 1.5rem;
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-color);
    margin-bottom: 1.5rem;
}

.guidance-card.guidance-good {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, var(--bg-card) 100%);
    border-color: rgba(16, 185, 129, 0.2);
}

.guidance-card.guidance-warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, var(--bg-card) 100%);
    border-color: rgba(245, 158, 11, 0.2);
}

.guidance-card.guidance-alert {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, var(--bg-card) 100%);
    border-color: rgba(239, 68, 68, 0.2);
}

.guidance-icon {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.guidance-good .guidance-icon {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}

.guidance-warning .guidance-icon {
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
}

.guidance-alert .guidance-icon {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
}

.guidance-content {
    flex: 1;
}

.guidance-content h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: var(--text-primary);
}

.guidance-content p {
    color: var(--text-secondary);
    margin: 0 0 1rem 0;
    line-height: 1.5;
}

.guidance-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    border-radius: 100px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.btn-action.primary {
    background: var(--casana-purple);
    color: white;
}

.btn-action.primary:hover {
    background: var(--casana-purple-dark);
    transform: translateY(-1px);
}

.btn-action.secondary {
    background: var(--bg-secondary);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
}

.btn-action.secondary:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.btn-action.danger {
    background: var(--status-danger);
    color: white;
}

.btn-action.danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
}

.btn-action.danger-outline {
    background: transparent;
    color: var(--status-danger);
    border: 2px solid var(--status-danger);
}

.btn-action.danger-outline:hover {
    background: rgba(239, 68, 68, 0.1);
}

/* Mobile Responsive */
@media (max-width: 600px) {
    .profile-header {
        padding: 1.5rem 1rem;
    }
    
    .profile-name {
        font-size: 1.5rem;
    }
    
    .contact-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-contact-primary,
    .btn-contact-secondary {
        width: 100%;
        justify-content: center;
    }
    
    .guidance-card {
        flex-direction: column;
        text-align: center;
    }
    
    .guidance-icon {
        margin: 0 auto;
    }
    
    .guidance-actions {
        justify-content: center;
    }
    
    .bp-systolic {
        font-size: 2.5rem;
    }
    
    .bp-diastolic {
        font-size: 1.5rem;
    }
    
    .hr-value {
        font-size: 2.5rem;
    }
}

/* Animation Classes */
.animate-in {
    opacity: 0;
    transform: translateY(20px);
    animation: slideIn 0.5s ease-out forwards;
}

.animate-delay-1 { animation-delay: 0.1s; }
.animate-delay-2 { animation-delay: 0.15s; }
.animate-delay-3 { animation-delay: 0.2s; }
.animate-delay-4 { animation-delay: 0.25s; }
.animate-delay-5 { animation-delay: 0.3s; }

@keyframes slideIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dark Mode Enhancements */
[data-theme="dark"] .profile-header,
[data-theme="dark"] .hero-vital-card,
[data-theme="dark"] .secondary-vital-card,
[data-theme="dark"] .weekly-summary,
[data-theme="dark"] .chart-card,
[data-theme="dark"] .activity-card,
[data-theme="dark"] .section-card,
[data-theme="dark"] .guidance-card {
    background: linear-gradient(180deg, var(--bg-card) 0%, rgba(15, 23, 42, 0.95) 100%);
    border-color: rgba(148, 163, 184, 0.1);
}

[data-theme="dark"] .hero-vital-card:hover,
[data-theme="dark"] .secondary-vital-card:hover {
    border-color: rgba(99, 102, 241, 0.3);
}

[data-theme="dark"] .chart-footer {
    background: rgba(0, 0, 0, 0.2);
}

[data-theme="dark"] .activity-header {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%);
}

/* Mobile Nav Call Action */
.mobile-nav .call-action {
    color: var(--casana-purple);
}
</style>

<?php if (!$userNotFound && $trends && count($trends) > 0): ?>
<script>
const trendsData = <?php echo json_encode($trends); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const colors = CasanaCharts.getColors();
    
    // Only show last 7 days
    const recentTrends = trendsData.slice(-7);
    
    CasanaCharts.createBPChart(
        document.getElementById('bpTrendChart'),
        recentTrends.map(t => ({
            date: new Date(t.date).toLocaleDateString('en-US', { weekday: 'short' }),
            systolic: t.avg_bp_systolic,
            diastolic: t.avg_bp_diastolic
        }))
    );
});
</script>
<?php endif; ?>

<script>
// Relationship label editing
function editRelationship() {
    const currentLabel = '<?php echo htmlspecialchars($relationshipLabel ?? '', ENT_QUOTES); ?>';
    const suggestions = ['Mom', 'Dad', 'Grandma', 'Grandpa', 'Spouse', 'Parent', 'Aunt', 'Uncle'];
    
    let newLabel = prompt(
        'Enter a relationship label for <?php echo htmlspecialchars($firstName ?? "this person", ENT_QUOTES); ?>:\n\n' +
        'Suggestions: ' + suggestions.join(', '),
        currentLabel
    );
    
    if (newLabel !== null && newLabel.trim() !== currentLabel) {
        // In production, this would make an API call to save the relationship
        alert('Relationship label would be saved as: "' + newLabel.trim() + '"\n\nThis feature requires API integration.');
        // window.location.reload(); // Uncomment when API is connected
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
