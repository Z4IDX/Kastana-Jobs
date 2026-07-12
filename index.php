<?php
require_once __DIR__ . '/config/config.php';

$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));

$allowedSorts = ['newest', 'popular', 'oldest', 'salary', 'salary_low', 'alpha'];
$sort = in_array($_GET['sort'] ?? '', $allowedSorts, true) ? $_GET['sort'] : 'newest';
$orderMap = [
    'newest'     => 'j.is_featured DESC, j.created_at DESC',
    'popular'    => 'j.views DESC, j.created_at DESC',
    'oldest'     => 'j.created_at ASC',
    'salary'     => '(j.salary_min IS NULL AND j.salary_max IS NULL) ASC, GREATEST(COALESCE(j.salary_max,0), COALESCE(j.salary_min,0)) DESC',
    'salary_low' => '(j.salary_min IS NULL AND j.salary_max IS NULL) ASC, COALESCE(j.salary_min, j.salary_max) ASC',
    'alpha'      => 'j.title ASC',
];

$allowedTypes = ['Full-time','Part-time','Contract','Internship','Remote','Temporary'];
$jobType = in_array($_GET['type'] ?? '', $allowedTypes, true) ? $_GET['type'] : 'all';

$categorySlug = $_GET['category'] ?? 'all';
$q = trim((string)($_GET['q'] ?? ''));

// Structured filters (zero-schema): location, date-posted, salary-disclosed.
$location = trim((string)($_GET['loc'] ?? ''));
$dateRanges  = ['24h' => 'INTERVAL 1 DAY', '3d' => 'INTERVAL 3 DAY', '7d' => 'INTERVAL 7 DAY', '30d' => 'INTERVAL 30 DAY'];
$datePosted  = array_key_exists($_GET['date'] ?? '', $dateRanges) ? $_GET['date'] : 'all';
$salaryShown = (($_GET['salary'] ?? '') === 'shown');

$filtersActive = ($categorySlug !== 'all' && $categorySlug !== '') || $jobType !== 'all' || $q !== ''
    || $sort !== 'newest' || ($location !== '' && $location !== 'all') || $datePosted !== 'all' || $salaryShown;

$expiryClause = "(j.expires_at IS NULL OR j.expires_at >= CURDATE())";
$where = "j.status = 'approved' AND $expiryClause";
$params = [];
if ($categorySlug !== 'all' && $categorySlug !== '') {
    $where .= " AND c.slug = ?";
    $params[] = $categorySlug;
}
if ($jobType !== 'all') {
    $where .= " AND j.job_type = ?";
    $params[] = $jobType;
}
if ($location !== '' && $location !== 'all') {
    $where .= " AND j.location = ?";
    $params[] = $location;
}
if ($datePosted !== 'all') {
    // Interval expression comes from the whitelisted $dateRanges map, never user input.
    $where .= " AND j.created_at >= (NOW() - {$dateRanges[$datePosted]})";
}
if ($salaryShown) {
    $where .= " AND (j.salary_min IS NOT NULL OR j.salary_max IS NOT NULL)";
}
if ($q !== '') {
    $where .= " AND (j.title LIKE ? OR j.title_ar LIKE ? OR j.company_name LIKE ? OR j.location LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM jobs j LEFT JOIN categories c ON c.id=j.category_id WHERE $where");
$countStmt->execute($params);
$totalJobsFiltered = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalJobsFiltered / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    "SELECT j.*, c.name AS category_name, c.name_ar AS category_name_ar, c.slug AS category_slug
     FROM jobs j LEFT JOIN categories c ON c.id = j.category_id
     WHERE $where ORDER BY {$orderMap[$sort]} LIMIT ? OFFSET ?"
);
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p, PDO::PARAM_STR); }
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i++, $offset, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll();

$categories = db()->query(
    "SELECT c.name, c.name_ar, c.slug, COUNT(j.id) AS job_count
     FROM categories c
     JOIN jobs j ON j.category_id = c.id AND j.status = 'approved' AND $expiryClause
     GROUP BY c.id, c.name, c.name_ar, c.slug
     ORDER BY c.name"
)->fetchAll();

$totalJobs = (int) db()->query("SELECT COUNT(*) FROM jobs j WHERE j.status='approved' AND $expiryClause")->fetchColumn();
$totalCompanies = (int) db()->query("SELECT COUNT(DISTINCT j.company_name) FROM jobs j WHERE j.status='approved' AND $expiryClause")->fetchColumn();

