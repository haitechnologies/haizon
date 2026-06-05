<?php
ob_start(); // allow clean JSON AJAX responses before HTML output
include('admin_elements/admin_header.php');

$module = 'public_ads';
$module_id = getModuleIdBySlug($module, $mysqli);
$module_caption = 'Public Ad';
$tbl_name = DB::PUBLIC_ADS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if (($action == "delete_$module" || $action == "toggle_$module") && !validate_csrf_token($_GET['csrf_token'] ?? '')) {
	$error_message = 'Invalid security token. Please refresh and try again.';
}

if ($action == "delete_$module" && !empty($id) && $error_message === '' && granted_('delete', $module)) {
	$stmt = $mysqli->prepare("DELETE FROM `" . $tbl_name . "` WHERE id = ?");
	if ($stmt) {
		$itemId = (int)$id;
		$stmt->bind_param('i', $itemId);
		$stmt->execute();
		$stmt->close();
		header("Location: listing_$module.php?deleted=1");
		exit;
	}
}

if ($action == "toggle_$module" && !empty($id) && $error_message === '' && granted_('edit', $module)) {
	$stmt = $mysqli->prepare("UPDATE `" . $tbl_name . "` SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ?");
	if ($stmt) {
		$itemId = (int)$id;
		$stmt->bind_param('i', $itemId);
		$stmt->execute();
		$stmt->close();
		header("Location: listing_$module.php?toggled=1");
		exit;
	}
}

/*
|--------------------------------------------------------------------------
| MASTER ADS TOGGLE
|--------------------------------------------------------------------------
*/
if ($action === 'toggle_ads_master' && validate_csrf_token($_GET['csrf_token'] ?? '') && granted_('edit', $module)) {
	// Read current value directly from DB (bypasses any stale static cache)
	$currentMaster = '1';
	$existingId = 0;
	$stmtCheck = $mysqli->prepare(
		"SELECT id, setting_value FROM `" . DB::SYSTEM_SETTINGS . "` WHERE setting_slug = 'ads_master_enabled' LIMIT 1"
	);
	if ($stmtCheck) {
		$stmtCheck->execute();
		$stmtCheck->bind_result($existingId, $currentMaster);
		$stmtCheck->fetch();
		$stmtCheck->close();
	}
	$newMasterVal = ($currentMaster === '1') ? '0' : '1';

	if ($existingId > 0) {
		// Row exists â€” just update the value
		$stmtUpd = $mysqli->prepare(
			"UPDATE `" . DB::SYSTEM_SETTINGS . "` SET setting_value = ?, updated_at = NOW() WHERE id = ?"
		);
		if ($stmtUpd) {
			$stmtUpd->bind_param('si', $newMasterVal, $existingId);
			$stmtUpd->execute();
			$stmtUpd->close();
		}
	} else {
		// Row doesn't exist yet â€” insert with all required columns
		$stmtIns = $mysqli->prepare(
			"INSERT INTO `" . DB::SYSTEM_SETTINGS . "` (setting_slug, setting_name, setting_value) VALUES ('ads_master_enabled', 'Ads Master Enabled', ?)"
		);
		if ($stmtIns) {
			$stmtIns->bind_param('s', $newMasterVal);
			$stmtIns->execute();
			$stmtIns->close();
		}
	}

	// Bust request-level caches so on-page render sees updated value
	unset($GLOBALS['SYSTEM_SETTINGS']['ads_master_enabled']);
	header('Location: listing_public_ads.php?master_toggled=1');
	exit;
}

