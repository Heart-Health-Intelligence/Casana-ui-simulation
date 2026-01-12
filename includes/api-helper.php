<?php
/**
 * Casana API Helper
 * Server-side API calls for PHP pages
 */

// Include centralized health thresholds and config
require_once __DIR__ . '/health-thresholds.php';
require_once __DIR__ . '/../config/config.php';

class CasanaAPI {
    private $baseUrl;
    private $apiKey;
    private $timeout;
    
    public function __construct() {
        $this->baseUrl = config('api.base_url', 'https://casana.mcchord.net/api');
        $this->apiKey = config('api.key', '');
        $this->timeout = config('api.timeout', 30);
    }
    
    /**
     * Make an API request
     * 
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array|null Response data or null on error
     */
    public function request($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "X-API-Key: {$this->apiKey}\r\nContent-Type: application/json\r\n",
                'timeout' => $this->timeout,
            ],
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    // ==========================================================================
    // Stats Endpoints
    // ==========================================================================
    
    public function getOverview() {
        return $this->request('/stats/overview');
    }
    
    public function getAgeDistribution() {
        return $this->request('/stats/age-distribution');
    }
    
    public function getHealthOverview($days = 30) {
        return $this->request('/stats/health-overview', ['days' => $days]);
    }
    
    public function getDurationAnalysis($days = 30) {
        return $this->request('/stats/duration-analysis', ['days' => $days]);
    }
    
    public function getHourlyUsage($days = 7) {
        return $this->request('/stats/hourly-usage', ['days' => $days]);
    }
    
    // ==========================================================================
    // User Endpoints
    // ==========================================================================
    
    public function getUsers($params = []) {
        $defaults = ['page' => 1, 'per_page' => 20];
        return $this->request('/users/', array_merge($defaults, $params));
    }
    
    public function getUser($id) {
        return $this->request("/users/{$id}");
    }
    
    public function getUserRecordings($id, $params = []) {
        $defaults = ['page' => 1, 'per_page' => 20];
        return $this->request("/users/{$id}/recordings", array_merge($defaults, $params));
    }
    
    public function getUserTrends($id, $params = []) {
        $defaults = ['days' => 30, 'group_by' => 'day'];
        return $this->request("/users/{$id}/trends", array_merge($defaults, $params));
    }
    
    // ==========================================================================
    // Monitor Endpoints
    // ==========================================================================
    
    public function getMonitors($params = []) {
        $defaults = ['page' => 1, 'per_page' => 20];
        return $this->request('/monitors/', array_merge($defaults, $params));
    }
    
    public function getMonitor($id) {
        return $this->request("/monitors/{$id}");
    }
    
    public function getMonitoredUserData($monitorId, $userId) {
        return $this->request("/monitors/{$monitorId}/users/{$userId}/data");
    }
    
    // ==========================================================================
    // Care Provider Endpoints
    // ==========================================================================
    
    public function getCareProviders($params = []) {
        $defaults = ['page' => 1, 'per_page' => 20];
        return $this->request('/care-providers/', array_merge($defaults, $params));
    }
    
    public function getCareProvider($id) {
        return $this->request("/care-providers/{$id}");
    }
    
    public function getPatientDetail($providerId, $userId) {
        return $this->request("/care-providers/{$providerId}/patients/{$userId}");
    }
    
    public function getPopulationStats($providerId) {
        return $this->request("/care-providers/{$providerId}/population-stats");
    }
    
    // ==========================================================================
    // Recording Endpoints
    // ==========================================================================
    
    public function getRecordings($params = []) {
        $defaults = ['page' => 1, 'per_page' => 20];
        return $this->request('/recordings/', array_merge($defaults, $params));
    }
    
    public function getRecording($id) {
        return $this->request("/recordings/{$id}");
    }
    
    public function getAlertRecordings($params = []) {
        $defaults = ['page' => 1, 'per_page' => 20, 'days' => 7];
        return $this->request('/recordings/alerts', array_merge($defaults, $params));
    }
    
    public function getExtendedRecordings($params = []) {
        $defaults = ['page' => 1, 'per_page' => 20, 'min_minutes' => 20];
        return $this->request('/recordings/extended', array_merge($defaults, $params));
    }
    
