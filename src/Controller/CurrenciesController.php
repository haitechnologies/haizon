<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\DB;
use App\Core\Database;

class CurrenciesController extends BaseController
{
    private SimpleCrudHandler $crud;
    private string $tblName = DB::CURRENCIES;

    public function handle(): void
    {
        $this->crud = new SimpleCrudHandler($this->db);
        $this->requiresModule('currencies', 'Currency');

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
        if ($action === "update_currencies" && $id > 0 && $this->canEdit()) {
            $currency = e_s__($_POST['currency']);
            $publish = isset($_POST['publish']) ? 1 : 0;

            if (empty($currency)) {
                $this->renderForm($id, $action, '', 'Currency is mandatory.');
                return;
            }

            if ($this->crud->exists($this->tblName, 'currency', $currency, $id)
                && $currency !== ($this->crud->findById($this->tblName, $id)['currency'] ?? '')) {
                $this->renderForm($id, $action, '', 'Duplicate Currency. Please enter different.');
                return;
            }

            $this->crud->update($this->tblName, ['currency', 'is_active'], [$currency, $publish], $id, $this->userId);
            fp__($this->tblName, $id);
            header("Location:listing_currencies.php?success_message=The {$this->moduleCaption} has been updated successfully.");
            exit;
        }

        if ($action === "add_currencies" && $this->canCreate()) {
            $currency = e_s__($_POST['currency']);
            $publish = isset($_POST['publish']) ? 1 : 0;

            if (empty($currency)) {
                $this->renderForm($id, $action, '', 'Currency is mandatory.');
                return;
            }

            if ($this->crud->exists($this->tblName, 'currency', $currency)) {
                $this->renderForm($id, $action, '', 'Currency already exists. Please enter a different one.');
                return;
            }

            $newId = $this->crud->create($this->tblName, ['currency', 'is_active'], [$currency, $publish], $this->userId);
            fp__($this->tblName, (int)$newId);
            header("Location:listing_currencies.php?success_message=The {$this->moduleCaption} has been saved successfully.");
            exit;
        }

        $this->renderForm($id, $action, '', '');
    }

    private function renderForm(int $id, string $action, string $success, string $error): void
    {
        $currency = '';
        $publish = 1;

        if ($id > 0) {
            $row = $this->crud->findById($this->tblName, $id);
            if ($row !== null) {
                $currency = s__($row['currency']);
                $publish = (int)s__($row['publish']);
            }
        }

        $module = 'currencies';
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
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> Currency</h5>
            </div>

            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="publish" <?php echo $publish ? 'checked="checked"' : ''; ?> form="frmcurrencies">
                    <label class="form-check-label">Publish</label>
                </div>
            </div>

            <div class="my-1">
                <?php if ($this->canCreate()) { ?>
                    <button type="submit" form="frmcurrencies" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_currencies.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>

            <form class="steps-basic clearfix" method="post" action="currencies.php" id="frmcurrencies">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_currencies">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_currencies">
                <?php } ?>

                <div class="card col-lg-6">
                    <div class="card-body">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Currency:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="currency" value="<?php echo e($currency); ?>" class="form-control">
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
