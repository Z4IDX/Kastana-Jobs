<?php
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
require_csrf();

$jobId = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
$act = input($_POST, 'save_action');
if ($jobId) toggle_saved_job($jobId, $act !== 'unsave');

// return_url is the visitor's current page (already a full site path, e.g. "/kastana-jobs/index.php?...").
// Validate it strictly (relative path only, .php extension, no scheme/host) to prevent open redirects,
// then send the Location header directly rather than through url() to avoid double-prefixing BASE_URL.
$return = input($_POST, 'return_url');
if (!preg_match('#^/[A-Za-z0-9_\-./]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$#', $return)) {
    $return = url('index.php');
}
header('Location: ' . $return);
exit;
