<?php
/**
 * Page: Business Directory Categories (Premium Redesign)
 * Route: /categories (alias: /business-directory)
 * Features: Modern hero, enhanced stats, professional cards, trending indicators
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';

$pageTitle = 'UAE Business Directory - Find Companies by Industry';
$pageDescription = 'Explore comprehensive business categories. Connect with verified suppliers, service providers, and partners across all sectors in the UAE.';
$bodyClass = 'page-categories';

$orderBy = 'c.total_companies DESC, c.name ASC';

// OPTIMIZED QUERY: Use total_companies column directly (no subquery needed!)
$sql = "
    SELECT 
        c.id,
        c.name,
        c.slug,
        c.icon,
        c.description,
        c.total_companies AS company_count
    FROM `" . DB::CATEGORIES . "` c
    WHERE c.is_active = 1
";

$sql .= " ORDER BY {$orderBy}";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $categories = [];
} else {
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt->close();
}

$totalCategories = count($categories);
$totalBusinesses = array_sum(array_map(static function ($category) {
    return (int)($category['company_count'] ?? 0);
}, $categories));

$topCategory = $categories[0]['name'] ?? 'N/A';

include __DIR__ . '/../includes/layout/header.php';
?>

<style>
    /* ===== HERO SECTION ===== */
    .directory-hero {
        background: linear-gradient(135deg, #0f4ad8 0%, #1d3a8a 50%, #0f2668 100%);
        position: relative;
        overflow: hidden;
        padding: clamp(28px, 4vw, 44px) clamp(18px, 2.6vw, 32px);
        border-radius: 16px;
        margin: 10px 0 28px;
        color: white;
        width: 100%;
    }

    .directory-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .directory-hero-content {
        position: relative;
        z-index: 1;
        max-width: 700px;
    }

    .directory-title {
        margin: 0 0 12px;
        font-size: clamp(1.5rem, 2.8vw, 2.2rem);
        line-height: 1.15;
        color: #ffffff;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .directory-subtitle {
        margin: 0 0 18px;
        color: rgba(255, 255, 255, 0.9);
        font-size: clamp(0.95rem, 1.2vw, 1.05rem);
        line-height: 1.55;
        font-weight: 500;
    }

    /* ===== STATS SECTION ===== */
    .directory-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-top: 0;
    }

    .directory-stat-card {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 14px;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .directory-stat-card:hover {
        background: rgba(255, 255, 255, 0.18);
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    .directory-stat-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.2);
        color: #fbbf24;
        font-size: 1rem;
        margin-bottom: 8px;
    }

    .directory-stat-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: #ffffff;
        line-height: 1;
        margin-bottom: 4px;
    }

    .directory-stat-label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* ===== GRID & CARDS ===== */
    .directory-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        margin-bottom: 24px;
    }

    .directory-card {
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border: 1px solid #e5ebf2;
        border-radius: 14px;
        padding: 18px;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        color: #111827;
        position: relative;
        overflow: hidden;
    }

    .directory-card::before {
        content: none;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #0f4ad8 0%, #1d3a8a 100%);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s ease;
    }

    .directory-card:hover {
        transform: translateY(-4px);
        border-color: #d2deed;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .directory-card-head {
        margin-bottom: 10px;
    }

    .directory-card-title {
        margin: 0;
        font-size: 1.02rem;
        font-weight: 700;
        line-height: 1.35;
        color: #0f172a;
        flex: 1;
    }

    .directory-card-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-bottom: 8px;
    }

    .directory-card-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 700;
        background: #f3f7fb;
        color: #275073;
        white-space: nowrap;
    }

    .directory-card-badge.trending {
        background: #fef3c7;
        color: #d97706;
    }

    .directory-card-badge.new {
        background: #dcfce7;
        color: #16a34a;
    }

    .directory-card-desc {
        margin: 0 0 10px;
        color: #526277;
        font-size: 0.88rem;
        line-height: 1.55;
        display: -webkit-box;
        line-clamp: 2;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }

    .directory-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px dashed #dce5ee;
        color: #1e4f83;
        font-size: 0.82rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .directory-card:hover .directory-card-footer {
        color: #1d3a8a;
    }

    .directory-card-footer i {
        transition: transform 0.3s ease;
    }

    .directory-card:hover .directory-card-footer i {
        transform: translateX(4px);
    }

    /* ===== EMPTY STATE ===== */
    .directory-empty {
        border: 2px dashed #cbd5e1;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 16px;
        padding: 60px 32px;
        text-align: center;
        color: #475569;
        grid-column: 1 / -1;
    }

    .directory-empty h3 {
        margin: 0 0 12px;
        font-size: 1.4rem;
        color: #0f172a;
        font-weight: 700;
    }

    .directory-empty p {
        margin: 0;
        font-size: 1rem;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .directory-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }

        .directory-hero {
            padding: 40px 24px;
        }
    }

    @media (max-width: 991.98px) {
        .directory-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
        }

        .directory-card {
            padding: 13px;
            border-radius: 14px;
            min-height: 168px;
        }

        .directory-card-title {
            font-size: 0.96rem;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .directory-card-desc {
            font-size: 0.84rem;
            line-height: 1.4;
        }

        .directory-card-footer {
            font-size: 0.78rem;
            padding-top: 8px;
        }
    }

    @media (max-width: 768px) {
        .directory-hero {
            padding: 30px 20px;
            margin-bottom: 32px;
        }

        .directory-title {
            font-size: 1.8rem;
        }

        .directory-subtitle {
            font-size: 0.95rem;
            margin-bottom: 24px;
        }

        .directory-stats {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .directory-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .directory-search {
            flex-direction: column;
            min-width: unset;
        }

        .directory-search .form-control,
        .directory-search .btn {
            width: 100%;
        }

        .directory-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .directory-card {
            padding: 14px;
        }

        .directory-card-head {
            margin-bottom: 8px;
        }

        .directory-card-title {
            font-size: 0.98rem;
        }

        .directory-card-desc {
            font-size: 0.84rem;
            -webkit-line-clamp: 3;
            line-clamp: 3;
        }

        .directory-card-footer {
            padding-top: 8px;
            font-size: 0.78rem;
        }

        .directory-quick-filters {
            gap: 8px;
        }

        .quick-filter-chip {
            font-size: 0.85rem;
            padding: 6px 12px;
        }
    }

    @media (max-width: 480px) {
        .directory-title {
            font-size: 1.5rem;
        }

        .directory-card-title {
            font-size: 0.95rem;
        }

    }
</style>

<section class="sptb">
    <div class="container">
        <!-- Hero Section -->
        <div class="directory-hero">
            <div class="directory-hero-content">
                <h1 class="directory-title">Explore Business Categories</h1>
                <p class="directory-subtitle">Discover verified companies and connect with trusted partners across all industries in the UAE.</p>
                
                <div class="directory-stats">
                    <div class="directory-stat-card">
                        <div class="directory-stat-icon"><i class="fa fa-list"></i></div>
                        <div class="directory-stat-value"><?php echo $totalCategories; ?></div>
                        <div class="directory-stat-label">Active Categories</div>
                    </div>
                    <div class="directory-stat-card">
                        <div class="directory-stat-icon"><i class="fa fa-building"></i></div>
                        <div class="directory-stat-value"><?php echo $totalBusinesses; ?></div>
                        <div class="directory-stat-label">Published Listings</div>
                    </div>
                    <div class="directory-stat-card">
                        <div class="directory-stat-icon"><i class="fa fa-fire"></i></div>
                        <div class="directory-stat-value"><?php echo htmlspecialchars($topCategory, ENT_QUOTES); ?></div>
                        <div class="directory-stat-label">Most Active Category</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Grid -->
        <?php if (!empty($categories)): ?>
            <div class="directory-grid">
                <?php foreach ($categories as $index => $category): ?>
                    <a href="<?php echo htmlspecialchars(url('/category/' . $category['slug']), ENT_QUOTES); ?>" class="directory-card">
                        <div class="directory-card-head">
                            <h3 class="directory-card-title"><?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?></h3>
                        </div>

                        <div class="directory-card-badges">
                            <span class="directory-card-badge">
                                <i class="fa fa-building"></i>
                                <?php echo number_format((int)$category['company_count']); ?> listings
                            </span>
                            <?php if ($index < 3): ?>
                                <span class="directory-card-badge trending">
                                    <i class="fa fa-bolt"></i>Trending
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="directory-card-desc">
                            <?php
                            $categoryNameText = trim((string)($category['name'] ?? ''));
                            $description = trim((string)($category['description'] ?? ''));
                            $descriptionMatchesTitle = $description !== '' && strcasecmp($description, $categoryNameText) === 0;
                            echo htmlspecialchars(
                                ($description !== '' && !$descriptionMatchesTitle)
                                    ? $description
                                    : 'Browse verified companies and connect with trusted service providers in this category.',
                                ENT_QUOTES
                            );
                            ?>
                        </p>

                        <div class="directory-card-footer">
                            <span>View Category</span>
                            <i class="fa fa-arrow-right ms-2"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="directory-empty">
                <i class="fa fa-search directory-empty-icon"></i>
                <h3>No categories found</h3>
                <p>Please check back later or <a href="<?php echo htmlspecialchars(url('/categories'), ENT_QUOTES); ?>" class="directory-empty-link">reload the categories page</a></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
