<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/sync.php';
	require_once __DIR__ . '/../lib/customer_accounts.php';

	if(isset($_POST['edit'])){
		$id = (int)($_POST['id'] ?? 0);
		$fullName = trim((string)($_POST['full_name'] ?? ''));
		$email = trim((string)($_POST['email'] ?? ''));
		$address = trim((string)($_POST['address'] ?? ''));
		$contact = trim((string)($_POST['contact'] ?? ''));

		if($id <= 0 || $fullName === ''){
			$_SESSION['error'] = 'Invalid customer details provided';
			header('location: users.php');
			exit;
		}

		$conn = $pdo->open();
		$stmt = $conn->prepare("SELECT * FROM users WHERE id=:id");
		$stmt->execute(['id'=>$id]);
		$row = $stmt->fetch();
		if(!$row || (int)($row['type'] ?? 0) !== 0){
			$_SESSION['error'] = 'Customer not found';
			$pdo->close();
			header('location: users.php');
			exit;
		}

		[$firstname, $lastname] = app_customer_split_name($fullName);
		if ($firstname === '') {
			$_SESSION['error'] = 'Customer name is required';
			$pdo->close();
			header('location: users.php');
			exit;
		}

		$currentState = app_customer_row_state($conn, $row);
		$currentHasRealEmail = app_customer_has_real_email($row);

		if ($email === '') {
			if ($currentHasRealEmail && $currentState === 'active') {
				$_SESSION['error'] = 'Login-enabled customers must keep a real email address.';
				$pdo->close();
				header('location: users.php');
				exit;
			}

			$emailPayload = [
				'email' => app_customer_is_placeholder_email($row['email'] ?? '', isset($row['is_placeholder_email']) ? (int) $row['is_placeholder_email'] : null)
					? (string) $row['email']
					: app_customer_generate_placeholder_email((string) ($row['uuid'] ?? '')),
				'is_placeholder_email' => 1,
			];
		} else {
			$emailPayload = app_customer_build_email_payload($conn, $email, (string) ($row['uuid'] ?? ''), $id);
		}

		try{
			$conn->beginTransaction();
			$fields = [
				'email = :email',
				'firstname = :firstname',
				'lastname = :lastname',
				'address = :address',
				'phone = :contact',
			];
			$params = [
				'email' => $emailPayload['email'],
				'firstname' => $firstname,
				'lastname' => $lastname,
				'address' => $address,
				'contact' => $contact,
				'id' => $id,
			];
			if (app_customer_db_has_column($conn, 'users', 'is_placeholder_email')) {
				$fields[] = 'is_placeholder_email = :is_placeholder_email';
				$params['is_placeholder_email'] = (int) $emailPayload['is_placeholder_email'];
			}
			if (app_customer_db_has_column($conn, 'users', 'account_state')) {
				$nextState = $currentState;
				if ((int) $emailPayload['is_placeholder_email'] === 1) {
					$nextState = 'incomplete';
				}
				$fields[] = 'account_state = :account_state';
				$params['account_state'] = $nextState;
			}

			$stmt = $conn->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id=:id");
			$stmt->execute($params);
			sync_enqueue_or_fail($conn, 'users', $id);
			$conn->commit();
			$_SESSION['success'] = 'Customer updated successfully';

		}
		catch(Throwable $e){
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			$_SESSION['error'] = $e->getMessage();
		}
		

		$pdo->close();
	}
	else{
		$_SESSION['error'] = 'Fill up edit user form first';
	}

	header('location: users.php');
