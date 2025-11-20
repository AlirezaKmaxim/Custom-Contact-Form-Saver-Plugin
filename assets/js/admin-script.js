/**
 * Form Submissions Admin Script
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Select/deselect all checkboxes
    const selectAllCheckbox = document.getElementById('ccf_select_all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }
    
    // Apply bulk action
    const applyBulkButton = document.getElementById('ccf-apply-bulk');
    if (applyBulkButton) {
        applyBulkButton.addEventListener('click', function() {
            const selectedAction = document.getElementById('ccf-bulk-action-select').value;
            const checkedBoxes = document.querySelectorAll('input[name="bulk_ids[]"]:checked');
            
            if (!selectedAction) {
                alert('Please select an action.');
                return;
            }
            
            if (checkedBoxes.length === 0) {
                alert('Please select at least one entry.');
                return;
            }
            
            // Confirm for delete action
            if (selectedAction === 'bulk_delete') {
                if (!confirm('Are you sure you want to delete ' + checkedBoxes.length + ' selected entries?')) {
                    return;
                }
            }
            
            // Set the action and submit form
            document.getElementById('ccf-bulk-action-input').value = selectedAction;
            document.getElementById('ccf-bulk-form').submit();
        });
    }
    
});