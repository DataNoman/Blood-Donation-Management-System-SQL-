async function handleDonorRegistration(e) {
    e.preventDefault();
    
    if (!validateCurrentStep()) {
        return;
    }
    
    showLoading(true);
    
    // Collect all form data
    registrationData = {
        type: 'donor',
        firstName: document.getElementById('donorFirstName').value,
        lastName: document.getElementById('donorLastName').value,
        email: document.getElementById('donorEmail').value,
        phone: document.getElementById('donorPhone').value,
        dateOfBirth: document.getElementById('donorDOB').value,
        gender: document.getElementById('donorGender').value,
        bloodGroup: document.getElementById('donorBloodGroup').value,
        weight: document.getElementById('donorWeight').value,
        lastDonationDate: document.getElementById('lastDonationDate').value,
        division: document.getElementById('donorDivision').value,
        district: document.getElementById('donorDistrict').value,
        address: document.getElementById('donorAddress').value,
        password: document.getElementById('donorPassword').value,
        medicalConditions: getSelectedConditions(),
        availability: getSelectedAvailability(),
        agreeTerms: document.getElementById('agreeTerms').checked,
        agreeNotifications: document.getElementById('agreeNotifications').checked
    };
    
    try {
        // Simulate API call
        await simulateApiCall(2500);
        
        // Show OTP verification
        showOtpModal(registrationData.phone);
        
    } catch (error) {
        showToast('Registration failed. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

async function handleRequesterRegistration(e) {
    e.preventDefault();
    
    if (!requesterRegisterForm.checkValidity()) {
        showToast('Please fill all required fields correctly', 'error');
        return;
    }
    
    showLoading(true);
    
    const formData = {
        type: 'requester',
        firstName: document.getElementById('requesterFirstName').value,
        lastName: document.getElementById('requesterLastName').value,
        email: document.getElementById('requesterEmail').value,
        phone: document.getElementById('requesterPhone').value,
        division: document.getElementById('requesterDivision').value,
        district: document.getElementById('requesterDistrict').value,
        password: document.getElementById('requesterPassword').value,
        agreeTerms: document.getElementById('requesterAgreeTerms').checked
    };
    
    // Password confirmation check
    const confirmPassword = document.getElementById('requesterConfirmPassword').value;
    if (formData.password !== confirmPassword) {
        showToast('Passwords do not match', 'error');
        showLoading(false);
        return;
    }
    
    try {
        // Simulate API call
        await simulateApiCall(2000);
        
        showToast('Registration successful! Please check your email for verification.', 'success');
        
        // Store user data
        const userData = {
            id: Date.now(),
            ...formData,
            verified: false
        };
        
        sessionStorage.setItem('bloodConnectUser', JSON.stringify(userData));
        
        // Redirect to dashboard
        setTimeout(() => {
            window.location.href = 'user-dashboard.html';
        }, 2000);
        
    } catch (error) {
        showToast('Registration failed. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

async function handleForgotPassword(e) {
    e.preventDefault();
    showLoading(true);
    
    const email = document.getElementById('resetEmail').value;
    
    try {
        // Simulate API call
        await simulateApiCall(1500);
        
        showToast('Password reset link sent to your email!', 'success');
        
        // Switch back to login after 3 seconds
        setTimeout(() => {
            showForm('login');
        }, 3000);
        
    } catch (error) {
        showToast('Failed to send reset link. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

// OTP Verification functions
function showOtpModal(phoneNumber) {
    otpPhoneNumber.textContent = phoneNumber;
    otpModal.style.display = 'block';
    startOtpTimer();
    
    // Focus first input
    otpInputs[0].focus();
}

function closeOtpModal() {
    otpModal.style.display = 'none';
    clearOtpTimer();
    resetOtpInputs();
}

function setupOtpInputs() {
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            const value = e.target.value;
            
            // Only allow digits
            if (!/^\d$/.test(value)) {
                e.target.value = '';
                return;
            }
            
            // Move to next input
            if (value && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });
        
        input.addEventListener('keydown', function(e) {
            // Move to previous input on backspace
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text');
            const digits = pasteData.replace(/\D/g, '').slice(0, 6);
            
            digits.split('').forEach((digit, i) => {
                if (otpInputs[i]) {
                    otpInputs[i].value = digit;
                }
            });
            
            // Focus last filled input or first empty
            const lastIndex = Math.min(digits.length - 1, otpInputs.length - 1);
            otpInputs[lastIndex].focus();
        });
    });
}

async function handleOtpVerification(e) {
    e.preventDefault();
    
    const otpCode = Array.from(otpInputs).map(input => input.value).join('');
    
    if (otpCode.length !== 6) {
        showToast('Please enter the complete 6-digit code', 'error');
        return;
    }
    
    showLoading(true);
    
    try {
        // Simulate API call
        await simulateApiCall(1500);
        
        // Verify OTP (mock verification)
        if (otpCode === '123456' || Math.random() > 0.3) {
            // Create user account
            const userData = {
                id: Date.now(),
                ...registrationData,
                verified: true,
                registrationDate: new Date().toISOString()
            };
            
            // Store user data
            sessionStorage.setItem('bloodConnectUser', JSON.stringify(userData));
            
            closeOtpModal();
            showToast('Phone verified successfully! Registration complete.', 'success');
            
            // Redirect to dashboard
            setTimeout(() => {
                window.location.href = 'user-dashboard.html';
            }, 2000);
            
        } else {
            showToast('Invalid verification code. Please try again.', 'error');
        }
        
    } catch (error) {
        showToast('Verification failed. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

function startOtpTimer() {
    let timeLeft = 60;
    otpTimer.textContent = timeLeft;
    
    otpTimerInterval = setInterval(() => {
        timeLeft--;
        otpTimer.textContent = timeLeft;
        
        if (timeLeft <= 0) {
            clearOtpTimer();
            resendOtp.style.display = 'inline-block';
        }
    }, 1000);
}

function clearOtpTimer() {
    if (otpTimerInterval) {
        clearInterval(otpTimerInterval);
        otpTimerInterval = null;
    }
}

function resetOtpInputs() {
    otpInputs.forEach(input => input.value = '');
}

async function handleResendOtp() {
    showLoading(true);
    
    try {
        await simulateApiCall(1000);
        showToast('New verification code sent!', 'success');
        resendOtp.style.display = 'none';
        resetOtpInputs();
        startOtpTimer();
    } catch (error) {
        showToast('Failed to resend code. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

// Helper functions
function getSelectedConditions() {
    const conditions = [];
    document.querySelectorAll('input[name="conditions"]:checked').forEach(checkbox => {
        conditions.push(checkbox.value);
    });
    return conditions;
}

function getSelectedAvailability() {
    const availability = [];
    document.querySelectorAll('input[name="availability"]:checked').forEach(checkbox => {
        availability.push(checkbox.value);
    });
    return availability;
}

function calculateAge(dateOfBirth) {
    const today = new Date();
    const birthDate = new Date(dateOfBirth);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age;
}

function daysBetween(date1, date2) {
    const oneDay = 24 * 60 * 60 * 1000;
    return Math.round(Math.abs((date1 - date2) / oneDay));
}

// Password functions
function togglePasswordVisibility(e) {
    const button = e.target.closest('.password-toggle');
    const input = button.previousElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function checkPasswordStrength(e) {
    const password = e.target.value;
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text span');
    
    let score = 0;
    let feedback = 'Weak';
    
    // Length check
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    
    // Character variety checks
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    // Set strength level
    if (score >= 5) {
        feedback = 'Strong';
        strengthBar.className = 'strength-bar strong';
    } else if (score >= 3) {
        feedback = 'Good';
        strengthBar.className = 'strength-bar good';
    } else if (score >= 2) {
        feedback = 'Fair';
        strengthBar.className = 'strength-bar fair';
    } else {
        feedback = 'Weak';
        strengthBar.className = 'strength-bar weak';
    }
    
    strengthText.textContent = feedback;
}

// Medical conditions handling
function setupMedicalConditions() {
    const noConditionsCheckbox = document.getElementById('noConditions');
    const otherConditions = document.querySelectorAll('input[name="conditions"]:not(#noConditions)');
    
    if (noConditionsCheckbox) {
        noConditionsCheckbox.addEventListener('change', function() {
            if (this.checked) {
                otherConditions.forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                });
            } else {
                otherConditions.forEach(checkbox => {
                    checkbox.disabled = false;
                });
            }
        });
        
        otherConditions.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    noConditionsCheckbox.checked = false;
                }
            });
        });
    }
}

// Social authentication
async function handleSocialAuth(e) {
    e.preventDefault();
    const provider = e.target.closest('.social-btn').classList.contains('google') ? 'Google' : 'Facebook';
    
    showLoading(true);
    
    try {
        await simulateApiCall(2000);
        showToast(`${provider} authentication is not available in demo mode`, 'info');
    } catch (error) {
        showToast(`${provider} authentication failed`, 'error');
    } finally {
        showLoading(false);
    }
}

// Real-time validation
function setupRealTimeValidation() {
    const inputs = document.querySelectorAll('input[required], select[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim()) {
                validateField(this);
            }
        });
        
        input.addEventListener('input', function() {
            // Remove error styling on input
            this.classList.remove('error');
            removeFieldError(this);
        });
    });
}

// Initialize validation
function initializeValidation() {
    // Add custom validation styles
    const style = document.createElement('style');
    style.textContent = `
        .input-wrapper input.error,
        .input-wrapper select.error {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
        }
        
        .field-error {
            color: var(--danger-color);
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .field-error::before {
            content: '⚠';
            font-size: 0.9rem;
        }
    `;
    document.head.appendChild(style);
}

// Utility functions
function showLoading(show) {
    if (show) {
        loadingOverlay.style.display = 'flex';
    } else {
        loadingOverlay.style.display = 'none';
    }
}

function showToast(message, type = 'success') {
    toastMessage.textContent = message;
    toast.className = `toast ${type}`;
    
    // Update icon based on type
    const icon = toast.querySelector('i');
    switch(type) {
        case 'success':
            icon.className = 'fas fa-check-circle';
            break;
        case 'error':
            icon.className = 'fas fa-times-circle';
            break;
        case 'warning':
            icon.className = 'fas fa-exclamation-triangle';
            break;
        case 'info':
            icon.className = 'fas fa-info-circle';
            break;
    }
    
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

function simulateApiCall(delay) {
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            // 90% success rate for demo
            if (Math.random() > 0.1) {
                resolve();
            } else {
                reject(new Error('API Error'));
            }
        }, delay);
    });
}

