<?php
/**
 * Reusable public search hero.
 *
 * Expected optional variables:
 * @var string $searchHeroTitle
 * @var string $searchHeroDescription
 * @var string $searchHeroFormAction
 * @var string $searchHeroFormId
 * @var string $searchHeroKeyword
 * @var string $searchHeroSubmitLabel
 * @var string $searchHeroVariant
 * @var bool $searchHeroShowCategory
 * @var bool $searchHeroShowLocation
 * @var array $searchHeroCategories
 * @var string $searchHeroSelectedCategory
 * @var array $searchHeroLocations
 * @var string $searchHeroSelectedLocation
 * @var array $searchHeroPopularTags
 * @var array $searchHeroHiddenFields
 */

$searchHeroTitle = (string)($searchHeroTitle ?? 'Find trusted UAE businesses');
$searchHeroDescription = (string)($searchHeroDescription ?? 'Search businesses by service, category, and location.');
$searchHeroFormAction = (string)($searchHeroFormAction ?? url('/listings'));
$searchHeroFormId = (string)($searchHeroFormId ?? 'public-search-hero-form');
$searchHeroKeyword = (string)($searchHeroKeyword ?? '');
$searchHeroSubmitLabel = (string)($searchHeroSubmitLabel ?? 'Search');
$searchHeroVariant = trim((string)($searchHeroVariant ?? 'home'));
$searchHeroShowCategory = !empty($searchHeroShowCategory);
$searchHeroShowLocation = !empty($searchHeroShowLocation);
$searchHeroCategories = is_array($searchHeroCategories ?? null) ? $searchHeroCategories : [];
$searchHeroSelectedCategory = (string)($searchHeroSelectedCategory ?? '');
$searchHeroLocations = is_array($searchHeroLocations ?? null) ? $searchHeroLocations : [];
$searchHeroSelectedLocation = (string)($searchHeroSelectedLocation ?? '');
$searchHeroPopularTags = is_array($searchHeroPopularTags ?? null) ? $searchHeroPopularTags : [];
$searchHeroHiddenFields = is_array($searchHeroHiddenFields ?? null) ? $searchHeroHiddenFields : [];
?>

<section class="public-search-hero public-search-hero--<?php echo htmlspecialchars($searchHeroVariant, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="container-narrow">
    <div class="hero-card public-search-hero__card">
      <div class="public-search-hero__content">
        <h1><?php echo htmlspecialchars($searchHeroTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p><?php echo htmlspecialchars($searchHeroDescription, ENT_QUOTES, 'UTF-8'); ?></p>
      </div>

      <form class="search-panel public-search-hero__form" method="get" action="<?php echo htmlspecialchars($searchHeroFormAction, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($searchHeroFormId, ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ($searchHeroHiddenFields as $fieldName => $fieldValue): ?>
          <?php if ($fieldValue === '' || $fieldValue === null) { continue; } ?>
          <input type="hidden" name="<?php echo htmlspecialchars((string)$fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string)$fieldValue, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>

        <div class="search-grid search-grid--wide public-search-hero__grid<?php echo ($searchHeroShowCategory || $searchHeroShowLocation) ? ' public-search-hero__grid--filters' : ''; ?>">
          <input
            class="field field--hero public-search-hero__keyword"
            name="keyword"
            type="text"
            placeholder="e.g. Bosch, CCTV, construction, hospitals..."
            aria-label="Search keyword"
            autocomplete="off"
            value="<?php echo htmlspecialchars($searchHeroKeyword, ENT_QUOTES, 'UTF-8'); ?>">

          <?php if ($searchHeroShowCategory): ?>
            <select class="select public-search-hero__select" name="category" aria-label="Select category">
              <option value="">All categories</option>
              <?php foreach ($searchHeroCategories as $categoryItem): ?>
                <?php
                  $categorySlug = (string)($categoryItem['slug'] ?? '');
                  $categoryName = (string)($categoryItem['name'] ?? ($categoryItem['category'] ?? 'Category'));
                ?>
                <option value="<?php echo htmlspecialchars($categorySlug, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $searchHeroSelectedCategory === $categorySlug ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>

          <?php if ($searchHeroShowLocation): ?>
            <select class="select public-search-hero__select" name="emirate" aria-label="Select city">
              <option value="">All UAE cities</option>
              <?php foreach ($searchHeroLocations as $locationValue => $locationLabel): ?>
                <option value="<?php echo htmlspecialchars((string)$locationValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $searchHeroSelectedLocation === (string)$locationValue ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars((string)$locationLabel, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>

          <button class="btn-ui btn-primary-ui btn--search public-search-hero__submit" type="submit"><?php echo htmlspecialchars($searchHeroSubmitLabel, ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
      </form>

      <?php if (!empty($searchHeroPopularTags)): ?>
        <div class="popular-tags public-search-hero__tags" aria-label="Popular searches">
          <span class="popular-tags__label">Popular:</span>
          <?php foreach ($searchHeroPopularTags as $tagItem): ?>
            <?php
              $tagLabel = (string)($tagItem['label'] ?? '');
              $tagUrl = (string)($tagItem['url'] ?? '');
              if ($tagLabel === '' || $tagUrl === '') {
                  continue;
              }
            ?>
            <a class="popular-tag" href="<?php echo htmlspecialchars($tagUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tagLabel, ENT_QUOTES, 'UTF-8'); ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>