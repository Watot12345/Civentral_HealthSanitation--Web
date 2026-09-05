/**
 * Modal System - Centralized Modal & UI Utilities
 * 
 * UPDATED: Added reusable form validation system
 * 
 * USAGE:
 *   // Form validation
 *   ModalSystem.validateForm('addAppointmentModal', {
 *       fields: {
 *           'add_patient_search': { 
 *               type: 'search', 
 *               hiddenField: 'add_patient_id', 
 *               label: 'Patient' 
 *           },
 *           'add_doctor_search': { 
 *               type: 'search', 
 *               hiddenField: 'add_employee_id', 
 *               label: 'Doctor' 
 *           },
 *           'add_service_type': { label: 'Service Type' },
 *           'add_appointment_date': { label: 'Date' },
 *           'add_appointment_time': { label: 'Time' }
 *       },
 *       submitButtonId: 'submitAddBtn',
 *       onSubmit: saveNewAppointment
 *   });
 */

const ModalSystem = (function() {
    'use strict';

    // ============================================================
    // DATA MASKING INTEGRATION
    // ============================================================
    
    function applyMaskingToModal(modal) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        if (!modal) return;
        
        let shouldBeMasked = true;
        
        if (typeof window.isDataMasked !== 'undefined') {
            shouldBeMasked = window.isDataMasked();
        } else {
            const isMasked = localStorage.getItem('data_masking_enabled');
            shouldBeMasked = isMasked === null ? true : isMasked === 'true';
        }
        
        const maskableElements = modal.querySelectorAll('.maskable');
        maskableElements.forEach(el => {
            if (shouldBeMasked) {
                el.classList.add('masked');
                if (el.tagName === 'INPUT') {
                    el.classList.add('input-maskable');
                    el.style.color = 'transparent';
                }
            } else {
                el.classList.remove('masked');
                el.classList.remove('input-maskable');
                el.style.color = '';
                if (el.tagName === 'INPUT' && el.dataset.real) {
                    el.value = el.dataset.real;
                }
            }
        });
    }

    function toggleMasking() {
        if (typeof window.toggleDataMask !== 'undefined') {
            window.toggleDataMask();
        } else {
            console.warn('Data masking system not loaded');
        }
    }

    function isMasked() {
        if (typeof window.isDataMasked !== 'undefined') {
            return window.isDataMasked();
        }
        const isMasked = localStorage.getItem('data_masking_enabled');
        return isMasked === null ? true : isMasked === 'true';
    }

    function refreshMasking(id) {
        const modal = document.getElementById(id);
        if (modal) {
            applyMaskingToModal(modal);
        }
    }

    // ============================================================
    // FORM VALIDATION SYSTEM (NEW)
    // ============================================================
    
    const validationInstances = {};

    /**
     * Initialize form validation for a modal
     * 
     * @param {string} modalId - The modal element ID
     * @param {object} config - Validation configuration
     * @param {object} config.fields - Field definitions { fieldId: { label, type, hiddenField, required, validator } }
     * @param {string} config.submitButtonId - ID of the submit button
     * @param {function} config.onSubmit - Submit callback function
     * @param {boolean} config.showErrorsOnLoad - Show errors immediately (default: false)
     */
    function validateForm(modalId, config) {
        config = config || {};
        const fields = config.fields || {};
        const submitButtonId = config.submitButtonId;
        const onSubmit = config.onSubmit;
        const showErrorsOnLoad = config.showErrorsOnLoad || false;
        
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.warn('ModalSystem.validateForm: Modal #' + modalId + ' not found');
            return;
        }
        
        const form = modal.querySelector('form');
        if (!form) {
            console.warn('ModalSystem.validateForm: No form found in modal #' + modalId);
            return;
        }
        
        const submitBtn = submitButtonId ? document.getElementById(submitButtonId) : form.querySelector('button[type="submit"]');
        
        let formTouched = false;
        let isSubmitting = false;
        
        // Store instance for later reference
        validationInstances[modalId] = {
            reset: resetForm,
            getFieldValue: getFieldValue,
            isValid: () => validateAllFields(false)
        };
        
        /**
         * Get field value based on field type
         */
        function getFieldValue(fieldId, fieldConfig) {
            const field = document.getElementById(fieldId);
            if (!field) return '';
            
            // For search fields with hidden input
            if (fieldConfig.type === 'search' && fieldConfig.hiddenField) {
                const hiddenField = document.getElementById(fieldConfig.hiddenField);
                return hiddenField ? hiddenField.value.trim() : '';
            }
            
            return field.value.trim();
        }
        
        /**
         * Highlight a field as error
         */
        function highlightField(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.style.borderColor = '#EF4444';
                field.style.borderWidth = '2px';
                field.style.boxShadow = '0 0 0 1px #EF4444';
            }
        }
        
        /**
         * Clear error from a field
         */
        function clearField(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.style.borderColor = '';
                field.style.borderWidth = '';
                field.style.boxShadow = '';
            }
        }
        
        /**
         * Clear all field errors
         */
        function clearAllFields() {
            Object.keys(fields).forEach(fieldId => {
                clearField(fieldId);
            });
        }
        
        /**
         * Validate all fields
         */
        function validateAllFields(showErrors) {
            let isValid = true;
            let firstErrorField = null;
            const errors = [];
            
            for (const [fieldId, fieldConfig] of Object.entries(fields)) {
                const value = getFieldValue(fieldId, fieldConfig);
                const label = fieldConfig.label || fieldId;
                let fieldValid = true;
                
                // Check required
                if (fieldConfig.required !== false && !value) {
                    fieldValid = false;
                    errors.push(`Please enter ${label}`);
                }
                
                // Custom validator
                if (fieldValid && typeof fieldConfig.validator === 'function') {
                    const validatorResult = fieldConfig.validator(value);
                    if (validatorResult !== true) {
                        fieldValid = false;
                        errors.push(validatorResult || `${label} is invalid`);
                    }
                }
                
                if (!fieldValid) {
                    isValid = false;
                    if (showErrors && formTouched) {
                        highlightField(fieldId);
                        if (!firstErrorField) firstErrorField = document.getElementById(fieldId);
                    }
                } else if (showErrors && formTouched) {
                    clearField(fieldId);
                }
            }
            
            // Update submit button
            if (submitBtn) {
                if (!isValid || isSubmitting) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    if (!isSubmitting) {
                        submitBtn.title = 'Please fill in all required fields';
                    }
                } else {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    submitBtn.title = '';
                }
            }
            
            return { isValid, errors, firstErrorField };
        }
        
        /**
         * Mark form as touched and validate
         */
        function touchForm() {
            if (!formTouched) {
                formTouched = true;
            }
            validateAllFields(true);
        }
        
        /**
         * Reset form state
         */
        function resetForm() {
            formTouched = false;
            isSubmitting = false;
            clearAllFields();
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                submitBtn.title = 'Please fill in all required fields';
            }
            
            validateAllFields(false);
        }
        
        /**
         * Handle form submission
         */
        async function handleSubmit(event) {
            event.preventDefault();
            
            // Mark as touched
            formTouched = true;
            
            // Validate
            const { isValid, errors, firstErrorField } = validateAllFields(true);
            
            if (!isValid) {
                // Show first error
                if (errors.length > 0) {
                    toastWrapper.error(errors[0], {
                        title: 'Missing Information',
                        duration: 4000
                    });
                }
                
                // Focus first error field
                if (firstErrorField) {
                    firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Add shake animation
                    firstErrorField.classList.add('shake-error');
                    setTimeout(() => firstErrorField.classList.remove('shake-error'), 500);
                }
                
                return false;
            }
            
            // All valid - call submit handler
            if (typeof onSubmit === 'function') {
                isSubmitting = true;
                
                try {
                    await onSubmit(event, {
                        getFieldValue: getFieldValue,
                        fields: fields,
                        form: form
                    });
                } catch (err) {
                    console.error('Form submit error:', err);
                } finally {
                    isSubmitting = false;
                    
                    // Re-enable button if form is still visible
                    if (submitBtn && !modal.classList.contains('hidden')) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        submitBtn.title = '';
                    }
                }
            }
        }
        
        // ============================================================
        // ATTACH EVENT LISTENERS
        // ============================================================
        
        // Attach to form submit
        form.addEventListener('submit', handleSubmit);
        
        // Watch each field for changes
        Object.keys(fields).forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('focus', touchForm);
                field.addEventListener('input', () => {
                    touchForm();
                    validateAllFields(true);
                });
                field.addEventListener('change', () => {
                    touchForm();
                    validateAllFields(true);
                });
            }
            
            // Watch hidden fields for search inputs
            const fieldConfig = fields[fieldId];
            if (fieldConfig.hiddenField) {
                const hiddenField = document.getElementById(fieldConfig.hiddenField);
                if (hiddenField) {
                    const observer = new MutationObserver(() => {
                        if (formTouched) {
                            validateAllFields(true);
                        }
                    });
                    observer.observe(hiddenField, { 
                        attributes: true, 
                        attributeFilter: ['value'] 
                    });
                }
            }
        });
        
        // Reset on modal open
        const modalObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.id === modalId) {
                    if (!mutation.target.classList.contains('hidden')) {
                        // Modal opened - reset
                        setTimeout(resetForm, 100);
                    }
                }
            });
        });
        
        modalObserver.observe(modal, { attributes: true, attributeFilter: ['class'] });
        
        // Initial state
        resetForm();
        
        // Override close to clean up
        const originalClose = close;
        
        console.log('✅ Form validation initialized for modal:', modalId);
        
        return validationInstances[modalId];
    }

    // ============================================================
    // PUBLIC: Open/Close Modals
    // ============================================================
    
    function open(id, options) {
        options = options || {};
        var modal = document.getElementById(id);
        if (!modal) {
            console.warn('ModalSystem.open: Element #' + id + ' not found');
            return;
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        // Focus Trap & Keyboard Navigation (Accessibility 7.7)
        try {
            modal._previouslyFocused = document.activeElement;
            var focusables = modal.querySelectorAll('a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex="0"]');
            if (focusables.length > 0) {
                focusables[0].focus();
            }

            modal._focusTrapHandler = function(e) {
                if (e.key !== 'Tab') return;
                var currentFocusables = modal.querySelectorAll('a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex="0"]');
                if (currentFocusables.length === 0) return;
                var first = currentFocusables[0];
                var last = currentFocusables[currentFocusables.length - 1];

                if (e.shiftKey) {
                    if (document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    }
                } else {
                    if (document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            };
            modal.addEventListener('keydown', modal._focusTrapHandler);
        } catch (e) {}

        if (options.applyMasking !== false) {
            setTimeout(function() {
                applyMaskingToModal(modal);
            }, 150);
        }
        
        if (typeof options.onOpen === 'function') {
            options.onOpen(modal);
        }
    }

    function close(id, options) {
        options = options || {};
        var modal = document.getElementById(id);
        if (!modal) {
            console.warn('ModalSystem.close: Element #' + id + ' not found');
            return;
        }

        // Remove focus trap listener and restore previous focus
        if (modal._focusTrapHandler) {
            modal.removeEventListener('keydown', modal._focusTrapHandler);
            modal._focusTrapHandler = null;
        }
        if (modal._previouslyFocused && typeof modal._previouslyFocused.focus === 'function') {
            try { modal._previouslyFocused.focus(); } catch (e) {}
            modal._previouslyFocused = null;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        
        if (typeof options.onClose === 'function') {
            options.onClose(modal);
        }
    }

    function isAnyOpen() {
        var modals = document.querySelectorAll('.fixed.inset-0');
        for (var i = 0; i < modals.length; i++) {
            if (modals[i].id && !modals[i].classList.contains('hidden')) {
                return true;
            }
        }
        return false;
    }

    // ============================================================
    // PUBLIC: Confirmation Dialog
    // ============================================================

    function confirm(message, onConfirm, options) {
        options = options || {};
        var title = options.title || 'Confirm Action';
        var confirmText = options.confirmText || 'Confirm';
        var cancelText = options.cancelText || 'Cancel';
        var type = options.type || 'danger';
        var applyMasking = options.applyMasking !== false;
        
        var iconColors = {
            danger: 'bg-rose-50 text-rose-500',
            warning: 'bg-amber-50 text-amber-500',
            info: 'bg-blue-50 text-blue-500'
        };
        var iconClasses = {
            danger: 'fa-trash-can',
            warning: 'fa-triangle-exclamation',
            info: 'fa-circle-info'
        };
        var btnColors = {
            danger: 'bg-rose-600 hover:bg-rose-700',
            warning: 'bg-amber-600 hover:bg-amber-700',
            info: 'bg-brand-dark hover:bg-brand-medium'
        };

        var iconColor = iconColors[type] || iconColors.danger;
        var iconClass = iconClasses[type] || iconClasses.danger;
        var btnColor = btnColors[type] || btnColors.danger;

        var modalId = 'modal-system-confirm-' + Date.now();
        var overlay = document.createElement('div');
        overlay.id = modalId;
        overlay.className = 'hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] items-center justify-center p-4';
        overlay.innerHTML = 
            '<div class="bg-white rounded-2xl shadow-xl w-full max-w-sm" onclick="event.stopPropagation()">' +
                '<div class="p-6 text-center">' +
                    '<div class="w-12 h-12 rounded-full ' + iconColor + ' flex items-center justify-center mx-auto mb-4">' +
                        '<i class="fa-solid ' + iconClass + '"></i>' +
                    '</div>' +
                    '<h3 class="font-bold text-slate-900 mb-1">' + escapeHtml(title) + '</h3>' +
                    '<p class="text-sm text-slate-500">' + escapeHtml(message) + '</p>' +
                '</div>' +
                '<div class="flex gap-2 px-6 pb-6">' +
                    '<button type="button" class="flex-1 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold modal-confirm-cancel">' +
                        escapeHtml(cancelText) +
                    '</button>' +
                    '<button type="button" class="flex-1 px-4 py-2 text-white rounded-lg transition text-sm font-semibold modal-confirm-ok ' + btnColor + '">' +
                        escapeHtml(confirmText) +
                    '</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(overlay);

        overlay.addEventListener('click', function() {
            close(modalId, { onClose: cleanup });
        });

        overlay.querySelector('.modal-confirm-cancel').addEventListener('click', function() {
            close(modalId, { onClose: cleanup });
        });

        overlay.querySelector('.modal-confirm-ok').addEventListener('click', function() {
            close(modalId, { onClose: cleanup });
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });

        function cleanup() {
            setTimeout(function() {
                var el = document.getElementById(modalId);
                if (el && el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 500);
        }

        open(modalId, { applyMasking: applyMasking });
        return modalId;
    }

    function createModal(options) {
        options = options || {};
        var id = options.id || 'modal-system-dynamic-' + Date.now();
        var title = options.title || '';
        var icon = options.icon || 'fa-circle';
        var size = options.size || 'max-w-2xl';
        var content = options.content || '';
        var footer = options.footer || '';
        var onClose = options.onClose || null;
        var applyMasking = options.applyMasking !== false;

        if (document.getElementById(id)) {
            console.warn('ModalSystem.createModal: ID "' + id + '" already exists');
            return id;
        }

        var modal = document.createElement('div');
        modal.id = id;
        modal.className = 'hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4';
        modal.innerHTML = 
            '<div class="bg-white rounded-2xl shadow-xl w-full ' + size + ' max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">' +
                '<div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">' +
                    '<h3 class="font-bold text-slate-900 flex items-center gap-2">' +
                        '<i class="fa-solid ' + icon + ' text-brand-medium"></i> ' + escapeHtml(title) +
                    '</h3>' +
                    '<button class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition modal-close-btn">' +
                        '<i class="fa-solid fa-xmark"></i>' +
                    '</button>' +
                '</div>' +
                '<div class="p-6">' + content + '</div>' +
                (footer ? '<div class="flex justify-end gap-2 px-6 pb-6 border-t border-slate-100 pt-4">' + footer + '</div>' : '') +
            '</div>';

        document.body.appendChild(modal);

        var closeBtns = modal.querySelectorAll('.modal-close-btn');
        for (var i = 0; i < closeBtns.length; i++) {
            closeBtns[i].addEventListener('click', function() {
                close(id, { onClose: onClose });
            });
        }

        modal.addEventListener('click', function() {
            close(id, { onClose: onClose });
        });

        if (applyMasking) {
            setTimeout(function() {
                applyMaskingToModal(modal);
            }, 100);
        }

        return id;
    }

    // ============================================================
    // Toast Wrapper
    // ============================================================

    var toastWrapper = {
        success: function(msg, opts) {
            opts = opts || {}; opts.type = 'success';
            if (typeof toast !== 'undefined' && toast.success) return toast.success(msg, opts);
            console.log('[toast:success]', msg);
        },
        error: function(msg, opts) {
            opts = opts || {}; opts.type = 'error';
            if (typeof toast !== 'undefined' && toast.error) return toast.error(msg, opts);
            console.log('[toast:error]', msg);
        },
        info: function(msg, opts) {
            opts = opts || {}; opts.type = 'info';
            if (typeof toast !== 'undefined' && toast.info) return toast.info(msg, opts);
            console.log('[toast:info]', msg);
        },
        warning: function(msg, opts) {
            opts = opts || {}; opts.type = 'warning';
            if (typeof toast !== 'undefined' && toast.warning) return toast.warning(msg, opts);
            console.log('[toast:warning]', msg);
        },
        dismiss: function(id) {
            if (typeof toast !== 'undefined' && toast.dismiss) toast.dismiss(id);
        },
        dismissAll: function() {
            if (typeof toast !== 'undefined' && toast.dismissAll) toast.dismissAll();
        }
    };

    // ============================================================
    // Setup
    // ============================================================

    var initialized = false;

    function init() {
        if (initialized) return;
        initialized = true;

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var modals = document.querySelectorAll('.fixed.inset-0');
                for (var i = 0; i < modals.length; i++) {
                    if (!modals[i].classList.contains('hidden')) {
                        close(modals[i].id);
                    }
                }
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('fixed') && e.target.classList.contains('inset-0')) {
                var modals = document.querySelectorAll('.fixed.inset-0');
                for (var i = 0; i < modals.length; i++) {
                    if (modals[i] === e.target && !modals[i].classList.contains('hidden')) {
                        close(modals[i].id);
                        break;
                    }
                }
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    init();

    // ============================================================
    // PUBLIC API
    // ============================================================

    return {
        open: open,
        close: close,
        isAnyOpen: isAnyOpen,
        confirm: confirm,
        createModal: createModal,
        validateForm: validateForm,  // NEW!
        toggleMasking: toggleMasking,
        isMasked: isMasked,
        refreshMasking: refreshMasking,
        applyMaskingToModal: applyMaskingToModal,
        toast: toastWrapper
    };
})();

console.log('✅ ModalSystem loaded with validation support.');