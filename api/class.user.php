<?php
//require_once 'constants.php';
require_once 'dbconfig.php';
include 'way2sms-api.php';
class USER
{	

	private $conn;
	
	public function __construct(){	
		$database = new Database();
		$db = $database->dbConnection();
		$this->conn = $db;
    }
	public function runQuery($sql){
		$stmt = $this->conn->prepare($sql);
		return $stmt;
	}
	public function lasdID(){
		$stmt = $this->conn->lastInsertId();
		return $stmt;
	}
	public function add_activity($user_id,$activity){
	  try
	  {
	   $sql = "INSERT INTO `activities`(`user_ID`,`activity`) VALUES ('$user_id','$activity')";
	   $stmt = $this->conn->prepare($sql);
	   $stmt->execute();
	  }
	  catch(PDOException $ex)
	  {
	   echo $ex->getMessage();
	  }
	 }
	public function storeUser($name, $email, $password,$phone) {
        $uuid = uniqid('', true);
        $hash = $this->hashSSHA($password);
        $encrypted_password = $hash["encrypted"]; // encrypted password
        $salt = $hash["salt"]; // salt
		$sql="INSERT INTO users(unique_id, name, email, encrypted_password, salt,  phone, created_at) 
		VALUES('$uuid','$name', '$email','$encrypted_password' ,  '$salt','$phone',  NOW())";
		
        $stmt = $this->conn->prepare($sql);
         $result = $stmt->execute();
		 //$sql;
        //$stmt->close();
		
        // check for successful store
        if ($result) {
		//echo "fhbhj";
		    $sql1="SELECT * FROM users WHERE email = '$email'";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->execute();
			// $sql1;
            $user = $stmt1->fetch(PDO::FETCH_ASSOC);
            return $user;
        } else {
            return false;
        }
    }
	# logging
	/*
	[2017-03-20 3:35:43] [INFO] [file.php] Here we are
	[2017-03-20 3:35:43] [ERROR] [file.php] Not good
	[2017-03-20 3:35:43] [DEBUG] [file.php] Regex empty

	mylog ('hallo') -> INFO
	mylog ('fail', 'e') -> ERROR
	mylog ('next', 'd') -> DEBUG
	mylog ('next', 'd', 'debug.log') -> DEBUG file debug.log
	*/
	public function getEventlog(){
		return "elite".date("[Y-m-d]")."log";
    }

	
	public function event_log($text, $level='i', $file='logs') {
		switch (strtolower($level)) {
			case 'e':
			case 'error':
				$level='ERROR';
				break;
			case 'i':
			case 'info':
				$level='INFO';
				break;
			case 'd':
			case 'debug':
				$level='DEBUG';
				break;
			default:
				$level='INFO';
		}
		error_log(date("[Y-m-d H:i:s]")."\t[".$level."]\t[".basename(__FILE__)."]\t".$text."\r\n", 3, $file);
    }
	public function login($email,$upass){
	 try
	 {
		$sql = "SELECT * FROM `users` WHERE email= '$email'";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		//echo $sql;
		$userRow=$stmt->fetch(PDO::FETCH_ASSOC);
		if($stmt->rowCount() == 1)
		{
			$salt = $userRow['salt'];
			$encrypted_password = $userRow['encrypted_password'];
			$hash1=base64_encode(sha1($upass));
			$hash = $this->checkhashSSHA($salt, $upass);
			if ($encrypted_password == $hash)
			{ 
		//echo "hii";
				$_SESSION['userSession'] = $userRow['id'];      
				$_SESSION['userID'] = trim($userRow['id']);
				$_SESSION['unique_ID'] = $userRow['unique_id'];
				$_SESSION['userEmail'] = $userRow['email'];
				$_SESSION['name'] = $userRow['name'];
				$_SESSION['timestamp'] = time();
				return true;
			}
			else
			{
				
			}
		}
		else
		{
			
		}  
	  }
	  catch(PDOException $ex)
	  {
	   //echo $ex->getMessage();
	  }
	 }
	 
