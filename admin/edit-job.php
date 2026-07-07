<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$categories = db()->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$allowedTypes = ['Full-time','Part-time','Contract','Internship','Remote','Temporary'];
$allowedStatus = ['pending','approved','rejected'];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = false;
$job = [
    'title'=>'','company_name'=>'','company_email'=>'','company_website'=>'',
    'location'=>'','job_type'=>'Full-time','category_id'=>'','salary_min'=>'','salary_max'=>'',
    'salary_currency'=>'USD','description'=>'','requirements'=>'','how_to_apply'=>'','apply_url'=>'',
    'status'=>'pending','is_featured'=>0,'expires_at'=>'',
    'title_ar'=>'','location_ar'=>'','description_ar'=>'','requirements_ar'=>'','how_to_apply_ar'=>'',
    'image_path'=>'','thumbnail_path'=>'',
];

if ($id) {
    $stmt = db()->prepare("SELECT * FROM jobs WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) { $job = $found; $isEdit = true; }
    else { flash_set('error', 'That posting no longer exists.'); redirect('admin/dashboard.php'); }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    foreach (['title','company_name','company_email','company_website','location',
              'job_type','category_id','salary_min','salary_max','salary_currency',
              'description','requirements','how_to_apply','apply_url','status',
              'title_ar','location_ar','description_ar','requirements_ar','how_to_apply_ar'] as $k) {
        $job[$k] = input($_POST, $k);
    }
    $job['salary_currency'] = strtoupper($job['salary_currency'] ?: 'USD');
    $job['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;

    // Validation (mirrors the public form, plus status).
    if (mb_strlen($job['title']) < 3) $errors[] = 'Job title is too short.';
    if (mb_strlen($job['company_name']) < 2) $errors[] = 'Company name is required.';
    if (!filter_var($job['company_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid company email is required.';
    if ($job['company_website'] !== '' && !filter_var($job['company_website'], FILTER_VALIDATE_URL)) $errors[] = 'Company website must be a valid URL.';
    if (mb_strlen($job['location']) < 2) $errors[] = 'Location is required.';
    if (!in_array($job['job_type'], $allowedTypes, true)) $errors[] = 'Invalid job type.';
    if (!in_array($job['status'], $allowedStatus, true)) $errors[] = 'Invalid status.';

    $job['expires_at'] = input($_POST, 'expires_at');
    $expiresAt = null;
    if ($job['expires_at'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $job['expires_at']);
        if (!$d || $d->format('Y-m-d') !== $job['expires_at']) {
            $errors[] = 'Expiry date is invalid.';
        } else {
            $expiresAt = $job['expires_at'];
        }
    }

    $catId = $job['category_id'] !== '' ? (int)$job['category_id'] : null;
    if ($catId !== null) {
        $ok = false; foreach ($categories as $c) if ((int)$c['id']===$catId) $ok = true;
        if (!$ok) $errors[] = 'Invalid category.';
    }
    $salaryMin = $job['salary_min'] !== '' ? (int)$job['salary_min'] : null;
    $salaryMax = $job['salary_max'] !== '' ? (int)$job['salary_max'] : null;
    if ($salaryMin !== null && $salaryMax !== null && $salaryMin > $salaryMax) $errors[] = 'Min salary exceeds max.';
    if (!preg_match('/^[A-Z]{3}$/', $job['salary_currency'])) $job['salary_currency'] = 'USD';
    if (mb_strlen($job['description']) < 20) $errors[] = 'Description is too short.';
    if (mb_strlen($job['how_to_apply']) < 5) $errors[] = 'Please add how to apply.';
    if ($job['apply_url'] !== '' && !filter_var($job['apply_url'], FILTER_VALIDATE_URL)) $errors[] = 'Application link must be a valid URL.';

    if (empty($errors)) {
        $up = save_uploaded_image('image');
        if ($up['error']) $errors[] = $up['error'];
    }

    if (empty($errors)) {
        // Resolve final image: new upload replaces, checkbox removes, else keep.
        $currentImage = $isEdit ? ($job['image_path'] ?: null) : null;
        $currentThumb = $isEdit ? ($job['thumbnail_path'] ?: null) : null;
        $finalImage = $currentImage;
        $finalThumb = $currentThumb;
        if (!empty($up['path'])) {
            delete_uploaded_image($currentImage);
            delete_uploaded_image($currentThumb);
            $finalImage = $up['path'];
            $finalThumb = $up['thumb_path'];
        } elseif (!empty($_POST['remove_image'])) {
            delete_uploaded_image($currentImage);
            delete_uploaded_image($currentThumb);
            $finalImage = null;
            $finalThumb = null;
        }

        $approvedAt = $job['status'] === 'approved' ? date('Y-m-d H:i:s') : null;
        $approvedBy = $job['status'] === 'approved' ? current_admin_id() : null;

        if ($isEdit) {
            $stmt = db()->prepare(
                "UPDATE jobs SET title=?, title_ar=?, company_name=?, company_email=?, company_website=?,
                 location=?, location_ar=?, job_type=?, category_id=?, salary_min=?, salary_max=?, salary_currency=?,
                 description=?, description_ar=?, requirements=?, requirements_ar=?,
                 how_to_apply=?, how_to_apply_ar=?, apply_url=?, image_path=?, thumbnail_path=?, status=?, is_featured=?, expires_at=?,
                 approved_at=COALESCE(?, approved_at), approved_by=COALESCE(?, approved_by)
                 WHERE id=?"
            );
            $stmt->execute([
                $job['title'],$job['title_ar']?:null,$job['company_name'],$job['company_email'],$job['company_website']?:null,
                $job['location'],$job['location_ar']?:null,$job['job_type'],$catId,$salaryMin,$salaryMax,$job['salary_currency'],
                $job['description'],$job['description_ar']?:null,$job['requirements']?:null,$job['requirements_ar']?:null,
                $job['how_to_apply'],$job['how_to_apply_ar']?:null,$job['apply_url']?:null,$finalImage,$finalThumb,
                $job['status'],$job['is_featured'],$expiresAt,$approvedAt,$approvedBy,$id,
            ]);
            log_activity($id, 'edit', $job['title'] . ' — ' . $job['company_name']);
            flash_set('success', 'Posting updated.');
        } else {
            $slug = slugify($job['title']) . '-' . substr(bin2hex(random_bytes(3)),0,5);
            $stmt = db()->prepare(
                "INSERT INTO jobs
                 (title,title_ar,slug,company_name,company_email,company_website,location,location_ar,job_type,category_id,
                  salary_min,salary_max,salary_currency,description,description_ar,requirements,requirements_ar,
                  how_to_apply,how_to_apply_ar,apply_url,image_path,thumbnail_path,status,is_featured,expires_at,approved_at,approved_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $job['title'],$job['title_ar']?:null,$slug,$job['company_name'],$job['company_email'],$job['company_website']?:null,
                $job['location'],$job['location_ar']?:null,$job['job_type'],$catId,$salaryMin,$salaryMax,$job['salary_currency'],
                $job['description'],$job['description_ar']?:null,$job['requirements']?:null,$job['requirements_ar']?:null,
                $job['how_to_apply'],$job['how_to_apply_ar']?:null,$job['apply_url']?:null,$finalImage,$finalThumb,
                $job['status'],$job['is_featured'],$expiresAt,$approvedAt,$approvedBy,
            ]);
            log_activity((int) db()->lastInsertId(), 'create', $job['title'] . ' — ' . $job['company_name']);
            flash_set('success', 'Posting created.');
        }
        redirect('admin/dashboard.php?tab=' . $job['status']);
    }
}

$admin_title = $isEdit ? 'Edit posting' : 'New posting';
require __DIR__ . '/includes/admin-header.php';
$v = fn($k) => e((string)($job[$k] ?? ''));
?>

<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Edit posting' : 'New posting' ?></h1>
    <p><?= $isEdit ? 'Update details, then save. Set the status to control visibility.' : 'Create a posting directly. Set status to Published to make it live.' ?></p>
  </div>
  <a href="<?= url('admin/dashboard.php') ?>" class="btn btn--ghost">← Back</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert--error">
    <strong>Please fix:</strong>
    <ul style="margin:0.3rem 0 0 1rem;padding:0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <div class="edit-card">

    <div class="edit-section-title">Role</div>
    <div class="field">
      <label>Job title <span class="req">*</span></label>
      <input type="text" name="title" value="<?= $v('title') ?>" maxlength="150" required>
    </div>
    <div class="field-row">
      <div class="field">
        <label>Job type <span class="req">*</span></label>
        <select name="job_type">
          <?php foreach ($allowedTypes as $t): ?>
            <option value="<?= $t ?>" <?= $job['job_type']===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Category</label>
        <select name="category_id">
          <option value="">— None —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (string)$job['category_id']===(string)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="edit-section-title">Company</div>
    <div class="field-row">
      <div class="field">
        <label>Company name <span class="req">*</span></label>
        <input type="text" name="company_name" value="<?= $v('company_name') ?>" maxlength="150" required>
      </div>
      <div class="field">
        <label>Company email <span class="req">*</span></label>
        <input type="email" name="company_email" value="<?= $v('company_email') ?>" maxlength="150" required>
      </div>
    </div>
    <div class="field-row">
      <div class="field">
        <label>Company website</label>
        <input type="url" name="company_website" value="<?= $v('company_website') ?>" placeholder="https://">
      </div>
      <div class="field">
        <label>Location <span class="req">*</span></label>
        <input type="text" name="location" value="<?= $v('location') ?>" maxlength="150" required>
      </div>
    </div>

    <div class="edit-section-title">Compensation</div>
    <div class="field-row">
      <div class="field">
        <label>Salary range</label>
        <div style="display:flex;gap:0.6rem">
          <input type="number" name="salary_min" value="<?= $v('salary_min') ?>" min="0" placeholder="Min">
          <input type="number" name="salary_max" value="<?= $v('salary_max') ?>" min="0" placeholder="Max">
        </div>
      </div>
      <div class="field">
        <label>Currency</label>
        <input type="text" name="salary_currency" value="<?= $v('salary_currency') ?>" maxlength="3" style="text-transform:uppercase">
      </div>
    </div>

    <div class="edit-section-title">Details</div>
    <div class="field">
      <label>Description <span class="req">*</span></label>
      <textarea name="description" required><?= $v('description') ?></textarea>
    </div>
    <div class="field">
      <label>Requirements</label>
      <textarea name="requirements"><?= $v('requirements') ?></textarea>
    </div>
    <div class="field">
      <label>How to apply <span class="req">*</span></label>
      <textarea name="how_to_apply" required><?= $v('how_to_apply') ?></textarea>
    </div>
    <div class="field">
      <label>Application link</label>
      <input type="url" name="apply_url" value="<?= $v('apply_url') ?>" placeholder="https://">
    </div>

    <div class="field">
      <label>Company logo / image <span class="hint">(JPG, PNG, WEBP or GIF, up to 2 MB)</span></label>
      <?php if (!empty($job['image_path'])): ?>
        <div class="img-review">
          <img src="<?= url($job['thumbnail_path'] ?: $job['image_path']) ?>" alt="Current image" class="img-review__thumb">
          <div>
            <p style="font-size:0.85rem;color:var(--ink-soft);margin:0 0 0.4rem"><?= e(t('f_image_current')) ?></p>
            <label style="font-weight:400;font-size:0.88rem;display:flex;gap:0.4rem;align-items:center;margin:0">
              <input type="checkbox" name="remove_image" value="1" style="width:auto"> <?= e(t('f_image_remove')) ?>
            </label>
          </div>
        </div>
        <p class="hint" style="margin-top:0.5rem"><?= e(t('f_image_replace')) ?></p>
      <?php endif; ?>
      <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" class="file-input" style="margin-top:0.5rem">
    </div>

    <div class="edit-section-title">Arabic content <span style="color:var(--ink-faint);font-weight:400;text-transform:none;letter-spacing:0">— optional; shown to visitors browsing in Arabic</span></div>
    <div class="field" dir="rtl">
      <label style="text-align:right">المسمّى الوظيفي (عربي)</label>
      <input type="text" name="title_ar" value="<?= $v('title_ar') ?>" maxlength="150" dir="rtl">
    </div>
    <div class="field" dir="rtl">
      <label style="text-align:right">الموقع (عربي)</label>
      <input type="text" name="location_ar" value="<?= $v('location_ar') ?>" maxlength="150" dir="rtl">
    </div>
    <div class="field" dir="rtl">
      <label style="text-align:right">وصف الوظيفة (عربي)</label>
      <textarea name="description_ar" dir="rtl"><?= $v('description_ar') ?></textarea>
    </div>
    <div class="field" dir="rtl">
      <label style="text-align:right">المتطلبات (عربي)</label>
      <textarea name="requirements_ar" dir="rtl"><?= $v('requirements_ar') ?></textarea>
    </div>
    <div class="field" dir="rtl">
      <label style="text-align:right">طريقة التقديم (عربي)</label>
      <textarea name="how_to_apply_ar" dir="rtl"><?= $v('how_to_apply_ar') ?></textarea>
    </div>

    <div class="edit-section-title">Publishing</div>
    <div class="field-row">
      <div class="field">
        <label>Status</label>
        <select name="status">
          <option value="pending"  <?= $job['status']==='pending'?'selected':'' ?>>Pending (hidden)</option>
          <option value="approved" <?= $job['status']==='approved'?'selected':'' ?>>Published (live)</option>
          <option value="rejected" <?= $job['status']==='rejected'?'selected':'' ?>>Rejected (hidden)</option>
        </select>
      </div>
      <div class="field" style="display:flex;align-items:center;gap:0.6rem;margin-top:1.9rem">
        <input type="checkbox" name="is_featured" id="is_featured" value="1" <?= $job['is_featured']?'checked':'' ?> style="width:auto">
        <label for="is_featured" style="margin:0">Feature this role on the board</label>
      </div>
      <div class="field">
        <label>Expires on <span class="hint">(optional — leave blank to never expire)</span></label>
        <input type="date" name="expires_at" value="<?= $v('expires_at') ?>">
      </div>
    </div>

    <div class="form-actions">
      <a href="<?= url('admin/dashboard.php') ?>" class="btn btn--ghost">Cancel</a>
      <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Save changes' : 'Create posting' ?></button>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
