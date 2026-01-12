<?php
/**
 * Casana API Helper
 * Server-side API calls for PHP pages
 */

class CasanaAPI {
    private $baseUrl = 'https://casana.mcchord.net/api';
    private $apiKey = 'dev-key-12345';
    
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
                'timeout' => 30,
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
 * 
 * @param array $data Vital signs data
 * @return string 'good', 'warning', or 'alert'
 */
function getHealthStatus($data) {
    // Check for hypertension flag
    if (isset($data['htn']) && $data['htn'] === true) {
        return 'alert';
    }
    
    // Check blood pressure
    $systolic = isset($data['bp_systolic']) ? $data['bp_systolic'] : 0;
    $diastolic = isset($data['bp_diastolic']) ? $data['bp_diastolic'] : 0;
    
    if ($systolic >= 140 || $diastolic >= 90) {
        return 'alert';
    }
    if ($systolic >= 130 || $diastolic >= 85) {
        return 'warning';
    }
    
    // Check oxygen
    $spo2 = isset($data['blood_oxygenation']) ? $data['blood_oxygenation'] : 100;
    if ($spo2 < 92) {
        return 'alert';
    }
    if ($spo2 < 95) {
        return 'warning';
    }
    
    return 'good';
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

// Create global API instance
$api = new CasanaAPI();
