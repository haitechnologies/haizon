<?php
use App\Core\DB;
if (!empty($_GET["vendor_overviews_ordering"] ?? $_REQUEST["vendor_overviews_ordering"] ?? "")) {
    $_SESSION["vendor_overviews_ordering"] = e_s__($_GET["vendor_overviews_ordering"] ?? $_REQUEST["vendor_overviews_ordering"]);
}
if (!isset($_SESSION["vendor_overviews_ordering"])) $_SESSION["vendor_overviews_ordering"] = "all";
$vendor_overviews_ordering = $_SESSION["vendor_overviews_ordering"];
$current_id = (int)($_GET["vendor_id"] ?? 0);
?>
<div class="sidebar-content">
    <div class="sidebar-section sidebar-section-body d-flex align-items-center pb-2 border-bottom border-1">
        <div><h6 class="mb-0">Vendors</h6></div>
        <div class="ms-auto">
            <button type="button" class="btn btn-primary border-transparent btn-icon btn-sm opacity-75 p-1 lh-1 fs-6" onclick="window.location.href='vendor_overviews.php';">
                <i class="ph-plus"></i>
            </button>
        </div>
    </div>
    <div class="sidebar-section pt-2">
        <div class="table-responsive">
            <table class="table table-hover"><tbody>
<?php
$r = $mysqli->query("SELECT t.id, t.company_name, t.display_name FROM `" . DB::VENDORS . "` t WHERE t.is_active = 1 ORDER BY t.id DESC LIMIT 25");
$c = $mysqli->query("SELECT COUNT(*) FROM `" . DB::VENDORS . "` t WHERE t.is_active = 1")->fetch_row();
$total = $c[0] ?? 0;
while ($row = $r->fetch_array()) {
    $sel = $row["id"] == $current_id ? "table-primary shadow-sm" : "";
    $name = $row["company_name"] ?: $row["display_name"];
?>
    <tr id="<?php echo $row["id"]; ?>" class="<?php echo $sel; ?>">
        <td><a href="vendor_overview_overview.php?vendor_id=<?php echo $row["id"]; ?>" class="text-black text-decoration-none d-block">
            <?php echo $name; ?>
        </a></td>
    </tr>
<?php } ?>
</tbody></table></div>
<div class="text-center mb-3"><a href="listing_vendors.php">View All (<?php echo $total; ?>)</a></div>
</div></div>