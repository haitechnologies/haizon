<?php
/**
 * AMP Page: Blog Post Detail
 * Route: /blog/{slug}/amp
 * 
 * Mobile-optimized blog post page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/frontend/Blogs.php';

// Get blog slug from route
$slug = $GLOBALS['route_params']['blog_slug'] ?? null;

if (!$slug) {
    header('Location: ' . url('/blog'));
    exit;
}

$blogsModel = new Blogs($conn);

// Get blog by slug (don't increment views for AMP pages)
$blog = $blogsModel->getBySlug($slug, false);

if (!$blog) {
  header('Location: ' . url('/blog/amp'));
    exit;
}

// Get blog category
$category = null;
if (!empty($blog['category_id'])) {
    $categoryQuery = "SELECT id, name, slug FROM " . DB::BLOG_CATEGORIES . " WHERE id = ? AND status = 1 LIMIT 1";
    $stmt = $conn->prepare($categoryQuery);
    $stmt->bind_param("i", $blog['category_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
}

// Get related blogs
$relatedBlogs = [];
if (!empty($blog['category_id'])) {
    $relatedBlogs = $blogsModel->getRelated($blog['category_id'], $blog['id'], 3);
}

// Page meta
$pageTitle = !empty($blog['meta_title'])
    ? $blog['meta_title']
    : $blog['title'] . ' - Blog';

$pageDescription = !empty($blog['meta_description'])
    ? $blog['meta_description']
    : truncateText(strip_tags($blog['content'] ?? ''), 160);

$pageKeywords = !empty($blog['meta_keywords'])
    ? $blog['meta_keywords']
    : $blog['title'] . ', UAE business news, blog';

$canonicalUrl = url('/blog/' . $blog['slug']);
$pageUrl = url('/blog/' . $blog['slug'] . '/amp');

// Open Graph
$ogTitle = $blog['title'] . ' - UAE Business Directory';
$ogDescription = $pageDescription;
$ogImage = !empty($blog['featured_image']) 
    ? getFullUrl('/' . $blog['featured_image']) 
    : getFullUrl('/assets/images/brand/logo.png');
$ogType = 'article';

// Twitter Card
$twitterCard = 'summary_large_image';
$twitterTitle = $ogTitle;
$twitterDescription = $pageDescription;
$twitterImage = $ogImage;

// Schema.org BlogPosting
$author = !empty($blog['author_name']) ? $blog['author_name'] : 'UAE Business Directory';
$datePublished = date('c', strtotime($blog['created_at']));
$dateModified = !empty($blog['updated_at']) 
    ? date('c', strtotime($blog['updated_at'])) 
    : $datePublished;

$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $blog['title'],
    'description' => $pageDescription,
    'author' => [
        '@type' => 'Person',
        'name' => $author
    ],
    'datePublished' => $datePublished,
    'dateModified' => $dateModified,
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'UAE Business Directory',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => getFullUrl('/assets/images/brand/logo.png')
        ]
    ]
];

if (!empty($blog['featured_image'])) {
    $schemaData['image'] = getFullUrl('/' . $blog['featured_image']);
}

if ($category) {
    $schemaData['articleSection'] = $category['name'];
  }

  // Add mainEntityOfPage
  $schemaData['mainEntityOfPage'] = [
    '@type' => 'WebPage',
    '@id' => $pageUrl
  ];

  // Add word count if available
  if (!empty($blog['content'])) {
    $schemaData['wordCount'] = str_word_count(strip_tags($blog['content']));
  }

  // Breadcrumb Schema (separate from main schema)
  $breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => getFullUrl('/')
      ],
      [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => 'Blog',
        'item' => getFullUrl('/blog')
      ]
    ]
  ];

  if ($category) {
    $breadcrumbSchema['itemListElement'][] = [
      '@type' => 'ListItem',
      'position' => 3,
      'name' => $category['name'],
      'item' => getFullUrl('/blog/category/' . urlencode($category['slug']))
    ];
    $breadcrumbSchema['itemListElement'][] = [
      '@type' => 'ListItem',
      'position' => 4,
      'name' => $blog['title'],
      'item' => $pageUrl
    ];
  } else {
    $breadcrumbSchema['itemListElement'][] = [
      '@type' => 'ListItem',
      'position' => 3,
      'name' => $blog['title'],
      'item' => $pageUrl
    ];
  }

  // Combine schemas into array for multiple schema output
  $schemaData = [
    $schemaData,
    $breadcrumbSchema
  ];

// AMP components
$ampComponents = [];
if (!empty($blog['featured_image'])) {
    $ampComponents[] = ['name' => 'amp-img', 'src' => 'https://cdn.ampproject.org/v0/amp-img-0.1.js'];
}

$pageCustomCss = <<<'CSS'
  /* Blog Detail Styles */
  .blog-header {
    background: linear-gradient(135deg, #0f4ad8 0%, #1e5fd8 100%);
    color: white;
    padding: 32px 16px;
    margin-bottom: 24px;
  }
  
  .blog-title {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1.3;
    margin-bottom: 16px;
  }
  
  .blog-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 0.9rem;
    opacity: 0.95;
  }
  
  .blog-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  
  .blog-category-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 0.85rem;
    margin-top: 12px;
  }
  
  .blog-content {
    background: white;
    padding: 24px 16px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
  }
  
  .blog-featured-image {
    width: 100%;
    height: auto;
    border-radius: 8px;
    margin-bottom: 24px;
  }
  
  .blog-content h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 24px 0 12px;
    color: #1e5fd8;
  }
  
  .blog-content h3 {
    font-size: 1.3rem;
    font-weight: 600;
    margin: 20px 0 10px;
    color: #333;
  }
  
  .blog-content p {
    line-height: 1.8;
    margin-bottom: 16px;
    color: #444;
  }
  
  .blog-content ul, .blog-content ol {
    margin: 16px 0;
    padding-left: 24px;
  }
  
  .blog-content li {
    margin-bottom: 8px;
    line-height: 1.6;
  }
  
  .blog-content blockquote {
    border-left: 4px solid #1e5fd8;
    padding: 12px 16px;
    margin: 20px 0;
    background: #f8f9fa;
    font-style: italic;
  }
  
  .related-posts {
    background: white;
    padding: 24px 16px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  
  .related-posts h3 {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: #333;
  }
  
  .related-post-item {
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
  }
  
  .related-post-item:last-child {
    border-bottom: none;
  }
  
  .related-post-title {
    font-size: 1rem;
    font-weight: 500;
    color: #1e5fd8;
    text-decoration: none;
    display: block;
    margin-bottom: 4px;
  }
  
  .related-post-date {
    font-size: 0.85rem;
    color: #6c757d;
  }
  
  .back-link {
    display: inline-block;
    background: #1e5fd8;
    color: white;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    margin: 24px 16px;
  }
  
  .view-regular {
    text-align: center;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 6px;
    margin: 16px;
  }
  
  .view-regular a {
    color: #1e5fd8;
    text-decoration: none;
    font-weight: 500;
  }
CSS;

include __DIR__ . '/amp-header.php';
?>

<!-- Blog Header -->
<div class="blog-header">
  <h1 class="blog-title"><?= htmlspecialchars($blog['title'], ENT_QUOTES) ?></h1>
  
  <div class="blog-meta">
    <div class="blog-meta-item">
      <span>📅</span>
      <span><?= dd_($blog['created_at'], 'd M Y') ?></span>
    </div>
    
    <?php if (!empty($blog['author_name'])): ?>
    <div class="blog-meta-item">
      <span>✍️</span>
      <span><?= htmlspecialchars($blog['author_name'], ENT_QUOTES) ?></span>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($blog['views'])): ?>
    <div class="blog-meta-item">
      <span>👁️</span>
      <span><?= number_format($blog['views']) ?> views</span>
    </div>
    <?php endif; ?>
  </div>
  
  <?php if ($category): ?>
  <div class="blog-category-badge">
    <?= htmlspecialchars($category['name'], ENT_QUOTES) ?>
  </div>
  <?php endif; ?>