/*
|--------------------------------------------------------------------------
| GET AD JSON (AJAX â€” for edit popup)
|--------------------------------------------------------------------------
*/
if ($action === 'get_ad_json' && !empty($id) && granted_('edit', $module)) {
	ob_clean();
	header('Content-Type: application/json; charset=utf-8');
	$itemId = (int)$id;
	$stmt = $mysqli->prepare("SELECT * FROM `" . $tbl_name . "` WHERE id = ? LIMIT 1");
	if ($stmt) {
		$stmt->bind_param('i', $itemId);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_assoc();
		$stmt->close();
		echo $row ? json_encode(['ok' => true, 'ad' => $row]) : json_encode(['ok' => false, 'error' => 'Ad not found']);
	} else {
		echo json_encode(['ok' => false, 'error' => 'Query error']);
	}
	exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE AD VIA AJAX (popup form submit)
|--------------------------------------------------------------------------
*/
if ($action === 'update_ad_ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	ob_clean();
	header('Content-Type: application/json; charset=utf-8');
	if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
		echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token. Please refresh and try again.']);
		exit;
	}
	if (!granted_('edit', $module)) {
		echo json_encode(['ok' => false, 'error' => 'Permission denied.']);
		exit;
	}
	$editId        = (int)($_POST['id'] ?? 0);
	$p_campaign    = e_s__($_POST['campaign_name'] ?? '');
	$p_placement   = e_s__($_POST['placement_key'] ?? 'home_hero');
	$p_format      = e_s__($_POST['ad_format'] ?? 'hybrid');
	$p_title       = e_s__($_POST['title'] ?? '');
	$p_desc        = e_s__($_POST['description'] ?? '');
	$p_cta         = e_s__($_POST['cta_text'] ?? 'Learn more');
	$p_url         = e_s__($_POST['target_url'] ?? '');
	$p_img         = e_s__($_POST['image_path'] ?? '');
	$p_imgalt      = e_s__($_POST['image_alt'] ?? '');
	$p_badge       = e_s__($_POST['badge_text'] ?? '');
	$p_prodcat     = e_s__($_POST['product_category'] ?? 'software');
	$p_scope       = e_s__($_POST['page_scope'] ?? '');
	$p_tags        = e_s__($_POST['keyword_tags'] ?? '');
	$p_priority    = max(0, (int)($_POST['priority'] ?? 5));
	$p_weight      = max(1, (int)($_POST['weight'] ?? 1));
	$p_starts      = e_s__($_POST['starts_at'] ?? '');
	$p_ends        = e_s__($_POST['ends_at'] ?? '');
	$p_active      = isset($_POST['is_active']) ? 1 : 0;
	if ($editId <= 0) {
		echo json_encode(['ok' => false, 'error' => 'Invalid ad ID.']);
		exit;
	}
	if ($p_campaign === '' || $p_title === '' || $p_url === '') {
		echo json_encode(['ok' => false, 'error' => 'Campaign name, title, and target URL are required.']);
		exit;
	}
	$stmt = $mysqli->prepare(
		"UPDATE `" . $tbl_name . "`
		 SET campaign_name=?, placement_key=?, ad_format=?, title=?, description=?, cta_text=?,
		     target_url=?, image_path=?, image_alt=?, badge_text=?, product_category=?,
		     page_scope=?, keyword_tags=?, priority=?, weight=?, is_active=?,
		     starts_at=?, ends_at=?, updated_at=NOW()
		 WHERE id=?"
	);
	if ($stmt) {
		$startsVal = $p_starts !== '' ? $p_starts : null;
		$endsVal   = $p_ends !== '' ? $p_ends : null;
		$stmt->bind_param(
			'sssssssssssssiiissi',
			$p_campaign, $p_placement, $p_format, $p_title, $p_desc, $p_cta,
			$p_url, $p_img, $p_imgalt, $p_badge, $p_prodcat,
			$p_scope, $p_tags, $p_priority, $p_weight, $p_active,
			$startsVal, $endsVal, $editId
		);
		$stmt->execute();
		$stmt->close();
		echo json_encode(['ok' => true, 'message' => 'Ad updated successfully.']);
	} else {
		echo json_encode(['ok' => false, 'error' => 'Database error: ' . $mysqli->error]);
	}
	exit;
}

