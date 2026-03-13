<?php
require_once __DIR__ . '/CreateDb.php';

class Rating
{
	private $itemUsersTable = 'users';
	private $itemTable = 'products';
	private $itemV2Table = 'products_v2';
	private $itemLegacyMapTable = 'product_legacy_map';
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

	public function getItemRating($itemId, $productV2Id = null)
	{
		$lookup = $this->getReviewLookup((int)$itemId, $productV2Id !== null ? (int)$productV2Id : null);
		if (empty($lookup['conditions'])) {
			return [];
		}

		$stmt = $this->dbConnect->prepare("
			SELECT r.ratingId, r.itemId, r.userId, u.firstname, u.lastname, u.photo, r.ratingNumber, r.title, r.comments, r.created, r.modified
			FROM {$this->itemRatingTable} AS r
			LEFT JOIN {$this->itemUsersTable} AS u ON (r.userId = u.id)
			WHERE " . implode(' OR ', $lookup['conditions']) . "
			ORDER BY r.modified DESC, r.created DESC
		");
		$stmt->execute($lookup['params']);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getRatingAverage($itemId, $productV2Id = null)
	{
		$lookup = $this->getReviewLookup((int)$itemId, $productV2Id !== null ? (int)$productV2Id : null);
		if (empty($lookup['conditions'])) {
			return 0.0;
		}

		$stmt = $this->dbConnect->prepare("
			SELECT AVG(ratingNumber) AS avg_rating
			FROM {$this->itemRatingTable} AS r
			WHERE " . implode(' OR ', $lookup['conditions']) . "
		");
		$stmt->execute($lookup['params']);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return isset($row['avg_rating']) ? (float)$row['avg_rating'] : 0.0;
	}

	public function saveRating(array $post, int $userID): array
	{
		$itemId = (int)($post['itemid'] ?? 0);
		$productV2Id = (int)($post['product_v2_id'] ?? 0);
		$rating = (int)($post['rating'] ?? 0);
		$title = trim((string)($post['title'] ?? ''));
		$comment = trim((string)($post['comment'] ?? ''));

		$errors = [];
		if ($itemId <= 0 && $productV2Id <= 0) {
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

		$target = $this->resolveRatingTarget($itemId, $productV2Id);
		if (!($target['success'] ?? false)) {
			return [
				'success' => false,
				'message' => (string)($target['message'] ?? 'Selected product does not exist.'),
				'errors' => $target['errors'] ?? ['itemid' => 'Product was not found.'],
			];
		}

		$resolvedItemId = $target['item_id'];
		$resolvedProductV2Id = $target['product_v2_id'];
		$hasProductV2Column = $this->columnExists($this->itemRatingTable, 'product_v2_id');

		try {
			$this->dbConnect->beginTransaction();

			$existingId = $this->findExistingRatingId($resolvedItemId, $resolvedProductV2Id, $userID);
			$now = date('Y-m-d H:i:s');

			if ($existingId !== null) {
				if ($hasProductV2Column) {
					$updateStmt = $this->dbConnect->prepare("
						UPDATE {$this->itemRatingTable}
						SET itemId = :item_id, product_v2_id = :product_v2_id, ratingNumber = :rating, title = :title, comments = :comments, modified = :modified
						WHERE ratingId = :rating_id
					");
					$updateStmt->execute([
						'item_id' => $resolvedItemId,
						'product_v2_id' => $resolvedProductV2Id,
						'rating' => $rating,
						'title' => $title,
						'comments' => $comment,
						'modified' => $now,
						'rating_id' => $existingId,
					]);
				} else {
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
						'rating_id' => $existingId,
					]);
				}
			} else {
				if ($hasProductV2Column) {
					$insertStmt = $this->dbConnect->prepare("
						INSERT INTO {$this->itemRatingTable} (itemId, product_v2_id, userId, ratingNumber, title, comments, created, modified)
						VALUES (:item_id, :product_v2_id, :user_id, :rating, :title, :comments, :created, :modified)
					");
					$insertStmt->execute([
						'item_id' => $resolvedItemId,
						'product_v2_id' => $resolvedProductV2Id,
						'user_id' => $userID,
						'rating' => $rating,
						'title' => $title,
						'comments' => $comment,
						'created' => $now,
						'modified' => $now,
					]);
				} else {
					$insertStmt = $this->dbConnect->prepare("
						INSERT INTO {$this->itemRatingTable} (itemId, userId, ratingNumber, title, comments, created, modified)
						VALUES (:item_id, :user_id, :rating, :title, :comments, :created, :modified)
					");
					$insertStmt->execute([
						'item_id' => $resolvedItemId,
						'user_id' => $userID,
						'rating' => $rating,
						'title' => $title,
						'comments' => $comment,
						'created' => $now,
						'modified' => $now,
					]);
				}
			}

			$this->dbConnect->commit();

			return [
				'success' => true,
				'message' => $existingId !== null ? 'Your review was updated successfully.' : 'Your review was submitted successfully.',
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

	private function getReviewLookup(int $itemId, ?int $productV2Id): array
	{
		$conditions = [];
		$params = [];

		if ($itemId > 0) {
			$conditions[] = 'r.itemId = :item_id';
			$params['item_id'] = $itemId;
		}

		if ($productV2Id !== null && $productV2Id > 0 && $this->columnExists($this->itemRatingTable, 'product_v2_id')) {
			$conditions[] = 'r.product_v2_id = :product_v2_id';
			$params['product_v2_id'] = $productV2Id;
		}

		return [
			'conditions' => $conditions,
			'params' => $params,
		];
	}

	private function resolveRatingTarget(int $itemId, int $productV2Id): array
	{
		$resolvedItemId = $itemId > 0 ? $itemId : null;
		$resolvedProductV2Id = $productV2Id > 0 ? $productV2Id : null;
		$legacyExists = ($resolvedItemId !== null && $this->productExists($this->itemTable, $resolvedItemId));

		if ($resolvedProductV2Id !== null) {
			if (!$this->tableExists($this->itemV2Table) || !$this->productExists($this->itemV2Table, $resolvedProductV2Id)) {
				return [
					'success' => false,
					'message' => 'Selected product does not exist.',
					'errors' => ['product_v2_id' => 'Product was not found.'],
				];
			}

			$mappedLegacyId = $this->getLegacyIdForV2Product($resolvedProductV2Id);
			if ($resolvedItemId === null && $mappedLegacyId !== null && $this->productExists($this->itemTable, $mappedLegacyId)) {
				$resolvedItemId = $mappedLegacyId;
				$legacyExists = true;
			}
		}

		if ($resolvedItemId !== null && !$legacyExists && $resolvedProductV2Id === null) {
			return [
				'success' => false,
				'message' => 'Selected product does not exist.',
				'errors' => ['itemid' => 'Product was not found.'],
			];
		}

		if ($legacyExists && $resolvedProductV2Id === null) {
			$mappedV2Id = $this->getV2IdForLegacyProduct($resolvedItemId);
			if ($mappedV2Id !== null) {
				$resolvedProductV2Id = $mappedV2Id;
			}
		}

		if ($resolvedProductV2Id !== null && !$this->columnExists($this->itemRatingTable, 'product_v2_id')) {
			if (!$legacyExists) {
				return [
					'success' => false,
					'message' => 'Reviews for this product require the latest review schema update.',
					'errors' => ['product_v2_id' => 'Review schema update pending.'],
				];
			}

			$resolvedProductV2Id = null;
		}

		if (!$legacyExists && $resolvedProductV2Id === null) {
			return [
				'success' => false,
				'message' => 'Selected product does not exist.',
				'errors' => ['itemid' => 'Product was not found.'],
			];
		}

		return [
			'success' => true,
			'item_id' => $legacyExists ? $resolvedItemId : null,
			'product_v2_id' => $resolvedProductV2Id,
		];
	}

	private function findExistingRatingId(?int $itemId, ?int $productV2Id, int $userID): ?int
	{
		$conditions = [];
		$params = [
			'user_id' => $userID,
		];

		if ($itemId !== null && $itemId > 0) {
			$conditions[] = 'itemId = :item_id';
			$params['item_id'] = $itemId;
		}

		if ($productV2Id !== null && $productV2Id > 0 && $this->columnExists($this->itemRatingTable, 'product_v2_id')) {
			$conditions[] = 'product_v2_id = :product_v2_id';
			$params['product_v2_id'] = $productV2Id;
		}

		if (empty($conditions)) {
			return null;
		}

		$stmt = $this->dbConnect->prepare("
			SELECT ratingId
			FROM {$this->itemRatingTable}
			WHERE userId = :user_id
			  AND (" . implode(' OR ', $conditions) . ")
			LIMIT 1
		");
		$stmt->execute($params);
		$ratingId = (int)$stmt->fetchColumn();

		return $ratingId > 0 ? $ratingId : null;
	}

	private function getLegacyIdForV2Product(int $productV2Id): ?int
	{
		if ($productV2Id <= 0 || !$this->tableExists($this->itemLegacyMapTable)) {
			return null;
		}

		$stmt = $this->dbConnect->prepare("
			SELECT legacy_product_id
			FROM {$this->itemLegacyMapTable}
			WHERE product_v2_id = :product_v2_id
			LIMIT 1
		");
		$stmt->execute(['product_v2_id' => $productV2Id]);
		$legacyId = (int)$stmt->fetchColumn();

		return $legacyId > 0 ? $legacyId : null;
	}

	private function getV2IdForLegacyProduct(int $itemId): ?int
	{
		if ($itemId <= 0 || !$this->tableExists($this->itemLegacyMapTable) || !$this->tableExists($this->itemV2Table)) {
			return null;
		}

		$stmt = $this->dbConnect->prepare("
			SELECT product_v2_id
			FROM {$this->itemLegacyMapTable}
			WHERE legacy_product_id = :legacy_product_id
			LIMIT 1
		");
		$stmt->execute(['legacy_product_id' => $itemId]);
		$productV2Id = (int)$stmt->fetchColumn();

		return $productV2Id > 0 ? $productV2Id : null;
	}

	private function productExists(string $table, int $id): bool
	{
		if ($id <= 0 || !$this->tableExists($table)) {
			return false;
		}

		$stmt = $this->dbConnect->prepare("SELECT id FROM {$table} WHERE id = :id LIMIT 1");
		$stmt->execute(['id' => $id]);

		return (bool)$stmt->fetchColumn();
	}

	private function tableExists(string $table): bool
	{
		static $cache = [];

		if (array_key_exists($table, $cache)) {
			return $cache[$table];
		}

		$stmt = $this->dbConnect->prepare("
			SELECT 1
			FROM information_schema.tables
			WHERE table_schema = DATABASE()
			  AND table_name = :table
			LIMIT 1
		");
		$stmt->execute(['table' => $table]);
		$cache[$table] = (bool)$stmt->fetchColumn();

		return $cache[$table];
	}

	private function columnExists(string $table, string $column): bool
	{
		static $cache = [];
		$key = $table . '.' . $column;

		if (array_key_exists($key, $cache)) {
			return $cache[$key];
		}

		$stmt = $this->dbConnect->prepare("
			SELECT 1
			FROM information_schema.columns
			WHERE table_schema = DATABASE()
			  AND table_name = :table
			  AND column_name = :column
			LIMIT 1
		");
		$stmt->execute([
			'table' => $table,
			'column' => $column,
		]);
		$cache[$key] = (bool)$stmt->fetchColumn();

		return $cache[$key];
	}
}
?>
