<?php
/**
 * Add Note Handler
 * Creates a new note for a monitored user
 */

require_once __DIR__ . '/../includes/api-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$monitorId = isset($_POST['monitor_id']) ? intval($_POST['monitor_id']) : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if (!$userId || !$content) {
    header("Location: user.php?monitor={$monitorId}&id={$userId}&error=missing_fields");
    exit;
}

// Get monitor name for author field
$monitor = $api->getMonitor($monitorId);
$authorName = $monitor ? $monitor['name'] : 'Family Member';

// Create the note
$result = $api->createNote($userId, $content, $authorName, [
    'note_type' => 'observation',
    'author_role' => 'family_monitor'
]);

if ($result) {
    header("Location: user.php?monitor={$monitorId}&id={$userId}&success=note_added");
} else {
    header("Location: user.php?monitor={$monitorId}&id={$userId}&error=note_failed");
}
exit;
