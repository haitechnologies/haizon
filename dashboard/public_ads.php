<?php

include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();

$module = 'public_ads';
$module_caption = 'Public Ad';
$tbl_name = DB::PUBLIC_ADS;

$action = isset($_REQUEST['action']) ? e_s__((string)$_REQUEST['action']) : '';
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

$error_message = '';
$success_message = '';

$requestCsrfToken = $_REQUEST['csrf_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $error_message = 'Invalid security token. Please refresh the page and try again.';
}

$campaign_name = '';
$placement_key = 'home_hero';
$ad_format = 'hybrid';
$title = '';
$description = '';
$cta_text = 'Learn more';
$target_url = '';
$image_path = '';
$image_alt = '';
$badge_text = '';
$product_category = 'software';
$page_scope = '';
$keyword_tags = '';
$priority = 5;
$weight = 1;
$starts_at = '';
$ends_at = '';
$is_active = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error_message === '') {
    $campaign_name = e_s__($_POST['campaign_name'] ?? '');
    $placement_key = e_s__($_POST['placement_key'] ?? 'home_hero');
    $ad_format = e_s__($_POST['ad_format'] ?? 'hybrid');
    $title = e_s__($_POST['title'] ?? '');
    $description = e_s__($_POST['description'] ?? '');
    $cta_text = e_s__($_POST['cta_text'] ?? 'Learn more');
    $target_url = e_s__($_POST['target_url'] ?? '');
    $image_path = e_s__($_POST['image_path'] ?? '');
    $image_alt = e_s__($_POST['image_alt'] ?? '');
    $badge_text = e_s__($_POST['badge_text'] ?? '');
    $product_category = e_s__($_POST['product_category'] ?? 'software');
    $page_scope = e_s__($_POST['page_scope'] ?? '');
    $keyword_tags = e_s__($_POST['keyword_tags'] ?? '');
    $priority = max(0, (int)($_POST['priority'] ?? 5));
    $weight = max(1, (int)($_POST['weight'] ?? 1));
    $starts_at = e_s__($_POST['starts_at'] ?? '');
    $ends_at = e_s__($_POST['ends_at'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($campaign_name === '' || $title === '' || $target_url === '') {
        $error_message = 'Campaign name, title, and target URL are required.';
    }
}

if (($action === 'delete_public_ads' || $action === 'toggle_public_ads') && !validate_csrf_token($requestCsrfToken)) {
    $error_message = 'Invalid security token. Please refresh the page and try again.';
}

if ($action === 'delete_public_ads' && $id > 0 && $error_message === '') {
    $stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: listing_public_ads.php?deleted=1');
        exit;
    }
}

if ($action === 'toggle_public_ads' && $id > 0 && $error_message === '') {
    $stmt = $mysqli->prepare("UPDATE `" . $tbl_name . "` SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: listing_public_ads.php?toggled=1');
        exit;
    }
}

if ($action === 'add_public_ads' && $_SERVER['REQUEST_METHOD'] === 'POST' && $error_message === '') {
    $stmt = $mysqli->prepare(
        "INSERT INTO `" . $tbl_name . "`
        (campaign_name, placement_key, ad_format, title, description, cta_text, target_url, image_path, image_alt, badge_text, product_category, page_scope, keyword_tags, priority, weight, is_active, starts_at, ends_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if ($stmt) {
        $startsAtValue = $starts_at !== '' ? $starts_at : null;
        $endsAtValue = $ends_at !== '' ? $ends_at : null;
        $stmt->bind_param('sssssssssssssiiiss', $campaign_name, $placement_key, $ad_format, $title, $description, $cta_text, $target_url, $image_path, $image_alt, $badge_text, $product_category, $page_scope, $keyword_tags, $priority, $weight, $is_active, $startsAtValue, $endsAtValue);
        $stmt->execute();
        $stmt->close();
        header('Location: listing_public_ads.php?created=1');
        exit;
    }
}

if ($action === 'update_public_ads' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && $error_message === '') {
    $stmt = $mysqli->prepare(
        "UPDATE `" . $tbl_name . "`
         SET campaign_name = ?, placement_key = ?, ad_format = ?, title = ?, description = ?, cta_text = ?, target_url = ?, image_path = ?, image_alt = ?, badge_text = ?, product_category = ?, page_scope = ?, keyword_tags = ?, priority = ?, weight = ?, is_active = ?, starts_at = ?, ends_at = ?, updated_at = NOW()
         WHERE id = ?"
    );
    if ($stmt) {
        $startsAtValue = $starts_at !== '' ? $starts_at : null;
        $endsAtValue = $ends_at !== '' ? $ends_at : null;
        $stmt->bind_param('sssssssssssssiiissi', $campaign_name, $placement_key, $ad_format, $title, $description, $cta_text, $target_url, $image_path, $image_alt, $badge_text, $product_category, $page_scope, $keyword_tags, $priority, $weight, $is_active, $startsAtValue, $endsAtValue, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: listing_public_ads.php?updated=1');
        exit;
    }
}

if ($action === 'edit_public_ads' && $id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM `" . $tbl_name . "` WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $campaign_name = s__($row['campaign_name']);
            $placement_key = s__($row['placement_key']);
            $ad_format = s__($row['ad_format']);
            $title = s__($row['title']);
            $description = s__($row['description']);
            $cta_text = s__($row['cta_text']);
            $target_url = s__($row['target_url']);
            $image_path = s__($row['image_path']);
            $image_alt = s__($row['image_alt']);
            $badge_text = s__($row['badge_text']);
            $product_category = s__($row['product_category']);
            $page_scope = s__($row['page_scope']);
            $keyword_tags = s__($row['keyword_tags']);
            $priority = (int)($row['priority'] ?? 5);
            $weight = (int)($row['weight'] ?? 1);
            $starts_at = !empty($row['starts_at']) ? date('Y-m-d\TH:i', strtotime($row['starts_at'])) : '';
            $ends_at = !empty($row['ends_at']) ? date('Y-m-d\TH:i', strtotime($row['ends_at'])) : '';
            $is_active = (int)($row['is_active'] ?? 1);
        }
        $stmt->close();
    }
}

if (isset($_GET['created'])) {
    $success_message = 'Public ad created successfully.';
} elseif (isset($_GET['updated'])) {
    $success_message = 'Public ad updated successfully.';
} elseif (isset($_GET['deleted'])) {
    $success_message = 'Public ad deleted successfully.';
} elseif (isset($_GET['toggled'])) {
    $success_message = 'Public ad status updated successfully.';
}

$ads = [];
$result = $mysqli->query("SELECT * FROM `" . $tbl_name . "` ORDER BY priority DESC, weight DESC, id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ads[] = $row;
    }
}

$placementOptions = ['home_hero', 'listings_inline', 'trade_feature', 'hs_sidebar', 'global_footer', 'ads_page', 'global'];
$formatOptions = ['text', 'image', 'hybrid'];
?>
<div class="content-wrapper">
    <form method="post" action="listing_public_ads.php" autocomplete="off">
        <?php if ($action === 'edit_public_ads' && $id > 0): ?>
            <input type="hidden" name="action" value="update_public_ads">
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <?php else: ?>
            <input type="hidden" name="action" value="add_public_ads">
        <?php endif; ?>
        <?php echo csrf_field(); ?>

        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="d-flex">
                    <div class="breadcrumb py-2">
                        <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                        <a href="index.php" class="breadcrumb-item">Home</a>
                        <span class="breadcrumb-item active">Public Ads</span>
                    </div>
                </div>
                <div class="p-3 rounded ms-lg-auto">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php echo $is_active ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="collapse d-lg-block mt-1" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="submit" class="btn btn-info my-1 me-2"><?php echo ($action === 'edit_public_ads' && $id > 0) ? 'Update' : 'Save'; ?> <?php echo $module_caption; ?></button>
                        <a href="listing_public_ads.php" class="btn btn-outline-primary my-1 me-2">Reset</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-inner">
            <div class="content">
                <?php if ($error_message !== ''): ?>
                    <div class="alert alert-danger"><?php echo e($error_message); ?></div>
                <?php endif; ?>
                <?php if ($success_message !== ''): ?>
                    <div class="alert alert-success"><?php echo e($success_message); ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0">Ad Content</h6></div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Campaign Name</label>
                                    <div class="col-lg-9"><input class="form-control" name="campaign_name" value="<?php echo e($campaign_name); ?>" required></div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Placement</label>
                                    <div class="col-lg-9">
                                        <select class="form-select" name="placement_key">
                                            <?php foreach ($placementOptions as $option): ?>
                                                <option value="<?php echo e($option); ?>" <?php echo $placement_key === $option ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Format</label>
                                    <div class="col-lg-9">
                                        <select class="form-select" name="ad_format">
                                            <?php foreach ($formatOptions as $option): ?>
                                                <option value="<?php echo e($option); ?>" <?php echo $ad_format === $option ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Title</label>
                                    <div class="col-lg-9"><input class="form-control" name="title" value="<?php echo e($title); ?>" required></div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Description</label>
                                    <div class="col-lg-9"><textarea class="form-control" name="description" rows="4" required><?php echo e($description); ?></textarea></div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">CTA Text</label>
                                    <div class="col-lg-9"><input class="form-control" name="cta_text" value="<?php echo e($cta_text); ?>"></div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Target URL</label>
                                    <div class="col-lg-9"><input class="form-control" name="target_url" value="<?php echo e($target_url); ?>" required></div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Image Path</label>
                                    <div class="col-lg-9"><input class="form-control" name="image_path" value="<?php echo e($image_path); ?>" placeholder="assets/images/banners/banner1.jpg"></div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Image Alt</label>
                                    <div class="col-lg-9"><input class="form-control" name="image_alt" value="<?php echo e($image_alt); ?>"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">Targeting</h6></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Badge Text</label>
                                    <input class="form-control" name="badge_text" value="<?php echo e($badge_text); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Product Category</label>
                                    <input class="form-control" name="product_category" value="<?php echo e($product_category); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Page Scope</label>
                                    <input class="form-control" name="page_scope" value="<?php echo e($page_scope); ?>" placeholder="home,listings,trade">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keyword Tags</label>
                                    <input class="form-control" name="keyword_tags" value="<?php echo e($keyword_tags); ?>" placeholder="crm,sales,automation">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Priority</label>
                                        <input class="form-control" name="priority" type="number" min="0" value="<?php echo (int)$priority; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Weight</label>
                                        <input class="form-control" name="weight" type="number" min="1" value="<?php echo (int)$weight; ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Starts At</label>
                                    <input class="form-control" name="starts_at" type="datetime-local" value="<?php echo e($starts_at); ?>">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Ends At</label>
                                    <input class="form-control" name="ends_at" type="datetime-local" value="<?php echo e($ends_at); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Current Public Ads</h6></div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Campaign</th>
                                    <th>Placement</th>
                                    <th>Format</th>
                                    <th>Status</th>
                                    <th>Impressions</th>
                                    <th>Clicks</th>
                                    <th>Priority</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ads as $ad): ?>
                                    <tr>
                                        <td><?php echo (int)$ad['id']; ?></td>
                                        <td>
                                            <strong><?php echo e($ad['campaign_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo e($ad['title']); ?></small>
                                        </td>
                                        <td><?php echo e($ad['placement_key']); ?></td>
                                        <td><?php echo e($ad['ad_format']); ?></td>
                                        <td><?php echo !empty($ad['is_active']) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                                        <td><?php echo number_format((int)($ad['impression_count'] ?? 0)); ?></td>
                                        <td><?php echo number_format((int)($ad['click_count'] ?? 0)); ?></td>
                                        <td><?php echo (int)$ad['priority']; ?>/<?php echo (int)$ad['weight']; ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="listing_public_ads.php?action=edit_public_ads&id=<?php echo (int)$ad['id']; ?>">Edit</a>
                                            <a class="btn btn-sm btn-outline-warning" href="listing_public_ads.php?action=toggle_public_ads&id=<?php echo (int)$ad['id']; ?>&csrf_token=<?php echo urlencode(csrf_token()); ?>">Toggle</a>
                                            <a class="btn btn-sm btn-outline-danger" href="listing_public_ads.php?action=delete_public_ads&id=<?php echo (int)$ad['id']; ?>&csrf_token=<?php echo urlencode(csrf_token()); ?>" onclick="return confirm('Delete this ad?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php include('admin_elements/copyright.php'); ?>
        </div>
    </form>
</div>

<?php include('admin_elements/admin_footer.php'); ?>