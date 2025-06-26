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
        setupTaskStatusUpdates();
        setupViewNotesButtons();
        handleResponsive();
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
        // Handle all sprint views: roadmap, marketing, and documentation
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
            
            const container = document.querySelector('.dmtp-roadmap-view, .dmtp-timeline-view, .dmtp-cards-view, .dmtp-marketing-view, .dmtp-documentation-view');
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
        const marketingToggleButtons = document.querySelectorAll('.dmtp-toggle-marketing');
        const documentationToggleButtons = document.querySelectorAll('.dmtp-toggle-documentation');
        
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
        
        // Marketing tasks toggle
        marketingToggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const sprintId = this.getAttribute('data-sprint-id');
                const marketingTable = document.getElementById('dmtp-marketing-' + sprintId);
                
                if (marketingTable) {
                    if (marketingTable.style.display === 'none' || marketingTable.style.display === '') {
                        marketingTable.style.display = 'block';
                        this.classList.add('active');
                        
                        // Add fade-in animation
                        marketingTable.style.opacity = '0';
                        setTimeout(function() {
                            marketingTable.style.transition = 'opacity 0.3s ease';
                            marketingTable.style.opacity = '1';
                        }, 10);
                        
                        // Check if table needs horizontal scroll indicator
                        const tableWrapper = marketingTable.querySelector('.dmtp-marketing-table-wrapper');
                        if (tableWrapper && tableWrapper.scrollWidth > tableWrapper.clientWidth) {
                            tableWrapper.classList.add('dmtp-scrollable');
                        }
                    } else {
                        marketingTable.style.display = 'none';
                        this.classList.remove('active');
                    }
                }
            });
        });
        
        // Documentation tasks toggle
        documentationToggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const sprintId = this.getAttribute('data-sprint-id');
                const documentationTable = document.getElementById('dmtp-documentation-' + sprintId);
                
                if (documentationTable) {
                    if (documentationTable.style.display === 'none' || documentationTable.style.display === '') {
                        documentationTable.style.display = 'block';
                        this.classList.add('active');
                        
                        // Add fade-in animation
                        documentationTable.style.opacity = '0';
                        setTimeout(function() {
                            documentationTable.style.transition = 'opacity 0.3s ease';
                            documentationTable.style.opacity = '1';
                        }, 10);
                        
                        // Check if table needs horizontal scroll indicator
                        const tableWrapper = documentationTable.querySelector('.dmtp-documentation-table-wrapper');
                        if (tableWrapper && tableWrapper.scrollWidth > tableWrapper.clientWidth) {
                            tableWrapper.classList.add('dmtp-scrollable');
                        }
                    } else {
                        documentationTable.style.display = 'none';
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
        const animatedElements = document.querySelectorAll('.dmtp-roadmap-item, .dmtp-timeline-item, .dmtp-sprint-card, .dmtp-marketing-item, .dmtp-documentation-item');
        
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
        const memberTables = document.querySelectorAll('.dmtp-member-table-wrapper');
        const hotfixTables = document.querySelectorAll('.dmtp-hotfixes-table-wrapper');
        const marketingTables = document.querySelectorAll('.dmtp-marketing-table-wrapper');
        const documentationTables = document.querySelectorAll('.dmtp-documentation-table-wrapper');
        
        // Handle member tables
        memberTables.forEach(function(wrapper) {
            const table = wrapper.querySelector('.dmtp-member-table');
            if (table && table.scrollWidth > wrapper.clientWidth) {
                wrapper.classList.add('dmtp-scrollable');
            } else {
                wrapper.classList.remove('dmtp-scrollable');
            }
        });
        
        // Handle hotfix tables
        hotfixTables.forEach(function(wrapper) {
            const table = wrapper.querySelector('.dmtp-hotfixes-table');
            if (table && table.scrollWidth > wrapper.clientWidth) {
                wrapper.classList.add('dmtp-scrollable');
            } else {
                wrapper.classList.remove('dmtp-scrollable');
            }
        });
        
        // Handle marketing tables
        marketingTables.forEach(function(wrapper) {
            const table = wrapper.querySelector('.dmtp-marketing-table');
            if (table && table.scrollWidth > wrapper.clientWidth) {
                wrapper.classList.add('dmtp-scrollable');
            } else {
                wrapper.classList.remove('dmtp-scrollable');
            }
        });
        
        // Handle documentation tables
        documentationTables.forEach(function(wrapper) {
            const table = wrapper.querySelector('.dmtp-documentation-table');
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

    /**
     * Set up interactive task status updates
     */
    function setupTaskStatusUpdates() {
        const taskCheckboxes = document.querySelectorAll('.dmtp-task-checkbox');
        
        taskCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.disabled) return;
                
                updateTaskStatus(this);
            });
        });
    }

    /**
     * Update task status via AJAX
     * @param {HTMLInputElement} checkbox - The changed checkbox
     */
    function updateTaskStatus(checkbox) {
        const sprintId = checkbox.getAttribute('data-sprint-id');
        const taskIndex = checkbox.getAttribute('data-task-index');
        const fieldType = checkbox.getAttribute('data-field-type');
        const taskType = checkbox.getAttribute('data-task-type');
        const newValue = checkbox.checked;
        
        // Determine action text for confirmation
        let actionText = '';
        if (fieldType === 'status') {
            actionText = newValue ? 'mark this task as completed' : 'mark this task as pending';
        } else if (fieldType === 'verified') {
            actionText = newValue ? 'mark this task as verified' : 'mark this task as not verified';
        }
        
        // Show confirmation dialog
        const confirmed = confirm('Are you sure you want to ' + actionText + '?');
        
        if (!confirmed) {
            // User cancelled - revert checkbox state
            checkbox.checked = !newValue;
            return;
        }
        
        // User confirmed - show loading state
        const label = checkbox.closest('label');
        label.classList.add('dmtp-updating');
        checkbox.disabled = true;
        
        // Prepare AJAX data
        const ajaxData = {
            action: 'dmtp_update_' + taskType + '_task',
            sprint_id: sprintId,
            task_index: taskIndex,
            field_type: fieldType,
            new_value: newValue,
            nonce: dmtp_ajax.nonce
        };
        
        // Send AJAX request
        fetch(dmtp_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(ajaxData)
        })
        .then(response => response.json())
        .then(data => {
            // Regardless of response, refresh the page to show updated state
            window.location.reload();
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            // Even on error, refresh to show current database state
            window.location.reload();
        });
    }

    /**
     * Set up view notes button functionality
     */
    function setupViewNotesButtons() {
        const viewNotesButtons = document.querySelectorAll('.dmtp-view-notes-btn');
        
        viewNotesButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const memberIndex = this.getAttribute('data-member-index');
                const notesRow = document.getElementById('dmtp-notes-row-' + memberIndex);

                // Find the closest table (sprint card/table wrapper)
                const sprintTable = this.closest('table');
                if (!sprintTable) return;

                // Determine if this notes row is already open
                const isOpen = notesRow && notesRow.style.display !== 'none' && notesRow.style.display !== '';

                // Hide all notes rows and reset all buttons in this table
                const allNotesRows = sprintTable.querySelectorAll('tr[id^="dmtp-notes-row-"]');
                const allNotesBtns = sprintTable.querySelectorAll('.dmtp-view-notes-btn');
                allNotesRows.forEach(function(row) {
                    row.style.display = 'none';
                    row.style.opacity = '';
                });
                allNotesBtns.forEach(function(btn) {
                    btn.innerHTML = '📝 View Notes';
                    btn.classList.remove('active');
                });

                // If it was not open, open it; if it was open, leave all closed
                if (notesRow && !isOpen) {
                    notesRow.style.display = 'table-row';
                    this.innerHTML = '👁️ Hide Notes';
                    this.classList.add('active');
                    // Add slide-down animation
                    notesRow.style.opacity = '0';
                    setTimeout(function() {
                        notesRow.style.transition = 'opacity 0.3s ease';
                        notesRow.style.opacity = '1';
                    }, 10);
                }
            });
        });
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