// Email validation for Bangladeshi domains
function validateBangladeshiEmail(email) {
    const bangladeshiDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'bracu.ac.bd', 'du.ac.bd', 'buet.ac.bd'];
    const domain = email.split('@')[1];
    return bangladeshiDomains.includes(domain) || domain.endsWith('.bd');
}

// Phone number formatting
function formatPhoneNumber(phone) {
    const cleaned = phone.replace(/\D/g, '');
    
    if (cleaned.startsWith('88')) {
        return '+' + cleaned;
    } else if (cleaned.startsWith('01')) {
        return '+88' + cleaned;
    }
    
    return phone;
}

// Initialize form with sample data (for demo purposes)
function fillSampleData() {
    if (window.location.search.includes('demo=true')) {
        // Fill donor registration form
        document.getElementById('donorFirstName').value = 'Ahmed';
        document.getElementById('donorLastName').value = 'Rahman';
        document.getElementById('donorEmail').value = 'ahmed.rahman@gmail.com';
        document.getElementById('donorPhone').value = '01712345678';
        document.getElementById('donorDOB').value = '1995-06-15';
        document.getElementById('donorGender').value = 'male';
        document.getElementById('donorBloodGroup').value = 'O+';
        document.getElementById('donorWeight').value = '70';
        document.getElementById('donorDivision').value = 'dhaka';
        document.getElementById('donorDistrict').value = 'Dhaka';
        document.getElementById('donorAddress').value = 'Bashundhara R/A, Dhaka';
        document.getElementById('donorPassword').value = 'SecurePass123!';
        document.getElementById('donorConfirmPassword').value = 'SecurePass123!';
        
        // Check sample availability
        document.getElementById('weekdays').checked = true;
        document.getElementById('anytime').checked = true;
        document.getElementById('agreeTerms').checked = true;
        document.getElementById('agreeNotifications').checked = true;
    }
}

