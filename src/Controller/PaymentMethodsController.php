<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\DB;
use App\Core\Database;

class PaymentMethodsController extends BaseController
{
    private SimpleCrudHandler $crud;
    private string $tblName = DB::PAYMENT_METHODS;

    public function handle(): void
    {
        $this->crud = new SimpleCrudHandler($this->db);
        $this->requiresModule('payment_methods', 'Payment method');

        $id = (int)($_REQUEST['id'] ?? 0);
        $action = $_REQUEST['action'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$this->validateCsrf()) {
            $this->renderForm($id, $action, '', 'Invalid security token.');
            return;
        }

        $this->handlePost($id, $action);
    }

    private function handlePost(int $id, string $action): void
    {
        if ($action === "update_payment_methods" && $id > 0 && $this->canEdit()) {
            $paymentMethod = e_s__($_POST['payment_method']);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($paymentMethod)) {
                $this->renderForm($id, $action, '', 'Payment method is mandatory.');
                return;
            }

            if ($this->crud->exists($this->tblName, 'payment_method', $paymentMethod, $id)
                && $paymentMethod !== ($this->crud->findById($this->tblName, $id)['payment_method'] ?? '')) {
                $this->renderForm($id, $action, '', 'Duplicate Payment method. Please enter different.');
                return;
            }

            $this->crud->update($this->tblName, ['payment_method', 'is_active'], [$paymentMethod, $isActive], $id, $this->userId);
            fp__($this->tblName, $id);
            header("Location:listing_payment_methods.php?success_message=The {$this->moduleCaption} has been updated successfully.");
            exit;
        }

        if ($action === "add_payment_methods" && $this->canCreate()) {
            $paymentMethod = e_s__($_POST['payment_method']);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($paymentMethod)) {
                $this->renderForm($id, $action, '', 'Payment method is mandatory.');
                return;
            }

            if ($this->crud->exists($this->tblName, 'payment_method', $paymentMethod)) {
                $this->renderForm($id, $action, '', 'Payment method already exists. Please enter a different one.');
                return;
            }

            $newId = $this->crud->create($this->tblName, ['payment_method', 'is_active'], [$paymentMethod, $isActive], $this->userId);
            fp__($this->tblName, (int)$newId);
            header("Location:listing_payment_methods.php?success_message=The {$this->moduleCaption} has been saved successfully.");
            exit;
        }

        $this->renderForm($id, $action, '', '');
    }

    private function renderForm(int $id, string $action, string $success, string $error): void
    {
        $paymentMethod = '';
        $isActive = 1;

        if ($id > 0) {
            $row = $this->crud->findById($this->tblName, $id);
            if ($row !== null) {
                $paymentMethod = s__($row['payment_method']);
                $isActive = (int)s__($row['is_active']);
            }
        }

        $module = 'payment_methods';
        $moduleId = $this->moduleId;
        $moduleCaption = $this->moduleCaption;
        $session_user_id = $this->userId;
        $container = $GLOBALS['container'] ?? null;
        $error_message = $error ?: ($_REQUEST['error_message'] ?? '');
        $success_message = $success ?: ($_REQUEST['success_message'] ?? '');

        include 'admin_elements/admin_header.php';
        ?>
<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> Payment Method</h5>
            </div>

            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" <?php echo $isActive ? 'checked="checked"' : ''; ?> form="frmpayment_methods">
                    <label class="form-check-label">Publish</label>
                </div>
            </div>

            <div class="my-1">
                <?php if ($this->canCreate()) { ?>
                    <button type="submit" form="frmpayment_methods" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_payment_methods.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>

            <form class="steps-basic clearfix" method="post" action="payment_methods.php" id="frmpayment_methods">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_payment_methods">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_payment_methods">
                <?php } ?>

                <div class="card col-lg-6">
                    <div class="card-body">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Payment method:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="payment_method" value="<?php echo e($paymentMethod); ?>" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>
<?php
        include 'admin_elements/admin_footer.php';
    }
}
