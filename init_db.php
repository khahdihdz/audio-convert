<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$pdo = getDb();

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS users (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	username TEXT NOT NULL UNIQUE,
	password_hash TEXT NOT NULL,
	created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
SQL);

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS games (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	title TEXT NOT NULL,
	description TEXT,
	publisher TEXT,
	platform TEXT,
	release_year INTEGER,
	cover_image TEXT,
	created_at TEXT NOT NULL DEFAULT (datetime('now')),
	updated_at TEXT
);
SQL);

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS screenshots (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	game_id INTEGER NOT NULL,
	image_path TEXT NOT NULL,
	FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);
SQL);

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS downloads (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	game_id INTEGER NOT NULL,
	label TEXT,
	url TEXT NOT NULL,
	FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);
SQL);

// Tạo user admin mặc định nếu chưa có
$stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM users');
$cnt = (int)$stmt->execute() && ($row = $stmt->fetch()) ? (int)$row['cnt'] : 0;
if ($cnt === 0) {
	$username = 'admin';
	$passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
	$ins = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:u, :p)');
	$ins->execute([':u' => $username, ':p' => $passwordHash]);
	echo "Created default user: admin / admin123\n";
} else {
	echo "Users already exist.\n";
}

echo "DB initialized.\n";