// Call sample data function on load (for demo)
setTimeout(fillSampleData, 1000);

// Export functions for global access
window.AuthSystem = {
    showForm,
    showToast,
    showLoading
};

// Setup all event listeners
function setupEventListeners() {
    // Form navigation
    showRegisterDonor.addEventListener('click', (e) => {
        e.preventDefault();
        showForm('registerDonor');
    });
    
    showRegisterRequester.addEventListener('click', (e) => {
        e.preventDefault();
        showForm('registerRequester');
    });
    
    showLoginFromDonor.addEventListener('click', (e) => {
        e.preventDefault();
        showForm('login');
    });
    
    showLoginFromRequester.addEventListener('click', (e) => {
        e.preventDefault();
        showForm('login');
    });
    
    showLoginFromForgot.addEventListener('click', (e) => {
        e.preventDefault();
        showForm('login');
    });
    
    forgotPasswordLink.addEventListener('click', (e) => {
        e.preventDefault();
        showForm('forgotPassword');
    });
    
    // Form submissions
    loginForm.addEventListener('submit', handleLogin);
    donorRegisterForm.addEventListener('submit', handleDonorRegistration);
    requesterRegisterForm.addEventListener('submit', handleRequesterRegistration);
    forgotPasswordForm.addEventListener('submit', handleForgotPassword);
    otpForm.addEventListener('submit', handleOtpVerification);
    
    // Multi-step form navigation
    nextStepBtn.addEventListener('click', handleNextStep);
    prevStepBtn.addEventListener('click', handlePrevStep);
    
    // Password toggles
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', togglePasswordVisibility);
    });
    
    // Password strength checker
    const donorPassword = document.getElementById('donorPassword');
    if (donorPassword) {
        donorPassword.addEventListener('input', checkPasswordStrength);
    }
    
    // OTP input handling
    setupOtpInputs();
    
    // Medical conditions handling
    setupMedicalConditions();
    
    // Close modal handlers
    closeOtp.addEventListener('click', closeOtpModal);
    resendOtp.addEventListener('click', handleResendOtp);
    
    // Social auth buttons
    document.querySelectorAll('.social-btn').forEach(btn => {
        btn.addEventListener('click', handleSocialAuth);
    });
    
    // Real-time validation
    setupRealTimeValidation();
}

