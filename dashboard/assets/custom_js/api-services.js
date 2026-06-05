/**
 * api-services.js - Service Layer for API Client
 * 
 * High-level API service functions using APIClient
 * Replaces old ajax.js functions with modern Promise-based alternatives
 * 
 * @version 2.0.0
 */

/**
 * Item/Service API Service
 */
const ItemService = {
    
    /**
     * Get all published services
     * 
     * @returns {Promise<Array>} Array of services
     */
    async getServices() {
        try {
            const result = await api.request('getServices', {}, { useCache: true });
            return result.data || [];
        } catch (error) {
            console.error('Failed to load services:', error);
            throw error;
        }
    },
    
    /**
     * Get item rate (unit price)
     * 
     * @param {number} itemId - Item ID
     * @param {number} rowNo - Row number (for form identification)
     * @returns {Promise<Object>} Item rate data
     */
    async getItemRate(itemId, rowNo) {
        try {
            const result = await api.request('getItemRate', {
                item_id: itemId,
                row_no: rowNo
            });
            return result.data;
        } catch (error) {
            console.error('Failed to load item rate:', error);
            throw error;
        }
    },
    
    /**
     * Populate service dropdown
     * 
     * Usage:
     * ItemService.populateServiceDropdown('service1')
     *     .then(() => console.log('Services loaded'))
     *     .catch(error => showError(error.message));
     */
    async populateServiceDropdown(selectElementId) {
        const selectElement = document.getElementById(selectElementId);
        if (!selectElement) {
            throw new Error(`Select element not found: ${selectElementId}`);
        }
        
        try {
            // Show loading state
            selectElement.disabled = true;
            const originalHTML = selectElement.innerHTML;
            selectElement.innerHTML = '<option value="">Loading...</option>';
            
            const services = await this.getServices();
            
            // Clear and rebuild
            selectElement.innerHTML = '';
            
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = '-- Select Service --';
            selectElement.appendChild(emptyOption);
            
            services.forEach(service => {
                const option = document.createElement('option');
                option.value = service.id;
                option.textContent = service.name;
                selectElement.appendChild(option);
            });
            
            selectElement.disabled = false;
            
        } catch (error) {
            selectElement.innerHTML = originalHTML;
            selectElement.disabled = false;
            throw error;
        }
    },
    
    /**
     * Populate item rate form fields
     * 
     * Usage:
     * ItemService.populateItemRate(123, 1)
     *     .then(() => calculateItemAmount(1))
     *     .catch(error => showError(error.message));
     */
    async populateItemRate(itemId, rowNo) {
        try {
            const rate = await this.getItemRate(itemId, rowNo);
            
            // Update form fields
            const itemRate = parseFloat(rate.rate) || 0;
            
            document.getElementById(`qty${rowNo}`).value = 1;
            document.getElementById(`rate${rowNo}`).value = itemRate;
            document.getElementById(`sub_total${rowNo}`).value = itemRate;
            document.getElementById(`total${rowNo}`).value = itemRate;
            document.getElementById(`tax${rowNo}`).value = '0';
            document.getElementById(`tax_amount${rowNo}`).value = '0';
            
            // Hide tax amount section if needed
            const taxDiv = document.getElementById(`div_tax_amount${rowNo}`);
            if (taxDiv) {
                taxDiv.style.display = 'none';
            }
            
            return rate;
            
        } catch (error) {
            console.error('Failed to populate item rate:', error);
            throw error;
        }
    }
};

/**
 * Customer API Service
 */
const CustomerService = {
    
    /**
     * Populate customer data
     */
    async populateCustomers(newPax = null, oldPax = null) {
        try {
            const result = await api.request('populateCustomers', {
                new_pax: newPax,
                old_pax: oldPax
            });
            return result.data;
        } catch (error) {
            console.error('Failed to populate customers:', error);
            throw error;
        }
    }
};

/**
 * UI Helper Functions
 */
const UIHelper = {
    
    /**
     * Show loading indicator
     */
    showLoading(elementId = null) {
        if (elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.add('loading');
                element.disabled = true;
            }
        }
    },
    
    /**
     * Hide loading indicator
     */
    hideLoading(elementId = null) {
        if (elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.remove('loading');
                element.disabled = false;
            }
        }
    },
    
    /**
     * Show error message
     */
    showError(message, duration = 5000) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            <strong>Error:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.content') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        if (duration > 0) {
            setTimeout(() => alertDiv.remove(), duration);
        }
    },
    
    /**
     * Show success message
     */
    showSuccess(message, duration = 3000) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            <strong>Success!</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.content') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        if (duration > 0) {
            setTimeout(() => alertDiv.remove(), duration);
        }
    }
};

// Export services
window.ItemService = ItemService;
window.CustomerService = CustomerService;
window.UIHelper = UIHelper;
