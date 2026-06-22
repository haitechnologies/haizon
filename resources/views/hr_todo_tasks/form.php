<?php

declare(strict_types=1);
/**
 * @var int $id
 * @var int $employeeId
 * @var string $taskType
 * @var string $description
 * @var string $dueDate
 * @var string $status
 * @var string $notes
 * @var string $moduleCaption
 * @var string $module
 * @var bool $canCreate
 * @var bool $canEdit
 * @var array $employees
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <?php include 'admin_elements/hr_navbar.php'; ?>
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php echo $id > 0 ? 'Edit' : 'New'; ?> <?php echo $moduleCaption; ?></h5>
            </div>
            <div class="my-1">
                <?php if ($canCreate || $canEdit) { ?>
                    <button type="submit" form="frmhr_todo_tasks" class="btn btn-primary btn-sm me-2">Save</button>
                    <a href="listing_hr_todo_tasks.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>
            <form class="steps-basic clearfix" method="post" id="frmhr_todo_tasks" action="hr_todo_tasks.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php if ($id > 0) { ?>
                    <input type="hidden" name="action" value="update_hr_todo_tasks">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php } else { ?>
                    <input type="hidden" name="action" value="add_hr_todo_tasks">
                <?php } ?>
                <div class="card col-lg-8">
                    <div class="content clearfix">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Employee:*</span></label>
                            <div class="col-lg-9">
                                <select required name="employee_id" class="form-control" <?php echo $id > 0 ? 'disabled' : ''; ?>>
                                    <option value="">-- Select Employee --</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo (int)$emp['id']; ?>" <?php echo (int)$emp['id'] === $employeeId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)$emp['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($id > 0): ?>
                                    <input type="hidden" name="employee_id" value="<?php echo $employeeId; ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Task Type:*</span></label>
                            <div class="col-lg-9">
                                <select required name="task_type" class="form-control">
                                    <option value="">-- Select Task Type --</option>
                                    <option value="annual_leave_reminder" <?php echo $taskType === 'annual_leave_reminder' ? 'selected' : ''; ?>>Annual Leave Reminder</option>
                                    <option value="air_ticket_eligibility" <?php echo $taskType === 'air_ticket_eligibility' ? 'selected' : ''; ?>>Air Ticket Eligibility</option>
                                    <option value="document_expiry" <?php echo $taskType === 'document_expiry' ? 'selected' : ''; ?>>Document Expiry</option>
                                    <option value="general" <?php echo $taskType === 'general' ? 'selected' : ''; ?>>General</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Description:</label>
                            <div class="col-lg-9">
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Due Date:</label>
                            <div class="col-lg-9">
                                <input type="date" name="due_date" value="<?php echo htmlspecialchars($dueDate); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Status:</label>
                            <div class="col-lg-9">
                                <select name="status" class="form-control">
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Notes:</label>
                            <div class="col-lg-9">
                                <textarea name="notes" class="form-control" rows="4"><?php echo htmlspecialchars($notes); ?></textarea>
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
