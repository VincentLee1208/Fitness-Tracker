<?php
    session_start();
    include('../db_config.php');
    
    $name = $_SESSION['username'];
    $getUserInfoSQL = "SELECT * FROM UserInfo WHERE Username = :user_name";
    $getUserInfoStmt = oci_parse($c, $getUserInfoSQL);

    oci_bind_by_name($getUserInfoStmt, ":user_name", $name);

    oci_execute($getUserInfoStmt);
    if($userInfo = oci_fetch_assoc($getUserInfoStmt)) {
        $id = $userInfo['USERID'];
        $_SESSION['id'] = $id;

        $gender = $userInfo['GENDER'];
        $_SESSION['gender'] = $gender;
        
        $age = $userInfo['AGE'];
        $_SESSION['age'] = $age;
    } else {
        echo "Issue connecting to database!";
    }

    oci_free_statement($getUserInfoStmt);

    if(isset($_POST['delete_user'])) {
        $deleteUserSQL = "DELETE FROM UserInfo WHERE UserID = :user_id";
        $deleteUserStmt = oci_parse($c, $deleteUserSQL);

        oci_bind_by_name($deleteUserStmt, ':user_id', $id);
        if(oci_execute($deleteUserStmt)) {
            session_destroy();
        } 

        oci_free_statement($deleteUserStmt);
        echo json_encode(['success' => true, 'message' => 'User successfully deleted']);
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../css/user.css">
    <title>Profile</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <?php include('../views/header.php'); ?>
    <div id="wrapper">

        <div class="page-top">
            <div class="title">
                <h2>
                    User Profile
                </h2>
            </div>

            <div id="user-container">
                <p>UserName: <?php echo $name; ?></p>
                <p>UserId: <?php echo $id; ?></p>
                <p>Gender: <?php echo $gender; ?></p>
                <p>Age: <?php echo $age; ?></p>

                <button id="delete-user-button" onclick="deleteUser()">Delete User</button>

            </div>
        </div>
    </div>
</body>

</html>

<script>
    function deleteUser() {
        if(confirm("Do you want to delete userName:<?php echo $name; ?>?")) {
            console.log("Delete user running");
            $.post('user.php', {delete_user: true }, function(response) {
            }, 'json'); 

            window.location.href = '../views/login.php';
        }
    }
</script>