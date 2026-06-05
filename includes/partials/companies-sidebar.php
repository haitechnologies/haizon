<?php
/**
 * Companies Listing Sidebar
 * 
 * Filters and additional options for companies listing page
 */

// Get popular categories for sidebar
$popularCategories = $categoriesModel->getPopular(10);
?>

<!-- Category Filter -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Categories</h3>
    </div>
    <div class="card-body">
        <div class="list-catergory">
            <div class="item-list">
                <ul class="list-group mb-0">
                    <li class="list-group-item <?= empty($categorySlug) ? 'active' : '' ?>">
                        <a href="<?php echo url('/listings'); ?>" class="<?= empty($categorySlug) ? 'text-white' : 'text-dark' ?>">
                            <i class="fa fa-th me-2"></i> All Categories
                            <span class="badgetext badge badge-pill badge-secondary ms-auto">
                                <?= number_format($companiesModel->getCount([])) ?>
                            </span>
                        </a>
                    </li>
                    <?php foreach ($popularCategories as $cat): ?>
                    <li class="list-group-item <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>">
                        <a href="<?= categoryUrl($cat['slug']) ?>" 
                           class="<?= $categorySlug === $cat['slug'] ? 'text-white' : 'text-dark' ?>">
                            <i class="fa fa-folder-open me-2"></i> <?= e($cat['category_name']) ?>
                            <span class="badgetext badge badge-pill badge-secondary ms-auto">
                                <?= number_format($cat['total_companies']) ?>
                            </span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Verified Filter -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filters</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label class="custom-control custom-checkbox mb-0">
                <input type="checkbox" class="custom-control-input" name="verified" value="1" 
                       <?= $verifiedOnly ? 'checked' : '' ?> onchange="this.form.submit()">
                <span class="custom-control-label">
                    <i class="fa fa-check-circle text-success me-1"></i>
                    Verified Companies Only
                </span>
            </label>
        </div>
    </div>
</div>

<!-- Featured Companies -->
<?php
$featuredCompanies = $companiesModel->getFeatured(5);
if (!empty($featuredCompanies)):
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Featured Companies</h3>
    </div>
    <div class="card-body">
        <div class="">
            <?php foreach ($featuredCompanies as $featuredCompany): ?>
            <div class="d-flex overflow-visible mb-3 pb-3 border-bottom">
                <img class="avatar bradius avatar-lg me-3 my-auto" 
                     src="<?= e(companyImage($featuredCompany['logo'] ?? null)) ?>" 
                     alt="<?= e($featuredCompany['company_name']) ?>">
                <div class="media-body">
                    <a href="<?= companyUrl($featuredCompany['slug']) ?>">
                        <h5 class="mt-0 mb-1 font-weight-semibold">
                            <?= e(truncateText($featuredCompany['company_name'], 30)) ?>
                            <?php if ((int)($featuredCompany['verified'] ?? 0) === 1): ?>
                            <i class="fa fa-check-circle text-success" style="font-size: 12px;"></i>
                            <?php endif; ?>
                        </h5>
                    </a>
                    <?php if (!empty($featuredCompany['category_name'])): ?>
                    <small class="text-muted">
                        <i class="fa fa-folder-open me-1"></i><?= e($featuredCompany['category_name']) ?>
                    </small>
                    <?php endif; ?>
                    <div class="mt-1">
                        <small class="text-muted">
                            <i class="fa fa-clock-o me-1"></i><?= timeAgo($featuredCompany['created_at']) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Companies -->
<?php
$recentCompanies = $companiesModel->getRecent(5);
if (!empty($recentCompanies)):
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recently Added</h3>
    </div>
    <div class="card-body">
        <div class="">
            <?php foreach ($recentCompanies as $recentCompany): ?>
            <div class="d-flex overflow-visible mb-3 pb-3 border-bottom">
                <img class="avatar bradius avatar-lg me-3 my-auto" 
                     src="<?= e(companyImage($recentCompany['logo'] ?? null)) ?>" 
                     alt="<?= e($recentCompany['company_name']) ?>">
                <div class="media-body">
                    <a href="<?= companyUrl($recentCompany['slug']) ?>">
                        <h5 class="mt-0 mb-1 font-weight-semibold">
                            <?= e(truncateText($recentCompany['company_name'], 30)) ?>
                        </h5>
                    </a>
                    <?php if (!empty($recentCompany['category_name'])): ?>
                    <small class="text-muted">
                        <i class="fa fa-folder-open me-1"></i><?= e($recentCompany['category_name']) ?>
                    </small>
                    <?php endif; ?>
                    <div class="mt-1">
                        <small class="text-muted">
                            <i class="fa fa-clock-o me-1"></i><?= timeAgo($recentCompany['created_at']) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Handle verified filter checkbox
document.querySelector('input[name="verified"]').addEventListener('change', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (this.checked) {
        urlParams.set('verified', '1');
    } else {
        urlParams.delete('verified');
    }
    urlParams.set('page', '1'); // Reset to page 1
    window.location.search = urlParams.toString();
});
</script>
