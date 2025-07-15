<?php 
    session_start();
    include('../db_config.php');

    class pointsClass {
        public $id;
        public $username;
        public $measurement;
        public $groupclass;
        public $total;
    }

    //get user points
    $getPointsSQL = "SELECT
                        u.UserID,
                        u.Username,
                        NVL(measurement.MeasurementPoints, 0) AS MeasurementPoints,
                        NVL(groupclass.GroupClassPoints, 0) AS GroupClassPoints,
                        COALESCE(MeasurementPoints, 0) + COALESCE(GroupClassPoints, 0) AS TotalPoints 
                    FROM UserInfo u
                    LEFT JOIN (
                        SELECT
                            tm.UserID,
                            COUNT(tm.MDate) AS MeasurementPoints
                        FROM TrackMeasurement tm
                        WHERE tm.MDate >= ADD_MONTHS(TRUNC(SYSDATE, 'MONTH'), -3)
                        GROUP BY tm.UserID
                    ) measurement ON u.UserID = measurement.UserID
                    LEFT JOIN (
                        SELECT
                            u.UserID,
                            SUM(CASE WHEN gc.WID IS NOT NULL THEN 10 ELSE 0 END) AS GroupClassPoints
                        FROM UserInfo u
                        LEFT JOIN DoesWorkout dw ON u.UserID = dw.UserID
                        LEFT JOIN Workout w ON dw.WID = w.WID AND w.WorkoutDate >= ADD_MONTHS(TRUNC(SYSDATE, 'MONTH'), -3)
                        LEFT JOIN GroupClass gc ON w.WID = gc.WID
                        GROUP BY u.UserID
                    ) groupclass ON u.UserID = groupclass.UserID
                    WHERE u.Username != 'admin' 
                    ORDER BY TotalPoints DESC";
    
    
    $getPointsStmt = oci_parse($c, $getPointsSQL);
    $userPoints = array();
    $message = "";
                 
    if(oci_execute($getPointsStmt)) {
        while($row = oci_fetch_assoc($getPointsStmt)) {
            $points = new pointsClass();
            $points->id = $row['USERID'];
            $points->username = $row['USERNAME'];
            $points->measurement = $row['MEASUREMENTPOINTS'];
            $points->groupclass = $row['GROUPCLASSPOINTS'];
            $points->total = $row['TOTALPOINTS'];
            $userPoints[] = $points;
        }
        
    } else {
        $error = oci_error($getPointsStmt);
        $message = "Issue retrieving user data: " . $error['message'];
    }
    
    
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../css/leaderboard.css">
    <title>Leaderboard</title>
</head>

<body>
    <?php include('../views/header.php'); ?>
    <div id="wrapper">
        <div class="title">
            <h1>Leaderboard</h1>
        </div>

        <div class="explanation">
            <p> Submitted measurement in last 3 months = 1 point </p>
            <p> Participated in class in last 3 months = 10 points </p>
        </div>

        <div class="placement">
            <table class="first_place">
                <thead>
                    <tr>
                        <th>
                            ID
                        </th>
                        <th>
                            Username
                        </th>
                        <th>
                            Measurement Points
                        </th>
                        <th>
                            Class Points
                        </th>
                        <th>
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php
                            if(!empty($userPoints)) {
                                echo "<td>{$userPoints[0]->id}</td>";
                                echo "<td>{$userPoints[0]->username}</td>";
                                echo "<td>{$userPoints[0]->measurement}</td>";
                                echo "<td>{$userPoints[0]->groupclass}</td>";
                                echo "<td>{$userPoints[0]->total}</td>";
                            }
                        ?>
                    </tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>
                            ID
                        </th>
                        <th>
                            Username
                        </th>
                        <th>
                            Measurement Points
                        </th>
                        <th>
                            Class Points
                        </th>
                        <th>
                            Total
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                        for($i = 1; $i < count($userPoints); $i++) {
                            if($i == 1) {
                                echo "<tr class='second_place'>";
                            } elseif($i == 2) {
                                echo "<tr class='third_place'>";
                            } else {
                                echo "<tr>";
                            }
                            echo "<td>{$userPoints[$i]->id}</td>";
                            echo "<td>{$userPoints[$i]->username}</td>";
                            echo "<td>{$userPoints[$i]->measurement}</td>";
                            echo "<td>{$userPoints[$i]->groupclass}</td>";
                            echo "<td>{$userPoints[$i]->total}</td>";
                            echo "</tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>