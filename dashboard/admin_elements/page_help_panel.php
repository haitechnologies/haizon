<?php declare(strict_types=1);
if (empty($pageHelpData) || !is_array($pageHelpData)) {
    return;
}

$helpTitle  = htmlspecialchars((string)($pageHelpData['title'] ?? 'Page Help'), ENT_QUOTES, 'UTF-8');
$helpIcon   = htmlspecialchars((string)($pageHelpData['icon'] ?? 'ph-question'), ENT_QUOTES, 'UTF-8');
$helpWhat   = (string)($pageHelpData['what'] ?? '');
$helpSteps  = (array)($pageHelpData['steps'] ?? []);
$helpFields = (array)($pageHelpData['fields'] ?? []);
$helpTips   = (array)($pageHelpData['tips'] ?? []);
?>

<div class="offcanvas offcanvas-end page-help-offcanvas" tabindex="-1" id="pageHelpPanel" aria-labelledby="pageHelpPanelLabel">
    <div class="offcanvas-header page-help-header border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="page-help-icon-wrap">
                <i class="<?php echo $helpIcon; ?>"></i>
            </div>
            <div>
                <h5 class="offcanvas-title mb-0" id="pageHelpPanelLabel"><?php echo $helpTitle; ?></h5>
                <small class="text-muted">Page Guide</small>
            </div>
        </div>
        <button type="button" class="btn btn-light btn-sm btn-icon border-transparent rounded-pill" data-bs-dismiss="offcanvas" aria-label="Close help">
            <i class="ph-x"></i>
        </button>
    </div>

    <div class="offcanvas-body p-0">

        <?php if ($helpWhat !== ''): ?>
        <div class="page-help-section">
            <div class="page-help-section-title">
                <i class="ph-info me-2"></i>What is this page?
            </div>
            <p class="page-help-text mb-0"><?php echo $helpWhat; ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($helpSteps)): ?>
        <div class="page-help-section">
            <div class="page-help-section-title">
                <i class="ph-rocket-launch me-2"></i>How to use
            </div>
            <ol class="page-help-steps mb-0">
                <?php foreach ($helpSteps as $step): ?>
                    <li><?php echo $step; ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>

        <?php if (!empty($helpFields)): ?>
        <div class="page-help-section">
            <div class="page-help-section-title">
                <i class="ph-columns me-2"></i>Key fields explained
            </div>
            <dl class="page-help-fields mb-0">
                <?php foreach ($helpFields as $fieldName => $fieldDesc): ?>
                    <dt><?php echo htmlspecialchars((string)$fieldName, ENT_QUOTES, 'UTF-8'); ?></dt>
                    <dd><?php echo htmlspecialchars((string)$fieldDesc, ENT_QUOTES, 'UTF-8'); ?></dd>
                <?php endforeach; ?>
            </dl>
        </div>
        <?php endif; ?>

        <?php if (!empty($helpTips)): ?>
        <div class="page-help-section page-help-tips-section">
            <div class="page-help-section-title">
                <i class="ph-lightbulb me-2"></i>Tips
            </div>
            <ul class="page-help-tips mb-0">
                <?php foreach ($helpTips as $tip): ?>
                    <li><?php echo htmlspecialchars((string)$tip, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="page-help-section page-help-dismiss text-center border-top-0">
            <button type="button" class="btn btn-sm btn-light w-100" data-bs-dismiss="offcanvas">
                <i class="ph-x-circle me-1"></i>Close
            </button>
            <label class="form-check-label page-help-remember mt-2 d-inline-flex align-items-center gap-1">
                <input type="checkbox" class="form-check-input mt-0" id="pageHelpDontShow" data-page="<?php echo htmlspecialchars($current_page ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <small class="text-muted">Don't show automatically on this page</small>
            </label>
        </div>
    </div>
</div>

<script>
(function() {
    var page = <?php echo json_encode($current_page ?? ''); ?>;
    var storageKey = 'hai_page_help_dismissed';

    function getDismissed() {
        try {
            return JSON.parse(localStorage.getItem(storageKey) || '{}');
        } catch(e) { return {}; }
    }

    var checkbox = document.getElementById('pageHelpDontShow');
    if (checkbox) {
        var dismissed = getDismissed();
        checkbox.checked = !!dismissed[page];

        checkbox.addEventListener('change', function() {
            var d = getDismissed();
            if (this.checked) {
                d[page] = true;
            } else {
                delete d[page];
            }
            localStorage.setItem(storageKey, JSON.stringify(d));
        });
    }

})();
</script>
