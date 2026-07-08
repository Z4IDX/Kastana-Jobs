<?php
/**
 * Kastana Jobs — Internationalisation (EN / AR)
 * UI strings + helpers for language, direction, and bilingual content.
 */

function translations(): array
{
    static $t = null;
    if ($t !== null) return $t;

    $t = [
        'en' => [
            'lang_name'        => 'English',
            'switch_to'        => 'العربية',
            'nav_browse'       => 'Browse roles',
            'nav_how'          => 'How it works',
            'nav_post'         => 'Post a job',

            'footer_browse'    => 'Browse',
            'footer_post'      => 'Post a job',
            'footer_admin'     => 'Admin',
            'footer_copy'      => 'Every role reviewed by hand.',

            'hero_eyebrow'     => 'Hand-reviewed roles',
            'hero_title'       => 'Work worth <em>chasing.</em><br>Curated, not cluttered.',
            'hero_lede'        => 'Every posting on %s is read and approved by a human before it goes live. No spam, no ghost listings — just real roles from companies worth your time.',
            'search_ph'        => 'Search by role, company, or location…',
            'search_btn'       => 'Search',
            'stat_roles'       => 'Open roles',
            'stat_companies'   => 'Companies',
            'stat_reviewed'    => 'Human reviewed',

            'board_eyebrow'    => 'The board',
            'board_title'      => 'Latest openings',
            'filter_ph'        => 'Filter listings…',
            'filter_all'       => 'All roles',
            'empty_title'      => 'No roles match your filter',
            'empty_body'       => 'Try a different category or clear your search.',
            'featured'         => 'Featured',
            'view_role'        => 'View role',
            'sort_label'       => 'Sort by',
            'sort_newest'      => 'Newest',
            'sort_salary'      => 'Salary',
            'sort_alpha'       => 'Title (A–Z)',
            'sort_apply'       => 'Apply',
            'pager_prev'       => 'Prev',
            'pager_next'       => 'Next',
            'pager_page'       => 'Page %d of %d',

            'cta_eyebrow'      => 'For companies',
            'cta_title'        => 'Hiring? Reach people who read the listing.',
            'cta_body'         => 'Submit a role in two minutes. Our team reviews it, polishes the details if needed, and publishes it to a focused audience.',
            'cta_btn'          => 'Post a job',

            'back_all'         => 'Back to all roles',
            'about_role'       => 'About the role',
            'looking_for'      => "What we're looking for",
            'how_apply'        => 'How to apply',
            'a_company'        => 'Company',
            'a_location'       => 'Location',
            'a_type'           => 'Type',
            'a_salary'         => 'Salary',
            'apply_now'        => 'Apply now',
            'apply_email'      => 'Apply by email',
            'mention'          => 'Mention you found this on %s.',
            'nf_title'         => 'This role is no longer available',
            'nf_body'          => 'It may have been filled or removed. Browse the current openings instead.',
            'posted'           => 'Posted',
            'copy_link'        => 'Copy link',
            'copy_link_done'   => 'Copied!',

            'apply_kastana_title' => 'Apply through %s',
            'apply_kastana_lede'  => 'Send your details directly to the company.',
            'f_app_name'       => 'Your name',
            'f_app_email'      => 'Your email',
            'f_app_phone'      => 'Phone',
            'f_app_note'       => 'Note to the employer',
            'f_app_submit'     => 'Send application',
            'apply_ok'         => 'Your application was sent. Good luck!',
            'err_app_name'     => 'Please enter your name.',
            'err_app_email'    => 'Please enter a valid email address.',
            'err_app_phone'    => 'Please enter a valid phone number.',

            'nav_saved'        => 'Saved',
            'saved_empty_title' => 'No saved roles yet',
            'saved_empty_body'  => 'Tap the star on any role to save it here for later.',
            'save_job'         => 'Save this role',
            'unsave_job'       => 'Remove from saved',

            'a_dashboard'      => 'Dashboard',
            'a_customize'      => 'Customize',
            'a_activity'       => 'Activity log',
            'a_companies'      => 'Companies',
            'a_categories'     => 'Categories',
            'a_account'        => 'Account',
            'a_signout'        => 'Sign out',
            'a_viewsite'       => 'View site',

            'f_intro_eyebrow'  => 'For companies',
            'f_post_title'     => 'Post a role',
            'f_post_lede'      => 'Fill in the details below. A human reviews every submission — once approved, your role goes live on the board. No account required.',
            'f_title'          => 'Job title',
            'f_company'        => 'Company name',
            'f_email'          => 'Company email',
            'f_email_hint'     => '(not shown publicly unless used for applications)',
            'f_website'        => 'Company website',
            'f_optional'       => '(optional)',
            'f_location'       => 'Location',
            'f_type'           => 'Job type',
            'f_category'       => 'Category',
            'f_select'         => '— Select —',
            'f_salary'         => 'Salary range',
            'f_currency'       => 'Currency',
            'f_min'            => 'Min',
            'f_max'            => 'Max',
            'f_desc'           => 'Job description',
            'f_desc_ph'        => 'What will this person do? What does a typical week look like?',
            'f_req'            => 'Requirements',
            'f_req_ph'         => 'Skills, experience, and nice-to-haves.',
            'f_apply'          => 'How to apply',
            'f_apply_ph'       => 'Tell candidates exactly what to do.',
            'f_applyurl'       => 'Application link',
            'f_applyurl_hint'  => '(optional — otherwise candidates email you)',
            'f_submit'         => 'Submit for review',
            'f_fix'            => 'Please fix the following:',
            'f_ok'             => 'Thanks! Your posting was received and is now waiting for review. We\'ll publish it once approved.',
            'err_title'     => 'Job title must be between 3 and 150 characters.',
            'err_company'   => 'Please enter your company name.',
            'err_email'     => 'Please enter a valid company email address.',
            'err_website'   => 'The company website must be a valid URL (including https://).',
            'err_location'  => 'Please enter a location (or "Remote").',
            'err_type'      => 'Please choose a valid job type.',
            'err_category'  => 'Please choose a valid category.',
            'err_salary'    => 'Minimum salary cannot be greater than the maximum.',
            'err_desc'      => 'Please write a description of at least 40 characters.',
            'err_apply'     => 'Please explain how candidates should apply.',
            'err_applyurl'  => 'The application link must be a valid URL.',
            'f_image'         => 'Company logo / image',
            'f_image_hint'    => '(optional — JPG, PNG, WEBP or GIF, up to 2 MB)',
            'f_image_current' => 'Current image',
            'f_image_remove'  => 'Remove current image',
            'f_image_replace' => 'Replace image (choose a new file)',
            'err_upload'      => 'The image could not be uploaded. Please try again.',
            'err_upload_type' => 'Please upload a JPG, PNG, WEBP, or GIF image.',
            'err_upload_size' => 'The image is too large (maximum 2 MB).',

            'f_ar_section'      => 'Arabic content (optional)',
            'f_title_ar'        => 'Job title (Arabic)',
            'f_location_ar'     => 'Location (Arabic)',
            'f_description_ar'  => 'Job description (Arabic)',
            'f_requirements_ar' => 'Requirements (Arabic)',
            'f_how_to_apply_ar' => 'How to apply (Arabic)',

            'jt_Full-time'  => 'Full-time',
            'jt_Part-time'  => 'Part-time',
            'jt_Contract'   => 'Contract',
            'jt_Internship' => 'Internship',
            'jt_Remote'     => 'Remote',
            'jt_Temporary'  => 'Temporary',

            'tm_now'   => 'just now',
            'tm_min'   => '%d min ago',
            'tm_hr'    => '%d hr ago',
            'tm_day'   => '%d days ago',
            'tm_wk'    => '%d wk ago',
        ],

        'ar' => [
            'lang_name'        => 'العربية',
            'switch_to'        => 'English',
            'nav_browse'       => 'تصفّح الوظائف',
            'nav_how'          => 'كيف يعمل',
            'nav_post'         => 'أضف وظيفة',

            'footer_browse'    => 'تصفّح',
            'footer_post'      => 'أضف وظيفة',
            'footer_admin'     => 'لوحة التحكم',
            'footer_copy'      => 'كل وظيفة تُراجَع يدويًا.',

            'hero_eyebrow'     => 'وظائف مُراجَعة يدويًا',
            'hero_title'       => 'عملٌ <em>يستحق السعي.</em><br>مُنتقاة، لا مزدحمة.',
            'hero_lede'        => 'كل إعلان على %s يُقرأ ويُعتمد يدويًا قبل نشره. لا رسائل مزعجة ولا إعلانات وهمية — فقط وظائف حقيقية من شركات تستحق وقتك.',
            'search_ph'        => 'ابحث بالمسمّى أو الشركة أو الموقع…',
            'search_btn'       => 'بحث',
            'stat_roles'       => 'وظائف متاحة',
            'stat_companies'   => 'شركات',
            'stat_reviewed'    => 'مُراجَعة يدويًا',

            'board_eyebrow'    => 'اللوحة',
            'board_title'      => 'أحدث الوظائف',
            'filter_ph'        => 'تصفية الإعلانات…',
            'filter_all'       => 'كل الوظائف',
            'empty_title'      => 'لا توجد وظائف مطابقة',
            'empty_body'       => 'جرّب فئة أخرى أو امسح البحث.',
            'featured'         => 'مميّزة',
            'view_role'        => 'عرض الوظيفة',
            'sort_label'       => 'ترتيب حسب',
            'sort_newest'      => 'الأحدث',
            'sort_salary'      => 'الراتب',
            'sort_alpha'       => 'المسمّى (أ–ي)',
            'sort_apply'       => 'تطبيق',
            'pager_prev'       => 'السابق',
            'pager_next'       => 'التالي',
            'pager_page'       => 'صفحة %d من %d',

            'cta_eyebrow'      => 'للشركات',
            'cta_title'        => 'توظّف؟ تواصَل مع من يقرؤون التفاصيل فعلًا.',
            'cta_body'         => 'أضف وظيفة خلال دقيقتين. يراجعها فريقنا، ويحسّن التفاصيل عند الحاجة، ثم ينشرها لجمهور مهتم.',
            'cta_btn'          => 'أضف وظيفة',

            'back_all'         => 'العودة لكل الوظائف',
            'about_role'       => 'عن الوظيفة',
            'looking_for'      => 'المتطلبات',
            'how_apply'        => 'طريقة التقديم',
            'a_company'        => 'الشركة',
            'a_location'       => 'الموقع',
            'a_type'           => 'النوع',
            'a_salary'         => 'الراتب',
            'apply_now'        => 'قدّم الآن',
            'apply_email'      => 'قدّم عبر البريد',
            'mention'          => 'اذكر أنك وجدت الوظيفة عبر %s.',
            'nf_title'         => 'هذه الوظيفة لم تعد متاحة',
            'nf_body'          => 'ربما تم شغلها أو إزالتها. تصفّح الوظائف المتاحة حاليًا.',
            'posted'           => 'نُشرت',
            'copy_link'        => 'نسخ الرابط',
            'copy_link_done'   => 'تم النسخ!',

            'apply_kastana_title' => 'قدّم عبر %s',
            'apply_kastana_lede'  => 'أرسل بياناتك مباشرة إلى الشركة.',
            'f_app_name'       => 'اسمك',
            'f_app_email'      => 'بريدك الإلكتروني',
            'f_app_phone'      => 'الهاتف',
            'f_app_note'       => 'رسالة لصاحب العمل',
            'f_app_submit'     => 'إرسال الطلب',
            'apply_ok'         => 'تم إرسال طلبك. بالتوفيق!',
            'err_app_name'     => 'يرجى إدخال اسمك.',
            'err_app_email'    => 'يرجى إدخال بريد إلكتروني صحيح.',
            'err_app_phone'    => 'يرجى إدخال رقم هاتف صحيح.',

            'nav_saved'        => 'المحفوظات',
            'saved_empty_title' => 'لا توجد وظائف محفوظة',
            'saved_empty_body'  => 'اضغط على النجمة في أي وظيفة لحفظها هنا.',
            'save_job'         => 'احفظ هذه الوظيفة',
            'unsave_job'       => 'إزالة من المحفوظات',

            'a_dashboard'      => 'لوحة التحكم',
            'a_customize'      => 'تخصيص',
            'a_activity'       => 'سجل النشاط',
            'a_companies'      => 'الشركات',
            'a_categories'     => 'الفئات',
            'a_account'        => 'الحساب',
            'a_signout'        => 'تسجيل الخروج',
            'a_viewsite'       => 'عرض الموقع',

            'f_intro_eyebrow'  => 'للشركات',
            'f_post_title'     => 'أضف وظيفة',
            'f_post_lede'      => 'املأ التفاصيل أدناه. يراجع فريقنا كل طلب — وبمجرد الموافقة تظهر وظيفتك على اللوحة. لا حاجة لحساب.',
            'f_title'          => 'المسمّى الوظيفي',
            'f_company'        => 'اسم الشركة',
            'f_email'          => 'بريد الشركة',
            'f_email_hint'     => '(لا يُعرض علنًا إلا إذا استُخدم للتقديم)',
            'f_website'        => 'موقع الشركة',
            'f_optional'       => '(اختياري)',
            'f_location'       => 'الموقع',
            'f_type'           => 'نوع الوظيفة',
            'f_category'       => 'الفئة',
            'f_select'         => '— اختر —',
            'f_salary'         => 'نطاق الراتب',
            'f_currency'       => 'العملة',
            'f_min'            => 'الأدنى',
            'f_max'            => 'الأعلى',
            'f_desc'           => 'وصف الوظيفة',
            'f_desc_ph'        => 'ماذا سيعمل هذا الشخص؟ كيف يبدو الأسبوع المعتاد؟',
            'f_req'            => 'المتطلبات',
            'f_req_ph'         => 'المهارات والخبرة والمزايا الإضافية.',
            'f_apply'          => 'طريقة التقديم',
            'f_apply_ph'       => 'اشرح للمتقدّمين ما عليهم فعله بالضبط.',
            'f_applyurl'       => 'رابط التقديم',
            'f_applyurl_hint'  => '(اختياري — وإلا سيراسلك المتقدّمون بالبريد)',
            'f_submit'         => 'إرسال للمراجعة',
            'f_fix'            => 'يرجى تصحيح ما يلي:',
            'f_ok'             => 'شكرًا! تم استلام إعلانك وهو الآن بانتظار المراجعة. سننشره بعد الموافقة.',
            'err_title'     => 'يجب أن يكون المسمّى الوظيفي بين 3 و150 حرفًا.',
            'err_company'   => 'يرجى إدخال اسم الشركة.',
            'err_email'     => 'يرجى إدخال بريد إلكتروني صحيح للشركة.',
            'err_website'   => 'يجب أن يكون موقع الشركة رابطًا صحيحًا (يتضمن ‎https://).',
            'err_location'  => 'يرجى إدخال الموقع (أو «عن بُعد»).',
            'err_type'      => 'يرجى اختيار نوع وظيفة صحيح.',
            'err_category'  => 'يرجى اختيار فئة صحيحة.',
            'err_salary'    => 'لا يمكن أن يكون الحد الأدنى للراتب أكبر من الأعلى.',
            'err_desc'      => 'يرجى كتابة وصف لا يقل عن 40 حرفًا.',
            'err_apply'     => 'يرجى توضيح كيفية تقديم المرشحين.',
            'err_applyurl'  => 'يجب أن يكون رابط التقديم رابطًا صحيحًا.',
            'f_image'         => 'شعار الشركة / صورة',
            'f_image_hint'    => '(اختياري — JPG أو PNG أو WEBP أو GIF، حتى 2 ميغابايت)',
            'f_image_current' => 'الصورة الحالية',
            'f_image_remove'  => 'إزالة الصورة الحالية',
            'f_image_replace' => 'استبدال الصورة (اختر ملفًا جديدًا)',
            'err_upload'      => 'تعذّر رفع الصورة. حاول مرة أخرى.',
            'err_upload_type' => 'يرجى رفع صورة بصيغة JPG أو PNG أو WEBP أو GIF.',
            'err_upload_size' => 'حجم الصورة كبير جدًا (بحد أقصى 2 ميغابايت).',

            'f_ar_section'      => 'المحتوى العربي (اختياري)',
            'f_title_ar'        => 'المسمّى الوظيفي (عربي)',
            'f_location_ar'     => 'الموقع (عربي)',
            'f_description_ar'  => 'وصف الوظيفة (عربي)',
            'f_requirements_ar' => 'المتطلبات (عربي)',
            'f_how_to_apply_ar' => 'طريقة التقديم (عربي)',

            'jt_Full-time'  => 'دوام كامل',
            'jt_Part-time'  => 'دوام جزئي',
            'jt_Contract'   => 'عقد',
            'jt_Internship' => 'تدريب',
            'jt_Remote'     => 'عن بُعد',
            'jt_Temporary'  => 'مؤقت',

            'tm_now'   => 'الآن',
            'tm_min'   => 'منذ %d د',
            'tm_hr'    => 'منذ %d س',
            'tm_day'   => 'منذ %d يوم',
            'tm_wk'    => 'منذ %d أسبوع',
        ],
    ];
    return $t;
}

