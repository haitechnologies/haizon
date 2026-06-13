<?php
use App\Core\DB;
if (!empty($_GET["payment_receiveds_ordering"] ?? $_REQUEST["payment_receiveds_ordering"] ?? "")) {
    $_SESSION["payment_receiveds_ordering"] = e_s__($_GET["payment_receiveds_ordering"] ?? $_REQUEST["payment_receiveds_ordering"]);
}
if (!isset($_SESSION["payment_receiveds_ordering"])) $_SESSION["payment_receiveds_ordering"] = "all";
$payment_receiveds_ordering = $_SESSION["payment_receiveds_ordering"];
$current_id = (int)($_GET["payment_received_id"] ?? 0);
?>
<div class="sidebar-content">
    <div class="sidebar-section sidebar-section-body d-flex align-items-center pb-2 border-bottom border-1">
        <div>
            <select class="form-select border-0 fw-bold" name="payment_receiveds_ordering" id="payment_receiveds_ordering" onchange="window.location.href='payment_received_overview.php?payment_received_id=<?php echo $payment_received_id; ?>&payment_receiveds_ordering='+this.value;">
                <option value="all" <?php if ($payment_receiveds_ordering === "all") echo "selected"; ?>>All</option>
<?php
$statusLabels = ['draft' => 'Draft', 'approved' => 'Approved', 'cleared' => 'Cleared', 'void' => 'Void'];
foreach ($statusLabels as $val => $lbl) {
    $sel = $payment_receiveds_ordering === $val ? "selected" : "";
    echo "<option value=\"{$val}\" {$sel}>{$lbl}</option>";
}
?>
            </select>
        </div>
        <div class="ms-auto">
            <button type="button" class="btn btn-primary border-transparent btn-icon btn-sm opacity-75 p-1 lh-1 fs-6" onclick="window.location.href='payment_receiveds.php';">
                <i class="ph-plus"></i>
            </button>
        </div>
    </div>
    <div class="sidebar-section pt-2">
        <div class="table-responsive">
            <table class="table table-hover"><tbody>
<?php
$where = $payment_receiveds_ordering !== "all" ? " AND t.payment_status = '" . $payment_receiveds_ordering . "'" : "";
$r = $mysqli->query("SELECT t.id, t.payment_status, t.payment_received_date, t.amount_received, t.customer_id FROM `" . DB::PAYMENTS_RECEIVED . "` t WHERE t.id > 0 AND t.customer_id != '' $where ORDER BY t.id DESC LIMIT 25");
$c = $mysqli->query("SELECT COUNT(*) FROM `" . DB::PAYMENTS_RECEIVED . "` t WHERE t.id > 0 AND t.customer_id != '' $where")->fetch_row();
$total = $c[0] ?? 0;
while ($row = $r->fetch_array()) {
    $sel = $row["id"] == $current_id ? "table-primary shadow-sm" : "";
    $name = getTableAttr("display_name", DB::CUSTOMERS, $row["customer_id"]);
    $date = dd_($row["payment_received_date"]);
    $amt = number_format($row["amount_received"], 2);
    $st = strtoupper($row["payment_status"]);
?>
    <tr id="<?php echo $row["id"]; ?>" class="<?php echo $sel; ?>">
        <td><a href="payment_received_overview.php?payment_received_id=<?php echo $row["id"]; ?>" class="text-black text-decoration-none d-block">
            <div class="row"><div class="col-lg-8"><?php echo $name; ?></div><div class="col-lg-4 text-end"><?php echo "AED " . $amt; ?></div></div>
            <div class="small text-muted"><?php echo $date . " - " . $st; ?></div>
        </a></td>
    </tr>
<?php } ?>
</tbody></table></div>
<div class="text-center mb-3"><a href="listing_payment_receiveds.php">View All (<?php echo $total; ?>)</a></div>
</div></div>