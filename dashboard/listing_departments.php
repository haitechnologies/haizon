<?php
declare(strict_types=1);

use App\Core\DB;
include('admin_elements/admin_header.php');
$module = 'departments';
$module_caption = 'Department';
$tbl_name = DB::DEPARTMENTS;
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
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

use App\Core\Container;
use App\Core\Database;
use App\Service\DepartmentService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

$container   = Container::getInstance();
$db          = $container->get(Database::class);
$deptService = $container->get(DepartmentService::class);

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {
    try {
        if (!is_SuperAdmin()) {
            $dept = $deptService->getById((int)$id);
            if ($dept->createdBy !== (int)$session_user_id) {
                $error_message = "You do not have permission to delete this department.";
            }
        }

        if (empty($error_message)) {
            $deptService->delete((int)$id);
            $success_message = "Item deleted successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        }
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
    } catch (\Throwable $e) {
        $error_message = "An error occurred while deleting the department.";
    }
}

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<div class="content-wrapper">

    <!-- Page header -->
    <?php include('admin_elements/page_header.php'); ?>
    <!-- /page header -->


    <div class="content datatable-enhanced">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="card">
            <div class="card-body">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th width="40">SR.</th>
                            <th>DEPARTMENT</th>
                            <th>EMPLOYEES</th>
                            <th width="90">CREATED AT</th>
                            <th width="80" class="col-center">STATUS</th>
                            <th width="90" class="col-center">ACTIONS</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<script>
$(document).ready(function() {
    window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
        columns: [
            { data: 0, orderable: false, searchable: false },
            { data: 1 },
            { data: 2, orderable: false, searchable: false },
            { data: 3 },
            { data: 4, className: 'col-center' },
            { data: 5, orderable: false, searchable: false, className: 'col-center' }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search departments...', lengthMenu: '_MENU_' }
    });
    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        var id = $(this).data('id'), module = $(this).data('module');
        if (!confirm('Are you sure you want to delete this record?')) return;
        $('<form method="POST">').append(
            $('<input type="hidden" name="action">').val('delete_' + module),
            $('<input type="hidden" name="id">').val(id)
        ).appendTo('body').submit();
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>