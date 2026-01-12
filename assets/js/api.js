/**
 * Casana API Client
 * Uses PHP proxy to avoid CORS issues
 */

const CasanaAPI = {
    // Use local PHP proxy to avoid CORS
    proxyUrl: '/includes/api-proxy.php',
    
    /**
     * Make a proxied API request
     * @param {string} endpoint - Proxy endpoint name
     * @param {object} params - Query parameters
     * @returns {Promise<object>} API response data
     */
    async request(endpoint, params = {}) {
        const queryParams = new URLSearchParams({ endpoint, ...params });
        const url = `${this.proxyUrl}?${queryParams.toString()}`;
        
        try {
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`API Error: ${response.status} ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API Request Failed:', error);
            throw error;
        }
    },
    
    // ==========================================================================
    // Stats Endpoints
    // ==========================================================================
    
    async getOverview() {
        return this.request('stats-overview');
    },
    
    // ==========================================================================
    // User Endpoints
    // ==========================================================================
    
    async getUsers(params = {}) {
        const defaults = { page: 1, per_page: 20 };
        return this.request('users', { ...defaults, ...params });
    },
    
    async getUser(id) {
        return this.request('user', { id });
    },
    
    async getUserRecordings(id, params = {}) {
        const defaults = { page: 1, per_page: 20 };
        return this.request('user-recordings', { user_id: id, ...defaults, ...params });
    },
    
    async getUserTrends(id, params = {}) {
        const defaults = { days: 30, group_by: 'day' };
        return this.request('user-trends', { user_id: id, ...defaults, ...params });
    },
    
    // ==========================================================================
    // Monitor Endpoints
    // ==========================================================================
    
    async getMonitors(params = {}) {
        const defaults = { page: 1, per_page: 20 };
        return this.request('monitors', { ...defaults, ...params });
    },
    
    async getMonitor(id) {
        return this.request('monitor', { id });
    },
    
    async getMonitoredUserData(monitorId, userId) {
        return this.request('monitor-user-data', { monitor_id: monitorId, user_id: userId });
    },
    
    // ==========================================================================
    // Care Provider Endpoints
    // ==========================================================================
    
    async getCareProviders(params = {}) {
        const defaults = { page: 1, per_page: 20 };
        return this.request('care-providers', { ...defaults, ...params });
    },
    
    async getCareProvider(id) {
        return this.request('care-provider', { id });
    },
    
    async getPopulationStats(providerId) {
        return this.request('population-stats', { provider_id: providerId });
    },
    
    // ==========================================================================
    // Recording Endpoints
    // ==========================================================================
    
    async getRecordings(params = {}) {
        const defaults = { page: 1, per_page: 20 };
        return this.request('recordings', { ...defaults, ...params });
    },
    
    async getRecording(id) {
        return this.request('recording', { id });
    },
    
    async getAlertRecordings(params = {}) {
        const defaults = { page: 1, per_page: 20, days: 7 };
        return this.request('alerts', { ...defaults, ...params });
    },
};

// ==========================================================================
// Utility Functions
// ==========================================================================

/**
 * Format a timestamp to relative time (e.g., "2 hours ago")
 * @param {string} timestamp - ISO timestamp
 * @returns {string} Relative time string
 */
function formatRelativeTime(timestamp) {
    const now = new Date();
    const date = new Date(timestamp);
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffSec < 60) {
        return 'Just now';
    }
    if (diffMin < 60) {
        return `${diffMin} minute${diffMin === 1 ? '' : 's'} ago`;
    }
    if (diffHour < 24) {
        return `${diffHour} hour${diffHour === 1 ? '' : 's'} ago`;
    }
    if (diffDay < 7) {
        return `${diffDay} day${diffDay === 1 ? '' : 's'} ago`;
    }
    
    return date.toLocaleDateString();
}

/**
 * Format a timestamp to readable date/time
 * @param {string} timestamp - ISO timestamp
 * @param {boolean} includeTime - Whether to include time
 * @returns {string} Formatted date string
 */
function formatDateTime(timestamp, includeTime = true) {
    const date = new Date(timestamp);
    const options = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    };
    
    if (includeTime) {
        options.hour = 'numeric';
        options.minute = '2-digit';
    }
    
    return date.toLocaleString('en-US', options);
}

/**
 * Format duration in seconds to human readable
 * @param {number} seconds - Duration in seconds
 * @returns {string} Formatted duration
 */
function formatDuration(seconds) {
    if (seconds < 60) {
        return `${seconds}s`;
    }
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    
    if (minutes < 60) {
        if (remainingSeconds === 0) {
            return `${minutes} min`;
        }
        return `${minutes}m ${remainingSeconds}s`;
    }
    
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return `${hours}h ${remainingMinutes}m`;
}

/**
 * Get initials from a name
 * @param {string} name - Full name
 * @returns {string} Initials (up to 2 characters)
 */
function getInitials(name) {
    if (!name) return '?';
    const parts = name.split(' ').filter(p => p.length > 0);
    if (parts.length === 0) return '?';
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
}

/**
 * Determine health status based on vitals
 * @param {object} data - Object with vital signs
 * @returns {string} 'good', 'warning', or 'alert'
 */
function getHealthStatus(data) {
    // Check for hypertension
    if (data.htn === true) {
        return 'alert';
    }
    
    // Check blood pressure
    if (data.bp_systolic >= 140 || data.bp_diastolic >= 90) {
        return 'alert';
    }
    if (data.bp_systolic >= 130 || data.bp_diastolic >= 85) {
        return 'warning';
    }
    
    // Check oxygen saturation
    if (data.blood_oxygenation < 92) {
        return 'alert';
    }
    if (data.blood_oxygenation < 95) {
        return 'warning';
    }
    
    // Check heart rate (assuming adult)
    if (data.heart_rate < 50 || data.heart_rate > 100) {
        return 'warning';
    }
    
    return 'good';
}

/**
 * Get friendly status message
 * @param {string} status - 'good', 'warning', or 'alert'
 * @param {string} name - Person's first name
 * @returns {string} Friendly message
 */
function getStatusMessage(status, name) {
    const firstName = name ? name.split(' ')[0] : 'They';
    
    switch (status) {
        case 'good':
            return `${firstName} is doing well`;
        case 'warning':
            return `${firstName} needs attention`;
        case 'alert':
            return `${firstName} may need care`;
        default:
            return `${firstName}'s status is unknown`;
    }
}

/**
 * Format blood pressure for display
 * @param {number} systolic - Systolic BP
 * @param {number} diastolic - Diastolic BP
 * @returns {string} Formatted BP string
 */
function formatBloodPressure(systolic, diastolic) {
    return `${systolic}/${diastolic}`;
}

/**
 * Get trend direction from data points
 * @param {Array} data - Array of objects with a value property
 * @param {string} key - Key to compare
 * @returns {string} 'up', 'down', or 'stable'
 */
function getTrendDirection(data, key) {
    if (!data || data.length < 2) return 'stable';
    
    const recent = data.slice(-3);
    const first = recent[0][key];
    const last = recent[recent.length - 1][key];
    
    const percentChange = ((last - first) / first) * 100;
    
    if (percentChange > 5) return 'up';
    if (percentChange < -5) return 'down';
    return 'stable';
}

/**
 * Debounce function for search inputs
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in ms
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export for module usage if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CasanaAPI, formatRelativeTime, formatDateTime, formatDuration, getInitials, getHealthStatus, getStatusMessage, formatBloodPressure, getTrendDirection, debounce };
}