// Distinct locations for the location filter dropdown.
$locations = db()->query(
    "SELECT DISTINCT location FROM jobs
     WHERE status='approved' AND (expires_at IS NULL OR expires_at >= CURDATE()) AND location <> ''
     ORDER BY location"
)->fetchAll(PDO::FETCH_COLUMN);

// Featured & trending strip: featured first, then filled by most-viewed.
$featured = db()->query(
    "SELECT j.* FROM jobs j
     WHERE j.status='approved' AND $expiryClause
     ORDER BY j.is_featured DESC, j.views DESC, j.created_at DESC
     LIMIT 3"
)->fetchAll();

// Recently-viewed strip (from session, kept in order, live jobs only, hidden while filtering).
$recentJobs = [];
$__recentIds = $_SESSION['recent_jobs'] ?? [];
if ($__recentIds && !$filtersActive) {
    $ph = implode(',', array_fill(0, count($__recentIds), '?'));
    $rstmt = db()->prepare("SELECT j.* FROM jobs j WHERE j.id IN ($ph) AND j.status='approved' AND $expiryClause");
    $rstmt->execute($__recentIds);
    $__byId = [];
    foreach ($rstmt->fetchAll() as $rj) { $__byId[$rj['id']] = $rj; }
    foreach ($__recentIds as $rid) { if (isset($__byId[$rid])) $recentJobs[] = $__byId[$rid]; }
}

// Rotating hero ticker: job tips interleaved with trust facts and a live stat.
// SVG glyphs are our own trusted markup (echoed unescaped).
$icon_tip  = '<path d="M9 18h6M10 21h4M12 3a6 6 0 0 1 4 10.5c-.6.6-1 1.3-1 2.1H9c0-.8-.4-1.5-1-2.1A6 6 0 0 1 12 3z"/>';
$icon_fact = '<path d="M12 3l7 3v5c0 4.2-2.9 7.6-7 8.6-4.1-1-7-4.4-7-8.6V6l7-3z"/><path d="M9 12l2 2 4-4"/>';
$icon_stat = '<path d="M4 20h16"/><path d="M7 20v-6M12 20V8M17 20v-9"/>';
$tickerItems = [
    ['label' => t('tip_label'),  'text' => t('tip_1'),                             'icon' => $icon_tip],
    ['label' => t('fact_label'), 'text' => t('prop_reviewed'),                     'icon' => $icon_fact],
    ['label' => t('tip_label'),  'text' => t('tip_2'),                             'icon' => $icon_tip],
    ['label' => t('stat_label'), 'text' => t('ticker_stat', $totalJobs, $totalCompanies), 'icon' => $icon_stat],
    ['label' => t('tip_label'),  'text' => t('tip_3'),                             'icon' => $icon_tip],
    ['label' => t('fact_label'), 'text' => t('prop_direct'),                       'icon' => $icon_fact],
    ['label' => t('tip_label'),  'text' => t('tip_4'),                             'icon' => $icon_tip],
    ['label' => t('fact_label'), 'text' => t('prop_nospam'),                       'icon' => $icon_fact],
    ['label' => t('tip_label'),  'text' => t('tip_5'),                             'icon' => $icon_tip],
];