    // ==========================================================================
    // Seat Endpoints
    // ==========================================================================
    
    public function getSeats($params = []) {
        $defaults = ['page' => 1, 'per_page' => 20];
        return $this->request('/seats/', array_merge($defaults, $params));
    }
    
    public function getSeat($serial) {
        return $this->request("/seats/{$serial}");
    }
    
    public function getSeatMetadata($serial, $params = []) {
        $defaults = ['page' => 1, 'per_page' => 20];
        return $this->request("/seats/{$serial}/metadata", array_merge($defaults, $params));
    }
    
    // ==========================================================================
    // Metadata Endpoints
    // ==========================================================================
    
    /**
     * Get all metadata for an entity
     */
    public function getEntityMetadata($entityType, $entityId) {
        return $this->request("/metadata/{$entityType}/{$entityId}");
    }
    
    /**
     * Get specific metadata by key
     */
    public function getMetadata($entityType, $entityId, $key) {
        return $this->request("/metadata/{$entityType}/{$entityId}/{$key}");
    }
    
    /**
     * Set metadata (create or update)
     */
    public function setMetadata($entityType, $entityId, $key, $data, $createdBy = null) {
        $payload = ['data' => $data];
        if ($createdBy) {
            $payload['created_by'] = $createdBy;
        }
        return $this->postRequest("/metadata/{$entityType}/{$entityId}/{$key}", $payload);
    }
    
    /**
     * Set multiple metadata keys at once
     */
    public function setBulkMetadata($entityType, $entityId, $metadata, $createdBy = null) {
        $payload = ['metadata' => $metadata];
        if ($createdBy) {
            $payload['created_by'] = $createdBy;
        }
        return $this->postRequest("/metadata/bulk/{$entityType}/{$entityId}", $payload);
    }
    
    // ==========================================================================
    // Clinical Notes Endpoints
    // ==========================================================================
    
    /**
     * Get notes for a user
     */
    public function getNotes($params = []) {
        return $this->request('/clinical/notes', $params);
    }
    
    /**
     * Get a specific note
     */
    public function getNote($id) {
        return $this->request("/clinical/notes/{$id}");
    }
    
    /**
     * Create a new note
     */
    public function createNote($userId, $content, $author, $params = []) {
        $payload = array_merge([
            'user_id' => $userId,
            'content' => $content,
            'author' => $author,
            'note_type' => 'observation'
        ], $params);
        return $this->postRequest('/clinical/notes', $payload);
    }
    
    /**
     * Update a note
     */
    public function updateNote($id, $params) {
        return $this->putRequest("/clinical/notes/{$id}", $params);
    }
    
    /**
     * Delete a note
     */
    public function deleteNote($id) {
        return $this->deleteRequest("/clinical/notes/{$id}");
    }
    
    // ==========================================================================
    // Clinical Follow-ups Endpoints
    // ==========================================================================
    
    /**
     * Get follow-ups
     */
    public function getFollowups($params = []) {
        return $this->request('/clinical/follow-ups', $params);
    }
    
    /**
     * Get a specific follow-up
     */
    public function getFollowup($id) {
        return $this->request("/clinical/follow-ups/{$id}");
    }
    
    /**
     * Create a new follow-up
     */
    public function createFollowup($userId, $title, $dueDate, $params = []) {
        $payload = array_merge([
            'user_id' => $userId,
            'title' => $title,
            'due_date' => $dueDate,
            'followup_type' => 'call',
            'priority' => 'normal'
        ], $params);
        return $this->postRequest('/clinical/follow-ups', $payload);
    }
    
    /**
     * Complete a follow-up
     */
    public function completeFollowup($id, $completedBy, $notes = null) {
        $payload = ['completed_by' => $completedBy];
        if ($notes) {
            $payload['completion_notes'] = $notes;
        }
        return $this->postRequest("/clinical/follow-ups/{$id}/complete", $payload);
    }
    
    /**
     * Cancel a follow-up
     */
    public function cancelFollowup($id, $cancelledBy, $reason = null) {
        $payload = ['cancelled_by' => $cancelledBy];
        if ($reason) {
            $payload['reason'] = $reason;
        }
        return $this->postRequest("/clinical/follow-ups/{$id}/cancel", $payload);
    }
    
