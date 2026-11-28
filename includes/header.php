<?php
if (!isset($page_title)) {
    $page_title = 'Pangkalan Pangan - Bersama Merawat Kebaikan';
}
if (!isset($base_url)) {
    $base_url = '';
}
header('Content-Type: text/html; charset=utf-8');
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="id" lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>assets/css/style.css?v=8" />
<?php if (isset($extra_css) && is_array($extra_css)): ?>
<?php foreach ($extra_css as $css_file): ?>
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>assets/css/<?php echo htmlspecialchars($css_file, ENT_QUOTES, 'UTF-8'); ?>" />
<?php endforeach; ?>
<?php endif; ?>
</head>
<body<?php echo isset($body_attrs) ? ' ' . $body_attrs : ''; ?>>
<div class="<?php echo isset($page_wrapper_class) ? htmlspecialchars($page_wrapper_class, ENT_QUOTES, 'UTF-8') : 'page-wrapper'; ?>">
