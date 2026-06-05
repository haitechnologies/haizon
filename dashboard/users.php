<?php


use App\Core\DB;
use App\Security\Roles;
include('admin_elements/admin_header.php');

$module             = 'users';
$module_caption     = 'Employee';
$tbl_name = $tbl_prefix . $module;

$error_message = '';
$success_message = '';

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
        log_error('CSRF token validation failed in users.php', 'WARNING', __FILE__, __LINE__);
    }
}


use App\Core\Container;
use App\Service\UserService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

$container = Container::getInstance();
$userService = $container->get(UserService::class);

$role_id_check = getTableAttr('role_id', DB::USERS, $id);

if (Roles::hasFullAccess((int)$role_id_check)) {
    flash_error("Super Admin accounts cannot be modified from this interface. To edit your own profile, please use the 'My Profile' menu.");
    header("Location:listing_users.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $can_access_system = isset($_POST['can_access_system']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
} else {
    $can_access_system = 1;
    $is_active = 0;
}

if ($action == "update_$module" || $action == "add_$module") {
    $role_id                = $_POST['role_id'] ?? '';
    $full_name              = $_POST['full_name'] ?? '';
    $email                  = $_POST['email'] ?? '';
    $password               = $_POST['password'] ?? '';
    $contact1               = $_POST['contact1'] ?? '';
    $contact2               = $_POST['contact2'] ?? '';
    $address                = $_POST['address'] ?? '';
    $dob                    = $_POST['dob'] ?? '';
} else {
    $role_id                = '';
    $full_name              = '';
    $email                  = '';
    $password               = '';
    $contact1               = '';
    $contact2               = '';
    $address                = '';
    $dob                    = '';
}

if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    try {
        $data = [
            'role_id' => $role_id,
            'full_name' => $full_name,
            'email' => $email,
            'password' => $password,
            'contact1' => $contact1,
            'contact2' => $contact2,
            'address' => $address,
            'dob' => $dob,
            'can_access_system' => $can_access_system === 1,
            'is_active' => $is_active === 1,
        ];
        $userService->update((int)$id, $data);
        $success_message = "Employee profile updated successfully.";
        fp__($tbl_name, $id);
        flash_success($success_message);
        header("Location:listing_$module.php");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
        flash_error($error_message);
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
        flash_error($error_message);
    } catch (\Throwable $e) {
        $error_message = "Unable to update employee profile. Please review the form and try again.";
        flash_error($error_message);
    }
} else if ($action == "add_$module" && granted('create', $module_id)) {
    try {
        $data = [
            'role_id' => $role_id,
            'full_name' => $full_name,
            'email' => $email,
            'password' => $password,
            'contact1' => $contact1,
            'contact2' => $contact2,
            'address' => $address,
            'dob' => $dob,
            'can_access_system' => $can_access_system === 1,
            'is_active' => $is_active === 1,
        ];
        $newUser = $userService->create($data, (int)($_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 0));
        $id = $newUser->id;
        fp__($tbl_name, $id);
        $success_message = "Employee account created successfully.";
        flash_success($success_message);
        header("Location:listing_$module.php");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
        flash_error($error_message);
    } catch (\Throwable $e) {
        $error_message = "Unable to create employee account. Please review the form and try again.";
        flash_error($error_message);
    }
}

