// assets/js/main.js - Script utama untuk navigasi dan interaksi

class KasirApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupMobileNavigation();
        this.setupDropdownMenus();
        this.setupFormSubmissions();
        this.setupAutoCloseAlerts();
        this.setupPrintButtons();
    }

    // Setup navigasi mobile
    setupMobileNavigation() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');
        const closeSidebar = document.getElementById('close-sidebar');

        // Toggle sidebar di mobile
        if (mobileMenuButton && sidebar) {
            mobileMenuButton.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                sidebar.classList.toggle('translate-x-0');
            });
        }

        // Close sidebar
        if (closeSidebar && sidebar) {
            closeSidebar.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
            });
        }

        // Close sidebar ketika klik di luar
        document.addEventListener('click', (e) => {
            if (sidebar && !sidebar.contains(e.target) && 
                mobileMenuButton && !mobileMenuButton.contains(e.target) &&
                !sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
            }
        });
    }

    // Setup dropdown menus
    setupDropdownMenus() {
        const dropdownButtons = document.querySelectorAll('.dropdown-button');
        
        dropdownButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const dropdown = button.nextElementSibling;
                
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    if (menu !== dropdown) {
                        menu.classList.add('hidden');
                    }
                });
                
                // Toggle current dropdown
                if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                    dropdown.classList.toggle('hidden');
                }
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        });
    }

    // Setup form submissions dengan loading state
    setupFormSubmissions() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitButton = form.querySelector('button[type="submit"]');
                
                if (submitButton) {
                    // Add loading state
                    this.setButtonLoading(submitButton, true);
                    
                    // Auto remove loading state after 10 seconds (safety)
                    setTimeout(() => {
                        this.setButtonLoading(submitButton, false);
                    }, 10000);
                }
            });
        });
    }

    // Set loading state pada button
    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.setAttribute('data-original-text', button.innerHTML);
            button.innerHTML = `
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Memproses...
            `;
        } else {
            button.disabled = false;
            const originalText = button.getAttribute('data-original-text');
            if (originalText) {
                button.innerHTML = originalText;
            }
        }
    }

    // Auto close alerts setelah 5 detik
    setupAutoCloseAlerts() {
        const alerts = document.querySelectorAll('.alert-auto-close');
        
        alerts.forEach(alert => {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }
            }, 5000);
        });
    }

    // Setup print buttons
    setupPrintButtons() {
        const printButtons = document.querySelectorAll('.print-trigger');
        
        printButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Show print preview atau langsung print
                if (button.dataset.preview === 'true') {
                    this.showPrintPreview(button.href);
                } else {
                    window.print();
                }
            });
        });
    }

    // Show print preview
    showPrintPreview(url) {
        const previewWindow = window.open(url, 'print_preview', 'width=800,height=600');
        if (previewWindow) {
            previewWindow.addEventListener('load', () => {
                previewWindow.print();
            });
        }
    }

    // Utility function untuk format currency
    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }

    // Utility function untuk format date
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    // Utility function untuk format datetime
    formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Show notification
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        const types = {
            success: 'bg-green-50 border-green-200 text-green-800',
            error: 'bg-red-50 border-red-200 text-red-800',
            warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
            info: 'bg-blue-50 border-blue-200 text-blue-800'
        };

        notification.className = `fixed top-4 right-4 z-50 p-4 border rounded-lg shadow-lg transform transition-transform duration-300 translate-x-full ${types[type] || types.info}`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${this.getNotificationIcon(type)} mr-3"></i>
                <span class="flex-1">${message}</span>
                <button class="ml-4 text-current hover:opacity-70" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove
        if (duration > 0) {
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, duration);
        }
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }
}

// Initialize app ketika DOM ready
document.addEventListener('DOMContentLoaded', function() {
    window.kasirApp = new KasirApp();
});

// Global functions untuk digunakan di HTML
function confirmAction(message) {
    return confirm(message || 'Apakah Anda yakin ingin melanjutkan?');
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('-translate-x-full');
        sidebar.classList.toggle('translate-x-0');
    }
}

function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'global-loader';
    loader.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    loader.innerHTML = `
        <div class="bg-white p-6 rounded-lg flex items-center">
            <i class="fas fa-spinner fa-spin text-blue-500 text-2xl mr-3"></i>
            <span>Memproses...</span>
        </div>
    `;
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.getElementById('global-loader');
    if (loader) {
        loader.remove();
    }
}

// Auto initialize components
document.addEventListener('DOMContentLoaded', function() {
    // Auto-close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-button')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });

    // Add active state to current page in navigation
    const currentPath = window.location.pathname;
    document.querySelectorAll('nav a').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});