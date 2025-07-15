<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../css/login.css">
    <title>Login</title>
</head>


<body>
    <!-- Filler navbar -->
    <header>
            
    </header>
    <div id="wrapper">
        <div id="wrapper-left">
            <div class="title">
                <h1>
                    Log In
                </h1>
            </div>
            
            <main>
                <form method="post" action="login.php">
                    <div class="input-group">
                        <label>Username:
                            <input type="text" name="username" >
                        </label>
                    </div>
                    <div class="input-group">
                        <button type="submit" class="btn" name="login_user">Log In</button>
                    </div>
                </form>
            </main>
        
        </div>

        <div id="wrapper-right">
            <div class="title">
                <h1>
                    Sign Up
                </h1>
            </div>
            
            <main>
                <form method="post" action="login.php">
                    <div class="input-group">
                        <label>Username:
                            <input type="text" name="username" >
                        </label>
                    </div>

                    <div class="input-group">
                        <label>Gender:
                            <select name="gender" id="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </label>
                    </div>

                    <div class="input-group">
                        <label>Age:
                            <input type="number" min="1" name="age">
                        </label>
                    </div>

                    <div class="input-group">
                        <button type="submit" class="btn" name="signup_user">Sign Up</button>
                    </div>
                </form>
            </main>
        </div>
    </div>
</body>

</html>

<?php
    session_start();
    include('../db_config.php');
    
    //User signup handler
	if(isset($_POST['signup_user'])) {
        
		$user_name = $_POST['username'];
		$user_gender = $_POST['gender'];
		$user_age = $_POST['age'];
		$message = "";
        
		if(empty($user_name) || empty($user_gender) || empty($user_age)) {
			$message = "Please fill in all of the fields";
		} else {
            
			//SQL to check if username exists already
			$checkUserExistsSQL = "SELECT COUNT(*) FROM UserInfo WHERE Username = :user_name";
			$checkUserExistsStmt = oci_parse($c, $checkUserExistsSQL);

			oci_bind_by_name($checkUserExistsStmt, ":user_name", $user_name);

			oci_execute($checkUserExistsStmt);
			$userNameExists = oci_fetch_row($checkUserExistsStmt);

			if($userNameExists[0] > 0) {
				$message = "Error! Username already exists.";
			} else {
				$insertNewUserSQL = "INSERT INTO UserInfo (UserID, Username, Gender, Age) VALUES (:user_id, :user_name, :user_gender, :user_age)";
				$insertNewUserStmt = oci_parse($c, $insertNewUserSQL);

                oci_bind_by_name($insertNewUserStmt, ":user_id", rand(1, 100000000));
				oci_bind_by_name($insertNewUserStmt, ":user_name", $user_name);
				oci_bind_by_name($insertNewUserStmt, ":user_gender", $user_gender);
				oci_bind_by_name($insertNewUserStmt, ":user_age", $user_age);

				if(oci_execute($insertNewUserStmt)) {
					$message = "User Registration Completed";
					$_SESSION['username'] = $user_name;
					header('location: ../views/user.php');
				} else {
					$message = "Failed to register the user";
				}
			}

			oci_free_statement($checkUserExistsStmt);
            
		}


		echo "<script>alert('$message');</script>";
        
	}

	//User login handler
	if(isset($_POST['login_user'])) {
        $user_name = trim($_POST['username']);
		$message = "";
		if(empty($user_name)) {
			$message = "Cannot login with empty username";
		} else {
			$checkUserExistsSQL = "SELECT COUNT(*) FROM UserInfo WHERE Username = :user_name";
			$checkUserExistsStmt = oci_parse($c, $checkUserExistsSQL);

			oci_bind_by_name($checkUserExistsStmt, ":user_name", $user_name);

			oci_execute($checkUserExistsStmt);
			$userNameExists = oci_fetch_row($checkUserExistsStmt);

			if($userNameExists[0] > 0) {
				$message = "Login successful";
				$_SESSION['username'] = $user_name;
				header('location: ../views/user.php');
			} else {
				$message = "Username does not exist";
			}

			oci_free_statement($checkUserExistsStmt);
		}

		echo "<script>alert('$message');</script>";
    }
?>
