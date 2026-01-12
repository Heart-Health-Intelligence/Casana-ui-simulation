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
        
    default:
        http_response_code(400);
        $result = ['error' => 'Invalid endpoint'];
}

if ($result === null) {
    http_response_code(500);
    $result = ['error' => 'API request failed'];
}

echo json_encode($result);
