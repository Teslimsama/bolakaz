<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';

	if (class_exists('Dotenv\\Dotenv')) {
		$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
		$dotenv->safeLoad();
	}
}

if (file_exists(__DIR__ . '/bootstrap/error_handler.php')) {
	require_once __DIR__ . '/bootstrap/error_handler.php';
	if (function_exists('app_register_error_handlers')) {
		app_register_error_handlers();
	}
}

$appTimezone = trim((string)($_ENV['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE') ?? 'Africa/Lagos'));
if (!in_array($appTimezone, timezone_identifiers_list(), true)) {
	$appTimezone = 'Africa/Lagos';
}
date_default_timezone_set($appTimezone);

class Database
{

	private $server;
	private $username;
	private $password;
	private $options  = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,);
	protected $conn;

	public function __construct()
	{
		$host = $this->env('DB_HOST', '127.0.0.1');
		$port = $this->env('DB_PORT', '3306');
		$name = $this->env('DB_NAME', 'bolakaz');
		$charset = $this->env('DB_CHARSET', 'utf8mb4');

		$this->server = sprintf(
			'mysql:host=%s;port=%s;dbname=%s;charset=%s',
			$host,
			$port,
			$name,
			$charset
		);
		$this->username = $this->env('DB_USER', 'root');
		$this->password = $this->env('DB_PASS', '');
	}

	public function open()
	{
		try {
			$this->conn = new PDO($this->server, $this->username, $this->password, $this->options);
			$offset = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P');
			$this->conn->exec("SET time_zone = '" . $offset . "'");
			return $this->conn;
		} catch (PDOException $e) {
			throw $e;
		}
	}

	public function close()
	{
		$this->conn = null;
	}

	private function env($key, $default = '')
	{
		$value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
		if ($value === false || $value === null || $value === '') {
			return $default;
		}

		return trim((string) $value);
	}
}

$pdo = new Database();
$conn = $pdo->open();
?>
