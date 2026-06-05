<?php
$globalSearchKeyword = trim((string)($_GET['keyword'] ?? $_GET['q'] ?? ''));
$globalSearchEmirate = trim((string)($_GET['emirate'] ?? $_GET['city'] ?? ''));

if ($globalSearchEmirate !== '') {
    $globalSearchEmirate = strtolower($globalSearchEmirate);
    $globalSearchEmirate = str_replace(['_', ' '], '-', $globalSearchEmirate);
    $globalSearchEmirate = preg_replace('/-+/', '-', $globalSearchEmirate);
    $globalSearchEmirate = trim((string)$globalSearchEmirate, '-');
}

$globalSearchEmirates = [
    'abu-dhabi' => 'Abu Dhabi',
    'dubai' => 'Dubai',
    'sharjah' => 'Sharjah',
    'ajman' => 'Ajman',
    'umm-al-quwain' => 'Umm Al Quwain',
    'ras-al-khaimah' => 'Ras Al Khaimah',
    'fujairah' => 'Fujairah',
];
?>

<section class="public-global-search" aria-label="Global business search">
    <div class="container">
        <div class="public-global-search__inner">
            <form class="search-panel public-global-search__form" method="get" action="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="search-grid public-global-search__grid">
                    <label class="visually-hidden" for="global-search-keyword">Search keyword</label>
                    <input
                        id="global-search-keyword"
                        class="field field--hero public-global-search__keyword"
                        name="keyword"
                        type="text"
                        placeholder="Search businesses, brands, services, or products"
                        autocomplete="off"
                        value="<?php echo htmlspecialchars($globalSearchKeyword, ENT_QUOTES, 'UTF-8'); ?>">

                    <label class="visually-hidden" for="global-search-emirate">Choose emirate</label>
                    <select id="global-search-emirate" class="select public-global-search__select" name="emirate">
                        <option value="">All Emirates</option>
                        <?php foreach ($globalSearchEmirates as $emirateValue => $emirateLabel): ?>
                            <option value="<?php echo htmlspecialchars($emirateValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $globalSearchEmirate === $emirateValue ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emirateLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="btn-ui btn-primary-ui btn--search public-global-search__submit" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>
</section>