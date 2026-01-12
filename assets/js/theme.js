/**
 * Casana Theme Manager
 * Handles light/dark mode switching with localStorage persistence
 */

const ThemeManager = {
    storageKey: 'casana-theme',
    
    /**
     * Initialize theme on page load
     */
    init() {
        // Check for saved preference or system preference
        const savedTheme = localStorage.getItem(this.storageKey);
        
        if (savedTheme) {
            this.setTheme(savedTheme);
        } else {
            // Check system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.setTheme(prefersDark ? 'dark' : 'light');
        }
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem(this.storageKey)) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
        
        // Set up toggle buttons
        this.setupToggles();
    },
    
    /**
     * Set the theme
     * @param {string} theme - 'light' or 'dark'
     */
    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(this.storageKey, theme);
        
        // Update any toggle buttons
        document.querySelectorAll('.theme-toggle').forEach(toggle => {
            toggle.setAttribute('data-current', theme);
        });
        
        // Update meta theme-color for mobile browsers
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#121420' : '#ffffff');
        }
        
        // Dispatch custom event for charts and other components that need to update
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    },
    
    /**
     * Get current theme
     * @returns {string} Current theme
     */
    getTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    },
    
    /**
     * Toggle between light and dark
     */
    toggle() {
        const current = this.getTheme();
        this.setTheme(current === 'dark' ? 'light' : 'dark');
    },
    
    /**
     * Set up toggle button event listeners
     */
    setupToggles() {
        document.querySelectorAll('.theme-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => this.toggle());
        });
    },
    
    /**
     * Check if dark mode is active
     * @returns {boolean}
     */
    isDark() {
        return this.getTheme() === 'dark';
    },
    
    /**
     * Get chart colors based on current theme
     * @returns {object} Colors for chart.js
     */
    getChartColors() {
        const isDark = this.isDark();
        
        return {
            primary: '#6A6EFF',
            success: isDark ? '#2DD4A7' : '#00594B',
            warning: isDark ? '#FBBF24' : '#F09C4F',
            danger: isDark ? '#F87171' : '#C24D70',
            text: isDark ? '#F1F3F5' : '#212529',
            textSecondary: isDark ? '#ADB5BD' : '#6C757D',
            grid: isDark ? '#2D3142' : '#E9ECEF',
            background: isDark ? '#1E2132' : '#FFFFFF',
        };
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
});

// Also try to set theme immediately to prevent flash
(function() {
    const savedTheme = localStorage.getItem('casana-theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    } else {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
    }
})();
