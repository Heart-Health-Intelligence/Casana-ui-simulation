/**
 * Casana Theme Manager
 * Handles light/dark/auto mode switching with localStorage persistence
 * 
 * Storage keys:
 * - casana-theme-mode: User's preference ('light', 'dark', or 'auto')
 * - casana-theme: Currently applied theme ('light' or 'dark') - used for quick init
 */

const ThemeManager = {
    modeKey: 'casana-theme-mode',
    themeKey: 'casana-theme',
    
    /**
     * Initialize theme on page load
     */
    init() {
        // Get saved mode preference (defaults to 'auto')
        const savedMode = localStorage.getItem(this.modeKey) || 'auto';
        
        // Apply the correct theme based on mode
        this.applyMode(savedMode);
        
        // Listen for system theme changes (only affects 'auto' mode)
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            const currentMode = this.getMode();
            if (currentMode === 'auto') {
                this._applyTheme(e.matches ? 'dark' : 'light');
            }
        });
        
        // Set up toggle buttons
        this.setupToggles();
    },
    
    /**
     * Apply a theme mode (light, dark, or auto)
     * @param {string} mode - 'light', 'dark', or 'auto'
     */
    applyMode(mode) {
        // Validate mode
        if (!['light', 'dark', 'auto'].includes(mode)) {
            mode = 'auto';
        }
        
        // Save the mode preference
        localStorage.setItem(this.modeKey, mode);
        
        // Determine which theme to apply
        let theme;
        if (mode === 'auto') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        } else {
            theme = mode;
        }
        
        // Apply the theme
        this._applyTheme(theme);
    },
    
    /**
     * Internal: Apply the actual theme to the document
     * @param {string} theme - 'light' or 'dark'
     */
    _applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        
        // Cache the applied theme for quick init on next page load
        localStorage.setItem(this.themeKey, theme);
        
        // Update any toggle buttons
        document.querySelectorAll('.theme-toggle').forEach(toggle => {
            toggle.setAttribute('data-current', theme);
        });
        
        // Update mode indicators (for settings page)
        document.querySelectorAll('[data-theme-mode]').forEach(el => {
            const mode = this.getMode();
            el.classList.toggle('active', el.dataset.themeMode === mode);
        });
        
        // Update meta theme-color for mobile browsers
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#121420' : '#ffffff');
        }
        
        // Dispatch custom event for charts and other components that need to update
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme, mode: this.getMode() } }));
    },
    
    /**
     * Set the theme mode
     * @param {string} mode - 'light', 'dark', or 'auto'
     */
    setMode(mode) {
        this.applyMode(mode);
    },
    
    /**
     * Get current theme mode
     * @returns {string} Current mode ('light', 'dark', or 'auto')
     */
    getMode() {
        return localStorage.getItem(this.modeKey) || 'auto';
    },
    
    /**
     * Get current applied theme
     * @returns {string} Current theme ('light' or 'dark')
     */
    getTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    },
    
    /**
     * Legacy: Set the theme directly (for backwards compatibility)
     * This sets the mode, not just the theme
     * @param {string} theme - 'light' or 'dark'
     */
    setTheme(theme) {
        // Treat as setting the mode explicitly
        if (theme === 'light' || theme === 'dark') {
            this.setMode(theme);
        }
    },
    
    /**
     * Toggle between light and dark (sets explicit mode, not auto)
     */
    toggle() {
        const current = this.getTheme();
        this.setMode(current === 'dark' ? 'light' : 'dark');
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
    const modeKey = 'casana-theme-mode';
    const themeKey = 'casana-theme';
    
    const savedMode = localStorage.getItem(modeKey);
    const cachedTheme = localStorage.getItem(themeKey);
    
    let theme;
    
    if (savedMode === 'light') {
        theme = 'light';
    } else if (savedMode === 'dark') {
        theme = 'dark';
    } else if (savedMode === 'auto' || !savedMode) {
        // Auto mode or no preference set - use system preference
        // But first check cached theme for faster init (will be corrected in init() if system pref changed)
        if (cachedTheme) {
            theme = cachedTheme;
        } else {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
    } else {
        // Unknown mode, default to auto behavior
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    
    document.documentElement.setAttribute('data-theme', theme);
})();