// Form switching functions
function showForm(formType) {
    // Hide all forms
    document.querySelectorAll('.auth-form-container').forEach(container => {
        container.classList.remove('active');
    });
    
    // Show target form
    let targetContainer;
    switch(formType) {
        case 'login':
            targetContainer = loginContainer;
            currentForm = 'login';
            break;
        case 'registerDonor':
            targetContainer = registerDonorContainer;
            currentForm = 'registerDonor';
            resetMultiStepForm();
            break;
        case 'registerRequester':
            targetContainer = registerRequesterContainer;
            currentForm = 'registerRequester';
            break;
        case 'forgotPassword':
            targetContainer = forgotPasswordContainer;
            currentForm = 'forgotPassword';
            break;
    }
    
    if (targetContainer) {
        targetContainer.classList.add('active');
    }
}

// Multi-step form functions
function resetMultiStepForm() {
    currentStep = 1;
    updateStepDisplay();
    updateNavigationButtons();
}

function handleNextStep() {
    if (validateCurrentStep()) {
        if (currentStep < maxSteps) {
            currentStep++;
            updateStepDisplay();
            updateNavigationButtons();
        }
    }
}

function handlePrevStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepDisplay();
        updateNavigationButtons();
    }
}

function updateStepDisplay() {
    // Hide all steps
    formSteps.forEach(step => step.classList.remove('active'));
    stepIndicators.forEach(indicator => {
        indicator.classList.remove('active', 'completed');
    });
    
    // Show current step
    const currentStepElement = document.querySelector(`[data-step="${currentStep}"]`);
    if (currentStepElement && currentStepElement.classList.contains('form-step')) {
        currentStepElement.classList.add('active');
    }
    
    // Update step indicators
    stepIndicators.forEach((indicator, index) => {
        const stepNumber = index + 1;
        if (stepNumber < currentStep) {
            indicator.classList.add('completed');
        } else if (stepNumber === currentStep) {
            indicator.classList.add('active');
        }
    });
}