if (!empty($id)) {
    try {
        $user = $userService->getById((int)$id);
        $role_id                = (string)$user->roleId;
        $full_name              = s__($user->fullName);
        $email                  = s__($user->email);
        $password               = s__($user->password ?? '');
        $contact1               = s__($user->contact1 ?? '');
        $contact2               = s__($user->contact2 ?? '');
        $address                = s__($user->address ?? '');
        $dob                    = $user->dob ? processDateYtoD($user->dob) : '';
        $can_access_system      = $user->canAccessSystem ? 1 : 0;
        $is_active              = $user->isActive ? 1 : 0;
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
        flash_error($error_message);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>
<div class="content-wrapper">

    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
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
                        <h1 class="ms-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h1>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>


                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="can_access_system" id="can_access_system" <?php if ($can_access_system == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="sc_r_success">Can Access System?</label>
                    </div>
                </div>

                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="sc_r_success">Is Active?</label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <div class="mt-2 mb-2">

                            <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                                <button type="submit" class="btn btn-primary btn-sm me-2">Save</button>
                            <?php } ?>

                            <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- /page header -->

        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="row">
                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-body">

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">System Role:*</span></label>
                                    <div class="col-lg-9">
                                        <select class="form-select" name="role_id" id="role_id">
                                            <option value='0'>Please select</option>
                                            <?php
                                            $result_roles = $mysqli->query("SELECT * FROM `" . DB::ROLES  . "` WHERE is_active=1 AND id > 2 ORDER BY role_name ASC");
                                            while ($rows_roles = $result_roles->fetch_array()) {
                                                // $role        = s__($rows_roles['role
                                            ?>
                                                <option value="<?php echo $rows_roles['id']; ?>" <?php if ($action == "edit_$module" && $rows_roles['id'] == $role_id) { ?>selected <?php } else if ($rows_roles['id'] == $role_id) { ?>selected <?php } ?>>
                                                    <?php echo $rows_roles['role_name']; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>
                                        <div class="form-text text-muted">System Access: Roles & Permissions</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label" for="full_name"><span class="text-danger">Full Name:*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="full_name" id="full_name" value="<?php echo $full_name; ?>" class="form-control" aria-required="true" aria-label="Full name (required)">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label" for="email"><span class="text-danger">Email:*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="email" name="email" id="email" value="<?php echo $email; ?>" class="form-control" aria-required="true" aria-label="Email address (required)">
                                    </div>
                                </div>


                                <?php if (has_full_access()) { ?>
                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">
                                            Password:
                                            <?php if (!empty($password)) { ?>
                                                <div class="text-end mt-1">
                                                    <span class="badge bg-indigo">Password Generated</span>
                                                </div>
                                            <?php } ?>

                                        </label>
                                        <div class="col-lg-9">
                                            <input type="password" name="password" id="password" class="form-control password-input" data-strength-target="#password">
                                            <div class="form-text text-muted">Password lenght must be between 6 - 20 chars</div>
                                            <div id="password-strength-status"></div>

                                        </div>
                                    </div>
                                <?php } ?>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Contact 1:*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="contact1" id="contact1" value="<?php echo $contact1; ?>" class="form-control">
                                        <div class="form-text text-muted">050 1234574</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Contact 2:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="contact2" id="contact2" value="<?php echo $contact2; ?>" class="form-control">
                                        <div class="form-text text-muted">050 1234574</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Address:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="address" id="address" value="<?php echo $address; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Date of Birth:</label>
                                    <div class="col-lg-9">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ph-calendar"></i>
                                            </span>
                                            <input type="text" class="form-control datepicker-basic datepicker-input in-edit" name="dob" id="dob" value="<?php ($dob == '01-01-1970') ? '' : print($dob); ?>" placeholder="Date of Birth">
                                        </div>
                                    </div>
                                </div>


                            </div>

                        </div>

                    </div>



                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-header">
                                <h2 class="mb-0">Documents</h2>
                            </div>

                            <div class="card-body">




                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <?php include('admin_elements/copyright.php'); ?>
</div>
</form>
</div>


<!-- 
    // ---------------------------------------------------------
    // ENABLE VIEW ONLY MODE FOR FORM ELEMENTS
    // ---------------------------------------------------------
-->
<?php if (isset($module_id) && granted('view', $module_id) && !granted('create', $module_id) && !granted('edit', $module_id)) { ?>
    <script>
        $(function() {
            toggleFormElements('true');
        });
    </script>
<?php } ?>

<?php include('admin_elements/admin_footer.php'); ?>