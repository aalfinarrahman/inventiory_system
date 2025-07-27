/**
 * Products Page JavaScript
 * File: /assets/js/products.js
 * Dependencies: None (Vanilla JavaScript)
 */

class ProductsManager {
    constructor() {
        this.searchInput = null;
        this.categoryFilter = null;
        this.stockFilter = null;
        this.statusFilter = null;
        this.resultCount = null;
        this.tableRows = [];
        this.originalProductCount = 0;
        
        this.init();
    }

    /**
     * Initialize the Products Manager
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
        this.setupEventListeners();
        this.initializeAnimations();
        this.loadSavedFilters();
        this.setupKeyboardShortcuts();
        this.initializeTooltips();
        
        // Initial filter application
        this.filterProducts();
        
        console.log('Products Manager initialized successfully');
    }

    /**
     * Initialize DOM elements
     */
    initializeElements() {
        this.searchInput = document.getElementById('searchInput');
        this.categoryFilter = document.getElementById('categoryFilter');
        this.stockFilter = document.getElementById('stockFilter');
        this.statusFilter = document.getElementById('statusFilter');
        this.resultCount = document.getElementById('resultCount');
        this.tableRows = Array.from(document.querySelectorAll('#productsTable tbody tr'));
        this.originalProductCount = this.tableRows.length;

        // Validate required elements
        if (!this.searchInput || !this.resultCount) {
            console.warn('Some required elements not found. Products functionality may be limited.');
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Filter event listeners
        if (this.searchInput) {
            this.searchInput.addEventListener('input', () => this.debounce(this.filterProducts.bind(this), 300)());
        }

        if (this.categoryFilter) {
            this.categoryFilter.addEventListener('change', () => this.filterProducts());
        }

        if (this.stockFilter) {
            this.stockFilter.addEventListener('change', () => this.filterProducts());
        }

        if (this.statusFilter) {
            this.statusFilter.addEventListener('change', () => this.filterProducts());
        }

        // Enhanced delete confirmations
        this.setupDeleteConfirmations();

        // Table row hover effects
        this.setupTableInteractions();

        // Export functionality
        window.exportTable = this.exportTable.bind(this);

        // Window events
        window.addEventListener('beforeunload', () => this.saveFilters());
    }

    /**
     * Setup delete confirmation dialogs
     */
    setupDeleteConfirmations() {
        const deleteLinks = document.querySelectorAll('a[onclick*="confirm"], .btn-danger');
        
        deleteLinks.forEach(link => {
            // Remove existing onclick handlers
            link.removeAttribute('onclick');
            
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                const row = link.closest('tr');
                const productNameElement = row.querySelector('.product-name');
                const productName = productNameElement ? productNameElement.textContent.trim() : 'this product';
                
                this.showDeleteConfirmation(productName, link.href, link);
            });
        });
    }

    /**
     * Show enhanced delete confirmation dialog
     */
    showDeleteConfirmation(productName, url, button) {
        const confirmDialog = this.createConfirmDialog({
            title: 'Delete Product',
            message: `Are you sure you want to delete "${productName}"?`,
            details: 'This action cannot be undone and will remove all associated stock records.',
            confirmText: 'Delete Product',
            cancelText: 'Cancel',
            type: 'danger'
        });

        confirmDialog.onConfirm = () => {
            this.handleDeleteProduct(url, button, productName);
        };

        confirmDialog.show();
    }

    /**
     * Handle product deletion
     */
    handleDeleteProduct(url, button, productName) {
        // Add loading state
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
        button.style.pointerEvents = 'none';
        button.classList.add('loading');

        // Show progress notification
        this.showNotification(`Deleting "${productName}"...`, 'info');

        // Navigate after delay for better UX
        setTimeout(() => {
            window.location.href = url;
        }, 1500);
    }

    /**
     * Setup table interactions
     */
    setupTableInteractions() {
        this.tableRows.forEach(row => {
            row.addEventListener('mouseenter', () => this.onRowHover(row, true));
            row.addEventListener('mouseleave', () => this.onRowHover(row, false));
            row.addEventListener('click', (e) => this.onRowClick(e, row));
        });
    }

    /**
     * Handle row hover effects
     */
    onRowHover(row, isHovering) {
        if (isHovering) {
            row.style.transform = 'translateY(-3px)';
            row.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.1)';
        } else {
            row.style.transform = 'translateY(0)';
            row.style.boxShadow = '';
        }
    }

    /**
     * Handle row click (for quick view)
     */
    onRowClick(e, row) {
        // Don't trigger on button clicks
        if (e.target.closest('.btn') || e.target.closest('a')) {
            return;
        }

        // Add ripple effect
        this.addRippleEffect(e, row);

        // Optional: Quick view functionality
        // this.showQuickView(row);
    }

    /**
     * Add ripple effect to row
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
            background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 50%);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
            z-index: 1;
        `;

        element.style.position = 'relative';
        element.appendChild(ripple);

        // Add ripple animation CSS if not exists
        if (!document.getElementById('ripple-styles')) {
            const style = document.createElement('style');
            style.id = 'ripple-styles';
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(2);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        setTimeout(() => ripple.remove(), 600);
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
     * Filter products based on all criteria
     */
    filterProducts() {
        const searchTerm = this.searchInput ? this.searchInput.value.toLowerCase() : '';
        const selectedCategory = this.categoryFilter ? this.categoryFilter.value : '';
        const selectedStock = this.stockFilter ? this.stockFilter.value : '';
        const selectedStatus = this.statusFilter ? this.statusFilter.value : '';
        
        let visibleCount = 0;

        this.tableRows.forEach(row => {
            const isVisible = this.checkRowVisibility(row, {
                searchTerm,
                selectedCategory,
                selectedStock,
                selectedStatus
            });
            
            this.toggleRowVisibility(row, isVisible);
            
            if (isVisible) {
                visibleCount++;
                if (searchTerm) {
                    this.highlightSearchTerm(row, searchTerm);
                } else {
                    this.removeHighlight(row);
                }
            }
        });

        this.updateResultCount(visibleCount);
        this.saveFilters();
    }

    /**
     * Check if row should be visible based on filters
     */
    checkRowVisibility(row, filters) {
        const productNameElement = row.querySelector('.product-name');
        const skuElement = row.querySelector('.sku-code');
        
        const productName = productNameElement ? productNameElement.textContent.toLowerCase() : '';
        const sku = skuElement ? skuElement.textContent.toLowerCase() : '';
        const category = row.getAttribute('data-category') || '';
        const stockStatus = row.getAttribute('data-stock') || '';
        const status = row.getAttribute('data-status') || '';

        // Check all conditions
        const matchesSearch = !filters.searchTerm || 
            productName.includes(filters.searchTerm) || 
            sku.includes(filters.searchTerm);
            
        const matchesCategory = !filters.selectedCategory || 
            category === filters.selectedCategory;
            
        const matchesStock = !filters.selectedStock || 
            (filters.selectedStock === 'low' && stockStatus === 'low-stock') ||
            (filters.selectedStock === 'normal' && stockStatus === 'normal-stock') ||
            (filters.selectedStock === 'out' && stockStatus === 'out-stock');
            
        const matchesStatus = !filters.selectedStatus || 
            status === filters.selectedStatus;

        return matchesSearch && matchesCategory && matchesStock && matchesStatus;
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
        const nameCell = row.querySelector('.product-name');
        const skuCell = row.querySelector('.sku-code');
        
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
     * Escape regex special characters
     */
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Update result count display
     */
    updateResultCount(visibleCount) {
        if (this.resultCount) {
            this.resultCount.textContent = `Showing ${visibleCount} of ${this.originalProductCount} products`;
            
            // Add visual feedback for filtering
            if (visibleCount < this.originalProductCount) {
                this.resultCount.style.color = '#f59e0b';
                this.resultCount.style.fontWeight = '700';
            } else {
                this.resultCount.style.color = '#64748b';
                this.resultCount.style.fontWeight = '600';
            }
        }
    }

    /**
     * Export filtered table data to CSV
     */
    exportTable() {
        const table = document.getElementById('productsTable');
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

        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        
        // Create download link
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        const timestamp = new Date().toISOString().split('T')[0];
        
        link.setAttribute('href', url);
        link.setAttribute('download', `products_export_${timestamp}.csv`);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
        
        this.showNotification(`Exported ${visibleRows.length - 1} products to CSV`, 'success');
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
                this.exportTable();
            }
            
            // Ctrl/Cmd + R: Refresh page
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                window.location.reload();
            }
        });
    }

    /**
     * Clear all filters
     */
    clearAllFilters() {
        if (this.searchInput) this.searchInput.value = '';
        if (this.categoryFilter) this.categoryFilter.value = '';
        if (this.stockFilter) this.stockFilter.value = '';
        if (this.statusFilter) this.statusFilter.value = '';
        
        this.filterProducts();
        this.showNotification('All filters cleared', 'info');
    }

    /**
     * Save filter preferences to localStorage
     */
    saveFilters() {
        const filterSettings = {
            search: this.searchInput ? this.searchInput.value : '',
            category: this.categoryFilter ? this.categoryFilter.value : '',
            stock: this.stockFilter ? this.stockFilter.value : '',
            status: this.statusFilter ? this.statusFilter.value : ''
        };

        try {
            localStorage.setItem('products_filters', JSON.stringify(filterSettings));
        } catch (e) {
            console.warn('Could not save filter preferences:', e);
        }
    }

    /**
     * Load saved filter preferences
     */
    loadSavedFilters() {
        try {
            const saved = localStorage.getItem('products_filters');
            if (saved) {
                const filterSettings = JSON.parse(saved);
                
                if (this.searchInput) this.searchInput.value = filterSettings.search || '';
                if (this.categoryFilter) this.categoryFilter.value = filterSettings.category || '';
                if (this.stockFilter) this.stockFilter.value = filterSettings.stock || '';
                if (this.statusFilter) this.statusFilter.value = filterSettings.status || '';
            }
        } catch (e) {
            console.warn('Could not load saved filter preferences:', e);
        }
    }

    /**
     * Initialize tooltips for action buttons
     */
    initializeTooltips() {
        const buttonsWithTooltips = document.querySelectorAll('[title]');
        
        buttonsWithTooltips.forEach(button => {
            button.addEventListener('mouseenter', (e) => this.showTooltip(e));
            button.addEventListener('mouseleave', () => this.hideTooltip());
        });
    }

    /**
     * Show tooltip
     */
    showTooltip(e) {
        const title = e.target.getAttribute('title');
        if (!title) return;

        // Remove title to prevent browser tooltip
        e.target.setAttribute('data-title', title);
        e.target.removeAttribute('title');

        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = title;
        tooltip.style.cssText = `
            position: absolute;
            background: #1e293b;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 1000;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(5px);
            transition: all 0.2s ease;
        `;

        document.body.appendChild(tooltip);

        // Position tooltip
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = `${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px`;
        tooltip.style.top = `${rect.top - tooltip.offsetHeight - 8}px`;

        // Animate in
        requestAnimationFrame(() => {
            tooltip.style.opacity = '1';
            tooltip.style.transform = 'translateY(0)';
        });

        this.currentTooltip = tooltip;
    }

    /**
     * Hide tooltip
     */
    hideTooltip() {
        if (this.currentTooltip) {
            this.currentTooltip.style.opacity = '0';
            this.currentTooltip.style.transform = 'translateY(5px)';
            
            setTimeout(() => {
                if (this.currentTooltip && this.currentTooltip.parentNode) {
                    this.currentTooltip.parentNode.removeChild(this.currentTooltip);
                }
                this.currentTooltip = null;
            }, 200);
        }

        // Restore title attributes
        const elementsWithDataTitle = document.querySelectorAll('[data-title]');
        elementsWithDataTitle.forEach(el => {
            el.setAttribute('title', el.getAttribute('data-title'));
            el.removeAttribute('data-title');
        });
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
     * Get notification color based on type
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
     * Create confirmation dialog
     */
    createConfirmDialog(options) {
        const dialog = {
            element: null,
            onConfirm: null,
            onCancel: null
        };

        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;

        const modal = document.createElement('div');
        modal.style.cssText = `
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        `;

        modal.innerHTML = `
            <h3 style="color: #1e293b; margin-bottom: 1rem; font-size: 1.25rem;">${options.title}</h3>
            <p style="color: #64748b; margin-bottom: 0.5rem; line-height: 1.5;">${options.message}</p>
            <p style="color: #9ca3af; font-size: 0.9rem; margin-bottom: 2rem;">${options.details}</p>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button class="dialog-cancel" style="padding: 0.75rem 1.5rem; border: 1px solid #d1d5db; background: white; color: #6b7280; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    ${options.cancelText}
                </button>
                <button class="dialog-confirm" style="padding: 0.75rem 1.5rem; border: none; background: #ef4444; color: white; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    ${options.confirmText}
                </button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // Event listeners
        modal.querySelector('.dialog-cancel').addEventListener('click', () => {
            if (dialog.onCancel) dialog.onCancel();
            dialog.hide();
        });

        modal.querySelector('.dialog-confirm').addEventListener('click', () => {
            if (dialog.onConfirm) dialog.onConfirm();
            dialog.hide();
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                if (dialog.onCancel) dialog.onCancel();
                dialog.hide();
            }
        });

        dialog.element = overlay;
        dialog.show = () => {
            overlay.style.opacity = '1';
            modal.style.transform = 'scale(1)';
        };

        dialog.hide = () => {
            overlay.style.opacity = '0';
            modal.style.transform = 'scale(0.9)';
            setTimeout(() => {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 300);
        };

        return dialog;
    }

    /**
     * Debounce function for search input
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

// Initialize Products Manager when script loads
const productsManager = new ProductsManager();