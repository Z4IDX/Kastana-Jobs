<?php
require_once __DIR__ . '/config/config.php';

$errors = [];
$old = [];

$categories = db()->query("SELECT id, name, name_ar FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (input($_POST, 'website_url') !== '') {          // honeypot
        flash_set('success', t('f_ok'));
        redirect('submit.php');
    }

    $old = [
        'title'           => input($_POST, 'title'),
        'company_name'    => input($_POST, 'company_name'),
        'company_email'   => input($_POST, 'company_email'),
        'company_website' => input($_POST, 'company_website'),
        'location'        => input($_POST, 'location'),
        'job_type'        => input($_POST, 'job_type'),
        'category_id'     => input($_POST, 'category_id'),
        'salary_min'      => input($_POST, 'salary_min'),
        'salary_max'      => input($_POST, 'salary_max'),
        'salary_currency' => strtoupper(input($_POST, 'salary_currency', 'USD')),
        'description'     => input($_POST, 'description'),
        'requirements'    => input($_POST, 'requirements'),
        'how_to_apply'    => input($_POST, 'how_to_apply'),
        'apply_url'       => input($_POST, 'apply_url'),
        'title_ar'        => input($_POST, 'title_ar'),
        'location_ar'     => input($_POST, 'location_ar'),
        'description_ar'  => input($_POST, 'description_ar'),
        'requirements_ar' => input($_POST, 'requirements_ar'),
        'how_to_apply_ar' => input($_POST, 'how_to_apply_ar'),
    ];

    $allowedTypes = ['Full-time','Part-time','Contract','Internship','Remote','Temporary'];

    if (mb_strlen($old['title']) < 3 || mb_strlen($old['title']) > 150) $errors[] = t('err_title');
    if (mb_strlen($old['company_name']) < 2) $errors[] = t('err_company');
    if (!filter_var($old['company_email'], FILTER_VALIDATE_EMAIL)) $errors[] = t('err_email');
    if ($old['company_website'] !== '' && !filter_var($old['company_website'], FILTER_VALIDATE_URL)) $errors[] = t('err_website');
    if (mb_strlen($old['location']) < 2) $errors[] = t('err_location');
    if (!in_array($old['job_type'], $allowedTypes, true)) $errors[] = t('err_type');

    $catId = null;
    if ($old['category_id'] !== '') {
        $catId = (int) $old['category_id']; $valid = false;
        foreach ($categories as $c) if ((int)$c['id'] === $catId) $valid = true;
        if (!$valid) $errors[] = t('err_category');
    }
    $salaryMin = $old['salary_min'] !== '' ? (int) $old['salary_min'] : null;
    $salaryMax = $old['salary_max'] !== '' ? (int) $old['salary_max'] : null;
    if ($salaryMin !== null && $salaryMax !== null && $salaryMin > $salaryMax) $errors[] = t('err_salary');
    if (!preg_match('/^[A-Z]{3}$/', $old['salary_currency'])) $old['salary_currency'] = 'USD';
    if (mb_strlen($old['description']) < 40) $errors[] = t('err_desc');
    if (mb_strlen($old['how_to_apply']) < 10) $errors[] = t('err_apply');
    if ($old['apply_url'] !== '' && !filter_var($old['apply_url'], FILTER_VALIDATE_URL)) $errors[] = t('err_applyurl');

    if (empty($errors)) {
        $up = save_uploaded_image('image');
        if ($up['error']) {
            $errors[] = $up['error'];
        } else {
            $slug = slugify($old['title']) . '-' . substr(bin2hex(random_bytes(3)), 0, 5);
            $stmt = db()->prepare(
                "INSERT INTO jobs
                 (title, title_ar, slug, company_name, company_email, company_website, location, location_ar, job_type,
                  category_id, salary_min, salary_max, salary_currency, description, description_ar, requirements, requirements_ar,
                  how_to_apply, how_to_apply_ar, apply_url, image_path, thumbnail_path, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')"
            );
            $stmt->execute([
                $old['title'], $old['title_ar'] ?: null, $slug, $old['company_name'], $old['company_email'],
                $old['company_website'] ?: null, $old['location'], $old['location_ar'] ?: null, $old['job_type'],
                $catId, $salaryMin, $salaryMax, $old['salary_currency'],
                $old['description'], $old['description_ar'] ?: null, $old['requirements'] ?: null, $old['requirements_ar'] ?: null,
                $old['how_to_apply'], $old['how_to_apply_ar'] ?: null, $old['apply_url'] ?: null, $up['path'], $up['thumb_path'],
            ]);
            flash_set('success', t('f_ok'));
            redirect('submit.php');
        }
    }
}

$page_title = t('f_post_title');
require __DIR__ . '/includes/header.php';
$val = fn($k) => e($old[$k] ?? '');
?>