function updateNavigationButtons() {
    prevStepBtn.style.display = currentStep === 1 ? 'none' : 'flex';
    
    if (currentStep === maxSteps) {
        nextStepBtn.style.display = 'none';
        submitDonorBtn.style.display = 'flex';
    } else {
        nextStepBtn.style.display = 'flex';
        submitDonorBtn.style.display = 'none';
    }
}

// Form validation functions
function validateCurrentStep() {
    const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
    if (!currentStepElement) return false;
    
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    // Additional step-specific validations
    switch(currentStep) {
        case 1:
            isValid = isValid && validateBasicInfo();
            break;
        case 2:
            isValid = isValid && validateMedicalInfo();
            break;
        case 3:
            isValid = isValid && validateLocationInfo();
            break;
        case 4:
            isValid = isValid && validateSecurityInfo();
            break;
    }
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Remove previous error styling
    field.classList.remove('error');
    removeFieldError(field);
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Field-specific validations
    if (value && field.type === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }
    
    if (value && field.type === 'tel') {
        const phoneRegex = /^(\+88)?01[3-9]\d{8}$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid Bangladeshi phone number';
        }
    }
    
    if (value && field.id.includes('Password') && field.id !== 'donorConfirmPassword' && field.id !== 'requesterConfirmPassword') {
        if (value.length < 8) {
            isValid = false;
            errorMessage = 'Password must be at least 8 characters long';
        }
    }
    
    if (field.id === 'donorConfirmPassword' || field.id === 'requesterConfirmPassword') {
        const passwordField = field.id === 'donorConfirmPassword' ? 
            document.getElementById('donorPassword') : 
            document.getElementById('requesterPassword');
        
        if (value !== passwordField.value) {
            isValid = false;
            errorMessage = 'Passwords do not match';
        }
    }
    
    // Show error if invalid
    if (!isValid) {
        field.classList.add('error');
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    const inputWrapper = field.closest('.input-wrapper');
    if (inputWrapper) {
        inputWrapper.appendChild(errorElement);
    }
}

