<?php
require_once __DIR__ . '/../config.php';
requireLogin();
$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrfOrAbort();

$id = (int)($_POST['id'] ?? 0);
$gameId = (int)($_POST['game_id'] ?? 0);

$stmt = $pdo->prepare('SELECT image_path FROM screenshots WHERE id = :id AND game_id = :gid');
$stmt->execute([':id'=>$id, ':gid'=>$gameId]);
$sc = $stmt->fetch();
if ($sc) {
	$del = $pdo->prepare('DELETE FROM screenshots WHERE id = :id');
	$del->execute([':id'=>$id]);
	safeUnlink(SCREENSHOT_DIR . '/' . $sc['image_path']);
}

header('Location: edit_game.php?id=' . $gameId);
exit;