    // ==========================================================================
    // HTTP Methods for POST/PUT/DELETE
    // ==========================================================================
    
    /**
     * Make a POST request
     */
    public function postRequest($endpoint, $data) {
        $url = $this->baseUrl . $endpoint;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "X-API-Key: {$this->apiKey}\r\nContent-Type: application/json\r\n",
                'content' => json_encode($data),
                'timeout' => $this->timeout,
            ],
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Make a PUT request
     */
    public function putRequest($endpoint, $data) {
        $url = $this->baseUrl . $endpoint;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => "X-API-Key: {$this->apiKey}\r\nContent-Type: application/json\r\n",
                'content' => json_encode($data),
                'timeout' => $this->timeout,
            ],
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Make a DELETE request
     */
    public function deleteRequest($endpoint) {
        $url = $this->baseUrl . $endpoint;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE',
                'header' => "X-API-Key: {$this->apiKey}\r\nContent-Type: application/json\r\n",
                'timeout' => $this->timeout,
            ],
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        return json_decode($response, true);
    }
}

// ==========================================================================
// Helper Functions
// ==========================================================================

/**
 * Format a timestamp to relative time
 * 
 * @param string $timestamp ISO timestamp
 * @return string Relative time string
 */
function formatRelativeTime($timestamp) {
    $now = new DateTime();
    $date = new DateTime($timestamp);
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    
    return 'Just now';
}

/**
 * Format timestamp to readable date/time
 * 
 * @param string $timestamp ISO timestamp
 * @param bool $includeTime Include time
 * @param bool $compact Use compact format for tables
 * @return string Formatted date
 */
function formatDateTime($timestamp, $includeTime = true, $compact = false) {
    $date = new DateTime($timestamp);
    
    if ($compact) {
        // Compact format: "Jan 11, 2:20 PM" - fits on one line
        return $date->format('M j, g:i A');
    }
    
    $format = 'M j, Y';
    if ($includeTime) {
        $format .= ' g:i A';
    }
    return $date->format($format);
}

/**
 * Format duration in seconds
 * 
 * @param int $seconds Duration
 * @return string Formatted duration
 */
function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    }
    
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    
    if ($minutes < 60) {
        if ($remainingSeconds === 0) {
            return $minutes . ' min';
        }
        return $minutes . 'm ' . $remainingSeconds . 's';
    }
    
    $hours = floor($minutes / 60);
    $remainingMinutes = $minutes % 60;
    return $hours . 'h ' . $remainingMinutes . 'm';
}

/**
 * Get initials from name
 * 
 * @param string $name Full name
 * @return string Initials
 */
function getInitials($name) {
    if (empty($name)) {
        return '?';
    }
    
    $parts = array_filter(explode(' ', $name));
    
    if (count($parts) === 0) {
        return '?';
    }
    
    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 1));
    }
    
    return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}

/**
 * Get health status from vital data
 * Uses centralized thresholds from health-thresholds.php
 * 
 * @param array $data Vital signs data
 * @return string 'good', 'warning', or 'alert'
 */
function getHealthStatus($data) {
    return getHealthStatusFromVitals($data);
}

/**
 * Get detailed BP status level
 * Uses centralized thresholds from health-thresholds.php
 * 
 * @param int $systolic Systolic BP
 * @param int $diastolic Diastolic BP
 * @return string 'normal', 'elevated', 'high', or 'critical'
 */
function getBPStatus($systolic, $diastolic) {
    $classification = getBPClassification($systolic, $diastolic);
    return $classification['status'];
}

/**
 * Get friendly status message
 * 
 * @param string $status Status code
 * @param string $name Person's name
 * @return string Message
 */
function getStatusMessage($status, $name) {
    $parts = explode(' ', $name);
    $firstName = isset($parts[0]) ? $parts[0] : 'They';
    
    switch ($status) {
        case 'good':
            return $firstName . ' is doing well';
        case 'warning':
            return $firstName . ' needs attention';
        case 'alert':
            return $firstName . ' may need care';
        default:
            return $firstName . "'s status is unknown";
    }
}

/**
 * Format blood pressure
 * 
 * @param int $systolic Systolic
 * @param int $diastolic Diastolic
 * @return string Formatted BP
 */
function formatBloodPressure($systolic, $diastolic) {
    return $systolic . '/' . $diastolic;
}

