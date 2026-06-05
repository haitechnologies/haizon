<?php
/**
 * Trending Searches Widget
 * Displays top trending search queries on the homepage
 * 
 * Variables used:
 * - $basePath: Base URL path for links
 * - $conn: Database connection
 * 
 * Optional:
 * - $limit: Number of trending items to show (default: 10)
 * - $show_stats: Whether to show search count numbers (default: true)
 * - $title: Widget title (default: 'Trending Searches')
 */

if (!isset($conn)) {
    error_log("Trending searches widget: Database connection not provided");
    exit;
}

require_once __DIR__ . '/../../classes/frontend/Searches.php';

$limit = $limit ?? 10;
$show_stats = $show_stats ?? true;
$title = $title ?? 'Trending Searches';

$searches = new Searches($conn);
$trending = $searches->getTrending(['limit' => $limit, 'min_count' => 2]);
$total_searches = $searches->getTotalSearchCount();
$unique_searches = $searches->getUniqueSearchCount();

?>
<div class="trending-searches-widget">
    <div class="widget-header">
        <h3 class="widget-title">
            <i class="fas fa-fire"></i> <?php echo htmlspecialchars($title); ?>
        </h3>
        <p class="widget-subtitle">
            <?php printf('%s searches from %s users', number_format($total_searches), number_format($unique_searches)); ?>
        </p>
    </div>

    <div class="trending-content">
        <?php if (!empty($trending)): ?>
            <div class="trending-list">
                <?php foreach ($trending as $index => $item): 
                    $rank = $index + 1;
                    $query = htmlspecialchars($item['search_query']);
                    $count = intval($item['search_count']);
                    $url = $basePath . 'search?' . http_build_query(['q' => $item['search_query']]);
                ?>
                    <div class="trending-item rank-<?php echo $rank; ?>">
                        <div class="trend-rank">
                            <?php if ($rank <= 3): ?>
                                <span class="badge-top-<?php echo $rank; ?>">
                                    <?php if ($rank == 1): ?>
                                        <i class="fas fa-crown"></i>
                                    <?php elseif ($rank == 2): ?>
                                        <i class="fas fa-medal"></i>
                                    <?php else: ?>
                                        <i class="fas fa-award"></i>
                                    <?php endif; ?>
                                    <?php echo $rank; ?>
                                </span>
                            <?php else: ?>
                                <span class="badge-rank"><?php echo $rank; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="trend-content">
                            <a href="<?php echo $url; ?>" class="trend-link">
                                <?php echo $query; ?>
                            </a>
                            <?php if ($show_stats): ?>
                                <span class="trend-count"><?php echo number_format($count); ?> searches</span>
                            <?php endif; ?>
                        </div>

                        <div class="trend-bar">
                            <?php 
                                $max_count = intval($trending[0]['search_count']);
                                $width = $max_count > 0 ? ($count / $max_count) * 100 : 0;
                            ?>
                            <div class="bar-fill" style="width: <?php echo $width; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="trending-footer">
                <a href="<?php echo $basePath; ?>search-analytics" class="btn-view-all">
                    View All Searches <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chart-bar"></i>
                <p>No trending searches yet. Come back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.trending-searches-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 24px;
    color: white;
    margin: 24px 0;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.widget-header {
    margin-bottom: 20px;
}

.widget-title {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.widget-title i {
    font-size: 24px;
}

.widget-subtitle {
    font-size: 13px;
    opacity: 0.9;
    margin: 0;
}

.trending-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.trending-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.trending-item:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(4px);
}

.trend-rank {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
}

.badge-top-1, .badge-top-2, .badge-top-3 {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.badge-top-1 {
    background: #ffd700;
    color: #764ba2;
}

.badge-top-2 {
    background: #c0c0c0;
    color: #333;
}

.badge-top-3 {
    background: #cd7f32;
    color: white;
}

.badge-rank {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.trend-content {
    flex: 1;
    min-width: 0;
}

.trend-link {
    display: block;
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    word-break: break-word;
    transition: opacity 0.2s;
}

.trend-link:hover {
    opacity: 0.8;
    text-decoration: underline;
}

.trend-count {
    display: block;
    font-size: 12px;
    opacity: 0.85;
    margin-top: 2px;
}

.trend-bar {
    flex-shrink: 0;
    width: 60px;
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
    overflow: hidden;
    margin-left: 12px;
}

.bar-fill {
    height: 100%;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 2px;
    transition: width 0.3s ease;
}

.trending-item:hover .bar-fill {
    background: white;
}

.trending-footer {
    margin-top: 16px;
    text-align: center;
}

.btn-view-all {
    display: inline-block;
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-view-all:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
}

.btn-view-all i {
    margin-left: 6px;
    font-size: 11px;
}

.empty-state {
    text-align: center;
    padding: 30px 20px;
    opacity: 0.9;
}

.empty-state i {
    font-size: 36px;
    margin-bottom: 12px;
    display: block;
}

.empty-state p {
    font-size: 14px;
    margin: 0;
}

/* Responsive design */
@media (max-width: 768px) {
    .trending-searches-widget {
        padding: 16px;
        margin: 16px 0;
    }

    .widget-title {
        font-size: 18px;
    }

    .trending-item {
        padding: 8px;
        gap: 8px;
    }

    .trend-rank {
        width: 30px;
        height: 30px;
    }

    .trend-link {
        font-size: 13px;
    }

    .trend-count {
        font-size: 11px;
    }

    .trend-bar {
        width: 50px;
    }
}
</style>