function removeFieldError(field) {
    const inputWrapper = field.closest('.input-wrapper');
    if (inputWrapper) {
        const existingError = inputWrapper.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
}

// Step-specific validation functions
function validateBasicInfo() {
    const firstName = document.getElementById('donorFirstName').value.trim();
    const lastName = document.getElementById('donorLastName').value.trim();
    const age = calculateAge(document.getElementById('donorDOB').value);
    
    if (age < 18 || age > 65) {
        showToast('Donors must be between 18 and 65 years old', 'error');
        return false;
    }
    
    return true;
}

function validateMedicalInfo() {
    const weight = parseInt(document.getElementById('donorWeight').value);
    
    if (weight < 45) {
        showToast('Minimum weight requirement is 45 kg for blood donation', 'error');
        return false;
    }
    
    const lastDonation = document.getElementById('lastDonationDate').value;
    if (lastDonation) {
        const daysSinceLastDonation = daysBetween(new Date(lastDonation), new Date());
        if (daysSinceLastDonation < 90) {
            showToast('You must wait at least 90 days between donations', 'error');
            return false;
        }
    }
    
    return true;
}

function validateLocationInfo() {
    return true; // Basic validation is handled by required fields
}

function validateSecurityInfo() {
    const termsCheckbox = document.getElementById('agreeTerms');
    if (!termsCheckbox.checked) {
        showToast('You must agree to the terms and conditions', 'error');
        return false;
    }
    
    return true;
}

// Authentication handlers
async function handleLogin(e) {
    e.preventDefault();
    showLoading(true);
    
    const formData = {
        email: document.getElementById('loginEmail').value,
        password: document.getElementById('loginPassword').value,
        remember: document.getElementById('rememberMe').checked
    };
    
    try {
        // Simulate API call
        await simulateApiCall(1500);
        
        // Mock successful login
        const userData = {
            id: Date.now(),
            email: formData.email,
            name: 'John Doe',
            type: 'donor',
            bloodGroup: 'O+',
            location: 'Dhaka',
            verified: true
        };
        
        // Store user data
        const storage = formData.remember ? localStorage : sessionStorage;
        storage.setItem('bloodConnectUser', JSON.stringify(userData));
        
        showToast('Login successful! Redirecting...', 'success');
        
        // Redirect based on user type
        setTimeout(() => {
            if (userData.type === 'admin') {
                window.location.href = 'admin-dashboard.html';
            } else {
                window.location.href = 'user-dashboard.html';
            }
        }, 2000);
        
    } catch (error) {
        showToast('Invalid email or password. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}// Authentication System JavaScript

// DOM Elements
const loginContainer = document.getElementById('loginContainer');
const registerDonorContainer = document.getElementById('registerDonorContainer');
const registerRequesterContainer = document.getElementById('registerRequesterContainer');
const forgotPasswordContainer = document.getElementById('forgotPasswordContainer');

// Forms
const loginForm = document.getElementById('loginForm');
const donorRegisterForm = document.getElementById('donorRegisterForm');
const requesterRegisterForm = document.getElementById('requesterRegisterForm');
const forgotPasswordForm = document.getElementById('forgotPasswordForm');
const otpForm = document.getElementById('otpForm');

// Navigation buttons
const showRegisterDonor = document.getElementById('showRegisterDonor');
const showRegisterRequester = document.getElementById('showRegisterRequester');
const showLoginFromDonor = document.getElementById('showLoginFromDonor');
const showLoginFromRequester = document.getElementById('showLoginFromRequester');
const showLoginFromForgot = document.getElementById('showLoginFromForgot');
const forgotPasswordLink = document.getElementById('forgotPasswordLink');

// Multi-step form elements
const formSteps = document.querySelectorAll('.form-step');
const stepIndicators = document.querySelectorAll('.step');
const nextStepBtn = document.getElementById('nextStep');
const prevStepBtn = document.getElementById('prevStep');
const submitDonorBtn = document.getElementById('submitDonor');

// Password toggles
const passwordToggles = document.querySelectorAll('.password-toggle');

// Other elements
const loadingOverlay = document.getElementById('loadingOverlay');
const toast = document.getElementById('toast');
const toastMessage = document.getElementById('toastMessage');
const otpModal = document.getElementById('otpModal');
const closeOtp = document.getElementById('closeOtp');
const otpInputs = document.querySelectorAll('.otp-input');
const otpTimer = document.getElementById('otpTimer');
const resendOtp = document.getElementById('resendOtp');
const otpPhoneNumber = document.getElementById('otpPhoneNumber');

// State management
let currentStep = 1;
let maxSteps = 4;
let currentForm = 'login';
let otpTimerInterval = null;
let registrationData = {};

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {