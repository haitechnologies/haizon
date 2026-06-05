<?php
$publicAds = isset($publicAds) && is_array($publicAds) ? $publicAds : [];
$publicAdSlot = trim((string)($publicAdSlot ?? 'inline'));
$publicAdHeading = trim((string)($publicAdHeading ?? 'Sponsored software'));
$projectRootPath = realpath(__DIR__ . '/../../');

if (empty($publicAds)) {
    return;
}

static $publicAdsCssPrinted = false;
if (!$publicAdsCssPrinted):
    $publicAdsCssPrinted = true;
?>
<style>
  .public-ads-slot {
    margin: 24px 0;
  }
  .public-ads-slot__label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #0f4ad8;
    margin-bottom: 12px;
  }
  .public-ads-grid {
    display: grid;
    gap: 16px;
  }
  .public-ads-grid--inline,
  .public-ads-grid--sidebar {
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  }
  .public-ads-grid--wide {
    grid-template-columns: 1fr;
  }
  .public-ad-card {
    display: flex;
    gap: 18px;
    align-items: stretch;
    background: linear-gradient(135deg, #fffdf7 0%, #eef5ff 100%);
    border: 1px solid #d6e4ff;
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(15, 74, 216, 0.08);
  }
  .public-ad-card--stack {
    flex-direction: column;
  }
  .public-ad-card__media {
    flex: 0 0 160px;
    width: 160px;
    max-width: 160px;
    aspect-ratio: 1 / 1;
    border-radius: 12px;
    overflow: hidden;
    background: #dbeafe;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .public-ad-card__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
  }

  .public-ads-grid--wide .public-ad-card__media {
    flex-basis: 200px;
    width: 200px;
    max-width: 200px;
  }

  .public-ad-card--stack .public-ad-card__media {
    width: 100%;
    max-width: 240px;
    margin: 0 auto;
  }
  .public-ad-card__body {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
    flex: 1;
  }
  .public-ad-card__badge {
    display: inline-flex;
    width: fit-content;
    font-size: 0.75rem;
    font-weight: 700;
    color: #92400e;
    background: #fef3c7;
    border-radius: 999px;
    padding: 5px 10px;
  }
  .public-ad-card__title {
    margin: 0;
    font-size: 1.15rem;
    line-height: 1.3;
    color: #0f172a;
  }
  .public-ad-card__desc {
    margin: 0;
    color: #475569;
    line-height: 1.65;
  }
  .public-ad-card__meta {
    font-size: 0.82rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
  }
  .public-ad-card__actions {
    margin-top: auto;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  .public-ad-card__cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 16px;
    border-radius: 999px;
    background: #0f4ad8;
    color: #ffffff;
    text-decoration: none;
    font-weight: 700;
  }
  .public-ad-card__cta:hover {
    color: #ffffff;
    background: #0b3cae;
  }
  .public-ad-card__campaign {
    color: #0f4ad8;
    font-weight: 600;
  }
  @media (max-width: 767.98px) {
    .public-ads-slot {
      margin: 10px 0;
    }

    .public-ads-slot__label {
      font-size: 0.7rem;
      margin-bottom: 6px;
    }

    .public-ads-slot--sidebar {
      display: none;
    }

    .public-ads-grid {
      gap: 8px;
    }

    .public-ads-grid .public-ad-card:nth-child(n + 2) {
      display: none;
    }

    .public-ad-card {
      flex-direction: column;
      gap: 10px;
      padding: 10px;
      border-radius: 12px;
    }

    .public-ad-card__media {
      max-width: none;
      width: 100%;
      flex-basis: auto;
      aspect-ratio: 16 / 9;
      border-radius: 10px;
    }

    .public-ad-card__body {
      gap: 6px;
    }

    .public-ad-card__title {
      font-size: 1rem;
      line-height: 1.25;
      display: -webkit-box;
      line-clamp: 2;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .public-ad-card__desc {
      font-size: 0.88rem;
      line-height: 1.35;
      display: -webkit-box;
      line-clamp: 2;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .public-ad-card__meta {
      font-size: 0.72rem;
    }

    .public-ad-card__cta {
      width: 100%;
      padding: 8px 12px;
      font-size: 0.85rem;
    }
  }
</style>
<?php endif; ?>

<div class="public-ads-slot public-ads-slot--<?php echo htmlspecialchars($publicAdSlot, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="public-ads-slot__label">Sponsored • <?php echo htmlspecialchars($publicAdHeading, ENT_QUOTES, 'UTF-8'); ?></div>
  <div class="public-ads-grid public-ads-grid--<?php echo htmlspecialchars($publicAdSlot, ENT_QUOTES, 'UTF-8'); ?>">
    <?php foreach ($publicAds as $publicAd): ?>
      <?php
        $imagePath = trim((string)($publicAd['image_path'] ?? ''));
        $hasImage = $imagePath !== '';
        $cardClass = $publicAdSlot === 'sidebar' ? 'public-ad-card public-ad-card--stack' : 'public-ad-card';
        $clickUrl = url('/ad-click?id=' . urlencode((string)($publicAd['id'] ?? 0)));
        $ctaText = trim((string)($publicAd['cta_text'] ?? ''));
        if ($ctaText === '') {
          $ctaText = 'See demo';
        }
        $imageUrl = $imagePath;
        if ($hasImage && !preg_match('#^(https?:)?//#i', $imagePath) && strpos($imagePath, 'data:') !== 0) {
          $normalizedImagePath = ltrim($imagePath, '/\\');
          $imageFsPath = $projectRootPath ? ($projectRootPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $normalizedImagePath)) : '';
          if ($imageFsPath !== '' && is_file($imageFsPath)) {
            $imageUrl = url('/' . $normalizedImagePath);
          } else {
            $imageUrl = url('/assets/images/banners/banner1.jpg');
          }
        }
        $imageAlt = trim((string)($publicAd['image_alt'] ?? $publicAd['title'] ?? 'Sponsored ad'));
      ?>
      <article class="<?php echo $cardClass; ?>">
        <?php if ($hasImage): ?>
          <div class="public-ad-card__media">
            <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        <?php endif; ?>
        <div class="public-ad-card__body">
          <div class="public-ad-card__meta">
            <span class="public-ad-card__campaign"><?php echo htmlspecialchars($publicAd['campaign_name'] ?? 'Software promotion', ENT_QUOTES, 'UTF-8'); ?></span>
            <?php if (!empty($publicAd['product_category'])): ?>
              · <?php echo htmlspecialchars($publicAd['product_category'], ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
          </div>
          <?php if (!empty($publicAd['badge_text'])): ?>
            <span class="public-ad-card__badge"><?php echo htmlspecialchars($publicAd['badge_text'], ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <h3 class="public-ad-card__title"><?php echo htmlspecialchars($publicAd['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
          <p class="public-ad-card__desc"><?php echo htmlspecialchars($publicAd['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
          <div class="public-ad-card__actions">
            <a class="public-ad-card__cta" href="<?php echo htmlspecialchars($clickUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener sponsored nofollow"><?php echo htmlspecialchars($ctaText, ENT_QUOTES, 'UTF-8'); ?></a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
