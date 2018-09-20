
<?php
$data = json_decode(file_get_contents("php://input"));
//print_r($_REQUEST);
include 'class.user.php';
require_once 'constants.php';
$user_home = new USER();


    // receiving the post params
	$firstname =$data->firstname;
	$lastname = $data->lastname;
    $email    =$data->email;
    $password =$data->password;
	$cpassword =$data->cpassword;
	$username =$data->username;
	$mobile =$data->mobile;
    
   
    // check if user is already existed with the same email
    if ($user_home->isUserExisted($email)) {
        // user already existed
        $data["error"] =TRUE;
		$data["error_msg"] = "email already exist !";
		//header('Content-Type: application/json');
		//echo json_encode($data);
		//event_log(json_encode($data));
    } else {
        // create a new user
        $user = $user_home->storeUser($firstname, $lastname, $email,$password,$cpassword,$username,$mobile);
        if ($user) {
            // user stored successfully
            $data["error"] = FALSE;
            $data["uid"] = $user["unique_id"];
            $data["name"] = $user["name"];
            $data["email"] = $user["email"];
            $data["created_at"] = $user["created_at"];
            $data["updated_at"] = $user["updated_at"];
            $data["phone"] = $user["phone"];
			//$data = array('status'=>'success');
           // header('Content-Type: application/json');
	       // echo json_encode($data);
		//event_log(json_encode($data));
			$id=$user["id"];
			$code = $user["unique_id"];
			$fname = $user["name"];
			$key = base64_encode($id);
			$message = "					
						Hello $fname,
						<br /><br />
						Welcome to NatureSnap!<br/>
						To complete your registration  please , just click following link<br/>
						<br /><br />
						<a href=".CURRENT_URL."verify.php?id=$key&code=$code>Click HERE to Activate :)</a>
						<br /><br />
						Thanks,";
						
			$subject = "Confirm Registration";
				//echo"sdgshag";		
			$user_home->send_mail($email,$subject,$message);
			//echo"gftrte";
            //echo json_encode($response);
        } else {
            // user failed to store
			$data["error"] =TRUE;
			$data["error_msg"] = "Unknown error occurred in registration!";
			//header('Content-Type: application/json');
			//echo json_encode($data);
			//event_log(json_encode($data));
  
        }
    }

?>