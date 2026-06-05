<?php

$dashboardBodyClass = 'page-dashboard-form page-dashboard-blogs';
include('admin_elements/admin_header.php');
require_once __DIR__ . '/../classes/EmailQueue.php';

$module             = 'blogs';
$module_caption     = 'Blog Post';
$error_message      = '';
$success_message    = '';


/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in blogs.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


$reviewAction = trim((string)($_POST['review_action'] ?? ''));

if (isset($_POST['is_active']) || isset($_POST['status']))       $is_active = 1;
else $is_active = 0;

if (isset($_POST['is_homepage']))  $is_homepage = 1;
else $is_homepage = 0;

$source = 'admin';
$submission_status = 'admin';
$submitted_by = 0;
$guest_author_name = '';
$guest_author_bio = '';
$guest_author_email = '';
$rejection_reason = '';


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ((($action == "update_$module") || ($action == "add_$module")) && $reviewAction === '') {
    $title              = e_s__($_POST['title']);
    $slug               = e_s__($_POST['slug']);
    $content            = $_POST['content'] ?? ''; // Keep HTML intact
    $excerpt            = e_s__($_POST['excerpt']);
    $featured_image     = e_s__($_POST['featured_image']);
    $category_id        = e_s__($_POST['category_id']);
    $meta_title         = e_s__($_POST['meta_title']);
    $meta_description   = e_s__($_POST['meta_description']);
    $permalink          = e_s__($_POST['permalink']);
    $rejection_reason   = trim((string)($_POST['rejection_reason'] ?? ''));
} else {
    $title              = '';
    $slug               = '';
    $content            = '';
    $excerpt            = '';
    $featured_image     = '';
    $category_id        = '';
    $meta_title         = '';
    $meta_description   = '';
    $permalink          = '';
}

$content_word_count = str_word_count(trim(strip_tags((string)$content)));

