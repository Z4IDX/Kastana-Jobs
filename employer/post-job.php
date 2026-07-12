<?php
require_once __DIR__ . '/../config/config.php';
require_employer();

$empId = current_employer_id();
$categories = db()->query("SELECT id, name, name_ar FROM categories ORDER BY name")->fetchAll();
$allowedTypes = ['Full-time','Part-time','Contract','Internship','Remote','Temporary'];

// Prefill company fields from the employer's own profile on a new post.
$profile = db()->prepare("SELECT company_name, email, phone, website FROM employers WHERE id = ?");
$profile->execute([$empId]);
$me = $profile->fetch() ?: ['company_name' => '', 'email' => '', 'phone' => '', 'website' => ''];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = false;
$job = [
    'title' => '', 'company_name' => $me['company_name'], 'company_email' => $me['email'],
    'company_phone' => $me['phone'], 'company_website' => $me['website'],
    'location' => '', 'job_type' => 'Full-time', 'category_id' => '', 'salary_min' => '', 'salary_max' => '',
    'salary_currency' => 'USD', 'description' => '', 'requirements' => '', 'how_to_apply' => '', 'apply_url' => '',
    'title_ar' => '', 'location_ar' => '', 'description_ar' => '', 'requirements_ar' => '', 'how_to_apply_ar' => '',
    'image_path' => '', 'thumbnail_path' => '',
];

