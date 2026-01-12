<?php
/**
 * Add Reminder Handler
 * Creates a new follow-up reminder for a monitored user
 */

require_once __DIR__ . '/../includes/api-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$monitorId = isset($_POST['monitor_id']) ? intval($_POST['monitor_id']) : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$dueDate = isset($_POST['due_date']) ? trim($_POST['due_date']) : '';
$followupType = isset($_POST['followup_type']) ? trim($_POST['followup_type']) : 'call';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

if (!$userId || !$title || !$dueDate) {
    header("Location: user.php?monitor={$monitorId}&id={$userId}&error=missing_fields");
    exit;
}

// Get monitor name for assigned_by field
$monitor = $api->getMonitor($monitorId);
$assignedBy = $monitor ? $monitor['name'] : 'Family Member';

// Create the follow-up
$params = [
    'followup_type' => $followupType,
    'assigned_by' => $assignedBy,
    'priority' => 'normal'
];

if ($description) {
    $params['description'] = $description;
}

$result = $api->createFollowup($userId, $title, $dueDate, $params);

if ($result) {
    header("Location: user.php?monitor={$monitorId}&id={$userId}&success=reminder_added");
} else {
    header("Location: user.php?monitor={$monitorId}&id={$userId}&error=reminder_failed");
}
exit;
