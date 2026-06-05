<?php
/**
 * Blog Card Template
 * 
 * Renders a blog post card for carousel/grid display
 * 
 * Expected variables:
 * @var array $blog Blog data from database
 */
?>
<div class="col-xl-4 col-lg-4 col-md-12">
	<div class="card mb-xl-0 overflow-hidden">
		<?php if (!empty($blog['featured_image'])): ?>
		<div class="item-card8-img  br-te-4 br-ts-4">
			<a href="<?= blogUrl($blog['slug']) ?>">
				<img src="<?= e($blog['featured_image']) ?>" alt="<?= e($blog['title']) ?>" class="cover-image">
			</a>
		</div>
		<?php endif; ?>
		<?php if (!empty($blog['category_name'])): ?>
		<div class="item-card8-overlaytext">
			<h6 class="mb-0"><?= e($blog['category_name']) ?></h6>
		</div>
		<?php endif; ?>
		<div class="card-body">
			<div class="item-card8-desc">
				<?php if (!empty($blog['created_at'])): ?>
				<p class="text-muted mb-2"><?= dd_($blog['created_at'], 'd M Y') ?></p>
				<?php endif; ?>
				<a href="<?= blogUrl($blog['slug']) ?>">
					<h4 class="font-weight-semibold"><?= e($blog['title']) ?></h4>
				</a>
				<?php if (!empty($blog['excerpt'])): ?>
				<p class="mb-0"><?= e(truncateText(strip_tags($blog['excerpt']), 120)) ?></p>
				<?php elseif (!empty($blog['content'])): ?>
				<p class="mb-0"><?= e(truncateText(strip_tags($blog['content']), 120)) ?></p>
				<?php endif; ?>
			</div>
		</div>
		<div class="card-footer">
			<div class="footerimg d-flex mt-0 mb-0">
				<div class="d-flex footerimg-l mb-0">
					<?php if (!empty($blog['author_name'])): ?>
						<img src="assets/images/faces/male/1.jpg" alt="<?= e($blog['author_name']) ?>" class="avatar brround  me-2">
					<h5 class="time-title text-muted p-0 leading-normal mt-2 mb-0">
						<?= e($blog['author_name']) ?>
					</h5>
					<?php endif; ?>
				</div>
				<div class="mt-2 footerimg-r ms-auto">
					<?php 
					$views = (int)($blog['views'] ?? 0);
					?>
					<span class="text-muted" title="<?= $views ?> views">
						<i class="fa fa-eye me-1"></i><?= number_format($views) ?>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>
