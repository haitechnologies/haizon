<?php
use App\Core\DB;
if (!empty($_GET["expenses_ordering"] ?? $_REQUEST["expenses_ordering"] ?? "")) {
    $_SESSION["expenses_ordering"] = e_s__($_GET["expenses_ordering"] ?? $_REQUEST["expenses_ordering"]);
}
if (!isset($_SESSION["expenses_ordering"])) $_SESSION["expenses_ordering"] = "all";
$expenses_ordering = $_SESSION["expenses_ordering"];
$current_id = (int)($_GET["expense_id"] ?? 0);
?>
<div class="sidebar-content">
    <div class="sidebar-section sidebar-section-body d-flex align-items-center pb-2 border-bottom border-1">
        <div>
            <select class="form-select border-0 fw-bold" name="expenses_ordering" id="expenses_ordering" onchange="window.location.href='expense_overview.php?expense_id=<?php echo $expense_id; ?>&expenses_ordering='+this.value;">
                <option value="all" <?php if ($expenses_ordering === "all") echo "selected"; ?>>All</option>
<?php
$statusLabels = ['draft' => 'Draft', 'submitted' => 'Submitted', 'approved' => 'Approved', 'rejected' => 'Rejected', 'paid' => 'Paid'];
foreach ($statusLabels as $val => $lbl) {
    $sel = $expenses_ordering === $val ? "selected" : "";
    echo "<option value=\"{$val}\" {$sel}>{$lbl}</option>";
}
?>
            </select>
        </div>
        <div class="ms-auto">
            <button type="button" class="btn btn-primary border-transparent btn-icon btn-sm opacity-75 p-1 lh-1 fs-6" onclick="window.location.href='expenses.php';">
                <i class="ph-plus"></i>
            </button>
        </div>
    </div>
    <div class="sidebar-section pt-2">
        <div class="table-responsive">
            <table class="table table-hover"><tbody>
<?php
$where = $expenses_ordering !== "all" ? " AND t.expense_status = '" . $expenses_ordering . "'" : "";
$r = $mysqli->query("SELECT t.id, t.expense_status, t.expense_date, t.grand_total, t.customer_id FROM `" . DB::EXPENSES . "` t WHERE t.id > 0 AND t.customer_id != '' $where ORDER BY t.id DESC LIMIT 25");
$c = $mysqli->query("SELECT COUNT(*) FROM `" . DB::EXPENSES . "` t WHERE t.id > 0 AND t.customer_id != '' $where")->fetch_row();
$total = $c[0] ?? 0;
while ($row = $r->fetch_array()) {
    $sel = $row["id"] == $current_id ? "table-primary shadow-sm" : "";
    $name = getTableAttr("display_name", DB::CUSTOMERS, $row["customer_id"]);
    $date = dd_($row["expense_date"]);
    $amt = number_format($row["grand_total"], 2);
    $st = strtoupper($row["expense_status"]);
?>
    <tr id="<?php echo $row["id"]; ?>" class="<?php echo $sel; ?>">
        <td><a href="expense_overview.php?expense_id=<?php echo $row["id"]; ?>" class="text-black text-decoration-none d-block">
            <div class="row"><div class="col-lg-8"><?php echo $name; ?></div><div class="col-lg-4 text-end"><?php echo "AED " . $amt; ?></div></div>
            <div class="small text-muted"><?php echo $date . " - " . $st; ?></div>
        </a></td>
    </tr>
<?php } ?>
</tbody></table></div>
<div class="text-center mb-3"><a href="listing_expenses.php">View All (<?php echo $total; ?>)</a></div>
</div></div>