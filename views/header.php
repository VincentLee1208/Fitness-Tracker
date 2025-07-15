<?php
    session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/header.css">
    <title>Your Website</title>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="../views/user.php">Profile</a></li>
                <li><a href="../views/measurements.php">Measurements</a></li>
                <li><a href="../views/workouts.php">Workouts</a></li>
                <li><a href="../views/nutrition.php">Nutrition</a></li>
                <li><a href="../views/classes.php">Classes</a></li>
                <?php
                if($_SESSION['username'] == 'admin') {
                    echo '<li><a href="../views/tables.php">Tables</a></li>';
                } else {
                    echo '<li><a href="../views/leaderboard.php">Leaderboard</a></li>';
                }
                ?>
                <li id="nav-button">
                    <form action="logout.php" method="post">
                        <input type="submit" name="submit" id="logout-button" value="Logout">
                    </form>
                </li>
            </ul>

        </nav>
    </header>
</body>
</html>