$adsMasterEnabled = true; // default ON
$stmtMasterRead = $mysqli->prepare(
	"SELECT setting_value FROM `" . DB::SYSTEM_SETTINGS . "` WHERE setting_slug = 'ads_master_enabled' LIMIT 1"
);
if ($stmtMasterRead) {
	$stmtMasterRead->execute();
	$stmtMasterRead->bind_result($masterSettingVal);
	if ($stmtMasterRead->fetch()) {
		$adsMasterEnabled = ($masterSettingVal === '1');
	}
	$stmtMasterRead->close();
}

if (isset($_GET['created'])) {
	$success_message = 'Public ad created successfully.';
} elseif (isset($_GET['updated'])) {
	$success_message = 'Public ad updated successfully.';
} elseif (isset($_GET['deleted'])) {
	$success_message = 'Public ad deleted successfully.';
} elseif (isset($_GET['toggled'])) {
	$success_message = 'Public ad status updated successfully.';
} elseif (isset($_GET['master_toggled'])) {
	$success_message = 'Public ads master switch updated successfully.';
}
?>

<div class="content-wrapper">

	<?php include('admin_elements/page_header.php'); ?>

	<div class="content datatable-enhanced">
		<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
		<style>
			#grid-<?php echo $module; ?> .js-ad-thumb {
				display: inline-block;
				line-height: 0;
			}

			#grid-<?php echo $module; ?> .js-ad-thumb img {
				transition: transform 0.15s ease;
			}

			#grid-<?php echo $module; ?> .js-ad-thumb:hover img {
				transform: scale(1.05);
			}
		</style>

		<?php include('admin_elements/breadcrumb.php'); ?>

		<?php if ($error_message !== ''): ?>
			<div class="alert alert-danger"><?php echo e($error_message); ?></div>
		<?php endif; ?>
		<?php if ($success_message !== ''): ?>
			<div class="alert alert-success"><?php echo e($success_message); ?></div>
		<?php endif; ?>

		<!-- =====================================================
		     MASTER ADS SWITCH
		     ===================================================== -->
		<div class="card mb-3 border-<?php echo $adsMasterEnabled ? 'success' : 'secondary'; ?>">
			<div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
				<div class="flex-grow-1">
					<div class="d-flex align-items-center gap-2 mb-1">
						<span class="badge <?php echo $adsMasterEnabled ? 'bg-success' : 'bg-secondary'; ?> fs-6 px-3 py-2">
							<?php echo $adsMasterEnabled ? 'ADS ON' : 'ADS OFF'; ?>
						</span>
						<h6 class="mb-0 fw-bold">Public Ads Master Switch</h6>
					</div>
					<small class="text-muted">
						<?php if ($adsMasterEnabled): ?>
							All active ads are currently <strong class="text-success">showing</strong> on the public website. Click to disable all ads instantly.
						<?php else: ?>
							All ads are currently <strong class="text-danger">hidden</strong> from the public website regardless of individual ad status. Click to re-enable.
						<?php endif; ?>
					</small>
				</div>
				<?php if (granted_('edit', $module)): ?>
				<a href="listing_public_ads.php?action=toggle_ads_master&csrf_token=<?php echo urlencode(csrf_token()); ?>"
				   class="btn btn-lg fw-semibold <?php echo $adsMasterEnabled ? 'btn-success' : 'btn-outline-secondary'; ?>"
				   onclick="return confirm('<?php echo $adsMasterEnabled ? 'Disable ALL public ads on the website?' : 'Enable ALL public ads on the website?'; ?>')">
					<i class="ph <?php echo $adsMasterEnabled ? 'ph-toggle-right' : 'ph-toggle-left'; ?> me-2" style="font-size:1.3em;vertical-align:middle;"></i>
					<?php echo $adsMasterEnabled ? 'Click to Disable All Ads' : 'Click to Enable All Ads'; ?>
				</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="card">
			<div class="card-body">
				<table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
					<thead>
						<tr>
							<th width="60">ID</th>
							<th width="80">Thumb</th>
							<th>Campaign</th>
							<th width="140">Placement</th>
							<th width="90">Format</th>
							<th width="100">Status</th>
							<th width="110">Impressions</th>
							<th width="90">Clicks</th>
							<th width="85">Priority</th>
							<th width="150">Updated</th>
							<th width="220">Actions</th>
						</tr>
					</thead>
				</table>
			</div>
		</div>

		<div class="modal fade" id="adThumbPreviewModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Ad Image Preview</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body text-center">
						<img id="adThumbPreviewImage" src="" alt="Ad preview" class="img-fluid rounded border">
					</div>
				</div>
			</div>
		</div>

		<!-- =====================================================
		     EDIT AD POPUP MODAL
		     ===================================================== -->
		<div class="modal fade" id="editAdModal" tabindex="-1" aria-labelledby="editAdModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-xl modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header bg-primary text-white">
						<h5 class="modal-title" id="editAdModalLabel">
							<i class="ph ph-pencil-simple me-2"></i>Edit Public Ad
							<small class="ms-2 opacity-75" id="editAdModalSubtitle"></small>
						</h5>
						<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>

					<div class="modal-body" id="editAdModalBody">
						<!-- Loading state -->
						<div id="editAdLoadingState" class="text-center py-5">
							<div class="spinner-border text-primary mb-3" role="status"></div>
							<p class="text-muted">Loading ad detailsâ€¦</p>
						</div>

						<!-- Error state -->
						<div id="editAdErrorState" class="alert alert-danger d-none"></div>

						<!-- Save feedback -->
						<div id="editAdSaveFeedback" class="d-none mb-3"></div>

						<!-- Form -->
						<form id="editAdForm" class="d-none" autocomplete="off">
							<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
							<input type="hidden" name="action" value="update_ad_ajax">
							<input type="hidden" name="id" id="editAdId">

							<div class="row g-3">
								<!-- LEFT COLUMN: Ad Content -->
								<div class="col-lg-7">
									<div class="card h-100">
										<div class="card-header d-flex align-items-center gap-2">
											<i class="ph ph-article text-primary"></i>
											<strong>Ad Content</strong>
										</div>
										<div class="card-body">

											<div class="mb-3">
												<label class="form-label fw-semibold">
													Campaign Name <span class="text-danger">*</span>
												</label>
												<input type="text" class="form-control" name="campaign_name" id="edit_campaign_name" required
												       placeholder="e.g. HaiPulse Pro Launch Q2">
												<div class="form-text">Internal name to identify this campaign.</div>
											</div>

											<div class="row g-3 mb-3">
												<div class="col-sm-6">
													<label class="form-label fw-semibold">Placement</label>
													<select class="form-select" name="placement_key" id="edit_placement_key">
														<option value="home_hero">Home Hero</option>
														<option value="listings_inline">Listings Inline</option>
														<option value="trade_feature">Trade Feature</option>
														<option value="hs_sidebar">HS Code Sidebar</option>
														<option value="global_footer">Global Footer</option>
														<option value="ads_page">Ads Page</option>
														<option value="global">Global (all pages)</option>
													</select>
													<div class="form-text">Where this ad appears on the site.</div>
												</div>
												<div class="col-sm-6">
													<label class="form-label fw-semibold">Format</label>
													<select class="form-select" name="ad_format" id="edit_ad_format">
														<option value="text">Text only</option>
														<option value="image">Image only</option>
														<option value="hybrid">Hybrid (text + image)</option>
													</select>
													<div class="form-text">How the ad is rendered visually.</div>
												</div>
											</div>

											<div class="mb-3">
												<label class="form-label fw-semibold">
													Ad Title <span class="text-danger">*</span>
												</label>
												<input type="text" class="form-control" name="title" id="edit_title" required
												       placeholder="Short, punchy headline">
											</div>

											<div class="mb-3">
												<label class="form-label fw-semibold">Description</label>
												<textarea class="form-control" name="description" id="edit_description" rows="3"
												          placeholder="Supporting copy shown below the title"></textarea>
											</div>

											<div class="row g-3 mb-3">
												<div class="col-sm-6">
													<label class="form-label fw-semibold">CTA Button Text</label>
													<input type="text" class="form-control" name="cta_text" id="edit_cta_text"
													       placeholder="e.g. Learn more">
												</div>
												<div class="col-sm-6">
													<label class="form-label fw-semibold">Badge Text</label>
													<input type="text" class="form-control" name="badge_text" id="edit_badge_text"
													       placeholder="e.g. New, Hot, Featured">
												</div>
											</div>

											<div class="mb-3">
												<label class="form-label fw-semibold">
													Target URL <span class="text-danger">*</span>
												</label>
												<input type="url" class="form-control" name="target_url" id="edit_target_url" required
												       placeholder="https://example.com/landing-page">
												<div class="form-text">Where users go when they click this ad.</div>
											</div>

											<div class="row g-3">
												<div class="col-sm-8">
													<label class="form-label fw-semibold">Image Path</label>
													<input type="text" class="form-control" name="image_path" id="edit_image_path"
													       placeholder="assets/images/banners/banner1.jpg">
												</div>
												<div class="col-sm-4">
													<label class="form-label fw-semibold">Image Alt Text</label>
													<input type="text" class="form-control" name="image_alt" id="edit_image_alt"
													       placeholder="Descriptive alt">
												</div>
											</div>

										</div>
									</div>
								</div>

								<!-- RIGHT COLUMN: Settings & Targeting -->
								<div class="col-lg-5">
									<div class="card mb-3">
										<div class="card-header d-flex align-items-center gap-2">
											<i class="ph ph-sliders-horizontal text-warning"></i>
											<strong>Delivery &amp; Status</strong>
										</div>
										<div class="card-body">
											<div class="form-check form-switch mb-3">
												<input class="form-check-input" type="checkbox" role="switch"
												       name="is_active" id="edit_is_active" value="1">
												<label class="form-check-label fw-semibold" for="edit_is_active">
													Active (visible on public site)
												</label>
											</div>

											<div class="row g-2 mb-3">
												<div class="col-6">
													<label class="form-label fw-semibold">Priority</label>
													<input type="number" class="form-control" name="priority" id="edit_priority"
													       min="0" max="100">
													<div class="form-text">Higher = shown first (0â€“100).</div>
												</div>
												<div class="col-6">
													<label class="form-label fw-semibold">Weight</label>
													<input type="number" class="form-control" name="weight" id="edit_weight"
													       min="1">
													<div class="form-text">Relative weighting for rotation.</div>
												</div>
											</div>

											<div class="row g-2">
												<div class="col-6">
													<label class="form-label fw-semibold">Starts At</label>
													<input type="datetime-local" class="form-control" name="starts_at" id="edit_starts_at">
													<div class="form-text">Leave blank = no start restriction.</div>
												</div>
												<div class="col-6">
													<label class="form-label fw-semibold">Ends At</label>
													<input type="datetime-local" class="form-control" name="ends_at" id="edit_ends_at">
													<div class="form-text">Leave blank = runs indefinitely.</div>
												</div>
											</div>
										</div>
									</div>

									<div class="card">
										<div class="card-header d-flex align-items-center gap-2">
											<i class="ph ph-crosshair text-info"></i>
											<strong>Targeting</strong>
										</div>
										<div class="card-body">
											<div class="mb-3">
												<label class="form-label fw-semibold">Product Category</label>
												<input type="text" class="form-control" name="product_category" id="edit_product_category"
												       placeholder="e.g. software, crm">
												<div class="form-text">Category this ad belongs to.</div>
											</div>
											<div class="mb-3">
												<label class="form-label fw-semibold">Page Scope</label>
												<input type="text" class="form-control" name="page_scope" id="edit_page_scope"
												       placeholder="home,listings,trade">
												<div class="form-text">Comma-separated page slugs. Leave blank for all pages.</div>
											</div>
											<div class="mb-0">
												<label class="form-label fw-semibold">Keyword Tags</label>
												<input type="text" class="form-control" name="keyword_tags" id="edit_keyword_tags"
												       placeholder="crm,sales,automation">
												<div class="form-text">Comma-separated keywords for contextual matching.</div>
											</div>
										</div>
									</div>
								</div>
							</div><!-- /.row -->
						</form>
					</div><!-- /.modal-body -->

					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="button" class="btn btn-primary fw-semibold" id="editAdSaveBtn" disabled>
							<i class="ph ph-floppy-disk me-2"></i>Save Changes
						</button>
					</div>
				</div>
			</div>
		</div>

	</div>

	<?php include('admin_elements/copyright.php'); ?>
