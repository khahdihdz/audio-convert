<?php
require_once __DIR__ . '/../config.php';
requireLogin();
$pdo = getDb();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		verifyCsrfOrAbort();
		$title = trim((string)($_POST['title'] ?? ''));
		$description = trim((string)($_POST['description'] ?? ''));
		$publisher = trim((string)($_POST['publisher'] ?? ''));
		$platform = trim((string)($_POST['platform'] ?? ''));
		$releaseYear = trim((string)($_POST['release_year'] ?? ''));
		$releaseYearInt = $releaseYear !== '' ? (int)$releaseYear : null;

		if ($title === '') {
			$errors[] = 'Tiêu đề là bắt buộc';
		}

		if (!$errors) {
			$pdo->beginTransaction();
			$ins = $pdo->prepare('INSERT INTO games (title, description, publisher, platform, release_year) VALUES (:t,:d,:p,:pl,:y)');
			$ins->execute([
				':t' => $title,
				':d' => $description,
				':p' => $publisher,
				':pl' => $platform,
				':y' => $releaseYearInt,
			]);
			$gameId = (int)$pdo->lastInsertId();

			$coverFile = null;
			try {
				$coverFile = handleUpload('cover', COVER_DIR);
			} catch (Throwable $e) {
				$errors[] = 'Cover: ' . $e->getMessage();
			}
			if ($coverFile) {
				$upd = $pdo->prepare('UPDATE games SET cover_image = :c WHERE id = :id');
				$upd->execute([':c' => $coverFile, ':id' => $gameId]);
			}

			$shotFiles = [];
			try {
				$shotFiles = handleMultipleUploads('screenshots', SCREENSHOT_DIR);
			} catch (Throwable $e) {
				// Đã xử lý lỗi lẻ trong handleMultipleUploads
			}
			if ($shotFiles) {
				$insS = $pdo->prepare('INSERT INTO screenshots (game_id, image_path) VALUES (:gid, :path)');
				foreach ($shotFiles as $sf) {
					$insS->execute([':gid' => $gameId, ':path' => $sf]);
				}
			}

			$labels = $_POST['download_labels'] ?? [];
			$urls = $_POST['download_urls'] ?? [];
			if (is_array($urls) && is_array($labels)) {
				$insD = $pdo->prepare('INSERT INTO downloads (game_id, label, url) VALUES (:gid,:lb,:url)');
				for ($i = 0; $i < count($urls); $i++) {
					$url = trim((string)$urls[$i]);
					$lb = trim((string)($labels[$i] ?? ''));
					if ($url === '') { continue; }
					$insD->execute([':gid' => $gameId, ':lb' => $lb, ':url' => $url]);
				}
			}

			$pdo->commit();
			header('Location: edit_game.php?id=' . $gameId);
			exit;
		}
	} catch (Throwable $e) {
		if ($pdo->inTransaction()) { $pdo->rollBack(); }
		$errors[] = 'Lỗi: ' . $e->getMessage();
	}
}

$title = 'Thêm game';
include __DIR__ . '/../partials/header.php';
?>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h3 m-0">Thêm game</h1>
		<a class="btn btn-secondary" href="index.php">Quay lại</a>
	</div>
	<?php if ($errors): ?>
		<div class="alert alert-danger">
			<ul class="m-0 ps-3">
				<?php foreach ($errors as $err): ?><li><?php echo esc($err); ?></li><?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<form method="post" enctype="multipart/form-data">
		<?php echo csrfField(); ?>
		<div class="row g-3">
			<div class="col-md-8">
				<div class="mb-3">
					<label class="form-label">Tiêu đề</label>
					<input type="text" name="title" class="form-control" required>
				</div>
				<div class="mb-3">
					<label class="form-label">Mô tả</label>
					<textarea name="description" class="form-control" rows="6"></textarea>
				</div>
				<div class="mb-3">
					<label class="form-label">Ảnh chụp màn hình</label>
					<input type="file" name="screenshots[]" class="form-control" multiple accept="image/*">
				</div>
			</div>
			<div class="col-md-4">
				<div class="mb-3">
					<label class="form-label">Nhà phát hành</label>
					<input type="text" name="publisher" class="form-control">
				</div>
				<div class="mb-3">
					<label class="form-label">Nền tảng</label>
					<input type="text" name="platform" class="form-control">
				</div>
				<div class="mb-3">
					<label class="form-label">Năm phát hành</label>
					<input type="number" name="release_year" class="form-control" min="1950" max="2100">
				</div>
				<div class="mb-3">
					<label class="form-label">Ảnh bìa (cover)</label>
					<input type="file" name="cover" class="form-control" accept="image/*">
				</div>
			</div>
		</div>
		<h2 class="h5 mt-4">Link tải</h2>
		<div id="download-list">
			<div class="row g-2 align-items-end mb-2">
				<div class="col-5">
					<label class="form-label">Nhãn</label>
					<input type="text" class="form-control" name="download_labels[]" placeholder="Google Drive / Mega...">
				</div>
				<div class="col-7">
					<label class="form-label">URL</label>
					<input type="url" class="form-control" name="download_urls[]" placeholder="https://...">
				</div>
			</div>
		</div>
		<button type="button" class="btn btn-outline-secondary btn-sm" onclick="addDownloadRow()">Thêm link</button>
		<div class="mt-4">
			<button class="btn btn-primary">Lưu</button>
			<a class="btn btn-secondary" href="index.php">Hủy</a>
		</div>
	</form>
	<script>
	function addDownloadRow() {
		const container = document.getElementById('download-list');
		const row = document.createElement('div');
		row.className = 'row g-2 align-items-end mb-2';
		row.innerHTML = `
			<div class="col-5">
				<input type="text" class="form-control" name="download_labels[]" placeholder="Nhãn">
			</div>
			<div class="col-7">
				<input type="url" class="form-control" name="download_urls[]" placeholder="https://...">
			</div>
		`;
		container.appendChild(row);
	}
	</script>
<?php include __DIR__ . '/../partials/footer.php'; ?>