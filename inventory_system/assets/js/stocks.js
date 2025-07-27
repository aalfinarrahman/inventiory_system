/**
 * Stock Management JavaScript
 * File: /assets/js/stocks.js
 * Dependencies: None (Vanilla JavaScript)
 */

class StockManager {
    constructor() {
        this.searchInput = null;
        this.locationFilter = null;
        this.stockLevelFilter = null;
        this.resultCount = null;
        this.tableRows = [];
        this.originalStockCount = 0;
        this.stockStats = {
            total: 0,
            lowStock: 0,
            outOfStock: 0,
            normalStock: 0
        };
        
        this.init();
    }

    /**
     * Initialize the Stock Manager
     */
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    /**
     * Setup all functionality
     */
    setup() {
        this.initializeElements();
        this.calculateStats();
        this.setupEventListeners();
        this.initializeAnimations();
        this.loadSavedFilters();
        this.setupKeyboardShortcuts();
        this.initializeTooltips();
        this.setupStockAlerts();
        
        // Initial filter application
        this.filterTable();
        
        console.log('Stock Manager initialized successfully');
    }

    /**
     * Initialize DOM elements
     */
    initializeElements() {
        this.searchInput = document.getElementById('searchInput');
        this.locationFilter = document.getElementById('locationFilter');
        this.stockLevelFilter = document.getElementById('stockLevelFilter');
        this.resultCount = document.getElementById('resultCount');
        this.tableRows = Array.from(document.querySelectorAll('#stockTable tbody tr'));
        this.originalStockCount = this.tableRows.length;

        // Validate required elements
        if (!this.searchInput || !this.resultCount) {
            console.warn('Some required elements not found. Stock functionality may be limited.');
        }
    }

    /**
     * Calculate stock statistics
     */
    calculateStats() {
        this.stockStats = {
            total: 0,
            lowStock: 0,
            outOfStock: 0,
            normalStock: 0
        };

        this.tableRows.forEach(row => {
            const quantityText = row.cells[3].textContent.replace(/,/g, '').trim();
            const minLevelText = row.cells[4].textContent.replace(/,/g, '').trim();
            
            const quantity = parseInt(quantityText) || 0;
            const minLevel = parseInt(minLevelText) || 0;

            this.stockStats.total++;

            if (quantity === 0) {
                this.stockStats.outOfStock++;
            } else if (quantity <= minLevel) {
                this.stockStats.lowStock++;
            } else {
                this.stockStats.normalStock++;
            }
        });

        this.updateStatsDisplay();
    }

    /**
     * Update statistics display
     */
    updateStatsDisplay() {
        const statsCards = document.querySelectorAll('.stock-stat-card');
        
        if (statsCards.length >= 3) {
            statsCards[0].querySelector('.stock-stat-number').textContent = this.stockStats.normalStock;
            statsCards[1].querySelector('.stock-stat-number').textContent = this.stockStats.lowStock;
            statsCards[2].querySelector('.stock-stat-number').textContent = this.stockStats.outOfStock;
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Filter event listeners with debouncing for search
        if (this.searchInput) {
            this.searchInput.addEventListener('input', 
                this.debounce(() => this.filterTable(), 300)
            );
        }

        if (this.locationFilter) {
            this.locationFilter.addEventListener('change', () => this.filterTable());
        }

        if (this.stockLevelFilter) {
            this.stockLevelFilter.addEventListener('change', () => this.filterTable());
        }

        // Enhanced table interactions
        this.setupTableInteractions();

        // Export functionality
        window.exportStockData = this.exportStockData.bind(this);

        // Bulk actions
        this.setupBulkActions();

        // Window events
        window.addEventListener('beforeunload', () => this.saveFilters());
        window.addEventListener('resize', () => this.handleResize());
    }

    /**
     * Setup table interactions
     */
    setupTableInteractions() {
        this.tableRows.forEach((row, index) => {
            // Add row hover effects
            row.addEventListener('mouseenter', () => this.onRowHover(row, true));
            row.addEventListener('mouseleave', () => this.onRowHover(row, false));
            
            // Add click handler for row selection
            row.addEventListener('click', (e) => this.onRowClick(e, row));
            
            // Add data attributes for better filtering
            const quantity = this.extractNumber(row.cells[3].textContent);
            const minLevel = this.extractNumber(row.cells[4].textContent);
            
            let stockStatus = 'normal';
            if (quantity === 0) {
                stockStatus = 'out';
            } else if (quantity <= minLevel) {
                stockStatus = 'low';
            }
            
            row.setAttribute('data-stock-status', stockStatus);
            row.setAttribute('data-quantity', quantity);
            row.setAttribute('data-min-level', minLevel);
        });
    }

    /**
     * Handle row hover effects
     */
    onRowHover(row, isHovering) {
        if (isHovering) {
            row.style.transform = 'translateY(-3px)';
            row.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.1)';
            
            // Highlight related elements
            this.highlightStockLevel(row, true);
        } else {
            row.style.transform = 'translateY(0)';
            row.style.boxShadow = '';
            
            this.highlightStockLevel(row, false);
        }
    }

