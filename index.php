<?php
require_once __DIR__ . '/config/config.php';
require_active_tenant(); // platform root -> landing; unknown/inactive subdomain -> themed 404

$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));

$allowedSorts = ['newest', 'salary', 'alpha'];
$sort = in_array($_GET['sort'] ?? '', $allowedSorts, true) ? $_GET['sort'] : 'newest';
$orderMap = [
    'newest' => 'j.is_featured DESC, j.created_at DESC',
    'salary' => 'GREATEST(COALESCE(j.salary_max,0), COALESCE(j.salary_min,0)) DESC, j.created_at DESC',
    'alpha'  => 'j.title ASC',
];

$categorySlug = $_GET['category'] ?? 'all';
$q = trim((string)($_GET['q'] ?? ''));

$tid = current_tenant_id();
$expiryClause = "(j.expires_at IS NULL OR j.expires_at >= CURDATE())";
$where = "j.tenant_id = ? AND j.status = 'approved' AND $expiryClause";
$params = [$tid];
if ($categorySlug !== 'all' && $categorySlug !== '') {
    $where .= " AND c.slug = ?";
    $params[] = $categorySlug;
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

$catStmt = db()->prepare(
    "SELECT DISTINCT c.name, c.name_ar, c.slug
     FROM categories c
     JOIN jobs j ON j.category_id = c.id AND j.tenant_id = ? AND j.status = 'approved' AND $expiryClause
     ORDER BY c.name"
);
$catStmt->execute([$tid]);
$categories = $catStmt->fetchAll();

$sJobs = db()->prepare("SELECT COUNT(*) FROM jobs j WHERE j.tenant_id = ? AND j.status='approved' AND $expiryClause");
$sJobs->execute([$tid]);
$totalJobs = (int) $sJobs->fetchColumn();
$sCompanies = db()->prepare("SELECT COUNT(DISTINCT j.company_name) FROM jobs j WHERE j.tenant_id = ? AND j.status='approved' AND $expiryClause");
$sCompanies->execute([$tid]);
$totalCompanies = (int) $sCompanies->fetchColumn();

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
    <form method="get" action="<?= url('index.php') ?>#roles" class="board__search">
      <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
      <input type="hidden" name="sort" value="<?= e($sort) ?>">
      <input type="search" name="q" class="chip" style="min-width:220px" value="<?= e($q) ?>" placeholder="<?= e(t('filter_ph')) ?>" aria-label="<?= e(t('filter_ph')) ?>">
      <button type="submit" class="btn btn--ghost btn--sm"><?= e(t('search_btn')) ?></button>
    </form>
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

    <form method="get" action="<?= url('index.php') ?>#roles" class="sort-form">
      <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
      <input type="hidden" name="q" value="<?= e($q) ?>">
      <label for="sort-select"><?= e(t('sort_label')) ?></label>
      <div class="sort-select">
        <select name="sort" id="sort-select">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= e(t('sort_newest')) ?></option>
          <option value="salary" <?= $sort === 'salary' ? 'selected' : '' ?>><?= e(t('sort_salary')) ?></option>
          <option value="alpha"  <?= $sort === 'alpha'  ? 'selected' : '' ?>><?= e(t('sort_alpha')) ?></option>
        </select>
      </div>
      <button type="submit" class="btn btn--ghost btn--sm sort-form__apply"><?= e(t('sort_apply')) ?></button>
    </form>
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
