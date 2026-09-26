/**
 * assets/js/crud-ajax.js
 * Universal AJAX CRUD Engine for Civentral
 * Standardizes async form submission, in-place table upserts, deletions, and CSRF protection.
 */
(function(window) {
    'use strict';

    const CrudAjax = {
        /**
         * Escape dynamic data before DOM insertion to prevent XSS
         */
        escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        /**
         * Resolve CSRF token from meta tag, input field, or existing helper
         */
        getCsrfToken() {
            if (typeof window.getCsrfToken === 'function') {
                const token = window.getCsrfToken();
                if (token) return token;
            }
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.content) return meta.content;
            const input = document.querySelector('input[name="csrf_token"]');
            if (input && input.value) return input.value;
            return '';
        },

        /**
         * Show toast notification using ModalSystem or fallback
         */
        showToast(message, type = 'info') {
            if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                if (type === 'success' && ModalSystem.toast.success) {
                    ModalSystem.toast.success(message);
                    return;
                }
                if ((type === 'danger' || type === 'error') && ModalSystem.toast.error) {
                    ModalSystem.toast.error(message);
                    return;
                }
                if (type === 'warning' && ModalSystem.toast.warning) {
                    ModalSystem.toast.warning(message);
                    return;
                }
                if (ModalSystem.toast.info) {
                    ModalSystem.toast.info(message);
                    return;
                }
            }
            if (typeof window.showToast === 'function') {
                window.showToast(message, type);
                return;
            }
            console.log(`[Toast ${type}]: ${message}`);
        },

        /**
         * Close modal by ID using ModalSystem or standard closeModal
         */
        closeModal(modalId) {
            if (!modalId) return;
            if (typeof ModalSystem !== 'undefined' && typeof ModalSystem.close === 'function') {
                ModalSystem.close(modalId);
                return;
            }
            if (typeof window.closeModal === 'function') {
                window.closeModal(modalId);
                return;
            }
            const el = document.getElementById(modalId);
            if (el) el.classList.add('hidden');
        },

        /**
         * Visual flash effect on row after creation or update
         */
        flashRow(rowElem, actionType = 'create') {
            if (!rowElem) return;
            const bgClass = actionType === 'create' ? 'bg-emerald-50' : 'bg-amber-50';
            rowElem.classList.add(bgClass, 'transition-colors', 'duration-700');
            setTimeout(() => {
                rowElem.classList.remove(bgClass);
            }, 1200);
        },

        /**
         * Universal Form Submission Handler
         * @param {HTMLFormElement} formElement
         * @param {Object} options
         */
        async submitForm(formElement, options = {}) {
            if (!formElement) return;

            const url = options.url || formElement.getAttribute('action') || window.location.href;
            const method = (options.method || formElement.getAttribute('method') || 'POST').toUpperCase();
            const modalId = options.modalId || null;
            const tableBodyId = options.tableBodyId || options.tableId || null;
            const renderRow = options.renderRow || null;
            const sendJson = options.sendJson !== false; // default true for clean API payloads
            const onSuccess = options.onSuccess || null;
            const onError = options.onError || null;

            // Find submit button for loading indicator
            const submitBtn = formElement.querySelector('button[type="submit"]') || formElement.querySelector('.btn-submit');
            let origBtnHtml = '';
            if (submitBtn) {
                origBtnHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
            }

            try {
                const formData = new FormData(formElement);
                const csrfToken = this.getCsrfToken();

                const headers = {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                };
                if (csrfToken) {
                    headers['X-CSRF-Token'] = csrfToken;
                }

                let body;
                if (sendJson) {
                    headers['Content-Type'] = 'application/json';
                    const payload = {};
                    formData.forEach((value, key) => {
                        payload[key] = value;
                    });
                    if (csrfToken && !payload.csrf_token) {
                        payload.csrf_token = csrfToken;
                    }
                    body = JSON.stringify(payload);
                } else {
                    if (csrfToken && !formData.has('csrf_token')) {
                        formData.append('csrf_token', csrfToken);
                    }
                    body = formData;
                }

                const response = await fetch(url, {
                    method: method,
                    headers: headers,
                    body: body
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    const errorMsg = data.message || 'Operation failed. Please check your input.';
                    this.showToast(errorMsg, 'error');
                    if (typeof onError === 'function') onError(data);
                    return data;
                }

                // Success
                this.showToast(data.message || 'Saved successfully', 'success');

                // Close modal if specified
                if (modalId) {
                    this.closeModal(modalId);
                }

                // Reset form fields
                formElement.reset();

                // In-place row upsert if table and renderer are specified
                const record = data.record || data.data;
                const action = data.action || (method === 'POST' ? 'create' : 'update');
                if (tableBodyId && renderRow && record) {
                    this.upsertRow(tableBodyId, record, renderRow, action);
                }

                if (typeof onSuccess === 'function') {
                    onSuccess(data);
                }

                return data;

            } catch (err) {
                console.error('[CrudAjax.submitForm error]:', err);
                const msg = err.message || 'A network error occurred. Please try again.';
                this.showToast(msg, 'error');
                if (typeof onError === 'function') onError(err);
                throw err;
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origBtnHtml;
                }
            }
        },

        /**
         * In-place Table Row Upsert
         * Replaces existing <tr> if data-id matches; prepends new <tr> if not found.
         */
        upsertRow(tableBodyId, record, renderRowFn, action = 'create') {
            const tbody = document.getElementById(tableBodyId);
            if (!tbody || !record || typeof renderRowFn !== 'function') return null;

            const recordId = record.id || record.triage_id || record.appointment_id || record.permit_id || record.request_id;
            const newRowHtml = renderRowFn(record);

            const tempContainer = document.createElement('tbody');
            tempContainer.innerHTML = newRowHtml.trim();
            const newRowElem = tempContainer.firstElementChild;
            if (!newRowElem) return null;

            if (recordId) {
                newRowElem.setAttribute('data-id', recordId);
            }

            // Look for existing row
            let existingRow = null;
            if (recordId) {
                existingRow = tbody.querySelector(`tr[data-id="${recordId}"]`);
            }

            if (existingRow) {
                tbody.replaceChild(newRowElem, existingRow);
                this.flashRow(newRowElem, 'update');
            } else {
                const emptyState = document.getElementById('emptyState');
                if (emptyState) emptyState.style.display = 'none';
                tbody.insertBefore(newRowElem, tbody.firstElementChild);
                this.flashRow(newRowElem, 'create');
            }

            // Apply data masking if enabled in system
            if (typeof ModalSystem !== 'undefined' && typeof ModalSystem.applyMaskingToModal === 'function') {
                ModalSystem.applyMaskingToModal(newRowElem);
            }

            return newRowElem;
        },

        /**
         * In-place Table Row Deletion with animation
         */
        deleteRow(tableBodyId, recordId, options = {}) {
            const tbody = document.getElementById(tableBodyId);
            if (!tbody || !recordId) return;

            const row = tbody.querySelector(`tr[data-id="${recordId}"]`);
            if (!row) return;

            row.classList.add('transition-all', 'duration-300', 'opacity-0', 'bg-rose-50');
            setTimeout(() => {
                row.remove();
                if (tbody.children.length === 0) {
                    const emptyState = document.getElementById('emptyState');
                    if (emptyState) emptyState.style.display = 'flex';
                }
                if (typeof options.onDeleted === 'function') {
                    options.onDeleted();
                }
            }, 300);
        },

        /**
         * Quick AJAX Action (e.g. status transition, approval, call next)
         */
        async sendAction(url, method = 'POST', payload = {}, options = {}) {
            const csrfToken = this.getCsrfToken();
            const headers = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            };
            if (csrfToken) {
                headers['X-CSRF-Token'] = csrfToken;
                if (!payload.csrf_token) {
                    payload.csrf_token = csrfToken;
                }
            }

            try {
                const res = await fetch(url, {
                    method: method.toUpperCase(),
                    headers: headers,
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    this.showToast(data.message || 'Action failed', 'error');
                    if (typeof options.onError === 'function') options.onError(data);
                    return data;
                }

                if (options.successMessage || data.message) {
                    this.showToast(options.successMessage || data.message, 'success');
                }

                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(data);
                }

                return data;
            } catch (err) {
                console.error('[CrudAjax.sendAction error]:', err);
                this.showToast(err.message || 'Network error occurred', 'error');
                if (typeof options.onError === 'function') options.onError(err);
                throw err;
            }
        }
    };

    window.CrudAjax = CrudAjax;
})(window);
