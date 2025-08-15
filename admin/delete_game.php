<?php
require_once __DIR__ . '/../config.php';
requireLogin();
$pdo = getDb();

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($id <= 0) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verifyCsrfOrAbort();
	// Lấy file để xóa
	$g = $pdo->prepare('SELECT cover_image FROM games WHERE id = :id');
	$g->execute([':id'=>$id]);
	$game = $g->fetch();
	$shots = $pdo->prepare('SELECT image_path FROM screenshots WHERE game_id = :id');
	$shots->execute([':id'=>$id]);
	$shotFiles = $shots->fetchAll();
	$del = $pdo->prepare('DELETE FROM games WHERE id = :id');
	$del->execute([':id'=>$id]);
	if ($game && $game['cover_image']) { safeUnlink(COVER_DIR . '/' . $game['cover_image']); }
	foreach ($shotFiles as $sf) { safeUnlink(SCREENSHOT_DIR . '/' . $sf['image_path']); }
	header('Location: index.php');
	exit;
}

$title = 'Xóa game';
include __DIR__ . '/../partials/header.php';
?>
	<div class="card">
		<div class="card-body">
			<h1 class="h5">Xác nhận xóa game #<?php echo (int)$id; ?></h1>
			<p>Hành động này không thể hoàn tác.</p>
			<form method="post">
				<?php echo csrfField(); ?>
				<input type="hidden" name="id" value="<?php echo (int)$id; ?>">
				<a href="index.php" class="btn btn-secondary">Hủy</a>
				<button class="btn btn-danger">Xóa</button>
			</form>
		</div>
	</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>