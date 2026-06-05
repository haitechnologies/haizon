<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class ShippingCustomersDataTable extends BaseDataTable {
    protected $table = DB::SHIPPING_CUSTOMERS;
    protected $searchFields = ['customer_name', 'customer_phone', 'customer_email'];
    protected $sortableColumns = [0 => 'id', 1 => 'customer_name', 2 => 'customer_phone', 3 => 'customer_city', 4 => 'customer_country', 5 => 'customer_type', 6 => 'is_active', 7 => 'id'];

    protected function formatRow($row, $requestData = []) {
        $id      = (int)($row['id'] ?? 0);
        $name    = (string)($row['customer_name'] ?? '');
        $phone   = (string)($row['customer_phone'] ?? '');
        $city    = (string)($row['customer_city'] ?? '');
        $country = (string)($row['customer_country'] ?? '');
        $type    = (string)($row['customer_type'] ?? '');
        $active  = (int)($row['is_active'] ?? 0);
        $badge   = $active ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id,
            htmlspecialchars($name),
            htmlspecialchars($phone),
            htmlspecialchars($city),
            htmlspecialchars($country),
            htmlspecialchars($type),
            $badge,
            $this->getActionButtons($id, 'shipping_customers'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $a = '';
        if (granted_('edit', $module))   $a .= ActionButtonHelper::editButton((int)$id, 'shipping_customers.php', $module, 'Edit', false);
        if (granted_('delete', $module)) $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        return $a;
    }
}
