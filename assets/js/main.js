/**
 * Library Management System - Main JavaScript
 */

// =====================================================
// THEME TOGGLE
// =====================================================

function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    const savedTheme = localStorage.getItem('theme') || 'dark';
    setTheme(savedTheme);
    
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.body.classList.contains('theme-light') ? 'light' : 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    });
}

function setTheme(theme) {
    if (theme === 'light') {
        document.body.classList.add('theme-light');
        document.body.classList.remove('theme-dark');
    } else {
        document.body.classList.add('theme-dark');
        document.body.classList.remove('theme-light');
    }
    localStorage.setItem('theme', theme);
    updateThemeIcon(theme);
}

function updateThemeIcon(theme) {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    const icon = themeToggle.querySelector('i');
    if (icon) {
        icon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
    }
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

function showToast(message, type = 'success', title = '') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const titles = {
        success: 'Success',
        error: 'Error',
        warning: 'Warning',
        info: 'Info'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${icons[type] || icons.info}"></i>
        </div>
        <div class="toast-content">
            <p class="toast-title">${title || titles[type] || 'Notification'}</p>
            <p class="toast-message">${message}</p>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

function formatDateTime(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

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

// =====================================================
// AJAX HELPER
// =====================================================

async function apiCall(url, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Request failed');
        }
        
        return result;
    } catch (error) {
        throw error;
    }
}

// =====================================================
// MODAL FUNCTIONS
// =====================================================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById(modalId + 'Overlay');
    
    if (modal) {
        modal.classList.add('show');
        if (overlay) overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById(modalId + 'Overlay');
    
    if (modal) {
        modal.classList.remove('show');
    }
    if (overlay) {
        overlay.classList.remove('show');
    }
    document.body.style.overflow = '';
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-close')) {
        const modal = e.target.closest('.modal');
        if (modal) {
            const modalId = modal.id;
            modal.classList.remove('show');
            const overlay = document.getElementById(modalId + 'Overlay');
            if (overlay) overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    if (e.target.classList.contains('modal-overlay')) {
        const modal = e.target.nextElementSibling;
        if (modal && modal.classList.contains('modal')) {
            modal.classList.remove('show');
        }
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.remove('show');
        });
        document.querySelectorAll('.modal-overlay.show').forEach(overlay => {
            overlay.classList.remove('show');
        });
        document.body.style.overflow = '';
    }
});

// =====================================================
// SIDEBAR TOGGLE
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const appContainer = document.querySelector('.app-container');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            appContainer?.classList.toggle('sidebar-collapsed');
            const isCollapsed = appContainer?.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }
    
    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    
    // Close mobile sidebar on outside click
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && !mobileMenuToggle?.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
    
    // Restore sidebar state
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        appContainer?.classList.add('sidebar-collapsed');
    }
});

// =====================================================
// CONFIRM DIALOG
// =====================================================

function confirmAction(message, callback) {
    const confirmModal = document.createElement('div');
    confirmModal.className = 'modal show';
    confirmModal.id = 'confirmModal';
    confirmModal.innerHTML = `
        <div class="modal-header">
            <h3 class="modal-title">Confirm Action</h3>
            <button class="modal-close" onclick="closeConfirmModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>${message}</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmBtn">Confirm</button>
        </div>
    `;
    
    document.body.appendChild(confirmModal);
    
    document.getElementById('confirmBtn').addEventListener('click', function() {
        closeConfirmModal();
        callback();
    });
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) modal.remove();
    document.body.style.overflow = '';
}

// =====================================================
// DELETE CONFIRMATION
// =====================================================

document.addEventListener('click', function(e) {
    if (e.target.closest('.delete-btn') || e.target.closest('[data-confirm]')) {
        e.preventDefault();
        const message = e.target.closest('[data-confirm]')?.dataset.confirm || 'Are you sure you want to delete this item?';
        const url = e.target.closest('a').href || window.location.href;
        
        confirmAction(message, function() {
            window.location.href = url;
        });
    }
});

