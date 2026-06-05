<?php
/**
 * Home page blog summary card.
 *
 * Expected variables:
 * @var array $blog
 */

$homeBlogSlug = (string)($blog['slug'] ?? '');
$homeBlogTitle = (string)($blog['title'] ?? '');
$homeBlogViews = (int)($blog['views'] ?? 0);
$homeBlogExcerpt = (string)($blog['summary'] ?? ($blog['description'] ?? ($blog['content'] ?? '')));
$homeBlogExcerpt = strip_tags($homeBlogExcerpt);
if (strlen($homeBlogExcerpt) > 120) {
    $homeBlogExcerpt = substr($homeBlogExcerpt, 0, 120) . '...';
}
?>

<article class="card-ui blog-card">
  <div class="blog-top">
    <span class="blog-views">
      <span class="home-eye-icon">👁</span>
      <?php echo number_format($homeBlogViews); ?> views
    </span>
  </div>
  <h3>
    <a href="<?php echo htmlspecialchars(url('/blog/' . $homeBlogSlug), ENT_QUOTES, 'UTF-8'); ?>" class="home-blog-link">
      <?php echo htmlspecialchars($homeBlogTitle, ENT_QUOTES, 'UTF-8'); ?>
    </a>
  </h3>
  <p class="meta-line">
    <time datetime="<?php echo htmlspecialchars((string)($blog['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
      <?php
      if (!empty($blog['created_at'])) {
          echo htmlspecialchars(date('d M Y', strtotime((string)$blog['created_at'])), ENT_QUOTES, 'UTF-8');
      }
      ?>
    </time>
  </p>
  <p class="muted home-blog-excerpt">
    <?php echo htmlspecialchars($homeBlogExcerpt, ENT_QUOTES, 'UTF-8'); ?>
  </p>
  <a href="<?php echo htmlspecialchars(url('/blog/' . $homeBlogSlug), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui home-btn-top">Read more</a>
</article>