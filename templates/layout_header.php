<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title><?php echo htmlspecialchars($title ?? 'Chat App'); ?></title>
  <?php if(defined('BASE_PATH')): ?>
    <base href="<?php echo BASE_PATH; ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="assets/style/style.css">
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css"/>
</head>
<body>
