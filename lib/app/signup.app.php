<?php
include('../../alert.message.php');
require_once('../../CreateDb.php');
// Initialize the session

//COLLECT DATA FROM FORM
$first_name = $_POST['firstname'];
$last_name = $_POST['lastname'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$username = $_POST['username'];
$referral = $_POST['referral'];
$gender = $_POST['gender'];
$state = $_POST['state'];
$dob = $_POST['dob'];
$acct_type = 'student';

$now = new DateTime();
$timestamp = $now->getTimestamp();



// //INSERT RECORDS INTO DB
// $sql = "INSERT INTO workers (firstname,lastname,phone,state,school,gender,password,dob,reference,email,timestamp) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?);";

$stmt = mysqli_stmt_init($db_connect);




 
// Define variables and initialize with empty values

$username_err = $password_err = $confirm_password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err =  $_SESSION['error'] =  "Please enter a username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))){
        $username_err = $_SESSION['error'] = "Username can only contain letters, numbers, and underscores.";
    } else{
        // Prepare a select statement
        $sql = "SELECT * FROM workers WHERE username = ?";
        
        if($stmt = mysqli_prepare($db_connect, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err =  $_SESSION['error'] =  "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = $_SESSION['error'] = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err =  $_SESSION['error'] =  "Password must have atleast 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["conpassword"]))){
        $confirm_password_err =  $_SESSION['error'] =  "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["conpassword"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err =  $_SESSION['error'] = "Password did not match.";
            header("location: ../../signup");
        }
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO shoppers (firstname,lastname,phone,email,username,state,password,dob,reference,gender,timestamp) VALUES(?,?,?,?,?,?,?,?,?,?,?);";
         
        if($stmt = mysqli_prepare($db_connect, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt,'ssssssssssi',$first_name,$last_name,$phone,$email,$username,$state,$password,$dob,$referral,$gender,$timestamp);
            
            // Set parameters
            $param_username = $username;
            $password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to login page
                $_SESSION['success'] = "Your Account Have Been Created";
                header("location: ../../signin");
            } else{
                $_SESSION['error'] ="Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($db_connect);
}