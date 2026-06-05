<?php

/*
|--------------------------------------------------------------------------
| ACCOUNTING FUNCTIONS - NOT NEEDED
|--------------------------------------------------------------------------
*/
// Accounting module disabled in this system

// Consolidate session handling
if (!empty($_GET['customers_ordering'] ?? $_REQUEST['customers_ordering'] ?? '')) {
    $_SESSION['customers_ordering'] = e_s__($_GET['customers_ordering'] ?? $_REQUEST['customers_ordering']);
}

// Set default if not exists
$_SESSION['customers_ordering'] = $_SESSION['customers_ordering'] ?? 'all';
$customers_ordering = $_SESSION['customers_ordering'];

// Build search query with array mapping
$status_map = [
    'active'    => "c.is_active=1",
    'inactive'  => "c.publish=0",
    'crm'       => "c.is_active=1",
    'duplicate' => "SPECIAL_DUPLICATE",
    'overdue'   => "SPECIAL_OVERDUE",
    'unpaid'    => "SPECIAL_UNPAID"
];

$search_query = isset($status_map[$customers_ordering]) ? " AND " . $status_map[$customers_ordering] : " AND c.id >= 1";

// Get current selected customer ID
$current_id = (int)($_GET['customer_id'] ?? 0);

?>
<div class="sidebar-content">

    <!-- Header -->
    <div class="sidebar-section sidebar-section-body d-flex align-items-center pb-2 border-bottom border-1">

        <div>

            <select class="form-select border-0 fw-bold" name="customers_ordering" id="customers_ordering" onchange="window.location.href='customer_overview.php?customer_id=<?php echo $customer_id; ?>&customers_ordering=' + this.value;">
                <?php
                $ordering_options = [
                    'all'       => 'All Customers',
                    'active'    => 'Active Customers',
                    'crm'       => 'CRM Customers',
                    'overdue'   => 'Overdue Customers',
                    'unpaid'    => 'Unpaid Customers',
                    'duplicate' => 'Duplicate Customers',
                    'inactive'  => 'Inactive Customers'
                ];
                
                foreach ($ordering_options as $value => $label) {
                    $selected = ($customers_ordering === $value) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                }
                ?>
            </select>
        </div>

        <div class="ms-auto">
            <button type="button" class="btn btn-primary border-transparent btn-icon btn-sm opacity-75 p-1 lh-1 fs-6" onclick="window.location.href='customers.php';">
                <i class="ph-plus"></i>
            </button>
        </div>

    </div>
    <!-- /header -->


    <!-- Sub navigation -->
    <div class="sidebar-section pt-2">

        <div class="table-responsive">
            <table class="table table-hover">
                <tbody>
                    <?php
                    // Build query based on ordering type
                    if ($customers_ordering === 'duplicate') {
                        // Duplicate customers query
                        $result = $mysqli->query(
                            "SELECT c.id, c.display_name, 0 as total_attachments
                             FROM `" . DB::CUSTOMERS . "` c
                             WHERE c.display_name IN (
                                SELECT display_name FROM `" . DB::CUSTOMERS . "`
                                GROUP BY display_name HAVING COUNT(display_name) > 1
                             )
                             GROUP BY c.id
                             ORDER BY c.display_name ASC
                             LIMIT 25"
                        );
                        
                        $total_customers = $result->num_rows;
                    } elseif ($customers_ordering === 'overdue') {
                        // Overdue customers - customers with overdue invoices
                        $result = $mysqli->query(
                            "SELECT c.id, c.display_name, c.publish,
                                    0 as total_attachments
                             FROM `" . DB::CUSTOMERS . "` c
                             INNER JOIN `" . DB::INVOICES . "` i ON c.id = i.customer_id
                             WHERE i.invoice_status = 'overdue'
                             GROUP BY c.id
                             ORDER BY c.display_name ASC
                             LIMIT 25"
                        );
                        
                        // Get total count
                        $result_count = $mysqli->query(
                            "SELECT COUNT(DISTINCT c.id) as total FROM `" . DB::CUSTOMERS . "` c
                             INNER JOIN `" . DB::INVOICES . "` i ON c.id = i.customer_id
                             WHERE i.invoice_status = 'overdue'"
                        );
                        $count_row = $result_count->fetch_array();
                        $total_customers = $count_row['total'] ?? 0;
                    } elseif ($customers_ordering === 'unpaid') {
                        // Unpaid customers - customers with any unpaid invoices (sent, partially_paid, overdue)
                        $result = $mysqli->query(
                            "SELECT c.id, c.display_name, c.publish,
                                    0 as total_attachments
                             FROM `" . DB::CUSTOMERS . "` c
                             INNER JOIN `" . DB::INVOICES . "` i ON c.id = i.customer_id
                             WHERE i.invoice_status IN ('sent', 'partially_paid', 'overdue')
                             GROUP BY c.id
                             ORDER BY c.display_name ASC
                             LIMIT 25"
                        );
                        
                        // Get total count
                        $result_count = $mysqli->query(
                            "SELECT COUNT(DISTINCT c.id) as total FROM `" . DB::CUSTOMERS . "` c
                             INNER JOIN `" . DB::INVOICES . "` i ON c.id = i.customer_id
                             WHERE i.invoice_status IN ('sent', 'partially_paid', 'overdue')"
                        );
                        $count_row = $result_count->fetch_array();
                        $total_customers = $count_row['total'] ?? 0;
                    } else {
                        // Standard query with attachment count using JOIN
                        $result = $mysqli->query(
                            "SELECT c.id, c.display_name, c.publish,
                                    0 as total_attachments
                             FROM `" . DB::CUSTOMERS . "` c
                             WHERE c.id > 0 {$search_query}
                             ORDER BY c.id DESC
                             LIMIT 25"
                        );
                        
                        // Get total count
                        $result_count = $mysqli->query(
                            "SELECT COUNT(DISTINCT c.id) as total FROM `" . DB::CUSTOMERS . "` c
                             WHERE c.id > 0 {$search_query}"
                        );
                        $count_row = $result_count->fetch_array();
                        $total_customers = $count_row['total'] ?? 0;
                    }

                    // Render rows
                    while ($row = $result->fetch_array()) {
                        $isSelected = ($row['id'] == $current_id) ? 'table-primary shadow-sm' : '';
                        
                        // Calculate outstanding receivables (unpaid invoices)
                        $customer_receivables = 0;
                        $rec_query = $mysqli->query("SELECT SUM(grand_total) as total FROM " . DB::INVOICES . " WHERE customer_id = {$row['id']} AND invoice_status NOT IN ('paid', 'cancelled')");
                        if ($rec_query && $rec_row = $rec_query->fetch_assoc()) {
                            $customer_receivables = (float)($rec_row['total'] ?? 0);
                        }
                        
                        $formatted_amount = BASE_CURRENCY['code'] . dec_($customer_receivables);
                        $attachment_icon = ($row['total_attachments'] > 0) ? '<i class="ph-paperclip"></i>' : '';
                    ?>
                        <tr id="<?php echo $row['id']; ?>" class="<?php echo $isSelected; ?>">
                            <td>
                                <a href="customer_overview.php?customer_id=<?php echo $row['id']; ?>" class="text-black text-decoration-none d-block">
                                    <div class="row">
                                        <div class="col-lg-10">
                                            <div><?php echo $row['display_name']; ?></div>
                                            <div class="text-muted small">
                                                <?php echo $formatted_amount; ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 text-end">
                                            <?php echo $attachment_icon; ?>
                                        </div>
                                    </div>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mb-3">
            <a href="listing_customers.php?dt_ordering_type=<?php echo $customers_ordering; ?>">View All Customers (<?php echo $total_customers; ?>)</a>
        </div>

    </div>
    <!-- /sub navigation -->

</div>