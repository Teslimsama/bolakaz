<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';

	if (class_exists('Dotenv\\Dotenv')) {
		$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
		$dotenv->safeLoad();
	}
}

class Database
{

	private $server = "mysql:host=localhost;dbname=bolakaz";
	private $username = "root";
	private $password = "";
	private $options  = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,);
	protected $conn;

	public function open()
	{
		try {
			$this->conn = new PDO($this->server, $this->username, $this->password, $this->options);
			return $this->conn;
		} catch (PDOException $e) {
			echo "There is some problem in connection: " . $e->getMessage();
		}
	}

	public function close()
	{
		$this->conn = null;
	}
}

$pdo = new Database();
$conn = $pdo->open();
?>