    /**
     * Highlight stock level indicators
     */
    highlightStockLevel(row, highlight) {
        const stockStatus = row.getAttribute('data-stock-status');
        const statusBadge = row.querySelector('.status-badge');
        
        if (statusBadge) {
            if (highlight) {
                statusBadge.style.transform = 'scale(1.1)';
                statusBadge.style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.2)';
            } else {
                statusBadge.style.transform = 'scale(1)';
                statusBadge.style.boxShadow = '';
            }
        }
    }

    /**
     * Handle row click
     */
    onRowClick(e, row) {
        // Don't trigger on button clicks
        if (e.target.closest('.btn') || e.target.closest('a')) {
            return;
        }

        // Toggle row selection
        this.toggleRowSelection(row);
        
        // Add ripple effect
        this.addRippleEffect(e, row);
    }

    /**
     * Toggle row selection
     */
    toggleRowSelection(row) {
        const isSelected = row.classList.contains('selected');
        
        if (isSelected) {
            row.classList.remove('selected');
        } else {
            row.classList.add('selected');
        }
        
        this.updateSelectionCount();
    }

    /**
     * Update selection count
     */
    updateSelectionCount() {
        const selectedRows = document.querySelectorAll('#stockTable tbody tr.selected');
        const count = selectedRows.length;
        
        if (count > 0) {
            this.showBulkActions(count);
        } else {
            this.hideBulkActions();
        }
    }

    /**
     * Show bulk actions
     */
    showBulkActions(count) {
        let bulkPanel = document.getElementById('bulk-actions-panel');
        
        if (!bulkPanel) {
            bulkPanel = this.createBulkActionsPanel();
            document.body.appendChild(bulkPanel);
        }
        
        bulkPanel.querySelector('.selection-count').textContent = `${count} items selected`;
        bulkPanel.style.display = 'flex';
        bulkPanel.style.transform = 'translateY(0)';
    }

    /**
     * Hide bulk actions
     */
    hideBulkActions() {
        const bulkPanel = document.getElementById('bulk-actions-panel');
        if (bulkPanel) {
            bulkPanel.style.transform = 'translateY(100%)';
            setTimeout(() => {
                bulkPanel.style.display = 'none';
            }, 300);
        }
    }

    /**
     * Create bulk actions panel
     */
    createBulkActionsPanel() {
        const panel = document.createElement('div');
        panel.id = 'bulk-actions-panel';
        panel.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100%);
            background: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            display: none;
            align-items: center;
            gap: 1rem;
            z-index: 1000;
            transition: transform 0.3s ease;
            border: 1px solid #e2e8f0;
        `;
        
        panel.innerHTML = `
            <span class="selection-count" style="font-weight: 600; color: #374151;"></span>
            <button class="btn btn-sm btn-warning" onclick="stockManager.bulkAdjustStock()">
                <i class="fas fa-edit"></i> Bulk Adjust
            </button>
            <button class="btn btn-sm btn-info" onclick="stockManager.exportSelected()">
                <i class="fas fa-download"></i> Export Selected
            </button>
            <button class="btn btn-sm" style="background: #6b7280; color: white;" onclick="stockManager.clearSelection()">
                <i class="fas fa-times"></i> Clear
            </button>
        `;
        
        return panel;
    }

    /**
     * Clear selection
     */
    clearSelection() {
        const selectedRows = document.querySelectorAll('#stockTable tbody tr.selected');
        selectedRows.forEach(row => row.classList.remove('selected'));
        this.hideBulkActions();
    }

    /**
     * Setup bulk actions
     */
    setupBulkActions() {
        // Add CSS for selected rows
        const style = document.createElement('style');
        style.textContent = `
            #stockTable tbody tr.selected {
                background: linear-gradient(135deg, #eff6ff, #dbeafe) !important;
                border-left: 4px solid #3b82f6;
            }
            
            #stockTable tbody tr.selected td {
                border-color: rgba(59, 130, 246, 0.3);
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Initialize table row animations
     */
    initializeAnimations() {
        this.tableRows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                row.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 50);
        });
    }

    /**
     * Filter table based on all criteria
     */
    filterTable() {
        const searchTerm = this.searchInput ? this.searchInput.value.toLowerCase() : '';
        const locationValue = this.locationFilter ? this.locationFilter.value.toLowerCase() : '';
        const stockLevelValue = this.stockLevelFilter ? this.stockLevelFilter.value : '';
        
        let visibleCount = 0;
        let filteredStats = {
            total: 0,
            lowStock: 0,
            outOfStock: 0,
            normalStock: 0
        };

        this.tableRows.forEach(row => {
            const isVisible = this.checkRowVisibility(row, {
                searchTerm,
                locationValue,
                stockLevelValue
            });
            
            this.toggleRowVisibility(row, isVisible);
            
            if (isVisible) {
                visibleCount++;
                
                // Update filtered stats
                const stockStatus = row.getAttribute('data-stock-status');
                filteredStats.total++;
                
                switch (stockStatus) {
                    case 'out':
                        filteredStats.outOfStock++;
                        break;
                    case 'low':
                        filteredStats.lowStock++;
                        break;
                    default:
                        filteredStats.normalStock++;
                }
                
                if (searchTerm) {
                    this.highlightSearchTerm(row, searchTerm);
                } else {
                    this.removeHighlight(row);
                }
            }
        });

        this.updateResultCount(visibleCount);
        this.updateFilteredStats(filteredStats);
        this.saveFilters();
    }

    /**
     * Check if row should be visible based on filters
     */
    checkRowVisibility(row, filters) {
        const productName = row.cells[1].textContent.toLowerCase();
        const sku = row.cells[0].textContent.toLowerCase();
        const location = row.cells[5].textContent.toLowerCase();
        const stockStatus = row.getAttribute('data-stock-status');

        // Check search match
        const searchMatch = !filters.searchTerm || 
            productName.includes(filters.searchTerm) || 
            sku.includes(filters.searchTerm);

        // Check location match
        const locationMatch = !filters.locationValue || 
            location.includes(filters.locationValue);

        // Check stock level match
        const stockLevelMatch = !filters.stockLevelValue || 
            filters.stockLevelValue === stockStatus;

        return searchMatch && locationMatch && stockLevelMatch;
    }

    /**
     * Toggle row visibility with animation
     */
    toggleRowVisibility(row, isVisible) {
        if (isVisible) {
            row.style.display = '';
            row.style.opacity = '0';
            row.style.transform = 'translateY(10px)';
            
            // Animate in
            requestAnimationFrame(() => {
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            });
        } else {
            row.style.opacity = '0';
            row.style.transform = 'translateY(-10px)';
            
            setTimeout(() => {
                row.style.display = 'none';
            }, 200);
        }
    }

    /**
     * Highlight search terms in row
     */
    highlightSearchTerm(row, term) {
        const nameCell = row.cells[1];
        const skuCell = row.cells[0];
        
        [nameCell, skuCell].forEach(cell => {
            if (!cell) return;
            
            const text = cell.getAttribute('data-original-text') || cell.textContent;
            cell.setAttribute('data-original-text', text);
            
            const regex = new RegExp(`(${this.escapeRegex(term)})`, 'gi');
            cell.innerHTML = text.replace(regex, '<span class="search-highlight">$1</span>');
        });
    }

    /**
     * Remove search highlighting
     */
    removeHighlight(row) {
        const highlighted = row.querySelectorAll('.search-highlight');
        highlighted.forEach(span => {
            const parent = span.parentNode;
            parent.replaceChild(document.createTextNode(span.textContent), span);
            parent.normalize();
        });
    }

    /**
     * Update result count display
     */
    updateResultCount(visibleCount) {
        if (this.resultCount) {
            this.resultCount.textContent = `Showing ${visibleCount} of ${this.originalStockCount} items`;
            
            // Add visual feedback for filtering
            if (visibleCount < this.originalStockCount) {
                this.resultCount.style.color = '#f59e0b';
                this.resultCount.style.fontWeight = '700';
            } else {
                this.resultCount.style.color = '#64748b';
                this.resultCount.style.fontWeight = '600';
            }
        }
    }

    /**
     * Update filtered statistics
     */
    updateFilteredStats(stats) {
        const statsCards = document.querySelectorAll('.stock-stat-card');
        
        if (statsCards.length >= 3) {
            statsCards[0].querySelector('.stock-stat-number').textContent = stats.normalStock;
            statsCards[1].querySelector('.stock-stat-number').textContent = stats.lowStock;
            statsCards[2].querySelector('.stock-stat-number').textContent = stats.outOfStock;
        }
    }

    /**
     * Export stock data to CSV
     */
    exportStockData() {
        const table = document.getElementById('stockTable');
        if (!table) {
            this.showNotification('Table not found for export', 'error');
            return;
        }

        const visibleRows = Array.from(table.querySelectorAll('tr')).filter(row => 
            row.style.display !== 'none'
        );
        
        if (visibleRows.length <= 1) {
            this.showNotification('No data to export', 'warning');
            return;
        }

        let csv = [];
        
        visibleRows.forEach(row => {
            const cols = Array.from(row.cells);
            const rowData = cols.slice(0, -1).map(col => {
                // Clean text content and escape quotes
                const text = col.textContent.trim().replace(/\s+/g, ' ');
                return '"' + text.replace(/"/g, '""') + '"';
            });
            csv.push(rowData.join(','));
        });

        this.downloadCSV(csv.join('\n'), `stock_report_${new Date().toISOString().split('T')[0]}.csv`);
        this.showNotification(`Exported ${visibleRows.length - 1} stock items to CSV`, 'success');
    }

    /**
     * Export selected rows
     */
    exportSelected() {
        const selectedRows = document.querySelectorAll('#stockTable tbody tr.selected');
        
        if (selectedRows.length === 0) {
            this.showNotification('No items selected for export', 'warning');
            return;
        }

        // Get header row
        const headerRow = document.querySelector('#stockTable thead tr');
        let csv = [];
        
        if (headerRow) {
            const headerCols = Array.from(headerRow.cells);
            const headerData = headerCols.slice(0, -1).map(col => '"' + col.textContent.trim() + '"');
            csv.push(headerData.join(','));
        }
        
        selectedRows.forEach(row => {
            const cols = Array.from(row.cells);
            const rowData = cols.slice(0, -1).map(col => {
                const text = col.textContent.trim().replace(/\s+/g, ' ');
                return '"' + text.replace(/"/g, '""') + '"';
            });
            csv.push(rowData.join(','));
        });

        this.downloadCSV(csv.join('\n'), `selected_stock_items_${new Date().toISOString().split('T')[0]}.csv`);
        this.showNotification(`Exported ${selectedRows.length} selected items to CSV`, 'success');
    }

    /**
     * Download CSV file
     */
    downloadCSV(csvContent, filename) {
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
    }

    /**
     * Setup stock alerts
     */
    setupStockAlerts() {
        const outOfStockItems = this.tableRows.filter(row => 
            row.getAttribute('data-stock-status') === 'out'
        );
        
        const lowStockItems = this.tableRows.filter(row => 
            row.getAttribute('data-stock-status') === 'low'
        );

        // Show alerts for critical stock levels
        if (outOfStockItems.length > 0) {
            setTimeout(() => {
                this.showNotification(
                    `${outOfStockItems.length} items are out of stock!`, 
                    'error'
                );
            }, 2000);
        } else if (lowStockItems.length > 0) {
            setTimeout(() => {
                this.showNotification(
                    `${lowStockItems.length} items have low stock levels`, 
                    'warning'
                );
            }, 2000);
        }
    }

    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + F: Focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                if (this.searchInput) {
                    this.searchInput.focus();
                    this.searchInput.select();
                }
            }
            
            // Escape: Clear all filters
            if (e.key === 'Escape') {
                this.clearAllFilters();
            }
            
            // Ctrl/Cmd + E: Export data
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                this.exportStockData();
            }
            
            // Ctrl/Cmd + A: Select all visible rows
            if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                e.preventDefault();
                this.selectAllVisible();
            }
        });
    }

    /**
     * Select all visible rows
     */
    selectAllVisible() {
        const visibleRows = this.tableRows.filter(row => row.style.display !== 'none');
        visibleRows.forEach(row => row.classList.add('selected'));
        this.updateSelectionCount();
    }

    /**
     * Clear all filters
     */
    clearAllFilters() {
        if (this.searchInput) this.searchInput.value = '';
        if (this.locationFilter) this.locationFilter.value = '';
        if (this.stockLevelFilter) this.stockLevelFilter.value = '';
        
        this.filterTable();
        this.showNotification('All filters cleared', 'info');
    }

    /**
     * Bulk adjust stock
     */
    bulkAdjustStock() {
        const selectedRows = document.querySelectorAll('#stockTable tbody tr.selected');
        
        if (selectedRows.length === 0) {
            this.showNotification('No items selected for bulk adjustment', 'warning');
            return;
        }

        // Get product IDs from selected rows
        const productIds = Array.from(selectedRows).map(row => {
            const adjustLink = row.querySelector('a[href*="adjust.php"]');
            if (adjustLink) {
                const url = new URL(adjustLink.href);
                return url.searchParams.get('id');
            }
            return null;
        }).filter(id => id !== null);

        if (productIds.length > 0) {
            // Redirect to bulk adjustment page with selected IDs
            const idsParam = productIds.join(',');
            window.location.href = `bulk-adjust.php?ids=${idsParam}`;
        } else {
            this.showNotification('Could not find product IDs for bulk adjustment', 'error');
        }
    }

    /**
     * Save filter preferences
     */
    saveFilters() {
        const filterSettings = {
            search: this.searchInput ? this.searchInput.value : '',
            location: this.locationFilter ? this.locationFilter.value : '',
            stockLevel: this.stockLevelFilter ? this.stockLevelFilter.value : ''
        };

        try {
            localStorage.setItem('stock_filters', JSON.stringify(filterSettings));
        } catch (e) {
            console.warn('Could not save filter preferences:', e);
        }
    }

    /**
     * Load saved filter preferences
     */
    loadSavedFilters() {
        try {
            const saved = localStorage.getItem('stock_filters');
            if (saved) {
                const filterSettings = JSON.parse(saved);
                
                if (this.searchInput) this.searchInput.value = filterSettings.search || '';
                if (this.locationFilter) this.locationFilter.value = filterSettings.location || '';
                if (this.stockLevelFilter) this.stockLevelFilter.value = filterSettings.stockLevel || '';
            }
        } catch (e) {
            console.warn('Could not load saved filter preferences:', e);
        }
    }

    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        const buttonsWithTooltips = document.querySelectorAll('[title]');
        
        buttonsWithTooltips.forEach(button => {
            button.addEventListener('mouseenter', (e) => this.showTooltip(e));
            button.addEventListener('mouseleave', () => this.hideTooltip());
        });
    }

    /**
     * Handle window resize
     */
    handleResize() {
        // Recalculate table layout if needed
        const table = document.getElementById('stockTable');
        if (table && window.innerWidth < 768) {
            // Mobile layout adjustments
            this.applyMobileTableLayout();
        }
    }

    /**
     * Apply mobile table layout
     */
    applyMobileTableLayout() {
        // Additional mobile-specific functionality
        console.log('Applying mobile table layout');
    }

    /**
     * Add ripple effect
     */
    addRippleEffect(e, element) {
        const ripple = document.createElement('div');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.3) 0%, transparent 50%);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
            z-index: 1;
        `;

        element.style.position = 'relative';
        element.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
    }

    /**
     * Show tooltip
     */
    showTooltip(e) {
        // Tooltip implementation (same as products.js)
        // ... (implementation details)
    }

    /**
     * Hide tooltip
     */
    hideTooltip() {
        // Tooltip hide implementation
        // ... (implementation details)
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        notification.innerHTML = `
            <i class="fas ${icons[type]}"></i>
            <span>${message}</span>
        `;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${this.getNotificationColor(type)};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;

        document.body.appendChild(notification);

        // Animate in
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });

        // Auto remove
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Get notification color
     */
    getNotificationColor(type) {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        return colors[type] || colors.info;
    }

    /**
     * Extract number from text
     */
    extractNumber(text) {
        const number = parseInt(text.replace(/,/g, '')) || 0;
        return number;
    }

    /**
     * Escape regex special characters
     */
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Debounce function
     */
    debounce(func, wait) {
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
}

// Initialize Stock Manager when script loads
const stockManager = new StockManager();