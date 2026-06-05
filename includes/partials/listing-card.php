<!-- OPTIMIZED Company Listing Card - Mobile-Friendly Version
     Features: Lazy loading for images, responsive design, touch-friendly buttons
     Usage: Include this in listing pages instead of inline card markup
-->
<?php
// Expected variables from parent scope:
// $company - company data array
// $detailUrl - URL to company detail page
// $emirate - selected emirate filter (optional)

$website = trim((string)($company['website'] ?? ''));
if ($website !== '' && !preg_match('/^https?:\/\//i', $website)) {
  $website = 'https://' . $website;
}

$listingCompanyName = display_text($company['company_name'] ?? '');
$listingCategoryName = display_text($company['category_name'] ?? 'Business');
$listingCity = display_text($company['city'] ?? '');
$listingEmirate = display_text(ucwords(str_replace('-', ' ', (string)($company['emirate'] ?? ''))));
?>

<article class="listing-card">
  <div class="listing-header">
    <h3 class="listing-name">
      <a href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars($listingCompanyName, ENT_QUOTES, 'UTF-8'); ?>
      </a>
    </h3>
  </div>

  <div class="listing-badges">
    <?php if ($company['featured'] ?? false): ?>
      <span class="listing-badge badge-featured">Featured</span>
    <?php endif; ?>
    <?php if ($company['verified'] ?? false): ?>
      <span class="listing-badge badge-verified">Verified</span>
    <?php endif; ?>
    <?php if (($company['rating'] ?? 0) && ($company['review_count'] ?? 0)): ?>
      <span class="listing-badge badge-rating">★ <?php echo number_format($company['rating'], 1); ?> (<?php echo number_format($company['review_count']); ?>)</span>
    <?php endif; ?>
  </div>

  <p class="listing-meta">
    <?php echo htmlspecialchars($listingCategoryName, ENT_QUOTES, 'UTF-8'); ?>
    <?php if ($company['city'] ?? null): ?>
      · <?php echo htmlspecialchars($listingCity, ENT_QUOTES, 'UTF-8'); ?>
    <?php elseif ($company['emirate'] ?? null): ?>
      · <?php echo htmlspecialchars($listingEmirate, ENT_QUOTES, 'UTF-8'); ?>
    <?php endif; ?>
  </p>

  <?php if (!empty($company['description'] ?? null)): ?>
    <p class="listing-desc">
      <?php
        $desc = trim((string)($company['description'] ?? ''));
        $companyName = trim($listingCompanyName);
        $categoryName = trim($listingCategoryName);
        $normalizedDesc = preg_replace('/\s+/', ' ', strtolower($desc));
        $normalizedCompanyName = preg_replace('/\s+/', ' ', strtolower($companyName));
        $normalizedCategoryName = preg_replace('/\s+/', ' ', strtolower($categoryName));
        $descriptionMatchesTitle = $normalizedDesc !== '' && ($normalizedDesc === $normalizedCompanyName || $normalizedDesc === $normalizedCategoryName);
        $displayDesc = $descriptionMatchesTitle
          ? 'Browse verified company details, contact options, and listing information for this business.'
          : (strlen($desc) > 190 ? substr($desc, 0, 187) . '...' : $desc);
        echo htmlspecialchars($displayDesc, ENT_QUOTES, 'UTF-8');
      ?>
    </p>
  <?php endif; ?>

  <!-- Contact Info with Touch-Friendly Targets -->
  <?php if (($company['phone'] ?? null) || ($company['email'] ?? null) || ($company['website'] ?? null)): ?>
    <div class="listing-info-grid">
      <?php if (!empty($company['phone'] ?? null)): ?>
        <a class="listing-info-item" 
           href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', (string)($company['phone'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
           role="button"
           tabindex="0">
          <i class="fa fa-phone me-1"></i><?php echo htmlspecialchars($company['phone'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
      <?php endif; ?>
      <?php if (!empty($company['email'] ?? null)): ?>
        <span class="listing-info-item">
          <i class="fa fa-envelope me-1"></i>Email Available
        </span>
      <?php endif; ?>
      <?php if ($website !== ''): ?>
        <span class="listing-info-item">
          <i class="fa fa-globe me-1"></i>Website
        </span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Call-to-Action Buttons - Mobile Optimized -->
  <div class="listing-actions">
    <a class="btn-ui btn-light-ui" href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>">
      View details
    </a>
    <?php if (!empty($company['phone'] ?? null)): ?>
      <a class="btn-ui btn-light-ui" 
         href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', (string)($company['phone'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
         title="Call <?php echo htmlspecialchars($listingCompanyName, ENT_QUOTES, 'UTF-8'); ?>">
        Call
      </a>
    <?php endif; ?>
        <?php if ($website !== ''): ?>
      <a class="btn-ui btn-light-ui" 
          href="<?php echo htmlspecialchars($website, ENT_QUOTES, 'UTF-8'); ?>" 
         target="_blank" 
         rel="noopener noreferrer"
         title="Visit website (opens in new tab)">
        Website
      </a>
    <?php endif; ?>
  </div>
</article>
