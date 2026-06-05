<?php
/**
 * PaymentMethodsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class PaymentMethodsDataTable extends BaseDataTable {
    protected $table = DB::PAYMENT_METHODS;
    protected $searchFields = ['payment_method'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'payment_method', 2 => 'created_at', 3 => 'is_active', 4 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $paymentMethod = htmlspecialchars($row['payment_method'] ?? '');
        $publish = (int)$row['is_active'];
        $createdAt = $row['created_at'] ?? '';
        
        $publishBadge = $publish == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');
        
        return [
            $id,
            $paymentMethod,
            timeAgo($createdAt),
            $publishBadge,
            $this->getActionButtons($id, 'payment_methods', $publish)
        ];
    }
    
    protected function getActionButtons($id, $module, $publish) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton($id, 'payment_methods.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton($id, $module);
        }
        return $actions;
    }
}
?>

