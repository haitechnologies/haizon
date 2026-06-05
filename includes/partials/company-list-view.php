<?php
/**
 * Company List View Template
 * 
 * Displays a company in list format (horizontal card)
 * 
 * Expected variable:
 * @var array $company Company data from database
 */
?>
<div class="card overflow-hidden">
    
    <div class="d-md-flex">
        <div class="item-card9-img">
            <div class="item-card9-imgs">
                <a href="<?= companyUrl($company['slug']) ?>"></a>
                <img src="<?= e(companyImage($company['logo'] ?? null)) ?>" alt="<?= e($company['company_name']) ?>" class="cover-image">
            </div>
            <div class="item-card9-icons">
                <a href="javascript:void(0)" class="item-card9-icons1 wishlist" data-favorite-company="<?= (int)$company['id'] ?>">
                    <i class="fa fa-heart-o"></i>
                </a>
            </div>
            <?php if (!empty($company['category_name'])): ?>
            <div class="item-cardreview-absolute bg-secondary"><?= e($company['category_name']) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="card border-0 mb-0">
            <div class="card-body h-100">
                <div class="item-card9">
                    <a href="<?= companyUrl($company['slug']) ?>" class="text-dark">
                        <h4 class="font-weight-semibold mt-1 mb-1">
                            <?= e($company['company_name']) ?>
                            <?php if ((int)($company['verified'] ?? 0) === 1): ?>
                            <i class="fa fa-check-circle text-success ms-1" data-bs-toggle="tooltip" 
                               data-bs-placement="top" title="Verified Business"></i>
                            <?php else: ?>
                            <i class="fa fa-exclamation-circle text-warning ms-1" data-bs-toggle="tooltip" 
                               data-bs-placement="top" title="Not Verified"></i>
                            <?php endif; ?>
                        </h4>
                    </a>
                    
                    <p class="mb-2">
                        <?php if (!empty($company['full_address'])): ?>
                        <a href="javascript:void(0)" class="me-4 icons">
                            <i class="fa fa-map-marker text-muted me-1"></i>
                            <?= e(truncateText($company['full_address'], 80)) ?>
                        </a>
                        <?php endif; ?>
                    </p>
                    
                    <?php if (!empty($company['company_profile'])): ?>
                    <p class="leading-tight"><?= e(truncateText(strip_tags($company['company_profile']), 150)) ?></p>
                    <?php endif; ?>
                    
                    <div class="d-flex mt-3">
                        <?php if (!empty($company['phone'])): ?>
                        <div class="me-4">
                            <a href="tel:<?= e($company['phone']) ?>" class="icons">
                                <i class="fa fa-phone text-muted me-1"></i>
                                <?= e(formatPhone($company['phone'])) ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($company['website'])): ?>
                        <div class="me-4">
                            <a href="<?= e($company['website']) ?>" target="_blank" rel="nofollow" class="icons">
                                <i class="fa fa-globe text-muted me-1"></i>
                                Website
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($company['email'])): ?>
                        <div>
                            <a href="mailto:<?= e($company['email']) ?>" class="icons">
                                <i class="fa fa-envelope text-muted me-1"></i>
                                Email
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card-footer pt-3 pb-3">
                <div class="item-card9-footer d-sm-flex">
                    <div class="item-card9-cost">
                        <?php if (!empty($company['created_at'])): ?>
                        <a href="javascript:void(0)" class="icons">
                            <i class="fa fa-clock-o text-muted me-1"></i>
                            <?= timeAgo($company['created_at']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="ms-auto">
                        <a href="<?= companyUrl($company['slug']) ?>" class="btn btn-secondary">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
