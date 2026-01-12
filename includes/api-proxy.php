<?php
/**
 * API Proxy
 * Makes server-side API calls to avoid CORS issues
 */

header('Content-Type: application/json');

require_once __DIR__ . '/api-helper.php';

$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$params = $_GET;
unset($params['endpoint']);

$result = null;

switch ($endpoint) {
    case 'users':
        $result = $api->getUsers($params);
        break;
        
    case 'user':
        $id = isset($params['id']) ? intval($params['id']) : 0;
        unset($params['id']);
        $result = $api->getUser($id);
        break;
        
    case 'monitors':
        $result = $api->getMonitors($params);
        break;
        
    case 'monitor':
        $id = isset($params['id']) ? intval($params['id']) : 0;
        unset($params['id']);
        $result = $api->getMonitor($id);
        break;
        
    case 'care-providers':
        $result = $api->getCareProviders($params);
        break;
        
    case 'care-provider':
        $id = isset($params['id']) ? intval($params['id']) : 0;
        unset($params['id']);
        $result = $api->getCareProvider($id);
        break;
        
    case 'recordings':
        $result = $api->getRecordings($params);
        break;
        
    case 'recording':
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $result = $api->getRecording($id);
        break;
        
    case 'alerts':
        $result = $api->getAlertRecordings($params);
        break;
        
    case 'user-recordings':
        $id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        unset($params['user_id']);
        $result = $api->getUserRecordings($id, $params);
        break;
        
    case 'user-trends':
        $id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        unset($params['user_id']);
        $result = $api->getUserTrends($id, $params);
        break;
        
    case 'stats-overview':
        $result = $api->getOverview();
        break;
        
    case 'population-stats':
        $id = isset($params['provider_id']) ? intval($params['provider_id']) : 0;
        $result = $api->getPopulationStats($id);
        break;
        
    case 'monitor-user-data':
        $monitorId = isset($params['monitor_id']) ? intval($params['monitor_id']) : 0;
        $userId = isset($params['user_id']) ? intval($params['user_id']) : 0;
        $result = $api->getMonitoredUserData($monitorId, $userId);
        break;
    
    case 'set-metadata':
        // POST endpoint for setting metadata
        $entityType = isset($params['entity_type']) ? $params['entity_type'] : '';
        $entityId = isset($params['entity_id']) ? intval($params['entity_id']) : 0;
        $key = isset($params['key']) ? $params['key'] : '';
        $data = isset($params['data']) ? json_decode($params['data'], true) : null;
        
        if (!$entityType || !$entityId || !$key || $data === null) {
            http_response_code(400);
            $result = ['error' => 'Missing required parameters: entity_type, entity_id, key, data'];
            break;
        }
        
        $result = $api->setMetadata($entityType, $entityId, $key, $data);
        break;
    
    case 'get-metadata':
        $entityType = isset($params['entity_type']) ? $params['entity_type'] : '';
        $entityId = isset($params['entity_id']) ? intval($params['entity_id']) : 0;
        $key = isset($params['key']) ? $params['key'] : '';
        
        if (!$entityType || !$entityId || !$key) {
            http_response_code(400);
            $result = ['error' => 'Missing required parameters: entity_type, entity_id, key'];
            break;
        }
        
        $result = $api->getMetadata($entityType, $entityId, $key);
        break;
    
    case 'create-note':
        // Store clinical note in local storage (in production, would go to backend API)
        $userId = isset($params['user_id']) ? intval($params['user_id']) : 0;
        $content = isset($params['content']) ? $params['content'] : '';
        $noteType = isset($params['note_type']) ? $params['note_type'] : 'general';
        $author = isset($params['author']) ? $params['author'] : 'Provider';
        
        if (!$userId || !$content) {
            http_response_code(400);
            $result = ['error' => 'Missing required parameters: user_id, content'];
            break;
        }
        
        // Generate a unique ID and store (in production, this would be an API call)
        $noteId = uniqid('note_');
        $note = [
            'id' => $noteId,
            'user_id' => $userId,
            'content' => $content,
            'note_type' => $noteType,
            'author' => $author,
            'created_at' => date('c')
        ];
        
        // Store in file-based storage for demo (production would use database)
        $notesFile = __DIR__ . '/../data/notes.json';
        $notes = file_exists($notesFile) ? json_decode(file_get_contents($notesFile), true) : [];
        if (!is_array($notes)) $notes = [];
        $notes[] = $note;
        
        // Ensure data directory exists
        if (!is_dir(__DIR__ . '/../data')) {
            mkdir(__DIR__ . '/../data', 0755, true);
        }
        file_put_contents($notesFile, json_encode($notes, JSON_PRETTY_PRINT));
        
        $result = $note;
        break;
    
    case 'create-followup':
        // Store follow-up in local storage
        $userId = isset($params['user_id']) ? intval($params['user_id']) : 0;
        $title = isset($params['title']) ? $params['title'] : '';
        $dueDate = isset($params['due_date']) ? $params['due_date'] : '';
        $followupType = isset($params['followup_type']) ? $params['followup_type'] : 'call';
        $priority = isset($params['priority']) ? $params['priority'] : 'normal';
        $description = isset($params['description']) ? $params['description'] : '';
        $assignedTo = isset($params['assigned_to']) ? $params['assigned_to'] : 'Provider';
        
        if (!$userId || !$title || !$dueDate) {
            http_response_code(400);
            $result = ['error' => 'Missing required parameters: user_id, title, due_date'];
            break;
        }
        
        $followupId = uniqid('followup_');
        $followup = [
            'id' => $followupId,
            'user_id' => $userId,
            'title' => $title,
            'due_date' => $dueDate,
            'followup_type' => $followupType,
            'priority' => $priority,
            'description' => $description,
            'assigned_to' => $assignedTo,
            'status' => 'pending',
            'created_at' => date('c')
        ];
        
        $followupsFile = __DIR__ . '/../data/followups.json';
        $followups = file_exists($followupsFile) ? json_decode(file_get_contents($followupsFile), true) : [];
        if (!is_array($followups)) $followups = [];
        $followups[] = $followup;
        
        if (!is_dir(__DIR__ . '/../data')) {
            mkdir(__DIR__ . '/../data', 0755, true);
        }
        file_put_contents($followupsFile, json_encode($followups, JSON_PRETTY_PRINT));
        
        $result = $followup;
        break;
        
    default:
        http_response_code(400);
        $result = ['error' => 'Invalid endpoint'];
}

if ($result === null) {
    http_response_code(500);
    $result = ['error' => 'API request failed'];
}

echo json_encode($result);