// =====================================================
// GLOBAL SEARCH
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    const globalSearch = document.getElementById('globalSearch');
    
    if (globalSearch) {
        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    const baseUrl = window.location.origin + '/library';
                    window.location.href = baseUrl + '/pages/books.php?search=' + encodeURIComponent(query);
                }
            }
        });
    }
    
    // Ctrl+K shortcut
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchBox = document.getElementById('globalSearch');
            if (searchBox) {
                searchBox.focus();
            }
        }
    });
});

// =====================================================
// FORM VALIDATION
// =====================================================

function validateForm(formElement) {
    let isValid = true;
    const inputs = formElement.querySelectorAll('[required]');
    
    inputs.forEach(input => {
        const value = input.value.trim();
        const type = input.type;
        
        if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                showFieldError(input, 'Please enter a valid email address');
            }
        }
        
        if (input.hasAttribute('minlength')) {
            const minLength = parseInt(input.getAttribute('minlength'));
            if (value.length < minLength) {
                isValid = false;
                showFieldError(input, `Minimum ${minLength} characters required`);
            }
        }
        
        if (input.hasAttribute('min') && value) {
            const min = parseFloat(input.getAttribute('min'));
            if (parseFloat(value) < min) {
                isValid = false;
                showFieldError(input, `Minimum value is ${min}`);
            }
        }
    });
    
    return isValid;
}

function showFieldError(input, message) {
    input.classList.add('error');
    const errorEl = input.parentElement.querySelector('.form-error');
    if (errorEl) {
        errorEl.textContent = message;
    }
}

function clearFieldErrors(formElement) {
    formElement.querySelectorAll('.error').forEach(el => {
        el.classList.remove('error');
    });
    formElement.querySelectorAll('.form-error').forEach(el => {
        el.textContent = '';
    });
}

// =====================================================
// DATA TABLES
// =====================================================

