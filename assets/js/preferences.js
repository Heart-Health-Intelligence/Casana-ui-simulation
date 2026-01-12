/**
 * Casana User Preferences
 * Applies user preferences (large text, default view) globally on page load
 * 
 * Storage keys:
 * - casana-large-text: boolean - whether to use larger text size
 * - casana-default-view: 'simple' | 'detailed' - default dashboard view
 * 
 * This script runs early and applies preferences immediately to avoid FOUC
 */

(function() {
    'use strict';
    
    // Storage keys
    var LARGE_TEXT_KEY = 'casana-large-text';
    var DEFAULT_VIEW_KEY = 'casana-default-view';
    
    /**
     * Apply large text preference immediately
     */
    function applyLargeText() {
        var largeText = localStorage.getItem(LARGE_TEXT_KEY) === 'true';
        if (largeText) {
            document.documentElement.style.fontSize = '18px';
            document.documentElement.classList.add('large-text-enabled');
        } else {
            document.documentElement.style.fontSize = '';
            document.documentElement.classList.remove('large-text-enabled');
        }
    }
    
    /**
     * Get the default dashboard view preference
     * @returns {string} 'simple' or 'detailed'
     */
    function getDefaultView() {
        return localStorage.getItem(DEFAULT_VIEW_KEY) || 'simple';
    }
    
    // Apply large text immediately (before DOMContentLoaded)
    applyLargeText();
    
    // Expose preferences manager globally
    window.CasanaPreferences = {
        LARGE_TEXT_KEY: LARGE_TEXT_KEY,
        DEFAULT_VIEW_KEY: DEFAULT_VIEW_KEY,
        
        /**
         * Check if large text is enabled
         * @returns {boolean}
         */
        isLargeTextEnabled: function() {
            return localStorage.getItem(LARGE_TEXT_KEY) === 'true';
        },
        
        /**
         * Enable or disable large text
         * @param {boolean} enabled
         */
        setLargeText: function(enabled) {
            localStorage.setItem(LARGE_TEXT_KEY, enabled ? 'true' : 'false');
            applyLargeText();
            
            // Dispatch event for components that need to react
            window.dispatchEvent(new CustomEvent('preferenceschange', {
                detail: { largeText: enabled }
            }));
        },
        
        /**
         * Get default dashboard view
         * @returns {string} 'simple' or 'detailed'
         */
        getDefaultView: function() {
            return getDefaultView();
        },
        
        /**
         * Set default dashboard view
         * @param {string} view - 'simple' or 'detailed'
         */
        setDefaultView: function(view) {
            if (view !== 'simple' && view !== 'detailed') {
                view = 'simple';
            }
            localStorage.setItem(DEFAULT_VIEW_KEY, view);
            
            // Dispatch event for components that need to react
            window.dispatchEvent(new CustomEvent('preferenceschange', {
                detail: { defaultView: view }
            }));
        },
        
        /**
         * Apply all preferences (call after DOM ready for components that need it)
         */
        applyAll: function() {
            applyLargeText();
        }
    };
})();
