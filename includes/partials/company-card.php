<?php
/**
 * Company Card Template
 * 
 * Renders a company listing card for carousel/grid display
 * 
 * Expected variables:
 * @var array $company Company data from database
 * @var bool $showFeaturedBadge Show featured/promoted badge (default: false)
 */

$showFeaturedBadge = $showFeaturedBadge ?? false;
?>
<div class="item">
	<div class="card mb-0 overflow-hidden">
		<?php if ($showFeaturedBadge): ?>
		<div class="power-ribbon power-ribbon-top-left text-warning"><span class="bg-warning"><i
					class="fa fa-bolt"></i></span></div>
		<?php endif; ?>
		<div class="item-card2-img">
			<a href="<?= companyUrl($company['slug']) ?>" class="absolute-link"></a>
			<img src="<?= e(companyImage($company['logo'] ?? null)) ?>" alt="<?= e($company['company_name']) ?>" class="cover-image">
			<div class="item-card2-icons">
				<a href="<?= companyUrl($company['slug']) ?>" class="item-card2-icons-l"><i class="fa fa-building"></i></a>
				<a href="javascript:void(0)" class="item-card2-icons-r" data-favorite-company="<?= (int)$company['id'] ?>"><i class="fa fa fa-heart-o"></i></a>
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
							<h4 class="mb-0"><?= e($company['company_name']) ?></h4>
						</a>
					</div>
					<div class="pt-3">
						<?php if (!empty($company['full_address'])): ?>
						<p class="pb-0 pt-0 mb-2 mt-2"><i class="fa fa-map-marker me-2"></i><?= e(truncateText($company['full_address'], 60)) ?></p>
						<?php endif; ?>
						<?php if (!empty($company['phone'])): ?>
						<p class="pb-0 pt-0 mb-2 mt-2"><a href="tel:<?= e(preg_replace('/\s+/', '', (string)$company['phone'])) ?>" class="text-dark"><i class="fa fa-phone me-2"></i><?= e(formatPhone($company['phone'])) ?></a></p>
						<?php endif; ?>
						<?php if (!empty($company['business_hours'])): ?>
						<p class="pb-0 pt-0 mb-2 mt-2">
							<i class="fa fa-clock-o me-2"></i><?= e($company['business_hours']) ?>
							<?= getOpenStatusBadge($company) ?>
						</p>
						<?php endif; ?>
					</div>
					<?php if (isset($company['company_profile']) && !empty($company['company_profile'])): ?>
					<p class=""><?= e(truncateText(strip_tags($company['company_profile']), 100)) ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="card-footer">
			<div class="item-card2-footer">
				<div class="item-card2-footer-u">
					<div class="d-flex align-items-center">
						<?php if (!empty($company['created_at'])): ?>
						<div class="text-muted me-3">
							<i class="fa fa-clock-o me-1"></i><?= timeAgo($company['created_at']) ?>
						</div>
						<?php endif; ?>
						<?php if (isset($company['profile_views'])): ?>
						<div class="text-muted">
							<i class="fa fa-eye me-1"></i><?= number_format((int)$company['profile_views']) ?> views
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