</div>

<!-- Featured Image -->
<?php if (!empty($blog['featured_image'])): ?>
<div style="padding: 0 16px;">
  <amp-img
    src="<?= htmlspecialchars($blog['featured_image'], ENT_QUOTES) ?>"
    alt="<?= htmlspecialchars($blog['title'], ENT_QUOTES) ?>"
    width="800"
    height="450"
    layout="responsive"
    class="blog-featured-image">
  </amp-img>
</div>
<?php endif; ?>

<!-- Blog Content -->
<div class="blog-content">
  <?php
  // Output blog content (strip any embedded scripts for AMP compliance)
  $content = $blog['content'] ?? '';
  // Remove script tags
  $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
  // Remove inline event handlers
  $content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
  // Remove style attributes (use amp-custom only)
  $content = preg_replace('/\s*style\s*=\s*["\'][^"\']*["\']/i', '', $content);
  
  echo $content;
  ?>
</div>

<!-- Related Posts -->
<?php if (!empty($relatedBlogs)): ?>
<div class="related-posts">
  <h3>Related Articles</h3>
  
  <?php foreach ($relatedBlogs as $related): ?>
  <div class="related-post-item">
    <a href="<?= url('/blog/' . urlencode($related['slug']) . '/amp') ?>" class="related-post-title">
      <?= htmlspecialchars($related['title'], ENT_QUOTES) ?>
    </a>
    <div class="related-post-date">
      <?= dd_($related['created_at'], 'd M Y') ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="context-nav">
  <div class="context-nav-title">Continue exploring on AMP</div>
  <div class="context-nav-links">
    <a href="<?= url('/blog/amp') ?>" class="context-nav-link">All Blog Articles</a>
    <a href="<?= url('/listings/amp') ?>" class="context-nav-link">Browse Companies</a>
    <a href="<?= url('/trade/hs-codes/amp') ?>" class="context-nav-link">HS Codes Directory</a>
    <a href="<?= url('/contact/amp') ?>" class="context-nav-link">Contact Team</a>
  </div>
</div>

<!-- View Full Version -->
<div class="view-regular">
  <a href="<?= $canonicalUrl ?>">View Full Website Version →</a>
</div>

<!-- Back to Blog -->
<a href="<?= url('/blog/amp') ?>" class="back-link">← Back to Blog</a>

<?php include __DIR__ . '/amp-footer.php'; ?>
