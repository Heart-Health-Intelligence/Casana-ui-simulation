/**
 * Casana Provider App JavaScript
 * Handles provider-specific functionality including:
 * - Global patient search
 * - Mobile sidebar toggle
 * - Keyboard navigation
 */

(function() {
    'use strict';

    // =========================================================================
    // Global Patient Search
    // =========================================================================
    
    const searchInput = document.getElementById('globalPatientSearch');
    let searchResults = null;
    let searchTimeout = null;
    let patients = [];
    let activeIndex = -1;

    // Initialize global search if the input exists
    if (searchInput) {
        initGlobalSearch();
    }

    function initGlobalSearch() {
        // Use existing search results dropdown or create one
        searchResults = document.getElementById('globalSearchResults');
        if (!searchResults) {
            searchResults = document.createElement('div');
            searchResults.className = 'global-search-results';
            searchResults.id = 'globalSearchResults';
            searchResults.setAttribute('role', 'listbox');
            searchResults.setAttribute('aria-label', 'Search results');
            searchInput.parentElement.style.position = 'relative';
            searchInput.parentElement.appendChild(searchResults);
        }

        // Event listeners
        searchInput.addEventListener('input', handleSearchInput);
        searchInput.addEventListener('focus', handleSearchFocus);
        searchInput.addEventListener('blur', handleSearchBlur);
        searchInput.addEventListener('keydown', handleSearchKeydown);
        
        // Load patients data if available on the page
        loadPatientsData();
    }

    function loadPatientsData() {
        // Try to get patients from page data
        if (typeof window.providerPatients !== 'undefined') {
            patients = window.providerPatients;
        }
    }

    function handleSearchInput(e) {
        const query = e.target.value.trim().toLowerCase();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            hideSearchResults();
            return;
        }

        searchTimeout = setTimeout(function() {
            performSearch(query);
        }, 200);
    }

    function performSearch(query) {
        // Filter patients based on query
        const results = patients.filter(function(patient) {
            const name = (patient.name || '').toLowerCase();
            const email = (patient.email || '').toLowerCase();
            return name.includes(query) || email.includes(query);
        }).slice(0, 8); // Limit to 8 results

        renderSearchResults(results, query);
    }

    function renderSearchResults(results, query) {
        if (results.length === 0) {
            searchResults.innerHTML = '<div class="no-results">No patients found</div>';
            showSearchResults();
            return;
        }

        let html = '<div class="search-result-group">Patients</div>';
        
        results.forEach(function(patient, index) {
            const initials = getInitials(patient.name);
            const highlighted = highlightMatch(patient.name, query);
            
            html += '<div class="search-result-item" role="option" data-index="' + index + '" data-user-id="' + patient.user_id + '" tabindex="-1">';
            html += '  <div class="entity-avatar" style="width: 36px; height: 36px; font-size: 0.8rem;">' + initials + '</div>';
            html += '  <div class="flex-grow-1">';
            html += '    <div class="fw-medium">' + highlighted + '</div>';
            html += '    <div class="small text-muted">' + (patient.age || '--') + ' years • ' + (patient.gender || '--') + '</div>';
            html += '  </div>';
            html += '  <i class="bi bi-chevron-right text-muted"></i>';
            html += '</div>';
        });

        searchResults.innerHTML = html;
        activeIndex = -1;
        
        // Add click handlers
        searchResults.querySelectorAll('.search-result-item').forEach(function(item) {
            item.addEventListener('mousedown', function(e) {
                e.preventDefault();
                const userId = this.getAttribute('data-user-id');
                navigateToPatient(userId);
            });
        });
        
        showSearchResults();
    }

    function highlightMatch(text, query) {
        const regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function getInitials(name) {
        if (!name) return '?';
        const parts = name.split(' ');
        if (parts.length >= 2) {
            return parts[0][0] + parts[parts.length - 1][0];
        }
        return parts[0].substring(0, 2);
    }

    function handleSearchFocus() {
        if (searchInput.value.trim().length >= 2) {
            showSearchResults();
        }
    }

    function handleSearchBlur() {
        // Delay hiding to allow click on results
        setTimeout(hideSearchResults, 200);
    }

    function handleSearchKeydown(e) {
        const items = searchResults.querySelectorAll('.search-result-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                updateActiveItem(items);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, -1);
                updateActiveItem(items);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (activeIndex >= 0 && items[activeIndex]) {
                    const userId = items[activeIndex].getAttribute('data-user-id');
                    navigateToPatient(userId);
                }
                break;
                
            case 'Escape':
                hideSearchResults();
                searchInput.blur();
                break;
        }
    }

    function updateActiveItem(items) {
        items.forEach(function(item, index) {
            if (index === activeIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    function showSearchResults() {
        searchResults.classList.add('show');
    }

    function hideSearchResults() {
        searchResults.classList.remove('show');
        activeIndex = -1;
    }

    function navigateToPatient(userId) {
        const providerId = getProviderId();
        window.location.href = 'patient.php?provider=' + providerId + '&id=' + userId;
    }

    function getProviderId() {
        // Try to get from URL first
        const urlParams = new URLSearchParams(window.location.search);
        let providerId = urlParams.get('id') || urlParams.get('provider');
        
        // Fallback to page variable
        if (!providerId && typeof window.providerId !== 'undefined') {
            providerId = window.providerId;
        }
        
        return providerId || 1;
    }

    // Global function for form submission
    window.handlePatientSearch = function(e) {
        e.preventDefault();
        const query = searchInput.value.trim();
        if (query.length >= 2) {
            const providerId = getProviderId();
            window.location.href = 'patients.php?id=' + providerId + '&search=' + encodeURIComponent(query);
        }
        return false;
    };

    // =========================================================================
    // Mobile Sidebar Toggle
    // =========================================================================
    
    const sidebar = document.getElementById('providerSidebar');
    let overlay = null;

    if (sidebar) {
        initSidebarToggle();
    }

    function initSidebarToggle() {
        // Create overlay
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.addEventListener('click', closeSidebar);
        document.body.appendChild(overlay);
    }

    window.toggleProviderSidebar = function() {
        if (sidebar.classList.contains('show')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    };

    function openSidebar() {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // =========================================================================
    // Table Row Keyboard Accessibility
    // =========================================================================
    
    document.querySelectorAll('.table-clickable').forEach(function(row) {
        // Make rows focusable
        row.setAttribute('tabindex', '0');
        row.setAttribute('role', 'button');
        
        // Add keyboard handler
        row.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // =========================================================================
    // Initialize patients data for global search
    // =========================================================================
    
    // Expose method to set patients data from page
    window.setProviderPatients = function(patientsData) {
        patients = patientsData || [];
    };

})();
