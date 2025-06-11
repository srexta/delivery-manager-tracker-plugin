/**
 * DMTP Public Scripts
 * Interactive features for the sprint roadmap shortcode
 */

(function() {
    'use strict';

    /**
     * Initialize all functionality when DOM is ready
     */
    function init() {
        setupTeamFiltering();
        setupScrollAnimations();
        setupTooltips();
        setupMemberToggle();
    }

    /**
     * Set up team filtering functionality
     */
    function setupTeamFiltering() {
        const teamSelect = document.getElementById('dmtp-team-select');
        if (!teamSelect) return;

        teamSelect.addEventListener('change', function() {
            const selectedTeam = this.value;
            filterSprintsByTeam(selectedTeam);
        });
    }

    /**
     * Filter sprints by selected team
     * @param {string} selectedTeam - The team name to filter by
     */
    function filterSprintsByTeam(selectedTeam) {
        const sprintItems = document.querySelectorAll('[data-teams]');
        let visibleCount = 0;

        sprintItems.forEach(function(item) {
            const itemTeams = item.getAttribute('data-teams');
            
            if (!selectedTeam || !itemTeams) {
                // Show all if no team selected or no teams data
                item.classList.remove('dmtp-hidden');
                item.classList.add('dmtp-visible');
                visibleCount++;
                return;
            }

            // Split teams by comma and trim whitespace for exact matching
            const teamsArray = itemTeams.split(',').map(team => team.trim());
            
            if (teamsArray.includes(selectedTeam)) {
                item.classList.remove('dmtp-hidden');
                item.classList.add('dmtp-visible');
                visibleCount++;
            } else {
                item.classList.add('dmtp-hidden');
                item.classList.remove('dmtp-visible');
            }
        });

        // Show "no results" message if needed
        showNoResultsMessage(visibleCount === 0);
    }

    /**
     * Show or hide no results message
     * @param {boolean} show - Whether to show or hide the message
     */
    function showNoResultsMessage(show) {
        let noResultsMsg = document.querySelector('.dmtp-no-results');
        
        if (show && !noResultsMsg) {
            // Create and show no results message
            noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'dmtp-no-results';
            noResultsMsg.innerHTML = '<p>No sprints found for the selected team.</p>';
            
            const container = document.querySelector('.dmtp-roadmap-view, .dmtp-timeline-view, .dmtp-cards-view');
            if (container) {
                container.appendChild(noResultsMsg);
            }
        } else if (!show && noResultsMsg) {
            // Hide no results message
            noResultsMsg.remove();
        }
    }

    /**
     * Set up member toggle functionality
     */
    function setupMemberToggle() {
        const memberToggleButtons = document.querySelectorAll('.dmtp-toggle-members');
        const hotfixToggleButtons = document.querySelectorAll('.dmtp-toggle-hotfixes');
        
        // Member contributions toggle
        memberToggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const sprintId = this.getAttribute('data-sprint-id');
                const memberTable = document.getElementById('dmtp-members-' + sprintId);
                
                if (memberTable) {
                    if (memberTable.style.display === 'none' || memberTable.style.display === '') {
                        memberTable.style.display = 'block';
                        this.classList.add('active');
                        
                        // Add fade-in animation
                        memberTable.style.opacity = '0';
                        setTimeout(function() {
                            memberTable.style.transition = 'opacity 0.3s ease';
                            memberTable.style.opacity = '1';
                        }, 10);
                        
                        // Check if table needs horizontal scroll indicator
                        const tableWrapper = memberTable.querySelector('.dmtp-member-table-wrapper');
                        if (tableWrapper && tableWrapper.scrollWidth > tableWrapper.clientWidth) {
                            tableWrapper.classList.add('dmtp-scrollable');
                        }
                    } else {
                        memberTable.style.display = 'none';
                        this.classList.remove('active');
                    }
                }
            });
        });
        
        // Hotfixes toggle
        hotfixToggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const sprintId = this.getAttribute('data-sprint-id');
                const hotfixTable = document.getElementById('dmtp-hotfixes-' + sprintId);
                
                if (hotfixTable) {
                    if (hotfixTable.style.display === 'none' || hotfixTable.style.display === '') {
                        hotfixTable.style.display = 'block';
                        this.classList.add('active');
                        
                        // Add fade-in animation
                        hotfixTable.style.opacity = '0';
                        setTimeout(function() {
                            hotfixTable.style.transition = 'opacity 0.3s ease';
                            hotfixTable.style.opacity = '1';
                        }, 10);
                        
                        // Check if table needs horizontal scroll indicator
                        const tableWrapper = hotfixTable.querySelector('.dmtp-hotfixes-table-wrapper');
                        if (tableWrapper && tableWrapper.scrollWidth > tableWrapper.clientWidth) {
                            tableWrapper.classList.add('dmtp-scrollable');
                        }
                    } else {
                        hotfixTable.style.display = 'none';
                        this.classList.remove('active');
                    }
                }
            });
        });
    }

    /**
     * Set up scroll animations
     */
    function setupScrollAnimations() {
        const animatedElements = document.querySelectorAll('.dmtp-roadmap-item, .dmtp-timeline-item, .dmtp-sprint-card');
        
        if ('IntersectionObserver' in window && animatedElements.length > 0) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('dmtp-animated');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            animatedElements.forEach(function(element) {
                observer.observe(element);
            });
        }
    }

    /**
     * Set up tooltips for better UX
     */
    function setupTooltips() {
        const elementsWithTooltips = document.querySelectorAll('[title]');
        
        elementsWithTooltips.forEach(function(element) {
            element.addEventListener('mouseenter', function() {
                const tooltip = this.getAttribute('title');
                if (tooltip) {
                    this.setAttribute('data-original-title', tooltip);
                    this.removeAttribute('title');
                    showTooltip(this, tooltip);
                }
            });
            
            element.addEventListener('mouseleave', function() {
                const originalTitle = this.getAttribute('data-original-title');
                if (originalTitle) {
                    this.setAttribute('title', originalTitle);
                    this.removeAttribute('data-original-title');
                    hideTooltip();
                }
            });
        });
    }

    /**
     * Show custom tooltip
     * @param {HTMLElement} element - The element to show the tooltip for
     * @param {string} text - The text to display in the tooltip
     */
    function showTooltip(element, text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'dmtp-tooltip';
        tooltip.textContent = text;
        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
        
        setTimeout(function() {
            tooltip.classList.add('dmtp-tooltip-visible');
        }, 10);
    }

    /**
     * Hide custom tooltip
     */
    function hideTooltip() {
        const tooltip = document.querySelector('.dmtp-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    /**
     * Handle responsive behavior
     */
    function handleResponsive() {
        const tables = document.querySelectorAll('.dmtp-member-table-wrapper');
        
        tables.forEach(function(wrapper) {
            const table = wrapper.querySelector('.dmtp-member-table');
            if (table && table.scrollWidth > wrapper.clientWidth) {
                wrapper.classList.add('dmtp-scrollable');
            } else {
                wrapper.classList.remove('dmtp-scrollable');
            }
        });
    }

    /**
     * Utility function to debounce events
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = function() {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Handle window resize for responsive behavior
    window.addEventListener('resize', debounce(function() {
        handleResponsive();
    }, 250));

    // Initialize responsive behavior after a short delay
    setTimeout(handleResponsive, 100);

})(); 