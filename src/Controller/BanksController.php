<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\DB;
use App\Core\Database;

class BanksController extends BaseController
{
    private SimpleCrudHandler $crud;
    private string $tblName = DB::BANKS;

    public function handle(): void
    {
        $this->crud = new SimpleCrudHandler($this->db);
        $this->requiresModule('banks', 'Bank Account');

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
        if ($action === "update_banks" && $id > 0 && $this->canEdit()) {
            $account_name = e_s__($_POST['account_name']);
            $account_code = e_s__($_POST['account_code']);
            $currency = e_s__($_POST['currency']);
            $bank_name = e_s__($_POST['bank_name']);
            $routing_number = e_s__($_POST['routing_number']);
            $description = e_s__($_POST['description']);
            $is_primary = isset($_POST['is_primary']) ? 1 : 0;
            $publish = isset($_POST['publish']) ? 1 : 0;

            if (empty($account_name)) {
                $this->renderForm($id, $action, '', 'Account name is mandatory.');
                return;
            }

            if ($this->crud->exists($this->tblName, 'account_name', $account_name, $id)
                && $account_name !== ($this->crud->findById($this->tblName, $id)['account_name'] ?? '')) {
                $this->renderForm($id, $action, '', 'Duplicate Bank Account. Please enter different.');
                return;
            }

            $this->crud->update(
                $this->tblName,
                ['account_name', 'account_code', 'currency', 'bank_name', 'routing_number', 'description', 'is_primary', 'is_active'],
                [$account_name, $account_code, $currency, $bank_name, $routing_number, $description, $is_primary, $publish],
                $id,
                $this->userId
            );
            fp__($this->tblName, $id);
            header("Location:listing_banks.php?success_message=The {$this->moduleCaption} has been updated successfully.");
            exit;
        }

        if ($action === "add_banks" && $this->canCreate()) {
            $account_name = e_s__($_POST['account_name']);
            $account_code = e_s__($_POST['account_code']);
            $currency = e_s__($_POST['currency']);
            $bank_name = e_s__($_POST['bank_name']);
            $routing_number = e_s__($_POST['routing_number']);
            $description = e_s__($_POST['description']);
            $is_primary = isset($_POST['is_primary']) ? 1 : 0;
            $publish = isset($_POST['publish']) ? 1 : 0;

            if (empty($account_name)) {
                $this->renderForm($id, $action, '', 'Account name is mandatory.');
                return;
            }

            if ($this->crud->exists($this->tblName, 'account_name', $account_name)) {
                $this->renderForm($id, $action, '', 'Bank Account already exists. Please enter a different one.');
                return;
            }

            $newId = $this->crud->create(
                $this->tblName,
                ['account_name', 'account_code', 'currency', 'bank_name', 'routing_number', 'description', 'is_primary', 'is_active'],
                [$account_name, $account_code, $currency, $bank_name, $routing_number, $description, $is_primary, $publish],
                $this->userId
            );
            fp__($this->tblName, (int)$newId);
            header("Location:listing_banks.php?success_message=The {$this->moduleCaption} has been saved successfully.");
            exit;
        }

        $this->renderForm($id, $action, '', '');
    }

    private function renderForm(int $id, string $action, string $success, string $error): void
    {
        $account_name = '';
        $account_code = '';
        $currency = '0';
        $bank_name = '';
        $routing_number = '';
        $description = '';
        $is_primary = 0;
        $publish = 1;

        if ($id > 0) {
            $row = $this->crud->findById($this->tblName, $id);
            if ($row !== null) {
                $account_name = s__($row['account_name']);
                $account_code = s__($row['account_code']);
                $currency = s__($row['currency']);
                $bank_name = s__($row['bank_name']);
                $routing_number = s__($row['routing_number']);
                $description = s__($row['description']);
                $is_primary = (int)s__($row['is_primary']);
                $publish = (int)s__($row['publish']);
            }
        }

        $allCurrencies = [];
        try {
            $allCurrencies = $this->db->fetchAll(
                "SELECT id, currency FROM `" . DB::CURRENCIES . "` WHERE is_active=1 ORDER BY currency"
            );
        } catch (\Throwable $e) {
            $allCurrencies = [];
        }

        $module = 'banks';
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
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> Bank Account</h5>
            </div>

            <div class="my-1 d-flex align-items-center gap-3">
                <div class="form-check form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="is_primary" id="is_primary" form="frmbanks" <?php echo $is_primary ? 'checked="checked"' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="is_primary">Is Primary?</label>
                </div>
                <div class="form-check form-switch mb-0 me-2">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="publish" id="publish" form="frmbanks" <?php echo $publish ? 'checked="checked"' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="publish">Publish</label>
                </div>
                <?php if ($this->canCreate()) { ?>
                    <button type="submit" form="frmbanks" class="btn btn-primary btn-sm">Save</button>
                <?php } ?>
                <a href="listing_banks.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>

            <form class="steps-basic clearfix" method="post" id="frmbanks" name="frmbanks" action="banks.php">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_banks">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_banks">
                <?php } ?>
                <div class="card col-lg-6">
                    <div class="card-body">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Account Name:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="account_name" value="<?php echo e($account_name); ?>" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Account Code:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="account_code" value="<?php echo e($account_code); ?>" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Currency:*</span></label>
                            <div class="col-lg-9">
                                <select class="form-select" name="currency">
                                    <option value="0">Please select</option>
                                    <?php foreach ($allCurrencies as $cur) { ?>
                                        <option value="<?php echo $cur['id']; ?>" <?php echo ((string)$cur['id'] === (string)$currency) ? 'selected' : ''; ?>>
                                            <?php echo e($cur['currency']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Bank Name:</label>
                            <div class="col-lg-9">
                                <input type="text" name="bank_name" value="<?php echo e($bank_name); ?>" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Rounting number (SWIFT):</label>
                            <div class="col-lg-9">
                                <input type="text" name="routing_number" value="<?php echo e($routing_number); ?>" class="form-control">
                                <div class="form-text text-muted">e.g 026009593</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Description:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="description" style="field-sizing: content;"><?php echo e($description); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>

<?php if ($this->canView() && !$this->canCreate() && !$this->canEdit()) { ?>
    <script>
        $(function() {
            toggleFormElements('true');
        });
    </script>
<?php } ?>
<?php
        include 'admin_elements/admin_footer.php';
    }
}