/** Current language: ?lang= wins (and is remembered), else session, else 'en'. */
function current_lang(): string
{
    $supported = ['en', 'ar'];
    if (isset($_GET['lang']) && in_array($_GET['lang'], $supported, true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    return $_SESSION['lang'] ?? 'en';
}

function is_rtl(): bool { return current_lang() === 'ar'; }
function dir_attr(): string { return is_rtl() ? 'rtl' : 'ltr'; }

/** Translate a UI key. Extra args are passed to sprintf. */
function t(string $key, ...$args): string
{
    $lang = current_lang();
    $all  = translations();
    $str  = $all[$lang][$key] ?? $all['en'][$key] ?? $key;
    return $args ? sprintf($str, ...$args) : $str;
}

/** Echo a translated string as trusted HTML (for strings that contain markup). */
function th(string $key): string { return t($key); }

/** URL that toggles to the other language, preserving the current path + query. */
function lang_switch_url(): string
{
    $other = is_rtl() ? 'en' : 'ar';
    $path  = strtok($_SERVER['REQUEST_URI'], '?');
    $q     = $_GET;
    $q['lang'] = $other;
    return $path . '?' . http_build_query($q);
}

/** Localised job type label. */
function job_type_label(string $type): string
{
    return t('jt_' . $type);
}

/**
 * Return a job field in the current language, falling back to the base value.
 * e.g. job_field($job, 'title') -> title_ar (if ar & present) else title.
 */
function job_field(array $job, string $base): string
{
    if (is_rtl()) {
        $ar = $job[$base . '_ar'] ?? '';
        if (is_string($ar) && trim($ar) !== '') return $ar;
    }
    return (string) ($job[$base] ?? '');
}

/** Category name in the current language, falling back to the base name. */
function cat_name(?string $name, ?string $nameAr): string
{
    if (is_rtl() && $nameAr !== null && trim($nameAr) !== '') return $nameAr;
    return (string) $name;
}
