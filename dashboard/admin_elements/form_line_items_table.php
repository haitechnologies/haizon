<?php

declare(strict_types=1);

/**
 * Form: Line Items Table
 *
 * Renders a dynamic line-items table with add/remove row functionality.
 *
 * Required keys in $lineItems:
 *   'name'    => string  (table identifier, e.g., 'sale_order_items')
 *   'columns' => array   (column definitions)
 *
 * Optional keys:
 *   'rows'        => array   (existing row data for edit mode)
 *   'min_rows'    => int     (minimum rows to show, default: 1)
 *   'table_class' => string  (extra table classes)
 *   'totals_html' => string  (HTML for totals row/section)
 */

if (!isset($lineItems) || !is_array($lineItems)) {
    return;
}

$_liName       = $lineItems['name'] ?? 'items';
$_liColumns    = $lineItems['columns'] ?? [];
$_liRows       = $lineItems['rows'] ?? [];
$_liMinRows    = $lineItems['min_rows'] ?? 1;
$_liTableClass = $lineItems['table_class'] ?? 'table table-bordered';
$_liTotalsHtml = $lineItems['totals_html'] ?? '';
$_liRowCount   = max(count($_liRows), $_liMinRows);
?>

<div class="table-responsive">
    <table class="<?php echo htmlspecialchars($_liTableClass); ?>" id="line_items_<?php echo htmlspecialchars($_liName); ?>">
        <thead>
            <tr>
                <?php foreach ($_liColumns as $_liCol): ?>
                    <th<?php if (!empty($_liCol['width'])): ?> width="<?php echo htmlspecialchars($_liCol['width']); ?>" <?php endif; ?><?php if (!empty($_liCol['class'])): ?> class="<?php echo htmlspecialchars($_liCol['class']); ?>" <?php endif; ?>>
                        <?php echo htmlspecialchars($_liCol['label'] ?? ''); ?>
                        </th>
                    <?php endforeach; ?>
                    <th width="50">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($_liIdx = 0; $_liIdx < $_liRowCount; $_liIdx++): ?>
                <tr>
                    <?php foreach ($_liColumns as $_liCol): ?>
                        <td>
                            <?php
                            $_liColName = $_liCol['name'] ?? '';
                            $_liColType = $_liCol['type'] ?? 'text';
                            $_liVal = $_liRows[$_liIdx][$_liColName] ?? ($_liCol['default'] ?? '');
                            $_liInputName = $_liColName . '[]';
                            ?>
                            <?php if ($_liColType === 'select'): ?>
                                <select name="<?php echo htmlspecialchars($_liInputName); ?>" class="form-select form-select-sm">
                                    <?php if (!empty($_liCol['options_html'])): ?>
                                        <?php echo $_liCol['options_html']; ?>
                                    <?php elseif (!empty($_liCol['options'])): ?>
                                        <?php foreach ($_liCol['options'] as $_liOptVal => $_liOptLbl): ?>
                                            <option value="<?php echo htmlspecialchars((string)$_liOptVal); ?>" <?php echo ((string)$_liOptVal === (string)$_liVal) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars((string)$_liOptLbl); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            <?php elseif ($_liColType === 'textarea'): ?>
                                <textarea name="<?php echo htmlspecialchars($_liInputName); ?>" class="form-control form-control-sm" rows="2"><?php echo htmlspecialchars($_liVal); ?></textarea>
                            <?php elseif ($_liColType === 'hidden'): ?>
                                <input type="hidden" name="<?php echo htmlspecialchars($_liInputName); ?>" value="<?php echo htmlspecialchars($_liVal); ?>">
                            <?php else: ?>
                                <input type="<?php echo htmlspecialchars($_liColType); ?>"
                                    name="<?php echo htmlspecialchars($_liInputName); ?>"
                                    class="form-control form-control-sm <?php echo htmlspecialchars($_liCol['input_class'] ?? ''); ?>"
                                    value="<?php echo htmlspecialchars($_liVal); ?>"
                                    <?php if (!empty($_liCol['placeholder'])): ?>placeholder="<?php echo htmlspecialchars($_liCol['placeholder']); ?>" <?php endif; ?>>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line" title="Remove"><i class="ph-trash"></i></button>
                    </td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($_liTotalsHtml)): ?>
    <?php echo $_liTotalsHtml; ?>
<?php endif; ?>

<div class="mt-2">
    <button type="button" class="btn btn-sm btn-outline-primary btn-add-line" data-target="line_items_<?php echo htmlspecialchars($_liName); ?>">
        <i class="ph-plus me-1"></i>Add Line
    </button>
</div>
