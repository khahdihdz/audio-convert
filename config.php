<?php

declare(strict_types=1);

// Cấu hình chung
const BASE_URL = '/';
const DATA_DIR = __DIR__ . '/data';
const UPLOAD_DIR = __DIR__ . '/uploads';
const COVER_DIR = UPLOAD_DIR . '/covers';
const SCREENSHOT_DIR = UPLOAD_DIR . '/screenshots';

// Bắt đầu session sớm
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Kết nối DB (SQLite)
function getDb(): PDO {
	static $pdo = null;
	if ($pdo !== null) {
		return $pdo;
	}
	if (!is_dir(DATA_DIR)) {
		mkdir(DATA_DIR, 0775, true);
	}
	$dsn = 'sqlite:' . DATA_DIR . '/app.db';
	$pdo = new PDO($dsn, null, null, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);
	// Bật foreign keys
	$pdo->exec('PRAGMA foreign_keys = ON');
	return $pdo;
}

// Helper escape HTML
function esc(?string $value): string {
	return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Auth helpers
function isLoggedIn(): bool {
	return isset($_SESSION['user_id']);
}

function requireLogin(): void {
	if (!isLoggedIn()) {
		header('Location: ' . BASE_URL . 'login.php');
		exit;
	}
}

// CSRF helpers
function ensureCsrfToken(): void {
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
}

function csrfField(): string {
	ensureCsrfToken();
	$token = esc($_SESSION['csrf_token']);
	return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verifyCsrfOrAbort(): void {
	ensureCsrfToken();
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($_SESSION['csrf_token'], (string)$token)) {
		http_response_code(400);
		echo 'CSRF token không hợp lệ.';
		exit;
	}
}

// Xử lý upload ảnh
function handleUpload(string $fieldName, string $destinationDir, array $allowedMime = ['image/jpeg','image/png','image/webp']): ?string {
	if (!isset($_FILES[$fieldName]) || !is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
		return null;
	}
	if (!is_dir($destinationDir)) {
		mkdir($destinationDir, 0775, true);
	}
	$tmpPath = $_FILES[$fieldName]['tmp_name'];
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mime = finfo_file($finfo, $tmpPath) ?: '';
	finfo_close($finfo);
	if (!in_array($mime, $allowedMime, true)) {
		throw new RuntimeException('Định dạng file không được hỗ trợ.');
	}
	$ext = match ($mime) {
		'image/jpeg' => '.jpg',
		'image/png' => '.png',
		'image/webp' => '.webp',
		default => ''
	};
	$filename = bin2hex(random_bytes(16)) . $ext;
	$target = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
	if (!move_uploaded_file($tmpPath, $target)) {
		throw new RuntimeException('Không thể lưu tệp tải lên.');
	}
	return $filename;
}

function handleMultipleUploads(string $fieldName, string $destinationDir, array $allowedMime = ['image/jpeg','image/png','image/webp']): array {
	$results = [];
	if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]['name'])) {
		return $results;
	}
	$names = $_FILES[$fieldName]['name'];
	$tmpNames = $_FILES[$fieldName]['tmp_name'];
	for ($i = 0; $i < count($names); $i++) {
		if (!is_uploaded_file($tmpNames[$i])) {
			continue;
		}
		$_FILES['__single'] = [
			'name' => $names[$i],
			'tmp_name' => $tmpNames[$i],
			'error' => $_FILES[$fieldName]['error'][$i] ?? 0,
			'size' => $_FILES[$fieldName]['size'][$i] ?? 0,
			'type' => $_FILES[$fieldName]['type'][$i] ?? ''
		];
		// Tạo stream giả cho handleUpload
		try {
			$result = handleUpload('__single', $destinationDir, $allowedMime);
			if ($result !== null) {
				$results[] = $result;
			}
		} catch (Throwable $e) {
			// Bỏ qua file lỗi lẻ
		}
		unset($_FILES['__single']);
	}
	return $results;
}

// Xóa file an toàn
function safeUnlink(?string $path): void {
	if ($path && is_file($path)) {
		@unlink($path);
	}
}