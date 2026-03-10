<?php
require_once __DIR__ . '/CreateDb.php';

class Rating
{
	private $itemUsersTable = 'users';
	private $itemTable = 'products';
	private $itemRatingTable = 'item_rating';
	private $dbConnect = null;

	public function __construct()
	{
		$database = new Database();
		$this->dbConnect = $database->open();
	}

	public function getItemList()
	{
		$stmt = $this->dbConnect->prepare("SELECT * FROM {$this->itemTable}");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getItem($itemId)
	{
		$stmt = $this->dbConnect->prepare("SELECT * FROM {$this->itemTable} WHERE id = :id LIMIT 1");
		$stmt->execute(['id' => (int)$itemId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getItemRating($itemId)
	{
		$stmt = $this->dbConnect->prepare("
			SELECT r.ratingId, r.itemId, r.userId, u.firstname, u.lastname, u.photo, r.ratingNumber, r.title, r.comments, r.created, r.modified
			FROM {$this->itemRatingTable} AS r
			LEFT JOIN {$this->itemUsersTable} AS u ON (r.userId = u.id)
			WHERE r.itemId = :item_id
			ORDER BY r.modified DESC, r.created DESC
		");
		$stmt->execute(['item_id' => (int)$itemId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getRatingAverage($itemId)
	{
		$stmt = $this->dbConnect->prepare("
			SELECT AVG(ratingNumber) AS avg_rating
			FROM {$this->itemRatingTable}
			WHERE itemId = :item_id
		");
		$stmt->execute(['item_id' => (int)$itemId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return isset($row['avg_rating']) ? (float)$row['avg_rating'] : 0.0;
	}

	public function saveRating(array $post, int $userID): array
	{
		$itemId = (int)($post['itemid'] ?? 0);
		$rating = (int)($post['rating'] ?? 0);
		$title = trim((string)($post['title'] ?? ''));
		$comment = trim((string)($post['comment'] ?? ''));

		$errors = [];
		if ($itemId <= 0) {
			$errors['itemid'] = 'Invalid product selected.';
		}
		if ($rating < 1 || $rating > 5) {
			$errors['rating'] = 'Rating must be between 1 and 5.';
		}
		if ($title === '') {
			$errors['title'] = 'Review title is required.';
		} elseif (mb_strlen($title) > 120) {
			$errors['title'] = 'Review title must be 120 characters or less.';
		}
		if ($comment === '') {
			$errors['comment'] = 'Review comment is required.';
		} elseif (mb_strlen($comment) > 3000) {
			$errors['comment'] = 'Review comment must be 3000 characters or less.';
		}

		if (!empty($errors)) {
			return [
				'success' => false,
				'message' => 'Please correct the highlighted review fields.',
				'errors' => $errors,
			];
		}

		$productStmt = $this->dbConnect->prepare("SELECT id FROM {$this->itemTable} WHERE id = :id LIMIT 1");
		$productStmt->execute(['id' => $itemId]);
		if (!$productStmt->fetch(PDO::FETCH_ASSOC)) {
			return [
				'success' => false,
				'message' => 'Selected product does not exist.',
				'errors' => ['itemid' => 'Product was not found.'],
			];
		}

		try {
			$this->dbConnect->beginTransaction();

			$existingStmt = $this->dbConnect->prepare("
				SELECT ratingId
				FROM {$this->itemRatingTable}
				WHERE itemId = :item_id AND userId = :user_id
				LIMIT 1
			");
			$existingStmt->execute([
				'item_id' => $itemId,
				'user_id' => $userID,
			]);
			$existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

			$now = date('Y-m-d H:i:s');
			if ($existing) {
				$updateStmt = $this->dbConnect->prepare("
					UPDATE {$this->itemRatingTable}
					SET ratingNumber = :rating, title = :title, comments = :comments, modified = :modified
					WHERE ratingId = :rating_id
				");
				$updateStmt->execute([
					'rating' => $rating,
					'title' => $title,
					'comments' => $comment,
					'modified' => $now,
					'rating_id' => (int)$existing['ratingId'],
				]);
			} else {
				$insertStmt = $this->dbConnect->prepare("
					INSERT INTO {$this->itemRatingTable} (itemId, userId, ratingNumber, title, comments, created, modified)
					VALUES (:item_id, :user_id, :rating, :title, :comments, :created, :modified)
				");
				$insertStmt->execute([
					'item_id' => $itemId,
					'user_id' => $userID,
					'rating' => $rating,
					'title' => $title,
					'comments' => $comment,
					'created' => $now,
					'modified' => $now,
				]);
			}

			$this->dbConnect->commit();

			return [
				'success' => true,
				'message' => $existing ? 'Your review was updated successfully.' : 'Your review was submitted successfully.',
			];
		} catch (Throwable $e) {
			if ($this->dbConnect->inTransaction()) {
				$this->dbConnect->rollBack();
			}
			error_log('Rating save failed: ' . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Unable to save review right now. Please try again.',
				'errors' => [],
			];
		}
	}
}
?>
