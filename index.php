<?php
require_once __DIR__ . '/config/config.php';

$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));

$allowedSorts = ['newest', 'oldest', 'salary', 'salary_low', 'alpha'];
$sort = in_array($_GET['sort'] ?? '', $allowedSorts, true) ? $_GET['sort'] : 'newest';
$orderMap = [
    'newest'     => 'j.is_featured DESC, j.created_at DESC',
    'oldest'     => 'j.created_at ASC',
    'salary'     => '(j.salary_min IS NULL AND j.salary_max IS NULL) ASC, GREATEST(COALESCE(j.salary_max,0), COALESCE(j.salary_min,0)) DESC',
    'salary_low' => '(j.salary_min IS NULL AND j.salary_max IS NULL) ASC, COALESCE(j.salary_min, j.salary_max) ASC',
    'alpha'      => 'j.title ASC',
];

$allowedTypes = ['Full-time','Part-time','Contract','Internship','Remote','Temporary'];
$jobType = in_array($_GET['type'] ?? '', $allowedTypes, true) ? $_GET['type'] : 'all';

$categorySlug = $_GET['category'] ?? 'all';
$q = trim((string)($_GET['q'] ?? ''));
$filtersActive = ($categorySlug !== 'all' && $categorySlug !== '') || $jobType !== 'all' || $q !== '' || $sort !== 'newest';

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
    "SELECT DISTINCT c.name, c.name_ar, c.slug
     FROM categories c
     JOIN jobs j ON j.category_id = c.id AND j.status = 'approved' AND $expiryClause
     ORDER BY c.name"
)->fetchAll();

$totalJobs = (int) db()->query("SELECT COUNT(*) FROM jobs j WHERE j.status='approved' AND $expiryClause")->fetchColumn();
$totalCompanies = (int) db()->query("SELECT COUNT(DISTINCT j.company_name) FROM jobs j WHERE j.status='approved' AND $expiryClause")->fetchColumn();

$page_title = t('board_title');
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="orbs orbs--hero" aria-hidden="true"><span></span><span></span><span></span></div>
  <div class="wrap hero__inner">
    <span class="eyebrow"><?= e(t('hero_eyebrow')) ?></span>
    <h1><?= th('hero_title') ?></h1>
    <p class="hero__lede"><?= e(t('hero_lede', APP_NAME)) ?></p>

    <form class="searchbar" method="get" action="<?= url('index.php') ?>#roles" role="search">
      <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
      <input type="hidden" name="type" value="<?= e($jobType) ?>">
      <input type="hidden" name="sort" value="<?= e($sort) ?>">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('search_ph')) ?>" aria-label="<?= e(t('search_btn')) ?>">
      <button type="submit" class="btn btn--honey"><?= e(t('search_btn')) ?></button>
    </form>

    <div class="hero__stats">
      <div class="hero__stat"><b><?= $totalJobs ?></b><span><?= e(t('stat_roles')) ?></span></div>
      <div class="hero__stat"><b><?= $totalCompanies ?></b><span><?= e(t('stat_companies')) ?></span></div>
      <div class="hero__stat"><b>100%</b><span><?= e(t('stat_reviewed')) ?></span></div>
    </div>
  </div>
</section>

<section class="board wrap" id="roles">
  <div class="orbs orbs--board" aria-hidden="true"><span></span><span></span></div>
  <div class="board__head">
    <div>
      <span class="eyebrow"><?= e(t('board_eyebrow')) ?></span>
      <h2><?= e(t('board_title')) ?></h2>
    </div>
    <span style="font-family:var(--font-mono);font-size:0.8rem;color:var(--ink-faint)"><?= e(t('results_count', $totalJobsFiltered)) ?></span>
  </div>

  <div class="board__toolbar">
    <div class="chips" role="group">
      <a class="chip <?= $categorySlug === 'all' ? 'is-active' : '' ?>" aria-pressed="<?= $categorySlug === 'all' ? 'true' : 'false' ?>"
         href="<?= e(query_url(['category' => null, 'page' => null])) ?>#roles"><?= e(t('filter_all')) ?></a>
      <?php foreach ($categories as $cat): ?>
        <a class="chip <?= $categorySlug === $cat['slug'] ? 'is-active' : '' ?>" aria-pressed="<?= $categorySlug === $cat['slug'] ? 'true' : 'false' ?>"
           href="<?= e(query_url(['category' => $cat['slug'], 'page' => null])) ?>#roles"><?= e(cat_name($cat['name'], $cat['name_ar'])) ?></a>
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
      <label for="sort-select"><?= e(t('sort_label')) ?></label>
      <div class="sort-select">
        <select name="sort" id="sort-select">
          <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>><?= e(t('sort_newest')) ?></option>
          <option value="oldest"     <?= $sort === 'oldest'     ? 'selected' : '' ?>><?= e(t('sort_oldest')) ?></option>
          <option value="salary"     <?= $sort === 'salary'     ? 'selected' : '' ?>><?= e(t('sort_salary_high')) ?></option>
          <option value="salary_low" <?= $sort === 'salary_low' ? 'selected' : '' ?>><?= e(t('sort_salary_low')) ?></option>
          <option value="alpha"      <?= $sort === 'alpha'      ? 'selected' : '' ?>><?= e(t('sort_alpha')) ?></option>
        </select>
      </div>
      <button type="submit" class="btn btn--ghost btn--sm sort-form__apply"><?= e(t('sort_apply')) ?></button>
    </form>
  </div>

  <?php if ($filtersActive): ?>
    <p style="margin:0 0 1.25rem"><a class="chip" href="<?= url('index.php') ?>#roles"><?= e(t('clear_filters')) ?> ✕</a></p>
  <?php endif; ?>

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
