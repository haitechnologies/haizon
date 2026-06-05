<?php
/**
 * Company Grid View Template
 * 
 * Displays a company in grid format (vertical card)
 * 
 * Expected variable:
 * @var array $company Company data from database
 */
?>
<div class="col-xl-4 col-lg-6 col-md-12">
    <div class="card overflow-hidden">
        <div class="item-card2-img">
            <a href="<?= companyUrl($company['slug']) ?>" class="absolute-link"></a>
            <img src="<?= e(companyImage($company['logo'] ?? null)) ?>" alt="<?= e($company['company_name']) ?>" class="cover-image">
            <div class="item-card2-icons">
                <a href="<?= companyUrl($company['slug']) ?>" class="item-card2-icons-l">
                    <i class="fa fa-building"></i>
                </a>
                <a href="javascript:void(0)" class="item-card2-icons-r" data-favorite-company="<?= (int)$company['id'] ?>">
                    <i class="fa fa-heart-o"></i>
                </a>
            </div>
            <?php if (!empty($company['category_name'])): ?>
            <div class="blog--category"><?= e($company['category_name']) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="card-body pb-0">
            <div class="item-card2">
                <div class="item-card2-desc">
                    <div class="item-card2-text">
                        <a href="<?= companyUrl($company['slug']) ?>" class="text-dark">
                            <h4 class="mb-1">
                                <?= e($company['company_name']) ?>
                                <?php if ((int)($company['verified'] ?? 0) === 1): ?>
                                <i class="fa fa-check-circle text-success" style="font-size: 14px;"></i>
                                <?php endif; ?>
                            </h4>
                        </a>
                    </div>
                    
                    <div class="pt-2">
                        <?php if (!empty($company['full_address'])): ?>
                        <p class="pb-0 pt-0 mb-2 mt-0">
                            <i class="fa fa-map-marker me-2"></i><?= e(truncateText($company['full_address'], 50)) ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($company['phone'])): ?>
                        <p class="pb-0 pt-0 mb-2 mt-0">
                            <a href="tel:<?= e(preg_replace('/\s+/', '', (string)$company['phone'])) ?>" class="text-dark">
                                <i class="fa fa-phone me-2"></i><?= e(formatPhone($company['phone'])) ?>
                            </a>
                        </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($company['business_hours'])): ?>
                        <p class="pb-0 pt-0 mb-2 mt-0">
                            <i class="fa fa-clock-o me-2"></i><?= e($company['business_hours']) ?>
                            <?= getOpenStatusBadge($company) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($company['company_profile'])): ?>
                    <p class="mt-2"><?= e(truncateText(strip_tags($company['company_profile']), 100)) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <div class="item-card2-footer">
                <div class="item-card2-footer-u">
                    <div class="d-flex">
                        <?php if (!empty($company['created_at'])): ?>
                        <div class="ms-auto text-muted">
                            <i class="fa fa-clock-o me-1"></i><?= timeAgo($company['created_at']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