$page_title = t('board_title');
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="wrap hero__inner">
    <span class="eyebrow"><?= e(t('hero_eyebrow')) ?></span>
    <h1 class="hero__title-slim"><?= e(t('hero_heading_slim')) ?></h1>

    <form class="searchbar" method="get" action="<?= url('index.php') ?>#roles" role="search">
      <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
      <input type="hidden" name="type" value="<?= e($jobType) ?>">
      <input type="hidden" name="sort" value="<?= e($sort) ?>">
      <input type="hidden" name="loc" value="<?= e($location) ?>">
      <input type="hidden" name="date" value="<?= e($datePosted) ?>">
      <?php if ($salaryShown): ?><input type="hidden" name="salary" value="shown"><?php endif; ?>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('search_ph')) ?>" aria-label="<?= e(t('search_btn')) ?>">
      <button type="submit" class="btn btn--honey"><?= e(t('search_btn')) ?></button>
    </form>

    <div class="hero__ticker" role="note" data-ticker>
      <?php foreach ($tickerItems as $i => $it): ?>
      <div class="hero__tip <?= $i === 0 ? 'is-active' : '' ?>" data-ticker-item<?= $i === 0 ? '' : ' hidden aria-hidden="true"' ?>>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $it['icon'] ?></svg>
        <p><span class="hero__tip-label"><?= e($it['label']) ?>:</span> <?= e($it['text']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($featured && !$filtersActive): ?>
<section class="wrap featured" aria-label="<?= e(t('featured_title')) ?>">
  <div class="featured__head"><h2><?= e(t('featured_title')) ?></h2></div>
  <div class="featured__grid">
    <?php foreach ($featured as $fj):
        $fTitle = job_field($fj, 'title');
        $fLoc   = job_field($fj, 'location');
        $fThumb = $fj['thumbnail_path'] ?: $fj['image_path'];
        $fInit  = strtoupper(mb_substr($fj['company_name'], 0, 1));
    ?>
    <a class="fcard" href="<?= url('job.php?id=' . $fj['id']) ?>">
      <div class="fcard__top">
        <span class="fcard__logo" aria-hidden="true"><?php if (!empty($fThumb)): ?><img src="<?= url($fThumb) ?>" alt="" loading="lazy" decoding="async" width="38" height="38"><?php else: ?><?= e($fInit) ?><?php endif; ?></span>
        <?php if ($fj['is_featured']): ?>
          <span class="tag tag--featured"><?= e(t('featured')) ?></span>
        <?php else: ?>
          <span class="tag tag--trending"><?= e(t('tag_trending')) ?></span>
        <?php endif; ?>
      </div>
      <div class="fcard__title"><?= e($fTitle) ?></div>
      <div class="fcard__meta"><?= e($fj['company_name']) ?> · <?= e($fLoc) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($recentJobs): ?>
<section class="wrap featured" aria-label="<?= e(t('recently_viewed')) ?>">
  <div class="featured__head"><h2><?= e(t('recently_viewed')) ?></h2></div>
  <div class="featured__grid">
    <?php foreach ($recentJobs as $fj):
        $fTitle = job_field($fj, 'title');
        $fLoc   = job_field($fj, 'location');
        $fThumb = $fj['thumbnail_path'] ?: $fj['image_path'];
        $fInit  = strtoupper(mb_substr($fj['company_name'], 0, 1));
    ?>
    <a class="fcard" href="<?= url('job.php?id=' . $fj['id']) ?>">
      <div class="fcard__top">
        <span class="fcard__logo" aria-hidden="true"><?php if (!empty($fThumb)): ?><img src="<?= url($fThumb) ?>" alt="" loading="lazy" decoding="async" width="38" height="38"><?php else: ?><?= e($fInit) ?><?php endif; ?></span>
        <span class="tag"><?= e(job_type_label($fj['job_type'])) ?></span>
      </div>
      <div class="fcard__title"><?= e($fTitle) ?></div>
      <div class="fcard__meta"><?= e($fj['company_name']) ?> · <?= e($fLoc) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="board wrap" id="roles">
  <div class="board__head">
    <div>
      <span class="eyebrow"><?= e(t('board_eyebrow')) ?></span>
      <h2><?= e(t('board_title')) ?></h2>
    </div>
    <form class="board-search" method="get" action="<?= url('index.php') ?>#roles" role="search">
      <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
      <input type="hidden" name="type" value="<?= e($jobType) ?>">
      <input type="hidden" name="sort" value="<?= e($sort) ?>">
      <input type="hidden" name="loc" value="<?= e($location) ?>">
      <input type="hidden" name="date" value="<?= e($datePosted) ?>">
      <?php if ($salaryShown): ?><input type="hidden" name="salary" value="shown"><?php endif; ?>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('search_ph')) ?>" aria-label="<?= e(t('search_btn')) ?>">
      <button type="submit" class="btn btn--ghost btn--sm"><?= e(t('search_btn')) ?></button>
    </form>
  </div>

