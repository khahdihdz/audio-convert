<?php
require_once __DIR__ . '/../config.php';
ensureCsrfToken();
?><!doctype html>
<html lang="vi">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo esc($title ?? 'Game Share'); ?></title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="<?php echo BASE_URL; ?>assets/styles.css" rel="stylesheet">
	</head>
	<body>
		<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
			<div class="container">
				<a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">Game Share</a>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarNav">
					<ul class="navbar-nav ms-auto">
						<li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>index.php">Trang chủ</a></li>
						<li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>admin/index.php">Quản trị</a></li>
					</ul>
				</div>
			</div>
		</nav>
		<main class="container">