<?php
require_once __DIR__ . '/config.php';
$pdo = getDb();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verifyCsrfOrAbort();
	$username = trim((string)($_POST['username'] ?? ''));
	$password = (string)($_POST['password'] ?? '');
	$stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u');
	$stmt->execute([':u' => $username]);
	$user = $stmt->fetch();
	if ($user && password_verify($password, $user['password_hash'])) {
		$_SESSION['user_id'] = (int)$user['id'];
		$_SESSION['username'] = $user['username'];
		header('Location: admin/index.php');
		exit;
	}
	$error = 'Sai tài khoản hoặc mật khẩu';
}

$title = 'Đăng nhập';
include __DIR__ . '/partials/header.php';
?>
	<div class="row justify-content-center">
		<div class="col-md-6 col-lg-5">
			<div class="card">
				<div class="card-header">Đăng nhập</div>
				<div class="card-body">
					<?php if ($error): ?><div class="alert alert-danger"><?php echo esc($error); ?></div><?php endif; ?>
					<form method="post">
						<?php echo csrfField(); ?>
						<div class="mb-3">
							<label class="form-label">Tên đăng nhập</label>
							<input type="text" name="username" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Mật khẩu</label>
							<input type="password" name="password" class="form-control" required>
						</div>
						<button class="btn btn-primary">Đăng nhập</button>
					</form>
				</div>
			</div>
		</div>
	</div>
<?php include __DIR__ . '/partials/footer.php'; ?>