<?php
  // Category glyphs (our own trusted SVG markup — echoed unescaped).
  $catIcons = [
    'data'        => '<path d="M4 20h16"/><path d="M6 20v-6M11 20V7M16 20v-9"/>',
    'design'      => '<path d="M15 5l4 4M5 19l1-4L16 5l3 3L9 18l-4 1z"/>',
    'engineering' => '<path d="M9 7l-5 5 5 5M15 7l5 5-5 5"/>',
    'fieldwork'   => '<path d="M12 21s-6-5.3-6-10a6 6 0 0 1 12 0c0 4.7-6 10-6 10z"/><circle cx="12" cy="11" r="2"/>',
    'finance'     => '<path d="M12 3v18M16 6H9.5a3 3 0 0 0 0 6h5a3 3 0 0 1 0 6H7"/>',
    'hr'          => '<circle cx="9" cy="9" r="3"/><path d="M4 20a5 5 0 0 1 10 0"/><path d="M16 7a3 3 0 0 1 0 6M20 20a5 5 0 0 0-3-4.6"/>',
    'marketing'   => '<path d="M4 10v4h3l7 4V6L7 10H4z"/><path d="M18 9a3 3 0 0 1 0 6"/>',
    'operations'  => '<circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M3 12h3M18 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
    'product'     => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M4 7.5l8 4.5 8-4.5M12 12v9"/>',
  ];
  $catIconDefault = '<path d="M3 8h18v11H3zM8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>';
  $catIconAll     = '<path d="M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v4H4zM14 15h6v4h-6z"/>';
  $catIcon = fn(string $inner) => '<svg class="chip__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
