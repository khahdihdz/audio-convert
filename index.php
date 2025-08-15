<?php
require_once __DIR__ . '/config.php';

$pdo = getDb();

$search = trim((string)($_GET['q'] ?? ''));
$where = '';
$params = [];
if ($search !== '') {
	$where = 'WHERE title LIKE :q OR publisher LIKE :q OR platform LIKE :q';
	$params[':q'] = '%' . $search . '%';
}

$stmt = $pdo->prepare("SELECT id, title, publisher, platform, release_year, cover_image FROM games $where ORDER BY created_at DESC");
$stmt->execute($params);
$games = $stmt->fetchAll();

$title = 'Danh sách game';
include __DIR__ . '/partials/header.php';
?>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h3 m-0">Danh sách game</h1>
		<form class="d-flex" method="get" action="index.php">
			<input class="form-control me-2" type="search" placeholder="Tìm kiếm..." name="q" value="<?php echo esc($search); ?>">
			<button class="btn btn-outline-light" type="submit">Tìm</button>
		</form>
	</div>
	<div class="row g-3">
		<?php foreach ($games as $game): ?>
			<div class="col-12 col-sm-6 col-md-4 col-lg-3">
				<div class="card h-100">
					<?php if (!empty($game['cover_image'])): ?>
						<img class="card-img-top card-cover" src="uploads/covers/<?php echo esc($game['cover_image']); ?>" alt="<?php echo esc($game['title']); ?>">
					<?php else: ?>
						<div class="card-img-top bg-secondary d-flex align-items-center justify-content-center text-white card-cover">No Cover</div>
					<?php endif; ?>
					<div class="card-body d-flex flex-column">
						<h5 class="card-title mb-1"><?php echo esc($game['title']); ?></h5>
						<div class="text-muted small mb-2"><?php echo esc($game['platform']); ?><?php echo $game['release_year'] ? ' • ' . esc((string)$game['release_year']) : ''; ?></div>
						<a href="view.php?id=<?php echo (int)$game['id']; ?>" class="btn btn-primary mt-auto">Xem chi tiết</a>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
		<?php if (count($games) === 0): ?>
			<div class="col-12"><div class="alert alert-info">Chưa có game nào.</div></div>
		<?php endif; ?>
	</div>
<?php include __DIR__ . '/partials/footer.php'; ?>