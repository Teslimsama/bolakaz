<?php include 'session.php'; ?>
<?php
require_once __DIR__ . '/lib/customer_accounts.php';
require_once __DIR__ . '/lib/sync.php';
$output = '';
if (!isset($_GET['code']) or !isset($_GET['user'])) {
	$output .= '
			<div class="alert alert-danger">
                <h4><i class="icon fa fa-warning"></i> Error!</h4>
                Code to activate account not found.
            </div>
            <h4>You may <a href="signup">Signup</a> or back to <a href="index">Homepage</a>.</h4>
		';
} else {
	$conn = $pdo->open();

	$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM users WHERE activate_code=:code AND id=:id");
	$stmt->execute(['code' => $_GET['code'], 'id' => $_GET['user']]);
	$row = $stmt->fetch();

	if ($row['numrows'] > 0) {
		if ($row['status']) {
			$output .= '
					<div class="alert alert-danger">
		                <h4><i class="icon fa fa-warning"></i> Error!</h4>
		                Account already activated.
		            </div>
		            <h4>You may <a href="signin">Login</a> or back to <a href="index">Homepage</a>.</h4>
				';
		} else {
			try {
				$conn->beginTransaction();
				$fields = ['status = :status', 'activate_code = :activate_code'];
				$params = [
					'status' => 1,
					'activate_code' => null,
					'id' => (int) $row['id'],
				];
				if (app_customer_db_has_column($conn, 'users', 'account_state')) {
					$fields[] = 'account_state = :account_state';
					$params['account_state'] = 'active';
				}
				if (app_customer_db_has_column($conn, 'users', 'is_placeholder_email')) {
					$fields[] = 'is_placeholder_email = :is_placeholder_email';
					$params['is_placeholder_email'] = 0;
				}
				$stmt = $conn->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id=:id");
				$stmt->execute($params);
				sync_enqueue_or_fail($conn, 'users', (int) $row['id']);
				$conn->commit();
				$output .= '
						<div class="alert alert-success">
			                <h4><i class="icon fa fa-check"></i> Success!</h4>
			                Account activated - Email: <b>' . $row['email'] . '</b>.
			            </div>
			            <h4>You may <a href="signin">Login</a> or back to <a href="index">Homepage</a>.</h4>
					';
			} catch (PDOException $e) {
				if ($conn->inTransaction()) {
					$conn->rollBack();
				}
				$output .= '
						<div class="alert alert-danger">
			                <h4><i class="icon fa fa-warning"></i> Error!</h4>
			                ' . $e->getMessage() . '
			            </div>
			            <h4>You may <a href="signup">Signup</a> or back to <a href="index">Homepage</a>.</h4>
					';
			}
		}
	} else {
		$output .= '
				<div class="alert alert-danger">
	                <h4><i class="icon fa fa-warning"></i> Error!</h4>
	                Cannot activate account. Wrong code.
	            </div>
	            <h4>You may <a href="signup">Signup</a> or back to <a href="index">Homepage</a>.</h4>
			';
	}

	$pdo->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $pageTitle = "Bolakaz | Account Activation"; include "head.php"; ?>
</head>

<body class="hold-transition skin-blue layout-top-nav">

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>
	<div class="container">



		<div class="content-wrapper">
			<div class="container">

				<!-- Main content -->
				<section class="content">
					<div class="row">
						<div class="col-sm-9">
							<?php echo $output; ?>
						</div>
						<div class="col-sm-3">

						</div>
					</div>
				</section>

			</div>
		</div>

	</div>
	<?php include 'footer.php'; ?>

</body>

</html>
