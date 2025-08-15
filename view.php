<?php
require_once __DIR__ . '/config.php';

$pdo = getDb();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
	header('Location: index.php');
	exit;
}

$stmt = $pdo->prepare('SELECT * FROM games WHERE id = :id');
$stmt->execute([':id' => $id]);
$game = $stmt->fetch();
if (!$game) {
	http_response_code(404);
	echo 'Không tìm thấy game';
	exit;
}

$ss = $pdo->prepare('SELECT * FROM screenshots WHERE game_id = :id');
$ss->execute([':id' => $id]);
$screens = $ss->fetchAll();

$dl = $pdo->prepare('SELECT * FROM downloads WHERE game_id = :id');
$dl->execute([':id' => $id]);
$links = $dl->fetchAll();

$title = $game['title'];
include __DIR__ . '/partials/header.php';
?>
	<div class="row g-4">
		<div class="col-md-4">
			<?php if (!empty($game['cover_image'])): ?>
				<img class="img-fluid rounded border" src="uploads/covers/<?php echo esc($game['cover_image']); ?>" alt="<?php echo esc($game['title']); ?>">
			<?php else: ?>
				<div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" style="height:300px;">No Cover</div>
			<?php endif; ?>
		</div>
		<div class="col-md-8">
			<h1 class="h3 mb-2"><?php echo esc($game['title']); ?></h1>
			<div class="text-muted mb-3">Nền tảng: <?php echo esc($game['platform'] ?: 'N/A'); ?><?php echo $game['release_year'] ? ' • ' . esc((string)$game['release_year']) : ''; ?></div>
			<p><?php echo nl2br(esc($game['description'] ?? '')); ?></p>
			<div class="mb-3"><strong>Nhà phát hành:</strong> <?php echo esc($game['publisher'] ?: 'N/A'); ?></div>
			<?php if (count($links) > 0): ?>
				<h2 class="h5 mt-4">Tải về</h2>
				<ul class="list-group">
					<?php foreach ($links as $ln): ?>
						<li class="list-group-item d-flex justify-content-between align-items-center">
							<span><?php echo esc($ln['label'] ?: $ln['url']); ?></span>
							<a href="<?php echo esc($ln['url']); ?>" class="btn btn-success btn-sm" target="_blank" rel="noopener">Download</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
	<?php if (count($screens) > 0): ?>
		<h2 class="h5 mt-5">Ảnh chụp màn hình</h2>
		<div class="row g-3">
			<?php foreach ($screens as $sc): ?>
				<div class="col-6 col-md-4 col-lg-3">
					<a href="uploads/screenshots/<?php echo esc($sc['image_path']); ?>" target="_blank">
						<img class="img-fluid rounded border" src="uploads/screenshots/<?php echo esc($sc['image_path']); ?>" alt="Screenshot">
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>