/**
 * Sanitize provider/doctor name to remove duplicate honorifics
 * Handles cases like "Dr. Dr. John Smith" -> "Dr. John Smith"
 * 
 * @param string $name Full name with possible honorifics
 * @return string Sanitized name
 */
function sanitizeProviderName($name) {
    if (empty($name)) {
        return $name;
    }
    
    // Remove duplicate "Dr." prefix (case-insensitive)
    $name = preg_replace('/^(Dr\.?\s*)+/i', 'Dr. ', $name);
    
    // Remove duplicate "MD" suffix
    $name = preg_replace('/(,?\s*MD\s*)+$/i', ' MD', $name);
    
    // Clean up any double spaces
    $name = preg_replace('/\s+/', ' ', $name);
    
    return trim($name);
}

/**
 * Format blood pressure as styled HTML with fraction-like display
 * 
 * @param int $systolic Systolic value
 * @param int $diastolic Diastolic value
 * @param bool $isHtn Whether reading is hypertensive
 * @return string HTML for styled BP display
 */
function formatBloodPressureStyled($systolic, $diastolic, $isHtn = false) {
    $colorClass = $isHtn ? 'text-danger' : '';
    return '<span class="bp-fraction ' . $colorClass . '"><span class="bp-systolic">' . $systolic . '</span><span class="bp-divider"></span><span class="bp-diastolic">' . $diastolic . '</span></span>';
}

/**
 * Calculate trend from historical data
 * Compares recent average to previous period average
 * 
 * @param array $trends Array of trend data from API
 * @param string $field Field name to calculate trend for
 * @param int $recentDays Number of recent days to average
 * @return array ['direction' => 'up'|'down'|'stable', 'change' => float]
 */
function calculateTrend($trends, $field, $recentDays = 3) {
    if (!$trends || count($trends) < 4) {
        return ['direction' => 'stable', 'change' => 0];
    }
    
    // Get recent period average
    $recent = array_slice($trends, -$recentDays);
    $previous = array_slice($trends, -($recentDays * 2), $recentDays);
    
    $recentValues = [];
    $previousValues = [];
    
    foreach ($recent as $day) {
        if (isset($day[$field]) && $day[$field] !== null) {
            $recentValues[] = $day[$field];
        }
    }
    
    foreach ($previous as $day) {
        if (isset($day[$field]) && $day[$field] !== null) {
            $previousValues[] = $day[$field];
        }
    }
    
    if (empty($recentValues) || empty($previousValues)) {
        return ['direction' => 'stable', 'change' => 0];
    }
    
    $recentAvg = array_sum($recentValues) / count($recentValues);
    $previousAvg = array_sum($previousValues) / count($previousValues);
    
    $change = $recentAvg - $previousAvg;
    $percentChange = $previousAvg > 0 ? ($change / $previousAvg) * 100 : 0;
    
    // Determine direction based on 3% threshold
    $direction = 'stable';
    if ($percentChange > 3) {
        $direction = 'up';
    } elseif ($percentChange < -3) {
        $direction = 'down';
    }
    
    return [
        'direction' => $direction,
        'change' => round($percentChange, 1),
        'current' => round($recentAvg, 1),
        'previous' => round($previousAvg, 1)
    ];
}

/**
 * Get trend indicator HTML
 * 
 * @param string $direction 'up', 'down', or 'stable'
 * @param bool $higherIsBad Whether an increase is bad (e.g., for BP)
 * @return string HTML for trend indicator
 */
function getTrendIndicator($direction, $higherIsBad = false) {
    if ($direction === 'stable') {
        return '<span class="trend-stable" title="Stable"><i class="bi bi-dash"></i></span>';
    }
    
    $isGood = ($direction === 'down' && $higherIsBad) || ($direction === 'up' && !$higherIsBad);
    $color = $isGood ? 'var(--status-success)' : 'var(--status-warning)';
    $icon = $direction === 'up' ? 'arrow-up' : 'arrow-down';
    $title = $direction === 'up' ? 'Trending up' : 'Trending down';
    
    return '<span style="color: ' . $color . ';" title="' . $title . '"><i class="bi bi-' . $icon . '"></i></span>';
}

// Create global API instance
$api = new CasanaAPI();
