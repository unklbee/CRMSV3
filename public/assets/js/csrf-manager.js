/**
 * Global CSRF Token Manager
 * File: public/assets/js/csrf-manager.js
 */
class CSRFManager {
    constructor() {
        this.config = window.AppConfig?.csrf || {};
        this.token = this.config.token || this.detectToken();
        this.hash = this.config.hash || this.detectHash();
        this.headerName = this.config.headerName || 'X-CSRF-TOKEN';
        this.initialized = false;

        this.init();
    }

    /**
     * Initialize CSRF Manager
     */
    init() {
        if (this.initialized) return;

        // Detect token and hash if not provided
        if (!this.token) this.token = this.detectToken();
        if (!this.hash) this.hash = this.detectHash();

        // Validate we have required values
        if (!this.token || !this.hash) {
            this.createFallbackValues();
        }

        this.initialized = true;
    }

    /**
     * Detect CSRF token from various sources
     */
    detectToken() {
        // Try global variables first
        if (typeof window.csrf_token !== 'undefined' && window.csrf_token) {
            return window.csrf_token;
        }

        // Try meta tag
        const metaTag = document.querySelector('meta[name*="csrf"]');
        if (metaTag) {
            return metaTag.getAttribute('name');
        }

        // Try form input
        const formInput = document.querySelector('input[name*="csrf"]');
        if (formInput) {
            return formInput.name;
        }

        // Default CodeIgniter 4 name
        return 'csrf_test_name';
    }

    /**
     * Detect CSRF hash from various sources
     */
    detectHash() {
        // Try global variables first
        if (typeof window.csrf_hash !== 'undefined' && window.csrf_hash) {
            return window.csrf_hash;
        }

        // Try meta tag
        const metaTag = document.querySelector('meta[name*="csrf"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }

        // Try form input
        const formInput = document.querySelector('input[name*="csrf"]');
        if (formInput) {
            return formInput.value;
        }

        return '';
    }

    /**
     * Create fallback values if detection fails
     */
    createFallbackValues() {
        if (!this.token) {
            this.token = 'csrf_test_name'; // Default CI4 token name
        }

        if (!this.hash) {
            // Generate a temporary hash (will be replaced by server)
            this.hash = 'temp_' + Math.random().toString(36).substr(2, 9);
        }
    }

    /**
     * Get current CSRF token name
     */
    getToken() {
        return this.token;
    }

    /**
     * Get current CSRF hash value
     */
    getHash() {
        return this.hash;
    }

    /**
     * Update CSRF token and hash
     */
    updateToken(newToken, newHash) {
        if (!newToken || !newHash) {
            return false;
        }

        this.token = newToken;
        this.hash = newHash;

        // Update global config
        if (window.AppConfig && window.AppConfig.csrf) {
            window.AppConfig.csrf.token = newToken;
            window.AppConfig.csrf.hash = newHash;
        }

        // Update global variables
        if (typeof window.csrf_token !== 'undefined') {
            window.csrf_token = newToken;
        }
        if (typeof window.csrf_hash !== 'undefined') {
            window.csrf_hash = newHash;
        }

        // Update meta tag
        this.updateMetaTag(newToken, newHash);

        // Update all forms
        this.updateAllForms();

        return true;
    }

    /**
     * Update meta tag
     */
    updateMetaTag(newToken, newHash) {
        let metaTag = document.querySelector(`meta[name="${this.token}"]`);

        if (metaTag) {
            // Update existing meta tag
            metaTag.setAttribute('name', newToken);
            metaTag.setAttribute('content', newHash);
        } else {
            // Create new meta tag if it doesn't exist
            metaTag = document.createElement('meta');
            metaTag.setAttribute('name', newToken);
            metaTag.setAttribute('content', newHash);
            document.head.appendChild(metaTag);
        }
    }

    /**
     * Update CSRF token in all forms
     */
    updateAllForms() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            let csrfInput = form.querySelector(`input[name="${this.token}"]`);

            if (csrfInput) {
                // Update existing input
                csrfInput.value = this.hash;
            } else {
                // Create new input if it doesn't exist
                csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = this.token;
                csrfInput.value = this.hash;
                form.appendChild(csrfInput);
            }
        });
    }

    /**
     * Get CSRF headers for fetch requests
     */
    getHeaders() {
        const headers = {};
        headers[this.headerName] = this.hash;
        headers['X-Requested-With'] = 'XMLHttpRequest';
        return headers;
    }

    /**
     * Add CSRF to FormData
     */
    addToFormData(formData) {
        if (!formData || typeof formData.append !== 'function') {
            return formData;
        }

        formData.append(this.token, this.hash);
        return formData;
    }

    /**
     * Get CSRF as object for JSON requests
     */
    getTokenObject() {
        const obj = {};
        obj[this.token] = this.hash;
        return obj;
    }

    /**
     * Refresh token from server
     */
    async refreshToken() {
        try {

            const response = await fetch('/auth/getCsrfToken', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.csrf_token && data.csrf_hash) {
                    this.updateToken(data.csrf_token, data.csrf_hash);
                    return true;
                }
            }
        } catch (error) {
        }
        return false;
    }

    /**
     * Handle CSRF error (403)
     */
    async handleCsrfError() {
        const success = await this.refreshToken();

        return success;
    }

    /**
     * Validate current token (check if it exists and looks valid)
     */
    isValid() {
        return this.token && this.hash && this.hash.length > 10;
    }

    /**
     * Get debug information
     */
    getDebugInfo() {
        return {
            initialized: this.initialized,
            token: this.token,
            hashLength: this.hash ? this.hash.length : 0,
            hashPreview: this.hash ? this.hash.substr(0, 10) + '...' : 'none',
            isValid: this.isValid()
        };
    }
}

// Initialize global instance
window.CSRFManager = new CSRFManager();

// Auto-refresh token on page focus (optional)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && window.CSRFManager) {
        // Optionally refresh token when user returns to page
        // Uncomment if needed:
        // setTimeout(() => window.CSRFManager.refreshToken(), 1000);
    }
});