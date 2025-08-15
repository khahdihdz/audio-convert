<?php
require_once __DIR__ . '/../config.php';
requireLogin();
$pdo = getDb();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM games WHERE id = :id');
$stmt->execute([':id' => $id]);
$game = $stmt->fetch();
if (!$game) { http_response_code(404); echo 'Không tìm thấy game'; exit; }

$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		verifyCsrfOrAbort();
		if (isset($_POST['update_info'])) {
			$title = trim((string)($_POST['title'] ?? ''));
			$description = trim((string)($_POST['description'] ?? ''));
			$publisher = trim((string)($_POST['publisher'] ?? ''));
			$platform = trim((string)($_POST['platform'] ?? ''));
			$releaseYear = trim((string)($_POST['release_year'] ?? ''));
			$releaseYearInt = $releaseYear !== '' ? (int)$releaseYear : null;
			if ($title === '') { $errors[] = 'Tiêu đề là bắt buộc'; }
			if (!$errors) {
				$upd = $pdo->prepare('UPDATE games SET title=:t, description=:d, publisher=:p, platform=:pl, release_year=:y, updated_at=datetime("now") WHERE id=:id');
				$upd->execute([':t'=>$title,':d'=>$description,':p'=>$publisher,':pl'=>$platform,':y'=>$releaseYearInt,':id'=>$id]);
				$success = 'Đã cập nhật thông tin.';
			}
		}
		if (isset($_POST['change_cover'])) {
			$old = $game['cover_image'] ?? null;
			$newCover = handleUpload('cover', COVER_DIR);
			if ($newCover) {
				$upd = $pdo->prepare('UPDATE games SET cover_image = :c, updated_at=datetime("now") WHERE id = :id');
				$upd->execute([':c' => $newCover, ':id' => $id]);
				if ($old) { safeUnlink(COVER_DIR . '/' . $old); }
				$success = 'Đã cập nhật cover.';
			}
		}
		if (isset($_POST['add_screenshots'])) {
			$files = handleMultipleUploads('screenshots', SCREENSHOT_DIR);
			if ($files) {
				$ins = $pdo->prepare('INSERT INTO screenshots (game_id, image_path) VALUES (:gid,:p)');
				foreach ($files as $f) { $ins->execute([':gid'=>$id, ':p'=>$f]); }
				$success = 'Đã thêm ảnh chụp màn hình.';
			}
		}
		if (isset($_POST['add_downloads'])) {
			$labels = $_POST['download_labels'] ?? [];
			$urls = $_POST['download_urls'] ?? [];
			$ins = $pdo->prepare('INSERT INTO downloads (game_id, label, url) VALUES (:gid,:lb,:url)');
			for ($i=0;$i<count($urls);$i++) {
				$url = trim((string)$urls[$i]);
				$lb = trim((string)($labels[$i] ?? ''));
				if ($url === '') continue;
				$ins->execute([':gid'=>$id, ':lb'=>$lb, ':url'=>$url]);
			}
			$success = 'Đã thêm link tải.';
		}
	} catch (Throwable $e) {
		$errors[] = 'Lỗi: ' . $e->getMessage();
	}
	// Reload game data sau cập nhật
	$stmt = $pdo->prepare('SELECT * FROM games WHERE id = :id');
	$stmt->execute([':id' => $id]);
	$game = $stmt->fetch();
}

$ss = $pdo->prepare('SELECT * FROM screenshots WHERE game_id = :id');
$ss->execute([':id' => $id]);
$screens = $ss->fetchAll();

$dl = $pdo->prepare('SELECT * FROM downloads WHERE game_id = :id');
$dl->execute([':id' => $id]);
$links = $dl->fetchAll();

