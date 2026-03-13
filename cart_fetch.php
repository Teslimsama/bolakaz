<?php
include 'session.php';
require_once __DIR__ . '/lib/catalog_v2.php';
$conn = $pdo->open();

$output = ['list' => '', 'count' => 0];
$subtotal = 0.0;

function cart_item_html(array $item): string
{
	$image = app_image_url($item['photo'] ?? '');
	$slug = rawurlencode((string)($item['slug'] ?? ''));
	$name = trim((string)($item['prodname'] ?? 'Product'));
	$category = trim((string)($item['catname'] ?? ''));
	$qty = max(1, (int)($item['quantity'] ?? 1));
	$price = (float)($item['price'] ?? 0);
	$lineTotal = $qty * $price;

	$title = strlen($name) > 56 ? substr($name, 0, 53) . '...' : $name;
	$sub = $category !== '' ? $category : 'Item';

	return ''
		. "<a class='sf-mini-cart-item' href='detail?product=" . e($slug) . "'>"
		. "<img src='" . e($image) . "' class='sf-mini-cart-thumb' width='56' height='56' style='width:56px;height:56px;min-width:56px;max-width:56px;min-height:56px;max-height:56px;object-fit:cover;display:block;flex:0 0 56px;' alt='Product image' onerror=\"this.onerror=null;this.src='" . e(app_placeholder_image()) . "';\">"
		. "<div class='sf-mini-cart-meta'>"
		. "<p class='sf-mini-cart-name'>" . e($title) . "</p>"
		. "<p class='sf-mini-cart-sub'>" . e($sub) . "</p>"
		. "</div>"
		. "<div class='sf-mini-cart-totals'>"
		. "<small>&times; " . $qty . "</small>"
		. "<strong>" . app_money($lineTotal) . "</strong>"
		. "</div>"
		. "</a>";
}

function cart_variant_label(PDO $conn, int $variantId, string $size, string $color): string
{
	if ($variantId > 0 && catalog_v2_ready($conn)) {
		$stmt = $conn->prepare("SELECT a.label, av.value
			FROM variant_option_values vov
			INNER JOIN attributes a ON a.id = vov.attribute_id
			INNER JOIN attribute_values av ON av.id = vov.attribute_value_id
			WHERE vov.variant_id = :variant_id
			ORDER BY a.label ASC");
		$stmt->execute(['variant_id' => $variantId]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$parts = [];
		foreach ($rows as $row) {
			$parts[] = trim((string)$row['label']) . ': ' . trim((string)$row['value']);
		}
		return implode(' | ', $parts);
	}

	$legacy = trim($size . ' ' . $color);
	return $legacy;
}

if (isset($_SESSION['user'])) {
	try {
		$stmt = $conn->prepare("SELECT products.slug, products.photo, products.price, products.name AS prodname, category.name AS catname, cart.quantity, cart.variant_id, cart.size, cart.color
			FROM cart
			LEFT JOIN products ON products.id = cart.product_id
			LEFT JOIN category ON category.id = products.category_id
			WHERE cart.user_id = :user_id
			ORDER BY cart.id DESC");
		$stmt->execute(['user_id' => $user['id']]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($rows as $row) {
			if (catalog_v2_ready($conn) && (int)($row['variant_id'] ?? 0) > 0) {
				$vpStmt = $conn->prepare("SELECT price FROM product_variants WHERE id = :id LIMIT 1");
				$vpStmt->execute(['id' => (int)$row['variant_id']]);
				$variantPrice = $vpStmt->fetchColumn();
				if ($variantPrice !== false) {
					$row['price'] = (float)$variantPrice;
				}
				$variantLabel = cart_variant_label($conn, (int)$row['variant_id'], '', '');
				if ($variantLabel !== '') {
					$row['catname'] = $variantLabel;
				}
			}
			$output['count']++;
			$subtotal += max(1, (int)($row['quantity'] ?? 1)) * (float)($row['price'] ?? 0);
			$output['list'] .= cart_item_html($row);
		}
	} catch (PDOException $e) {
		$output['list'] = "<div class='sf-mini-cart-empty'>Unable to load cart right now.</div>";
	}
} else {
	$sessionCart = $_SESSION['cart'] ?? [];
	if (is_array($sessionCart)) {
		foreach ($sessionCart as $row) {
			$productId = (int)($row['productid'] ?? 0);
			$qty = max(1, (int)($row['quantity'] ?? 1));
			if ($productId <= 0) {
				continue;
			}

			$stmt = $conn->prepare("SELECT products.slug, products.photo, products.price, products.name AS prodname, category.name AS catname
				FROM products
				LEFT JOIN category ON category.id = products.category_id
				WHERE products.id = :id
				LIMIT 1");
			$stmt->execute(['id' => $productId]);
			$product = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$product) {
				continue;
			}

			$product['quantity'] = $qty;
			$product['variant_id'] = (int)($row['variant_id'] ?? 0);
			$product['size'] = trim((string)($row['size'] ?? ''));
			$product['color'] = trim((string)($row['color'] ?? ''));
			if (catalog_v2_ready($conn) && (int)$product['variant_id'] > 0) {
				$vpStmt = $conn->prepare("SELECT price FROM product_variants WHERE id = :id LIMIT 1");
				$vpStmt->execute(['id' => (int)$product['variant_id']]);
				$variantPrice = $vpStmt->fetchColumn();
				if ($variantPrice !== false) {
					$product['price'] = (float)$variantPrice;
				}
				$variantLabel = cart_variant_label($conn, (int)$product['variant_id'], '', '');
				if ($variantLabel !== '') {
					$product['catname'] = $variantLabel;
				}
			}
			$output['count']++;
			$subtotal += $qty * (float)($product['price'] ?? 0);
			$output['list'] .= cart_item_html($product);
		}
	}
}

if ($output['count'] < 1) {
	$output['list'] = "<div class='sf-mini-cart-empty'>Your cart is empty.</div>";
} else {
	$output['list'] .= "<div class='sf-mini-cart-summary'><span>Subtotal</span><strong>" . app_money($subtotal) . "</strong></div>";
}

$pdo->close();
echo json_encode($output);
