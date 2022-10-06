<?php
Class Database{
 
	private $server = "mysql:host=localhost;dbname=bolakaz";
	private $username = "root";
	private $password = "";
	private $options  = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,);
	protected $conn;
 	
	public function open(){
 		try{
 			$this->conn = new PDO($this->server, $this->username, $this->password, $this->options);
 			return $this->conn;
 		}
 		catch (PDOException $e){
 			echo "There is some problem in connection: " . $e->getMessage();
 		}

    }
 
	public function close(){
   		$this->conn = null;
 	}
 
}

$pdo = new Database();
$conn = $pdo->open();
 
// $servername = "localhost";
// $database = "bolakaz";
// $username = "root";
// $password = "";

// $db_connect = new mysqli($servername, $username, $password,  $database);

// if ($db_connect->connect_error) {
//    die("connection failed:" . $db_connect->connect_error);
// }
// $sql = "SELECT * FROM producttb";

// $result = mysqli_query($db_connect, $sql);

// if (mysqli_num_rows($result) > 0) {
//    return $result;
// }
?>