	public function get_email($id){
        $sql = "SELECT * FROM `users` WHERE `id` = '$id'";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$email = $row['email'];
		return  $email;
    }
    public function get_id($email){
        $sql = "SELECT * FROM `users` WHERE `email` = '$email'";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$id = $row['id'];
		//echo $id;
		return  $id;
    }
	public function storeEvent($lat,$lng,$ip){
	
	    $stmt = $this->conn->prepare("INSERT INTO `event_log`(`latittude`, `logitude`,`user_ip`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sss", $lat, $lng, $ip);
        $result = $stmt->execute();
        $stmt->close();
	
	}
    public function getUserByEmailAndPassword($email, $password) {
      $sql="SELECT * FROM users WHERE email = '$email'";
	 
      $stmt = $this->conn->prepare($sql);
		$stmt->execute();
		
	 //echo $sql;
		 if ($stmt->execute()) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
			//print_r($user);
           // $stmt->close();

            // verifying user password
             $salt = $user['salt'];
             $encrypted_password = $user['encrypted_password'];
            $userstatus = $user['userStatus'];
			//echo"-";
           $hash = $this->checkhashSSHA($salt, $password);
            // check for password equality
            if ($encrypted_password == $hash) {
				//echo"hjghj";
                // user authentication details are correct
            if ($userstatus == 'Y') {
                // user authentication details are correct
                return $user;
            }
			}
        } else {
            return NULL;
        }
    }
    public function isUserExisted($email) {
	 $sql="SELECT email from users WHERE email = '$email'";
        $stmt = $this->conn->prepare($sql);

       
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
		
            return true;
        } else {
            // user not existed
            //$stmt->close();
            return false;
        }
    }
    public function checkForSubscribe($userid) {
		$date = date("Y-m-d");
		$sql = "SELECT * from `subscribers` WHERE `user_ID` = '$userid' AND `expiry_date` >= '$date'";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
        if($stmt->rowCount() == 1){
			$userRow=$stmt->fetch(PDO::FETCH_ASSOC);
			$result['result'] = "TRUE"; 
			$result['data'] = $userRow; 
		}
		else{
			$result['result'] = "FALSE";
        }
		return $result;
		
    }
    public function hashSSHA($password) {

        $salt = sha1(rand());
        $salt = substr($salt, 0, 10);
        $encrypted = base64_encode(sha1($password . $salt, true) . $salt);
        $hash = array("salt" => $salt, "encrypted" => $encrypted);
        return $hash;
    }
	public function checkhashSSHA($salt, $password) {

        $hash = base64_encode(sha1($password . $salt, true) . $salt);

        return $hash;
    }
	public function is_logged_in(){
		if(isset($_SESSION['userSession']))
		{
			return true;
		}
	}
	public function redirect($url){
		$link = "<script>window.location.replace('$url');</script>";
		echo $link ;
	}
	public function openinwindow($url){
		$link = "<script>window.open('$url'); </script>";
		echo $link ;
	}
	public function alertmessage($msg){
		$link = "<script>alert('$msg'); </script>";
		echo $link ;
	}
	public function redirectwithjava($url){
		$link = "<script>window.location.replace('$url');</script>";
		echo $link ;
	}
	public function logout(){
		session_destroy();
		$_SESSION['userSession'] = false;
	}
	public function send_mail($email,$subject,$message){						
		//require_once('mailer/class.phpmailer.php');
		require_once('phpmailer/class.phpmailer.php');
		$mail = new PHPMailer();
		$mail->IsSMTP(); 
		$mail->SMTPDebug  = 0;                     
		$mail->SMTPAuth   = true;                  
		//$mail->SMTPSecure = "ssl";                 
		//$mail->Host       = "smtp.gmail.com";      
		//$mail->Port       = 465;  
		//$mail->SetLanguage("en", 'includes/phpMailer/language/');		
		$mail->AddAddress($email);
		//$mail->Username="ManoharPV@gmail.com";  // User User Email
		//$mail->Password="xxxxxx";            // Password
		$mail->SetFrom('elitecap@dinkhoo.com','NatureSnap');  // Email
		$mail->AddReplyTo("elitecap@dinkhoo.com","NatureSnap");  // email
		$mail->Subject    = $subject;
		$mail->MsgHTML($message);
		$mail->Send();
	}	
	function sendactive_mail($send,$subject,$message,$uploadfile){						
		//require_once('mailer/class.phpmailer.php');
		require_once('member/phpmailer/class.phpmailer.php');
		if (array_key_exists('userfile', $_FILES)) {
		$mail = new PHPMailer();
		$mail->IsSMTP(); 
		$mail->SMTPDebug  = 0;                     
		$mail->SMTPAuth   = true;                  
		//$mail->SMTPSecure = "ssl";                 
		//$mail->Host       = "smtp.gmail.com";      
		//$mail->Port       = 465;  
		//$mail->SetLanguage("en", 'includes/phpMailer/language/');		
		$mail->AddAddress(trim($send));
		$mail->AddBCC(trim($send));
		//$mail->Username="ManoharPV@gmail.com";  // User User Email
		//$mail->Password="xxxxxx";            // Password
		$mail->SetFrom('elitecap@dinkhoo.com','Medicall');  // Email
		$mail->AddReplyTo("elitecap@dinkhoo.com","Information");  // email
		$mail->Subject    = $subject;
		$mail->MsgHTML($message);
			  for ($ct = 0; $ct < count($_FILES['userfile']['tmp_name']); $ct++) {
        $uploadfile = tempnam(sys_get_temp_dir(), hash('sha256', $_FILES['userfile']['name'][$ct]));
        $filename = $_FILES['userfile']['name'][$ct];
        if (move_uploaded_file($_FILES['userfile']['tmp_name'][$ct], $uploadfile)) {
            $mail->addAttachment($uploadfile, $filename);
        } else {
            $msg .= 'Failed to move file to ' . $uploadfile;
        }
		
    }
		$mail->Send();
		$mail->ClearAddresses();
		$mail->ClearBCCs();
		}

	}
	}
	?>