<?php
require_once __DIR__ . '/../config.php';
requireLogin();
$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrfOrAbort();

$id = (int)($_POST['id'] ?? 0);
$gameId = (int)($_POST['game_id'] ?? 0);

$del = $pdo->prepare('DELETE FROM downloads WHERE id = :id');
$del->execute([':id'=>$id]);

header('Location: edit_game.php?id=' . $gameId);
exit;