?>
  <div class="board__toolbar">
    <div class="chips" role="group">
      <a class="chip <?= $categorySlug === 'all' ? 'is-active' : '' ?>" aria-pressed="<?= $categorySlug === 'all' ? 'true' : 'false' ?>"
         href="<?= e(query_url(['category' => null, 'page' => null])) ?>#roles"><?= $catIcon($catIconAll) ?><span class="chip__label"><?= e(t('filter_all')) ?></span><span class="chip__count"><?= (int)$totalJobs ?></span></a>
      <?php foreach ($categories as $cat): ?>
        <a class="chip <?= $categorySlug === $cat['slug'] ? 'is-active' : '' ?>" aria-pressed="<?= $categorySlug === $cat['slug'] ? 'true' : 'false' ?>"
           href="<?= e(query_url(['category' => $cat['slug'], 'page' => null])) ?>#roles"><?= $catIcon($catIcons[$cat['slug']] ?? $catIconDefault) ?><span class="chip__label"><?= e(cat_name($cat['name'], $cat['name_ar'])) ?></span><span class="chip__count"><?= (int)$cat['job_count'] ?></span></a>
      <?php endforeach; ?>
    </div>

    <form method="get" action="<?= url('index.php') ?>#roles" class="sort-form" id="filter-form">
      <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
      <input type="hidden" name="q" value="<?= e($q) ?>">
      <label for="type-select"><?= e(t('filter_type')) ?></label>
      <div class="sort-select">
        <select name="type" id="type-select">
          <option value="all" <?= $jobType === 'all' ? 'selected' : '' ?>><?= e(t('filter_all_types')) ?></option>
          <?php foreach ($allowedTypes as $tp): ?>
            <option value="<?= $tp ?>" <?= $jobType === $tp ? 'selected' : '' ?>><?= e(job_type_label($tp)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($locations): ?>
      <label for="loc-select"><?= e(t('filter_location')) ?></label>
      <div class="sort-select">
        <select name="loc" id="loc-select">
          <option value="all" <?= ($location === '' || $location === 'all') ? 'selected' : '' ?>><?= e(t('filter_all_locations')) ?></option>
          <?php foreach ($locations as $lc): ?>
            <option value="<?= e($lc) ?>" <?= $location === $lc ? 'selected' : '' ?>><?= e($lc) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <label for="date-select"><?= e(t('filter_date')) ?></label>
      <div class="sort-select">
        <select name="date" id="date-select">
          <option value="all" <?= $datePosted === 'all' ? 'selected' : '' ?>><?= e(t('date_all')) ?></option>
          <option value="24h" <?= $datePosted === '24h' ? 'selected' : '' ?>><?= e(t('date_24h')) ?></option>
          <option value="3d"  <?= $datePosted === '3d'  ? 'selected' : '' ?>><?= e(t('date_3d')) ?></option>
          <option value="7d"  <?= $datePosted === '7d'  ? 'selected' : '' ?>><?= e(t('date_7d')) ?></option>
          <option value="30d" <?= $datePosted === '30d' ? 'selected' : '' ?>><?= e(t('date_30d')) ?></option>
        </select>
      </div>
      <label for="sort-select"><?= e(t('sort_label')) ?></label>
      <div class="sort-select">
        <select name="sort" id="sort-select">
          <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>><?= e(t('sort_newest')) ?></option>
          <option value="popular"    <?= $sort === 'popular'    ? 'selected' : '' ?>><?= e(t('sort_popular')) ?></option>
          <option value="oldest"     <?= $sort === 'oldest'     ? 'selected' : '' ?>><?= e(t('sort_oldest')) ?></option>
          <option value="salary"     <?= $sort === 'salary'     ? 'selected' : '' ?>><?= e(t('sort_salary_high')) ?></option>
          <option value="salary_low" <?= $sort === 'salary_low' ? 'selected' : '' ?>><?= e(t('sort_salary_low')) ?></option>
          <option value="alpha"      <?= $sort === 'alpha'      ? 'selected' : '' ?>><?= e(t('sort_alpha')) ?></option>
        </select>
      </div>
      <label class="filter-check"><input type="checkbox" name="salary" value="shown" <?= $salaryShown ? 'checked' : '' ?>> <?= e(t('filter_salary_shown')) ?></label>
      <button type="submit" class="btn btn--ghost btn--sm sort-form__apply"><?= e(t('sort_apply')) ?></button>
    </form>
  </div>

<?php
  // Active-filter pills, each links to the same board with that one filter removed.
  $activeFilters = [];
  if ($q !== '')                              $activeFilters[] = ['label' => '“' . $q . '”', 'url' => query_url(['q' => null, 'page' => null])];
  if ($categorySlug !== 'all' && $categorySlug !== '') {
      $cn = $categorySlug;
      foreach ($categories as $ct) { if ($ct['slug'] === $categorySlug) { $cn = cat_name($ct['name'], $ct['name_ar']); break; } }
      $activeFilters[] = ['label' => $cn, 'url' => query_url(['category' => null, 'page' => null])];
  }
  if ($jobType !== 'all')                     $activeFilters[] = ['label' => job_type_label($jobType), 'url' => query_url(['type' => null, 'page' => null])];
  if ($location !== '' && $location !== 'all')$activeFilters[] = ['label' => $location, 'url' => query_url(['loc' => null, 'page' => null])];
  if ($datePosted !== 'all')                  $activeFilters[] = ['label' => t('date_' . $datePosted), 'url' => query_url(['date' => null, 'page' => null])];
  if ($salaryShown)                           $activeFilters[] = ['label' => t('filter_salary_shown'), 'url' => query_url(['salary' => null, 'page' => null])];
?>
  <div class="board__meta">
    <span class="board__count"><?= e(t('results_count', $totalJobsFiltered)) ?></span>
    <?php foreach ($activeFilters as $af): ?>
      <a class="chip chip--filter" href="<?= e($af['url']) ?>#roles" title="<?= e(t('clear_filters')) ?>"><?= e($af['label']) ?> <span aria-hidden="true">✕</span></a>
    <?php endforeach; ?>
    <?php if ($filtersActive): ?>
      <a class="chip chip--clear" href="<?= url('index.php') ?>#roles"><?= e(t('clear_filters')) ?></a>
    <?php endif; ?>
  </div>

  <div class="jobs-grid">
    <?php foreach ($jobs as $job):
        $salary  = format_salary($job['salary_min'] ?: null, $job['salary_max'] ?: null, $job['salary_currency']);
        $initial = strtoupper(mb_substr($job['company_name'], 0, 1));
        $title   = job_field($job, 'title');
        $loc     = job_field($job, 'location');
        include __DIR__ . '/includes/job-card.php';
    endforeach; ?>

    <?php if (empty($jobs)): ?>
      <div class="empty">
        <h3><?= e(t('empty_title')) ?></h3>
        <p><?= e(t('empty_body')) ?></p>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="pager" aria-label="Pagination">
      <?php if ($page > 1): ?><a class="btn btn--ghost btn--sm" href="<?= e(query_url(['page' => $page - 1])) ?>#roles">← <?= e(t('pager_prev')) ?></a><?php endif; ?>
      <span><?= e(t('pager_page', $page, $totalPages)) ?></span>
      <?php if ($page < $totalPages): ?><a class="btn btn--ghost btn--sm" href="<?= e(query_url(['page' => $page + 1])) ?>#roles"><?= e(t('pager_next')) ?> →</a><?php endif; ?>
    </nav>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