function initDataTable(tableId, options = {}) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const rowsPerPage = options.perPage || 10;
    let currentPage = 1;
    
    function renderPage(page) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });
        
        renderPagination();
    }
    
    function renderPagination() {
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        const paginationContainer = table.parentElement.querySelector('.pagination-controls');
        if (!paginationContainer) return;
        
        let html = '';
        
        html += `<button class="pagination-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
            <i class="fas fa-chevron-left"></i>
        </button>`;
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span style="padding: 0 8px;">...</span>`;
            }
        }
        
        html += `<button class="pagination-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
            <i class="fas fa-chevron-right"></i>
        </button>`;
        
        paginationContainer.innerHTML = html;
    }
    
    window.changePage = function(page) {
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderPage(page);
        
        const paginationInfo = table.parentElement.querySelector('.pagination-info');
        if (paginationInfo) {
            const start = (page - 1) * rowsPerPage + 1;
            const end = Math.min(page * rowsPerPage, rows.length);
            paginationInfo.textContent = `Showing ${start} to ${end} of ${rows.length} entries`;
        }
    };
    
    // Initial render
    renderPage(1);
}

// =====================================================
// ANIMATED COUNTER
// =====================================================

function animateCounter(element, target, duration = 2000) {
    const start = 0;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const current = Math.floor(start + (target - start) * easeOutQuart);
        
        element.textContent = current.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('[data-counter]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.dataset.counter);
                animateCounter(entry.target, target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => observer.observe(counter));
});

// =====================================================
// CHART INITIALIZATION (Simple SVG-based)
// =====================================================

function initSimpleChart(elementId, data, options = {}) {
    const container = document.getElementById(elementId);
    if (!container) return;
    
    const width = container.offsetWidth || 400;
    const height = options.height || 300;
    const padding = 40;
    
    const maxValue = Math.max(...data.map(d => d.value));
    const chartWidth = width - padding * 2;
    const chartHeight = height - padding * 2;
    
    let svg = `<svg width="${width}" height="${height}" class="chart-svg">`;
    
    // Grid lines
    const gridLines = 5;
    for (let i = 0; i <= gridLines; i++) {
        const y = padding + (chartHeight / gridLines) * i;
        const value = maxValue - (maxValue / gridLines) * i;
        svg += `<line x1="${padding}" y1="${y}" x2="${width - padding}" y2="${y}" stroke="var(--border-color)" stroke-dasharray="4"/>`;
        svg += `<text x="${padding - 10}" y="${y + 4}" fill="var(--text-muted)" font-size="11" text-anchor="end">${Math.round(value)}</text>`;
    }
    
    // Bars
    if (options.type === 'bar') {
        const barWidth = chartWidth / data.length - 10;
        data.forEach((item, index) => {
            const barHeight = (item.value / maxValue) * chartHeight;
            const x = padding + index * (chartWidth / data.length) + 5;
            const y = height - padding - barHeight;
            
            svg += `<rect x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" 
                fill="${item.color || 'var(--primary)'}" rx="4" class="chart-bar">
                <title>${item.label}: ${item.value}</title></rect>`;
            svg += `<text x="${x + barWidth / 2}" y="${height - padding + 20}" 
                fill="var(--text-secondary)" font-size="11" text-anchor="middle">${item.label}</text>`;
        });
    }
    
    // Line chart
    if (options.type === 'line') {
        const points = data.map((item, index) => {
            const x = padding + (index / (data.length - 1)) * chartWidth;
            const y = height - padding - (item.value / maxValue) * chartHeight;
            return { x, y };
        });
        
        const pathD = points.map((p, i) => (i === 0 ? 'M' : 'L') + `${p.x},${p.y}`).join(' ');
        svg += `<path d="${pathD}" fill="none" stroke="var(--primary)" stroke-width="2"/>`;
        
        points.forEach((p, i) => {
            svg += `<circle cx="${p.x}" cy="${p.y}" r="5" fill="var(--primary)">
                <title>${data[i].label}: ${data[i].value}</title></circle>`;
        });
    }
    
    // Pie chart
    if (options.type === 'pie') {
        const total = data.reduce((sum, item) => sum + item.value, 0);
        let currentAngle = -90;
        
        data.forEach((item) => {
            const angle = (item.value / total) * 360;
            const startAngle = currentAngle;
            const endAngle = currentAngle + angle;
            
            const x1 = width / 2 + 80 * Math.cos((startAngle * Math.PI) / 180);
            const y1 = height / 2 + 80 * Math.sin((startAngle * Math.PI) / 180);
            const x2 = width / 2 + 80 * Math.cos((endAngle * Math.PI) / 180);
            const y2 = height / 2 + 80 * Math.sin((endAngle * Math.PI) / 180);
            
            const largeArc = angle > 180 ? 1 : 0;
            
            svg += `<path d="M${width/2},${height/2} L${x1},${y1} A80,80 0 ${largeArc},1 ${x2},${y2} Z" 
                fill="${item.color}" class="chart-slice">
                <title>${item.label}: ${item.value} (${((item.value/total)*100).toFixed(1)}%)</title></path>`;
            
            currentAngle = endAngle;
        });
        
        // Legend
        data.forEach((item, index) => {
            const y = padding + index * 24;
            svg += `<rect x="${width - 120}" y="${y}" width="12" height="12" fill="${item.color}" rx="2"/>`;
            svg += `<text x="${width - 100}" y="${y + 10}" fill="var(--text-secondary)" font-size="11">${item.label}</text>`;
        });
    }
    
    svg += '</svg>';
    container.innerHTML = svg;
}

// =====================================================
// EXPORT FUNCTIONS
// =====================================================

function exportToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('th, td');
        const rowData = Array.from(cols).map(col => {
            let text = col.innerText.replace(/"/g, '""');
            return `"${text}"`;
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

function printReport() {
    window.print();
}

// =====================================================
// INITIALIZE ON PAGE LOAD
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme toggle
    initThemeToggle();
    
    // Show flash messages
    const flashMessage = document.querySelector('.alert');
    if (flashMessage) {
        const type = flashMessage.classList.contains('alert-success') ? 'success' : 
                     flashMessage.classList.contains('alert-error') ? 'error' : 
                     flashMessage.classList.contains('alert-warning') ? 'warning' : 'info';
        showToast(flashMessage.textContent.trim(), type);
    }
    
    // Initialize data tables
    document.querySelectorAll('[data-table]').forEach(table => {
        initDataTable(table.id);
    });
    
    // Form validation
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
