<?php
require_once __DIR__ . '/../config.php';
requireLogin();
$pdo = getDb();

$stmt = $pdo->query('SELECT id, title, platform, release_year FROM games ORDER BY created_at DESC');
$games = $stmt->fetchAll();

$title = 'Quản trị';
include __DIR__ . '/../partials/header.php';
?>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h3 m-0">Quản trị</h1>
		<div>
			<a class="btn btn-secondary me-2" href="../index.php" target="_blank">Xem trang</a>
			<a class="btn btn-outline-danger me-2" href="../logout.php">Đăng xuất</a>
			<a class="btn btn-primary" href="new_game.php">Thêm game</a>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead>
				<tr>
					<th>#</th>
					<th>Tiêu đề</th>
					<th>Nền tảng</th>
					<th>Năm</th>
					<th>Hành động</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($games as $g): ?>
					<tr>
						<td><?php echo (int)$g['id']; ?></td>
						<td><?php echo esc($g['title']); ?></td>
						<td><?php echo esc($g['platform']); ?></td>
						<td><?php echo esc((string)$g['release_year']); ?></td>
						<td>
							<a class="btn btn-sm btn-outline-primary" href="edit_game.php?id=<?php echo (int)$g['id']; ?>">Sửa</a>
							<a class="btn btn-sm btn-outline-danger" href="delete_game.php?id=<?php echo (int)$g['id']; ?>" onclick="return confirm('Xóa game này?');">Xóa</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>