<section class="form-page wrap">
  <div class="form-shell">
    <div class="form-intro">
      <span class="eyebrow" style="justify-content:center"><?= e(t('f_intro_eyebrow')) ?></span>
      <h1><?= e(t('f_post_title')) ?></h1>
      <p><?= e(t('f_post_lede')) ?></p>
    </div>

    <?php foreach (flash_get() as $f): ?>
      <div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert--error">
        <div><strong><?= e(t('f_fix')) ?></strong>
          <ul class="error-list"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <form method="post" action="<?= url('submit.php') ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <div class="hp-field" aria-hidden="true">
          <label>Leave empty<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="field">
          <label><?= e(t('f_title')) ?> <span class="req">*</span></label>
          <input type="text" name="title" value="<?= $val('title') ?>" maxlength="150" required>
        </div>

        <div class="field-row">
          <div class="field">
            <label><?= e(t('f_company')) ?> <span class="req">*</span></label>
            <input type="text" name="company_name" value="<?= $val('company_name') ?>" maxlength="150" required>
          </div>
          <div class="field">
            <label><?= e(t('f_email')) ?> <span class="req">*</span> <span class="hint"><?= e(t('f_email_hint')) ?></span></label>
            <input type="email" name="company_email" value="<?= $val('company_email') ?>" maxlength="150" required>
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label><?= e(t('f_website')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label>
            <input type="url" name="company_website" value="<?= $val('company_website') ?>" placeholder="https://">
          </div>
          <div class="field">
            <label><?= e(t('f_location')) ?> <span class="req">*</span></label>
            <input type="text" name="location" value="<?= $val('location') ?>" maxlength="150" required>
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label><?= e(t('f_type')) ?> <span class="req">*</span></label>
            <select name="job_type" required>
              <?php foreach (['Full-time','Part-time','Contract','Internship','Remote','Temporary'] as $tp): ?>
                <option value="<?= $tp ?>" <?= ($old['job_type'] ?? '') === $tp ? 'selected' : '' ?>><?= e(job_type_label($tp)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label><?= e(t('f_category')) ?></label>
            <select name="category_id">
              <option value=""><?= e(t('f_select')) ?></option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (string)($old['category_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e(cat_name($c['name'], $c['name_ar'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label><?= e(t('f_salary')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label>
            <div style="display:flex;gap:0.6rem">
              <input type="number" name="salary_min" value="<?= $val('salary_min') ?>" min="0" placeholder="<?= e(t('f_min')) ?>">
              <input type="number" name="salary_max" value="<?= $val('salary_max') ?>" min="0" placeholder="<?= e(t('f_max')) ?>">
            </div>
          </div>
          <div class="field">
            <label><?= e(t('f_currency')) ?></label>
            <input type="text" name="salary_currency" value="<?= e($old['salary_currency'] ?? 'USD') ?>" maxlength="3" placeholder="USD" style="text-transform:uppercase">
          </div>
        </div>

        <div class="field">
          <label><?= e(t('f_desc')) ?> <span class="req">*</span></label>
          <textarea name="description" required placeholder="<?= e(t('f_desc_ph')) ?>"><?= $val('description') ?></textarea>
        </div>
        <div class="field">
          <label><?= e(t('f_req')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label>
          <textarea name="requirements" placeholder="<?= e(t('f_req_ph')) ?>"><?= $val('requirements') ?></textarea>
        </div>
        <div class="field">
          <label><?= e(t('f_apply')) ?> <span class="req">*</span></label>
          <textarea name="how_to_apply" required placeholder="<?= e(t('f_apply_ph')) ?>"><?= $val('how_to_apply') ?></textarea>
        </div>
        <div class="field">
          <label><?= e(t('f_applyurl')) ?> <span class="hint"><?= e(t('f_applyurl_hint')) ?></span></label>
          <input type="url" name="apply_url" value="<?= $val('apply_url') ?>" placeholder="https://">
        </div>

        <span class="eyebrow" style="margin:1.5rem 0 0.5rem"><?= e(t('f_ar_section')) ?></span>
        <div class="field" dir="rtl">
          <label style="text-align:right"><?= e(t('f_title_ar')) ?></label>
          <input type="text" name="title_ar" value="<?= $val('title_ar') ?>" maxlength="150" dir="rtl">
        </div>
        <div class="field" dir="rtl">
          <label style="text-align:right"><?= e(t('f_location_ar')) ?></label>
          <input type="text" name="location_ar" value="<?= $val('location_ar') ?>" maxlength="150" dir="rtl">
        </div>
        <div class="field" dir="rtl">
          <label style="text-align:right"><?= e(t('f_description_ar')) ?></label>
          <textarea name="description_ar" dir="rtl"><?= $val('description_ar') ?></textarea>
        </div>
        <div class="field" dir="rtl">
          <label style="text-align:right"><?= e(t('f_requirements_ar')) ?></label>
          <textarea name="requirements_ar" dir="rtl"><?= $val('requirements_ar') ?></textarea>
        </div>
        <div class="field" dir="rtl">
          <label style="text-align:right"><?= e(t('f_how_to_apply_ar')) ?></label>
          <textarea name="how_to_apply_ar" dir="rtl"><?= $val('how_to_apply_ar') ?></textarea>
        </div>

        <div class="field">
          <label><?= e(t('f_image')) ?> <span class="hint"><?= e(t('f_image_hint')) ?></span></label>
          <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" class="file-input">
        </div>

        <button type="submit" class="btn btn--primary btn--block" style="margin-top:0.5rem"><?= e(t('f_submit')) ?> <span class="dir-arrow">→</span></button>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
