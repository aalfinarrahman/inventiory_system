/**
 * Login Page JavaScript
 * File: /assets/js/login.js
 * 
 * Enhances the login experience with client-side validation,
 * animations, and user feedback.
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    // Simple login validation
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        form.addEventListener('submit', function(e) {
            if (!usernameInput.value.trim()) {
                alert('Please enter username or email');
                e.preventDefault();
                usernameInput.focus();
                return;
            }
            
            if (!passwordInput.value) {
                alert('Please enter password');
                e.preventDefault();
                passwordInput.focus();
                return;
            }
        });
    });

    // Form validation
    function validateForm() {
        const username = usernameInput.value.trim();
        const password = passwordInput.value;
        
        if (!username) {
            showFieldError(usernameInput, 'Username or email is required');
            return false;
        }
        
        if (!password) {
            showFieldError(passwordInput, 'Password is required');
            return false;
        }
        
        if (password.length < 3) {
            showFieldError(passwordInput, 'Password is too short');
            return false;
        }
        
        return true;
    }

    // Show field error
    function showFieldError(field, message) {
        field.focus();
        field.style.borderColor = '#ef4444';
        field.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
        
        // Remove error styling after user starts typing
        field.addEventListener('input', function() {
            this.style.borderColor = '#e2e8f0';
            this.style.boxShadow = 'none';
        }, { once: true });
        
        // Show temporary error message
        showNotification(message, 'error');
    }

    // Form submission handler
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            // Add loading state
            loginBtn.classList.add('loading');
            loginBtn.querySelector('span').textContent = 'Signing in...';
            loginBtn.disabled = true;
            
            // Hapus setTimeout yang tidak perlu
            // Form akan langsung submit
        });
    }

    // Input enhancement
    [usernameInput, passwordInput].forEach(input => {
        if (!input) return;
        
        // Remove error styling on focus
        input.addEventListener('focus', function() {
            this.style.borderColor = '#667eea';
            this.style.boxShadow = '0 0 0 4px rgba(102, 126, 234, 0.1)';
        });
        
        // Add subtle animation on blur
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.style.borderColor = '#e2e8f0';
                this.style.boxShadow = 'none';
            }
        });
        
        // Real-time validation feedback
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.borderColor = '#10b981';
            } else {
                this.style.borderColor = '#e2e8f0';
            }
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Enter key anywhere on page submits form
        if (e.key === 'Enter' && !e.shiftKey) {
            const activeElement = document.activeElement;
            if (activeElement.tagName !== 'BUTTON' && loginForm) {
                e.preventDefault();
                loginForm.dispatchEvent(new Event('submit'));
            }
        }
        
        // Escape key clears form
        if (e.key === 'Escape') {
            clearForm();
        }
    });

    // Clear form function
    function clearForm() {
        if (!usernameInput || !passwordInput) return;
        
        usernameInput.value = '';
        passwordInput.value = '';
        usernameInput.focus();
        
        // Reset styles
        [usernameInput, passwordInput].forEach(input => {
            input.style.borderColor = '#e2e8f0';
            input.style.boxShadow = 'none';
        });
        
        showNotification('Form cleared', 'info');
    }

    // Auto-fill demo credentials
    document.querySelectorAll('.credential-item').forEach(item => {
        item.addEventListener('click', function() {
            if (!usernameInput || !passwordInput) return;
            
            const role = this.querySelector('.role').textContent.toLowerCase();
            usernameInput.value = role;
            passwordInput.value = role;
            
            // Add visual feedback
            usernameInput.style.borderColor = '#10b981';
            passwordInput.style.borderColor = '#10b981';
            
            showNotification(`Demo credentials filled for ${role}`, 'success');
            
            // Focus password field for easy submission
            passwordInput.focus();
        });
    });

    // Show notification function
    function showNotification(message, type = 'info') {
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
            background: ${getNotificationColor(type)};
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

    // Get notification color
    function getNotificationColor(type) {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        return colors[type] || colors.info;
    }

    // Success message countdown
    const successAlert = document.querySelector('.alert-success span');
    if (successAlert) {
        let countdown = 3;
        const updateCountdown = () => {
            successAlert.textContent = `Login successful! Redirecting in ${countdown}...`;
            countdown--;
            if (countdown >= 0) {
                setTimeout(updateCountdown, 1000);
            }
        };
        updateCountdown();
    }

    // Animate particles
    const particles = document.querySelectorAll('.particle');
    particles.forEach((particle, index) => {
        const size = Math.random() * 20 + 10;
        const speed = Math.random() * 2 + 1;
        const opacity = Math.random() * 0.5 + 0.3;
        const delay = index * 0.2;
        
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.opacity = opacity;
        particle.style.animation = `float ${speed}s ease-in-out infinite`;
        particle.style.animationDelay = `${delay}s`;
        particle.style.left = `${Math.random() * 100}%`;
        particle.style.top = `${Math.random() * 100}%`;
    });

    // Security: Clear sensitive data on page unload
    window.addEventListener('beforeunload', function() {
        if (passwordInput) {
            passwordInput.value = '';
        }
    });
});