$title = 'Sửa game: ' . $game['title'];
include __DIR__ . '/../partials/header.php';
?>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h3 m-0">Sửa game</h1>
		<a class="btn btn-secondary" href="index.php">Quay lại</a>
	</div>
	<?php if ($success): ?><div class="alert alert-success"><?php echo esc($success); ?></div><?php endif; ?>
	<?php if ($errors): ?>
		<div class="alert alert-danger">
			<ul class="m-0 ps-3"><?php foreach ($errors as $err): ?><li><?php echo esc($err); ?></li><?php endforeach; ?></ul>
		</div>
	<?php endif; ?>
	<form method="post">
		<?php echo csrfField(); ?>
		<input type="hidden" name="update_info" value="1">
		<div class="row g-3">
			<div class="col-md-8">
				<div class="mb-3">
					<label class="form-label">Tiêu đề</label>
					<input type="text" name="title" class="form-control" value="<?php echo esc($game['title']); ?>" required>
				</div>
				<div class="mb-3">
					<label class="form-label">Mô tả</label>
					<textarea name="description" class="form-control" rows="6"><?php echo esc($game['description']); ?></textarea>
				</div>
			</div>
			<div class="col-md-4">
				<div class="mb-3">
					<label class="form-label">Nhà phát hành</label>
					<input type="text" name="publisher" class="form-control" value="<?php echo esc($game['publisher']); ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Nền tảng</label>
					<input type="text" name="platform" class="form-control" value="<?php echo esc($game['platform']); ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Năm phát hành</label>
					<input type="number" name="release_year" class="form-control" min="1950" max="2100" value="<?php echo esc((string)$game['release_year']); ?>">
				</div>
			</div>
		</div>
		<div class="mt-3">
			<button class="btn btn-primary">Lưu</button>
		</div>
	</form>
	<hr class="my-4">
	<h2 class="h5">Ảnh bìa (cover)</h2>
	<div class="row g-3 align-items-center">
		<div class="col-md-4">
			<?php if (!empty($game['cover_image'])): ?>
				<img class="img-fluid rounded border" src="../uploads/covers/<?php echo esc($game['cover_image']); ?>" alt="Cover">
			<?php else: ?>
				<div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" style="height:200px;">No Cover</div>
			<?php endif; ?>
		</div>
		<div class="col-md-8">
			<form method="post" enctype="multipart/form-data" class="d-flex gap-2 align-items-end">
				<?php echo csrfField(); ?>
				<input type="hidden" name="change_cover" value="1">
				<div class="flex-grow-1">
					<label class="form-label">Chọn ảnh mới</label>
					<input type="file" name="cover" class="form-control" accept="image/*">
				</div>
				<button class="btn btn-outline-primary">Cập nhật</button>
			</form>
		</div>
	</div>
	<hr class="my-4">
	<h2 class="h5">Ảnh chụp màn hình</h2>
	<div class="row g-3">
		<?php foreach ($screens as $sc): ?>
			<div class="col-6 col-md-4 col-lg-3">
				<div class="position-relative">
					<img class="img-fluid rounded border" src="../uploads/screenshots/<?php echo esc($sc['image_path']); ?>" alt="Screenshot">
					<form method="post" action="remove_screenshot.php" class="position-absolute" style="top:8px; right:8px;">
						<?php echo csrfField(); ?>
						<input type="hidden" name="id" value="<?php echo (int)$sc['id']; ?>">
						<input type="hidden" name="game_id" value="<?php echo (int)$id; ?>">
						<button class="btn btn-sm btn-danger" onclick="return confirm('Xóa ảnh này?');">X</button>
					</form>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<form method="post" enctype="multipart/form-data" class="mt-3">
		<?php echo csrfField(); ?>
		<input type="hidden" name="add_screenshots" value="1">
		<div class="mb-3">
			<label class="form-label">Thêm ảnh</label>
			<input type="file" name="screenshots[]" class="form-control" multiple accept="image/*">
		</div>
		<button class="btn btn-outline-primary">Tải lên</button>
	</form>
	<hr class="my-4">
	<h2 class="h5">Link tải</h2>
	<ul class="list-group mb-3">
		<?php foreach ($links as $ln): ?>
			<li class="list-group-item d-flex justify-content-between align-items-center">
				<span><?php echo esc($ln['label'] ?: $ln['url']); ?></span>
				<form method="post" action="remove_download.php" onsubmit="return confirm('Xóa link này?');">
					<?php echo csrfField(); ?>
					<input type="hidden" name="id" value="<?php echo (int)$ln['id']; ?>">
					<input type="hidden" name="game_id" value="<?php echo (int)$id; ?>">
					<button class="btn btn-sm btn-danger">Xóa</button>
				</form>
			</li>
		<?php endforeach; ?>
	</ul>
	<form method="post">
		<?php echo csrfField(); ?>
		<input type="hidden" name="add_downloads" value="1">
		<div id="download-list" class="mb-2">
			<div class="row g-2 align-items-end mb-2">
				<div class="col-5"><input type="text" class="form-control" name="download_labels[]" placeholder="Nhãn"></div>
				<div class="col-7"><input type="url" class="form-control" name="download_urls[]" placeholder="https://..."></div>
			</div>
		</div>
		<button type="button" class="btn btn-outline-secondary btn-sm" onclick="addDownloadRow()">Thêm link</button>
		<div class="mt-2">
			<button class="btn btn-primary">Thêm</button>
		</div>
	</form>
	<script>
	function addDownloadRow() {
		const container = document.getElementById('download-list');
		const row = document.createElement('div');
		row.className = 'row g-2 align-items-end mb-2';
		row.innerHTML = `
			<div class="col-5"><input type="text" class="form-control" name="download_labels[]" placeholder="Nhãn"></div>
			<div class="col-7"><input type="url" class="form-control" name="download_urls[]" placeholder="https://..."></div>
		`;
		container.appendChild(row);
	}
	</script>
<?php include __DIR__ . '/../partials/footer.php'; ?>