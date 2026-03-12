// assets/js/form-validation.js - Validasi form untuk produk dan user

class FormValidator {
    constructor() {
        this.init();
    }

    init() {
        this.setupProductFormValidation();
        this.setupUserFormValidation();
        this.setupTransactionFormValidation();
        this.setupGeneralFormValidation();
    }

    // Validasi form produk
    setupProductFormValidation() {
        const forms = document.querySelectorAll('form[data-validate="product"]');
        
        forms.forEach(form => {
            // Real-time validation
            this.setupRealTimeValidation(form);
            
            // Form submission validation
            form.addEventListener('submit', (e) => {
                if (!this.validateProductForm(form)) {
                    e.preventDefault();
                    this.showFormError(form, 'Harap perbaiki error di form sebelum menyimpan');
                }
            });
        });
    }

    // Validasi form user
    setupUserFormValidation() {
        const forms = document.querySelectorAll('form[data-validate="user"]');
        
        forms.forEach(form => {
            this.setupRealTimeValidation(form);
            
            form.addEventListener('submit', (e) => {
                if (!this.validateUserForm(form)) {
                    e.preventDefault();
                    this.showFormError(form, 'Harap perbaiki error di form sebelum menyimpan');
                }
            });
        });
    }

    // Validasi form transaksi
    setupTransactionFormValidation() {
        const forms = document.querySelectorAll('form[data-validate="transaction"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateTransactionForm(form)) {
                    e.preventDefault();
                    this.showFormError(form, 'Harap periksa data transaksi');
                }
            });
        });
    }

    // Validasi form umum
    setupGeneralFormValidation() {
        const forms = document.querySelectorAll('form:not([data-validate])');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateGeneralForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    // Real-time validation
    setupRealTimeValidation(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Validate on blur
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
            
            // Clear error on input
            input.addEventListener('input', () => {
                if (this.isFieldValid(input)) {
                    this.clearFieldError(input);
                }
            });
        });
    }

    // Validasi form produk
    validateProductForm(form) {
        let isValid = true;
        
        // Nama produk (required, min 2 chars)
        const namaProduk = form.querySelector('[name="nama_produk"]');
        if (!this.validateRequired(namaProduk) || !this.validateMinLength(namaProduk, 2)) {
            isValid = false;
        }
        
        // Harga (required, numeric, min 1)
        const harga = form.querySelector('[name="harga"]');
        if (!this.validateRequired(harga) || !this.validateNumeric(harga) || !this.validateMinValue(harga, 1)) {
            isValid = false;
        }
        
        // Stok (required, numeric, min 0)
        const stok = form.querySelector('[name="stok"]');
        if (!this.validateRequired(stok) || !this.validateNumeric(stok) || !this.validateMinValue(stok, 0)) {
            isValid = false;
        }
        
        // Stok minimum (required, numeric, min 1)
        const stokMinimum = form.querySelector('[name="stok_minimum"]');
        if (!this.validateRequired(stokMinimum) || !this.validateNumeric(stokMinimum) || !this.validateMinValue(stokMinimum, 1)) {
            isValid = false;
        }
        
        // Barcode (optional, but must be unique)
        const barcode = form.querySelector('[name="barcode"]');
        if (barcode && barcode.value && !this.validateBarcode(barcode)) {
            isValid = false;
        }
        
        return isValid;
    }

    // Validasi form user
    validateUserForm(form) {
        let isValid = true;
        
        // Username (required, min 3 chars, alphanumeric)
        const username = form.querySelector('[name="username"]');
        if (!this.validateRequired(username) || !this.validateMinLength(username, 3) || !this.validateAlphanumeric(username)) {
            isValid = false;
        }
        
        // Password (required for new user, min 6 chars)
        const password = form.querySelector('[name="password"]');
        const isEdit = form.querySelector('[name="user_id"]');
        
        if (!isEdit || password.value) {
            if (!this.validateRequired(password) || !this.validateMinLength(password, 6)) {
                isValid = false;
            }
        }
        
        // Nama lengkap (required, min 2 chars)
        const namaLengkap = form.querySelector('[name="nama_lengkap"]');
        if (!this.validateRequired(namaLengkap) || !this.validateMinLength(namaLengkap, 2)) {
            isValid = false;
        }
        
        // Role (required)
        const role = form.querySelector('[name="role"]');
        if (!this.validateRequired(role)) {
            isValid = false;
        }
        
        return isValid;
    }

    // Validasi form transaksi
    validateTransactionForm(form) {
        let isValid = true;
        
        // Check if cart has items
        const cartItems = form.querySelectorAll('[data-cart-item]');
        if (cartItems.length === 0) {
            this.showFormError(form, 'Keranjang belanja kosong. Tambahkan produk terlebih dahulu.');
            isValid = false;
        }
        
        // Check payment amount
        const jumlahBayar = form.querySelector('[name="jumlah_bayar"]');
        const total = form.querySelector('[data-total]');
        
        if (jumlahBayar && total) {
            const bayar = this.parseCurrency(jumlahBayar.value);
            const totalAmount = parseFloat(total.dataset.total);
            
            if (bayar < totalAmount) {
                this.showFieldError(jumlahBayar, 'Jumlah bayar kurang dari total');
                isValid = false;
            }
        }
        
        return isValid;
    }

    // Validasi form umum
    validateGeneralForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!this.validateRequired(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    // Validasi field individual
    validateField(field) {
        let isValid = true;
        
        // Required validation
        if (field.hasAttribute('required') && !this.validateRequired(field)) {
            isValid = false;
        }
        
        // Type-specific validations
        if (field.type === 'email' && field.value && !this.validateEmail(field)) {
            isValid = false;
        }
        
        if (field.dataset.validation === 'numeric' && field.value && !this.validateNumeric(field)) {
            isValid = false;
        }
        
        if (field.dataset.minLength && !this.validateMinLength(field, parseInt(field.dataset.minLength))) {
            isValid = false;
        }
        
        if (field.dataset.minValue && !this.validateMinValue(field, parseFloat(field.dataset.minValue))) {
            isValid = false;
        }
        
        if (isValid) {
            this.clearFieldError(field);
            this.showFieldSuccess(field);
        }
        
        return isValid;
    }

    // Validation methods
    validateRequired(field) {
        const isValid = field.value.trim() !== '';
        if (!isValid) {
            this.showFieldError(field, 'Field ini wajib diisi');
        }
        return isValid;
    }

    validateEmail(field) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(field.value);
        if (!isValid) {
            this.showFieldError(field, 'Format email tidak valid');
        }
        return isValid;
    }

    validateNumeric(field) {
        const isValid = !isNaN(parseFloat(field.value)) && isFinite(field.value);
        if (!isValid) {
            this.showFieldError(field, 'Harus berupa angka');
        }
        return isValid;
    }

    validateMinLength(field, minLength) {
        const isValid = field.value.length >= minLength;
        if (!isValid) {
            this.showFieldError(field, `Minimal ${minLength} karakter`);
        }
        return isValid;
    }

    validateMinValue(field, minValue) {
        const value = parseFloat(field.value);
        const isValid = !isNaN(value) && value >= minValue;
        if (!isValid) {
            this.showFieldError(field, `Nilai minimal adalah ${minValue}`);
        }
        return isValid;
    }

    validateAlphanumeric(field) {
        const regex = /^[a-zA-Z0-9_]+$/;
        const isValid = regex.test(field.value);
        if (!isValid) {
            this.showFieldError(field, 'Hanya boleh mengandung huruf, angka, dan underscore');
        }
        return isValid;
    }

    validateBarcode(field) {
        const regex = /^[a-zA-Z0-9\-_]+$/;
        const isValid = regex.test(field.value);
        if (!isValid) {
            this.showFieldError(field, 'Format barcode tidak valid');
        }
        return isValid;
    }

    // UI methods
    showFieldError(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('border-red-500', 'focus:border-red-500');
        field.classList.remove('border-green-500', 'focus:border-green-500');
        
        const errorElement = document.createElement('div');
        errorElement.className = 'text-red-500 text-xs mt-1';
        errorElement.textContent = message;
        errorElement.dataset.fieldError = field.name;
        
        field.parentNode.appendChild(errorElement);
        
        // Scroll to error field
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        field.focus();
    }

    showFieldSuccess(field) {
        field.classList.remove('border-red-500', 'focus:border-red-500');
        field.classList.add('border-green-500', 'focus:border-green-500');
    }

    clearFieldError(field) {
        field.classList.remove('border-red-500', 'focus:border-red-500');
        
        const existingError = field.parentNode.querySelector(`[data-field-error="${field.name}"]`);
        if (existingError) {
            existingError.remove();
        }
    }

    showFormError(form, message) {
        // Remove existing form errors
        const existingErrors = form.querySelectorAll('.form-error-message');
        existingErrors.forEach(error => error.remove());
        
        // Add new error message
        const errorElement = document.createElement('div');
        errorElement.className = 'form-error-message bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-4';
        errorElement.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        form.insertBefore(errorElement, form.firstChild);
        
        // Scroll to error
        errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    isFieldValid(field) {
        return !field.parentNode.querySelector(`[data-field-error="${field.name}"]`);
    }

    parseCurrency(currencyString) {
        return parseFloat(currencyString.replace(/[^\d]/g, '')) || 0;
    }
}

// Initialize form validator ketika DOM ready
document.addEventListener('DOMContentLoaded', function() {
    window.formValidator = new FormValidator();
});

// Utility functions untuk validasi
function validateField(field) {
    if (window.formValidator) {
        return window.formValidator.validateField(field);
    }
    return true;
}

function clearFieldError(field) {
    if (window.formValidator) {
        window.formValidator.clearFieldError(field);
    }
}

// Auto-initialize validation untuk required fields
document.addEventListener('DOMContentLoaded', function() {
    const requiredFields = document.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        field.addEventListener('invalid', function(e) {
            e.preventDefault();
            if (window.formValidator) {
                window.formValidator.validateField(this);
            }
        });
    });
});