if ($id) {
    $stmt = db()->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ? LIMIT 1");
    $stmt->execute([$id, $empId]);
    $found = $stmt->fetch();
    if ($found) { $job = $found; $isEdit = true; }
    else { flash_set('error', t('nf_title')); redirect('employer/dashboard.php'); }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    foreach (['title','company_name','company_email','company_phone','company_website','location',
              'job_type','category_id','salary_min','salary_max','salary_currency',
              'description','requirements','how_to_apply','apply_url',
              'title_ar','location_ar','description_ar','requirements_ar','how_to_apply_ar'] as $k) {
        $job[$k] = input($_POST, $k);
    }
    $job['salary_currency'] = strtoupper($job['salary_currency'] ?: 'USD');

    if (mb_strlen($job['title']) < 3 || mb_strlen($job['title']) > 150) $errors[] = t('err_title');
    if (mb_strlen($job['company_name']) < 2) $errors[] = t('err_company');
    if (!filter_var($job['company_email'], FILTER_VALIDATE_EMAIL)) $errors[] = t('err_email');
    if ($job['company_website'] !== '' && !filter_var($job['company_website'], FILTER_VALIDATE_URL)) $errors[] = t('err_website');
    if ($job['company_phone'] !== '' && !preg_match('/^[0-9+\-\s()]{6,40}$/', $job['company_phone'])) $errors[] = t('err_app_phone');
    if (mb_strlen($job['location']) < 2) $errors[] = t('err_location');
    if (!in_array($job['job_type'], $allowedTypes, true)) $errors[] = t('err_type');

    $catId = $job['category_id'] !== '' ? (int) $job['category_id'] : null;
    if ($catId !== null) {
        $ok = false; foreach ($categories as $c) if ((int) $c['id'] === $catId) $ok = true;
        if (!$ok) $errors[] = t('err_category');
    }
    $salaryMin = $job['salary_min'] !== '' ? (int) $job['salary_min'] : null;
    $salaryMax = $job['salary_max'] !== '' ? (int) $job['salary_max'] : null;
    if ($salaryMin !== null && $salaryMax !== null && $salaryMin > $salaryMax) $errors[] = t('err_salary');
    if (!preg_match('/^[A-Z]{3}$/', $job['salary_currency'])) $job['salary_currency'] = 'USD';
    if (mb_strlen($job['description']) < 40) $errors[] = t('err_desc');
    if (mb_strlen($job['how_to_apply']) < 10) $errors[] = t('err_apply');
    if ($job['apply_url'] !== '' && !filter_var($job['apply_url'], FILTER_VALIDATE_URL)) $errors[] = t('err_applyurl');

    if (empty($errors)) {
        $up = save_uploaded_image('image');
        if ($up['error']) $errors[] = $up['error'];
    }

    if (empty($errors)) {
        $currentImage = $isEdit ? ($job['image_path'] ?: null) : null;
        $currentThumb = $isEdit ? ($job['thumbnail_path'] ?: null) : null;
        $finalImage = $currentImage; $finalThumb = $currentThumb;
        if (!empty($up['path'])) {
            delete_uploaded_image($currentImage); delete_uploaded_image($currentThumb);
            $finalImage = $up['path']; $finalThumb = $up['thumb_path'];
        } elseif (!empty($_POST['remove_image'])) {
            delete_uploaded_image($currentImage); delete_uploaded_image($currentThumb);
            $finalImage = null; $finalThumb = null;
        }

        $vals = [
            $job['title'], $job['title_ar'] ?: null,
            $job['company_name'], $job['company_email'], $job['company_phone'] ?: null, $job['company_website'] ?: null,
            $job['location'], $job['location_ar'] ?: null, $job['job_type'], $catId,
            $salaryMin, $salaryMax, $job['salary_currency'],
            $job['description'], $job['description_ar'] ?: null, $job['requirements'] ?: null, $job['requirements_ar'] ?: null,
            $job['how_to_apply'], $job['how_to_apply_ar'] ?: null, $job['apply_url'] ?: null,
            $finalImage, $finalThumb,
        ];

        if ($isEdit) {
            // Any edit returns the posting to the review queue.
            db()->prepare(
                "UPDATE jobs SET title=?, title_ar=?, company_name=?, company_email=?, company_phone=?, company_website=?,
                 location=?, location_ar=?, job_type=?, category_id=?, salary_min=?, salary_max=?, salary_currency=?,
                 description=?, description_ar=?, requirements=?, requirements_ar=?, how_to_apply=?, how_to_apply_ar=?, apply_url=?,
                 image_path=?, thumbnail_path=?, status='pending', approved_at=NULL, approved_by=NULL
                 WHERE id=? AND employer_id=?"
            )->execute([...$vals, $id, $empId]);
        } else {
            $slug = slugify($job['title']) . '-' . substr(bin2hex(random_bytes(3)), 0, 5);
            db()->prepare(
                "INSERT INTO jobs
                 (employer_id, title, title_ar, slug, company_name, company_email, company_phone, company_website,
                  location, location_ar, job_type, category_id, salary_min, salary_max, salary_currency,
                  description, description_ar, requirements, requirements_ar, how_to_apply, how_to_apply_ar, apply_url,
                  image_path, thumbnail_path, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')"
            )->execute([
                $empId, $job['title'], $job['title_ar'] ?: null, $slug,
                $job['company_name'], $job['company_email'], $job['company_phone'] ?: null, $job['company_website'] ?: null,
                $job['location'], $job['location_ar'] ?: null, $job['job_type'], $catId,
                $salaryMin, $salaryMax, $job['salary_currency'],
                $job['description'], $job['description_ar'] ?: null, $job['requirements'] ?: null, $job['requirements_ar'] ?: null,
                $job['how_to_apply'], $job['how_to_apply_ar'] ?: null, $job['apply_url'] ?: null,
                $finalImage, $finalThumb,
            ]);
        }
        flash_set('success', t('emp_saved_ok'));
        redirect('employer/dashboard.php');
    }
}

$page_title = $isEdit ? t('emp_post_edit') : t('emp_post_new');
require __DIR__ . '/../includes/header.php';
$val = fn($k) => e((string) ($job[$k] ?? ''));
?>
<section class="form-page wrap">
  <div class="form-shell">
    <div class="form-intro">
      <span class="eyebrow" style="justify-content:center"><?= e(t('f_intro_eyebrow')) ?></span>
      <h1><?= e($isEdit ? t('emp_post_edit') : t('emp_post_new')) ?></h1>
      <p><?= e(t('emp_edit_note')) ?></p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert--error"><div><strong><?= e(t('f_fix')) ?></strong><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div></div>
    <?php endif; ?>

    <div class="form-card">
      <form method="post" action="<?= url('employer/post-job.php' . ($isEdit ? '?id=' . $id : '')) ?>" enctype="multipart/form-data" novalidate data-dirty-guard>
        <?= csrf_field() ?>

        <div class="field"><label><?= e(t('f_title')) ?> <span class="req">*</span></label><input type="text" name="title" value="<?= $val('title') ?>" maxlength="150" required></div>

        <div class="field-row">
          <div class="field"><label><?= e(t('f_company')) ?> <span class="req">*</span></label><input type="text" name="company_name" value="<?= $val('company_name') ?>" maxlength="150" required></div>
          <div class="field"><label><?= e(t('f_email')) ?> <span class="req">*</span></label><input type="email" name="company_email" value="<?= $val('company_email') ?>" maxlength="150" required dir="ltr"></div>
        </div>
        <div class="field-row">
          <div class="field"><label><?= e(t('f_phone')) ?> <span class="hint"><?= e(t('f_phone_hint')) ?></span></label><input type="tel" name="company_phone" value="<?= $val('company_phone') ?>" maxlength="40" dir="ltr"></div>
          <div class="field"><label><?= e(t('f_website')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label><input type="url" name="company_website" value="<?= $val('company_website') ?>" placeholder="https://" dir="ltr"></div>
        </div>

        <div class="field-row">
          <div class="field"><label><?= e(t('f_type')) ?> <span class="req">*</span></label>
            <select name="job_type" required><?php foreach ($allowedTypes as $tp): ?><option value="<?= $tp ?>" <?= $job['job_type'] === $tp ? 'selected' : '' ?>><?= e(job_type_label($tp)) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field"><label><?= e(t('f_category')) ?></label>
            <select name="category_id"><option value=""><?= e(t('f_select')) ?></option><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (string) $job['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e(cat_name($c['name'], $c['name_ar'])) ?></option><?php endforeach; ?></select>
          </div>
        </div>

        <div class="field"><label><?= e(t('f_location')) ?> <span class="req">*</span></label><input type="text" name="location" value="<?= $val('location') ?>" maxlength="150" required></div>

        <div class="field-row">
          <div class="field"><label><?= e(t('f_salary')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label>
            <div style="display:flex;gap:0.6rem"><input type="number" name="salary_min" value="<?= $val('salary_min') ?>" min="0" placeholder="<?= e(t('f_min')) ?>"><input type="number" name="salary_max" value="<?= $val('salary_max') ?>" min="0" placeholder="<?= e(t('f_max')) ?>"></div>
          </div>
          <div class="field"><label><?= e(t('f_currency')) ?></label><input type="text" name="salary_currency" value="<?= e($job['salary_currency'] ?? 'USD') ?>" maxlength="3" dir="ltr" style="text-transform:uppercase"></div>
        </div>

        <div class="field"><label><?= e(t('f_desc')) ?> <span class="req">*</span></label><textarea name="description" required data-min="40" placeholder="<?= e(t('f_desc_ph')) ?>"><?= $val('description') ?></textarea></div>
        <div class="field"><label><?= e(t('f_req')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label><textarea name="requirements" placeholder="<?= e(t('f_req_ph')) ?>"><?= $val('requirements') ?></textarea></div>
        <div class="field"><label><?= e(t('f_apply')) ?> <span class="req">*</span></label><textarea name="how_to_apply" required data-min="10" placeholder="<?= e(t('f_apply_ph')) ?>"><?= $val('how_to_apply') ?></textarea></div>
        <div class="field"><label><?= e(t('f_applyurl')) ?> <span class="hint"><?= e(t('f_applyurl_hint')) ?></span></label><input type="url" name="apply_url" value="<?= $val('apply_url') ?>" placeholder="https://" dir="ltr"></div>

        <span class="eyebrow" style="margin:1.5rem 0 0.5rem"><?= e(t('f_ar_section')) ?></span>
        <div class="field" dir="rtl"><label><?= e(t('f_title_ar')) ?></label><input type="text" name="title_ar" value="<?= $val('title_ar') ?>" maxlength="150" dir="rtl"></div>
        <div class="field" dir="rtl"><label><?= e(t('f_location_ar')) ?></label><input type="text" name="location_ar" value="<?= $val('location_ar') ?>" maxlength="150" dir="rtl"></div>
        <div class="field" dir="rtl"><label><?= e(t('f_description_ar')) ?></label><textarea name="description_ar" dir="rtl"><?= $val('description_ar') ?></textarea></div>
        <div class="field" dir="rtl"><label><?= e(t('f_requirements_ar')) ?></label><textarea name="requirements_ar" dir="rtl"><?= $val('requirements_ar') ?></textarea></div>
        <div class="field" dir="rtl"><label><?= e(t('f_how_to_apply_ar')) ?></label><textarea name="how_to_apply_ar" dir="rtl"><?= $val('how_to_apply_ar') ?></textarea></div>

        <div class="field">
          <label><?= e(t('f_image')) ?> <span class="hint"><?= e(t('f_image_hint')) ?></span></label>
          <?php if (!empty($job['image_path'])): ?>
            <div class="img-review" style="margin-bottom:0.5rem">
              <img src="<?= url($job['thumbnail_path'] ?: $job['image_path']) ?>" alt="" class="img-review__thumb">
              <label style="font-weight:400;font-size:0.88rem;display:flex;gap:0.4rem;align-items:center;margin:0"><input type="checkbox" name="remove_image" value="1" style="width:auto"> <?= e(t('f_image_remove')) ?></label>
            </div>
          <?php endif; ?>
          <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" class="file-input">
        </div>

        <div style="display:flex;gap:0.6rem;margin-top:0.5rem">
          <a href="<?= url('employer/dashboard.php') ?>" class="btn btn--ghost"><?= e(t('back_all')) ?></a>
          <button type="submit" class="btn btn--primary btn--block"><?= e(t('emp_save')) ?></button>
        </div>
      </form>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