</div>

<script>
$(document).ready(function() {
	window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
		stateSave: false,
		deferRender: true,
		retrieve: false,
		dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
		lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
		pageLength: 25,
		ajax: {
			url: 'datatables_dispatcher.php',
			data: function(d) {
				d.module = '<?php echo $module; ?>';
				d.csrf_token = $('input[name="csrf_token"]').first().val() || '';
				return d;
			}
		},
		columns: [
			{ data: 0 },
			{ data: 1 },
			{ data: 2 },
			{ data: 3 },
			{ data: 4 },
			{ data: 5 },
			{ data: 6 },
			{ data: 7 },
			{ data: 8 },
			{ data: 9 },
			{ data: 10, orderable: false, searchable: false }
		],
		order: [[0, 'desc']]
	});

	$(document).on('click', '.js-ad-thumb', function(e) {
		e.preventDefault();
		var src = $(this).data('image') || '';
		var alt = $(this).data('alt') || 'Ad preview';
		if (!src) {
			return;
		}

		$('#adThumbPreviewImage').attr('src', src).attr('alt', alt);
		if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
			window.bootstrap.Modal.getOrCreateInstance(document.getElementById('adThumbPreviewModal')).show();
		}
	});

	// ---- Edit Ad Popup ----
	var editAdModal = null;
	if (window.bootstrap && document.getElementById('editAdModal')) {
		editAdModal = new window.bootstrap.Modal(document.getElementById('editAdModal'), {backdrop: 'static'});
	}

	function openEditAdModal(adId) {
		if (!editAdModal) return;
		// Reset state
		$('#editAdLoadingState').removeClass('d-none');
		$('#editAdErrorState').addClass('d-none').text('');
		$('#editAdSaveFeedback').addClass('d-none');
		$('#editAdForm').addClass('d-none');
		$('#editAdSaveBtn').prop('disabled', true);
		$('#editAdModalSubtitle').text('#' + adId);
		editAdModal.show();

		$.ajax({
			url: 'listing_public_ads.php',
			method: 'GET',
			dataType: 'json',
			data: {
				action: 'get_ad_json',
				id: adId,
				csrf_token: $('input[name="csrf_token"]').first().val()
			},
			success: function(res) {
				$('#editAdLoadingState').addClass('d-none');
				if (!res || !res.ok) {
					$('#editAdErrorState').removeClass('d-none').text(res && res.error ? res.error : 'Failed to load ad data.');
					return;
				}
				var ad = res.ad;
				$('#editAdId').val(ad.id);
				$('#editAdModalSubtitle').text('#' + ad.id + ' â€” ' + (ad.campaign_name || ''));
				$('#edit_campaign_name').val(ad.campaign_name || '');
				$('#edit_placement_key').val(ad.placement_key || 'home_hero');
				$('#edit_ad_format').val(ad.ad_format || 'hybrid');
				$('#edit_title').val(ad.title || '');
				$('#edit_description').val(ad.description || '');
				$('#edit_cta_text').val(ad.cta_text || '');
				$('#edit_target_url').val(ad.target_url || '');
				$('#edit_image_path').val(ad.image_path || '');
				$('#edit_image_alt').val(ad.image_alt || '');
				$('#edit_badge_text').val(ad.badge_text || '');
				$('#edit_product_category').val(ad.product_category || '');
				$('#edit_page_scope').val(ad.page_scope || '');
				$('#edit_keyword_tags').val(ad.keyword_tags || '');
				$('#edit_priority').val(ad.priority || 5);
				$('#edit_weight').val(ad.weight || 1);
				// datetime-local format: YYYY-MM-DDTHH:mm
				$('#edit_starts_at').val(ad.starts_at ? ad.starts_at.replace(' ', 'T').substring(0, 16) : '');
				$('#edit_ends_at').val(ad.ends_at ? ad.ends_at.replace(' ', 'T').substring(0, 16) : '');
				$('#edit_is_active').prop('checked', parseInt(ad.is_active) === 1);
				$('#editAdForm').removeClass('d-none');
				$('#editAdSaveBtn').prop('disabled', false);
			},
			error: function(xhr) {
				$('#editAdLoadingState').addClass('d-none');
				$('#editAdErrorState').removeClass('d-none').text('Network error loading ad. Please try again.');
			}
		});
	}

	// Row click opens edit popup (ignore clicks on action buttons/links)
	$('#grid-<?php echo $module; ?> tbody').on('click', 'tr', function(e) {
		if ($(e.target).closest('a, button').length) return;
		var table = $('#grid-<?php echo $module; ?>').DataTable();
		var rowData = table.row(this).data();
		if (!rowData) return;
		// First column is the ID
		var adId = parseInt(rowData[0], 10);
		if (adId > 0) {
			openEditAdModal(adId);
		}
	});

	// Add pointer cursor to rows
	$('<style>#grid-<?php echo $module; ?> tbody tr { cursor: pointer; }</style>').appendTo('head');

	// Inline status toggle via badge click
	$(document).on('click', '.js-toggle-ad-status', function(e) {
		e.stopPropagation();
		var $badge = $(this);
		var adId = parseInt($badge.data('ad-id'), 10);
		var csrf = $badge.data('csrf');
		if (!adId) return;
		$badge.css('opacity', '0.5').css('pointer-events', 'none');
		$.get('listing_public_ads.php', { action: 'toggle_public_ads', id: adId, csrf_token: csrf })
			.always(function() {
				$('#grid-<?php echo $module; ?>').DataTable().ajax.reload(null, false);
			});
	});

	// Save changes from modal
	$('#editAdSaveBtn').on('click', function() {
		var $btn = $(this);
		var $form = $('#editAdForm');
		var formData = $form.serialize();
		$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Savingâ€¦');
		$('#editAdSaveFeedback').addClass('d-none');

		$.ajax({
			url: 'listing_public_ads.php',
			method: 'POST',
			dataType: 'json',
			data: formData,
			success: function(res) {
				$btn.prop('disabled', false).html('<i class="ph ph-floppy-disk me-2"></i>Save Changes');
				var $fb = $('#editAdSaveFeedback').removeClass('d-none');
				if (res && res.ok) {
					$fb.removeClass('alert-danger').addClass('alert alert-success').text(res.message || 'Saved successfully.');
					// Refresh the DataTable row without full page reload
					var grid = $('#grid-<?php echo $module; ?>').DataTable();
					if (grid) {
						grid.ajax.reload(null, false);
					}
					// Close modal after 1 second
					setTimeout(function() {
						if (editAdModal) editAdModal.hide();
					}, 1200);
				} else {
					$fb.removeClass('alert-success').addClass('alert alert-danger').text(res && res.error ? res.error : 'Save failed. Please try again.');
				}
			},
			error: function() {
				$btn.prop('disabled', false).html('<i class="ph ph-floppy-disk me-2"></i>Save Changes');
				$('#editAdSaveFeedback').removeClass('d-none').removeClass('alert-success').addClass('alert alert-danger').text('Network error. Please try again.');
			}
		});
	});
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