if ($reviewAction !== '' && !empty($id) && granted('edit', $module_id) && empty($error_message)) {
    $rejection_reason = trim((string)($_POST['rejection_reason'] ?? ''));

    if ($reviewAction === 'approve_guest_post') {
        $approveStmt = $mysqli->prepare(
            "UPDATE `" . DB::BLOGS . "`
             SET submission_status = 'approved', is_active = 1, publish = 1, updated_by = ?, updated_at = NOW()
             WHERE id = ? AND source = 'guest'"
        );

        if ($approveStmt) {
            $approveStmt->bind_param('ii', $session_user_id, $id);
            if ($approveStmt->execute()) {
                $emailStmt = $mysqli->prepare("SELECT guest_author_name, guest_author_email, title, slug FROM `" . DB::BLOGS . "` WHERE id = ? LIMIT 1");
                if ($emailStmt) {
                    $emailStmt->bind_param('i', $id);
                    $emailStmt->execute();
                    $emailResult = $emailStmt->get_result();
                    $emailRow = $emailResult ? $emailResult->fetch_assoc() : null;
                    $emailStmt->close();

                    if (is_array($emailRow) && !empty($emailRow['guest_author_email'])) {
                        try {
                            $queue = new EmailQueue($mysqli);
                            $queue->enqueue(
                                (string)$emailRow['guest_author_email'],
                                'Your guest post has been approved - HAIPULSE',
                                "Hello " . trim((string)($emailRow['guest_author_name'] ?? 'Contributor')) . ",\n\nYour guest post \"" . (string)($emailRow['title'] ?? '') . "\" has been approved and published.\n\nView it here: " . url('/blog/' . (string)($emailRow['slug'] ?? '')) . "\n\nRegards,\nHAIPULSE",
                                ['Content-Type' => 'text/plain; charset=UTF-8'],
                                1
                            );
                        } catch (Throwable $e) {
                            error_log('Guest post approval email queue error: ' . $e->getMessage());
                        }
                    }
                }

                header('Location: listing_guest_posts.php?status=approved&success_message=' . urlencode('Guest post approved successfully.'));
                exit;
            }
            $error_message = 'Unable to approve this guest post.';
            $approveStmt->close();
        } else {
            $error_message = 'Unable to approve this guest post right now.';
        }
    } elseif ($reviewAction === 'reject_guest_post') {
        if ($rejection_reason === '') {
            $error_message = 'A rejection reason is required before rejecting a guest post.';
        } else {
            $rejectStmt = $mysqli->prepare(
                "UPDATE `" . DB::BLOGS . "`
                 SET submission_status = 'rejected', rejection_reason = ?, is_active = 0, publish = 0, updated_by = ?, updated_at = NOW()
                 WHERE id = ? AND source = 'guest'"
            );

            if ($rejectStmt) {
                $rejectStmt->bind_param('sii', $rejection_reason, $session_user_id, $id);
                if ($rejectStmt->execute()) {
                    $emailStmt = $mysqli->prepare("SELECT guest_author_name, guest_author_email, title FROM `" . DB::BLOGS . "` WHERE id = ? LIMIT 1");
                    if ($emailStmt) {
                        $emailStmt->bind_param('i', $id);
                        $emailStmt->execute();
                        $emailResult = $emailStmt->get_result();
                        $emailRow = $emailResult ? $emailResult->fetch_assoc() : null;
                        $emailStmt->close();

                        if (is_array($emailRow) && !empty($emailRow['guest_author_email'])) {
                            try {
                                $queue = new EmailQueue($mysqli);
                                $queue->enqueue(
                                    (string)$emailRow['guest_author_email'],
                                    'Guest post update - HAIPULSE',
                                    "Hello " . trim((string)($emailRow['guest_author_name'] ?? 'Contributor')) . ",\n\nThank you for your submission \"" . (string)($emailRow['title'] ?? '') . "\". It was not approved for publishing at this time.\n\nReason: " . $rejection_reason . "\n\nRegards,\nHAIPULSE",
                                    ['Content-Type' => 'text/plain; charset=UTF-8'],
                                    1
                                );
                            } catch (Throwable $e) {
                                error_log('Guest post rejection email queue error: ' . $e->getMessage());
                            }
                        }
                    }

                    header('Location: listing_guest_posts.php?status=rejected&success_message=' . urlencode('Guest post rejected successfully.'));
                    exit;
                }
                $error_message = 'Unable to reject this guest post.';
                $rejectStmt->close();
            } else {
                $error_message = 'Unable to reject this guest post right now.';
            }
        }
    }
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id) && $reviewAction === '') {

    $currentBlogSource = 'admin';
    $sourceStmt = $mysqli->prepare("SELECT source FROM `" . DB::BLOGS . "` WHERE id = ? LIMIT 1");
    if ($sourceStmt) {
        $sourceStmt->bind_param('i', $id);
        $sourceStmt->execute();
        $sourceResult = $sourceStmt->get_result();
        $sourceRow = $sourceResult ? $sourceResult->fetch_assoc() : null;
        $sourceStmt->close();
        $currentBlogSource = strtolower((string)($sourceRow['source'] ?? 'admin'));
    }

    if (empty($title)) {
        $error_message = 'Blog title is mandatory.';
    } else if (empty($slug)) {
        $error_message = 'Slug is mandatory.';
    } else if (empty($content)) {
        $error_message = 'Blog content is mandatory.';
    } else if ($currentBlogSource === 'guest' && $content_word_count > 3000) {
        $error_message = 'Guest post content cannot exceed 3000 words.';
    } else {

        /* ---------------------- PREPARED STATEMENT UPDATE ---------------------- */
        $stmt = $mysqli->prepare("
            UPDATE `" . DB::BLOGS . "` SET
                title = ?,
                slug = ?,
                content = ?,
                excerpt = ?,
                featured_image = ?,
                category_id = ?,
                meta_title = ?,
                meta_description = ?,
                permalink = ?,
                word_count = ?,
                is_homepage = ?,
                is_active = ?,
                publish = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param(
                "sssssisssiiiii",
                $title,
                $slug,
                $content,
                $excerpt,
                $featured_image,
                $category_id,
                $meta_title,
                $meta_description,
                $permalink,
                $content_word_count,
                $is_homepage, $is_active, $is_active,
                $session_user_id,
                $id
            );
            
            if ($stmt->execute()) {
                $success_message = "The $module_caption has been updated successfully.";
                fp__(DB::BLOGS, $id);
                header("Location:listing_$module.php?success_message=$success_message");
            } else {
                $error_message = "The $module_caption could not be updated. Please try again.";
            }
            $stmt->close();
        } else {
            $error_message = "The $module_caption could not be updated. Database error: " . $mysqli->error;
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id) && $reviewAction === '') {

    if (empty($title)) {
        $error_message = 'Blog title is mandatory.';
    } else if (empty($slug)) {
        $error_message = 'Slug is mandatory.';
    } else if (empty($content)) {
        $error_message = 'Blog content is mandatory.';
    } else {

        $insert_stmt = $mysqli->prepare("
            INSERT INTO `" . DB::BLOGS . "`
            (title, slug, content, excerpt, featured_image, category_id, meta_title, meta_description, permalink, source, submission_status, word_count, is_homepage, is_active, publish, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'admin', 'admin', ?, ?, ?, ?)
        ");
        
        if ($insert_stmt) {
            $insert_stmt->bind_param(
                "sssssisssiiiii",
                $title,
                $slug,
                $content,
                $excerpt,
                $featured_image,
                $category_id,
                $meta_title,
                $meta_description,
                $permalink,
                $content_word_count,
                $is_homepage, $is_active, $is_active,
                $session_user_id
            );
            
            if ($insert_stmt->execute()) {
                $id = $mysqli->insert_id;
                fp__(DB::BLOGS, $id);
                $success_message = "The $module_caption has been saved successfully.";
                header("Location:listing_$module.php?success_message=$success_message");
            } else {
                $error_message = "The $module_caption could not be saved. Please try again.";
            }
            $insert_stmt->close();
        } else {
            $error_message = "The $module_caption could not be saved. Database error: " . $mysqli->error;
        }
    }
}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/
if (!empty($id)) {

    $result = $mysqli->query("SELECT * FROM `" . DB::BLOGS . "` WHERE id=$id");
    $row = $result->fetch_array();

    $title              = s__($row['title']);
    $slug               = s__($row['slug']);
    $content            = s__($row['content']);
    $excerpt            = s__($row['excerpt']);
    $featured_image     = s__($row['featured_image']);
    $category_id        = s__($row['category_id']);
    $meta_title         = s__($row['meta_title']);
    $meta_description   = s__($row['meta_description']);
    $permalink          = s__($row['permalink']);
    $source             = s__($row['source'] ?? 'admin');
    $submission_status  = s__($row['submission_status'] ?? 'admin');
    $submitted_by       = (int)($row['submitted_by'] ?? 0);
    $guest_author_name  = s__($row['guest_author_name'] ?? '');
    $guest_author_bio   = s__($row['guest_author_bio'] ?? '');
    $guest_author_email = s__($row['guest_author_email'] ?? '');
    $rejection_reason   = s__($row['rejection_reason'] ?? '');
    $is_homepage        = (int)($row['is_homepage'] ?? 0);
    $is_active          = (int)($row['is_active'] ?? 0);
}



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">
    <?php include('admin_elements/messages.php'); ?>

    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>
        <?php echo csrf_field(); ?>

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <h5 class="ms-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch me-3">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="status" id="status" <?php if ($is_active == 1) { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="status">Publish</label>
                    </div>
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-info" name="is_homepage" id="is_homepage" <?php if ($is_homepage == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="is_homepage">Feature on Homepage</label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <div class="mt-2 mb-2">

                            <?php if (!empty($id) && $source === 'guest' && $submission_status === 'pending' && granted('edit', $module_id)) { ?>
                                <button type="submit" name="review_action" value="approve_guest_post" class="btn btn-success btn-sm me-2">Approve</button>
                                <button type="submit" name="review_action" value="reject_guest_post" class="btn btn-warning btn-sm me-2">Reject</button>
                            <?php } ?>

                            <?php if (isset($module_id) && (granted('create', $module_id) || granted('edit', $module_id))) { ?>
                                <button type="submit" class="btn btn-primary btn-sm me-2">Save</button>
                            <?php } ?>

                            <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- /page header -->

        <!-- Inner content -->
        <div class="content-inner">

            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="card">

                    <div class="card-body">

                        <?php if ($source === 'guest'): ?>
                            <div class="alert alert-info mb-4">
                                <div class="d-flex flex-wrap gap-3 align-items-start justify-content-between">
                                    <div>
                                        <strong>Guest submission</strong>
                                        <div class="small mt-1">Status: <span class="badge <?php echo $submission_status === 'approved' ? 'bg-success' : ($submission_status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark'); ?>"><?php echo htmlspecialchars(ucfirst($submission_status), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                        <?php if ($guest_author_name !== ''): ?><div class="small mt-1">Author: <?php echo htmlspecialchars($guest_author_name, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                                        <?php if ($guest_author_email !== ''): ?><div class="small mt-1">Email: <?php echo htmlspecialchars($guest_author_email, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                                        <?php if ($submitted_by > 0): ?><div class="small mt-1">Submitted by frontend user ID: <?php echo (int)$submitted_by; ?></div><?php endif; ?>
                                    </div>
                                    <div class="small text-muted">Review content, then approve to publish or reject with feedback.</div>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-lg-3 col-form-label">Guest Author Bio:</label>
                                <div class="col-lg-9">
                                    <textarea class="form-control" rows="3" readonly><?php echo htmlspecialchars($guest_author_bio, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-lg-3 col-form-label">Rejection Reason:</label>
                                <div class="col-lg-9">
                                    <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="3" placeholder="Required only when rejecting a guest post"><?php echo htmlspecialchars($rejection_reason, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Blog Title:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="title" id="title" placeholder="Enter blog post title" value="<?php echo $title; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Slug:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="slug" id="slug" placeholder="Enter slug (e.g., my-blog-post)" value="<?php echo $slug; ?>" required>
                                <small class="text-muted">URL-friendly identifier</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Category:*</span></label>
                            <div class="col-lg-9">
                                <select class="form-select" name="category_id" id="category_id" required>
                                    <option value="">-- Select Category --</option>
                                    <?php
                                    $category_result = $mysqli->query("SELECT id, name FROM `" . tbl_blog_categories . "` WHERE status=1 ORDER BY name");
                                    while ($cat_row = $category_result->fetch_assoc()) {
                                        $selected = ($category_id == $cat_row['id']) ? 'selected' : '';
                                        echo '<option value="' . $cat_row['id'] . '" ' . $selected . '>' . $cat_row['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Excerpt:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="excerpt" id="excerpt" rows="2" placeholder="Brief summary of the blog post"><?php echo $excerpt; ?></textarea>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Content:*</span></label>
                            <div class="col-lg-9">
                                <textarea class="form-control editor" name="content" id="content" placeholder="Blog post content" required><?php echo $content; ?></textarea>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Featured Image:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="featured_image" id="featured_image" placeholder="Image filename" value="<?php echo $featured_image; ?>">
                                <small class="text-muted">Filename of the featured image</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Permalink:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="permalink" id="permalink" placeholder="Full URL path" value="<?php echo $permalink; ?>">
                            </div>
                        </div>

                    </div>

                </div>

                <!-- SEO Section -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">SEO Settings</h6>
                    </div>
                    <div class="card-body">

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Meta Title:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="meta_title" id="meta_title" placeholder="SEO meta title" value="<?php echo $meta_title; ?>" maxlength="60">
                                <small class="text-muted">Recommended: 50-60 characters</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Meta Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="meta_description" id="meta_description" rows="2" placeholder="SEO meta description" maxlength="160"><?php echo $meta_description; ?></textarea>
                                <small class="text-muted">Recommended: 150-160 characters</small>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
        <!-- /inner content -->

    </form>

    <?php include('admin_elements/copyright.php'); ?>

</div>

<?php include('admin_elements/admin_footer.php'); ?>

