<?php
    session_start();
    include('../db_config.php');

    class table {
        public $name;
        public $attributes;
    }

    $tables = array();
    $tableNamesSQL = "SELECT table_name FROM user_tables";
    $tableNamesStmt = oci_parse($c, $tableNamesSQL);

    if(oci_execute($tableNamesStmt)) {
        while($row = oci_fetch_assoc($tableNamesStmt)) {
            $newTable = new table();
            $newTable->name = $row['TABLE_NAME'];

            $attSQL = "SELECT COLUMN_NAME FROM USER_TAB_COLUMNS WHERE TABLE_NAME = :tablename";
            $attStmt = oci_parse($c, $attSQL);

            oci_bind_by_name($attStmt, ":tablename", $row['TABLE_NAME']);
            if(oci_execute($attStmt)) {
                $newTable->attributes = array();
                while($atts = oci_fetch_assoc($attStmt)) {
                    $newTable->attributes[] = $atts['COLUMN_NAME'];
                }

                $tables[] = $newTable;
            } else {
                $error = $oci_error($tableNamesStmt);
                echo $error['message'];
            }
        }
    } else {
        $error = $oci_error($tableNamesStmt);
        echo $error['message'];
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../css/classes.css">
    <title>Tables</title>
</head>

<body>
    <?php include('../views/header.php'); ?>

    <div id="wrapper">
        <h1>See Tables</h1>

        <form method="post" action="tables.php">
            <div class="title">
                <h2>
                    Table Name
                </h2>
            </div>
            <div class="input-group">
                <label for="table-select">Table Select: </label>
                <select id="table-select" name="table-select">
                    <option value="" disabled selected>Select a table name</option>
                    <?php
                        foreach($tables as $table) {
                            echo "<option value={$table->name}>{$table->name}</option>";
                        }
                    ?>
                </select>
            </div>        


            <div class="title">
                <h2>
                    Table Attributes
                </h2>
            </div>
            <div class="input-group">
                <div id="attributes" name="attributes">
                    
                </div>
            </div>
            
            <div class="input-group">
                <button type="submit" class="view-table-btn" id="view-table-btn" name="view-table-btn">View table</button>
            </div>
        </form>

        <table>
            <?php
                //display table  
                if(isset($_POST['view-table-btn'])) {
                    if(!isset($_POST['table-select'])) {
                        $message = "No Table Selected!";
                    } else {
                        $selectedAtts = $_POST['selectedAttributes'];
                        if(empty($selectedAtts)) {
                            $message = "No Attributes Selected!";
                        } else {
                            $message = "Loading Table...";

                            $tableName = $_POST['table-select'];
                            echo "<thead>";
                            echo "<tr>";
                            foreach($selectedAtts as $attribute) {
                                echo "<th>{$attribute}</th>";
                            }
                            echo "</tr>";
                            echo "</thead>";

                            echo "<tbody>";
                            $tableInfoSQL = "SELECT " . implode(", ", $selectedAtts) . " FROM {$tableName}";
                            $tableInfoStmt = oci_parse($c, $tableInfoSQL);

                            if(oci_execute($tableInfoStmt)) {
                                while($row = oci_fetch_assoc($tableInfoStmt)) {
                                    echo "<tr>";
                                    foreach($selectedAtts as $attribute) {
                                        echo "<td>{$row[$attribute]}</td>";
                                    }
                                    echo "</tr>";
                                    
                                }
                            } else {
                                $message = "Failed to load table!";
                            }

                            echo "</tbody>";

                        }
                    }

                    echo "<script>alert('$message');</script>";
                }
            ?>
        </table>

        <h1>See Averages</h1>
        <form method="post" action="tables.php">
            <div class="input-group">
                <label for="att-select">See average </label>
                <select id="att-select" name="att-select">
                    <option value="measurement">measurements submitted</option>
                    <option value="classes">classes attended</option>
                </select>
                per month for each User, in the last
                <input type="number" id="month-select" name="month-select" min=1 max=12 value=1 />
            </div>        
            <div class="input-group">
                <button type="submit" class="btn" name="see_average">View Average</button>
            </div>
        </form>

        <table>
            <?php
                if(isset($_POST['see_average'])) {
                    $att = $_POST['att-select'];
                    $month = $_POST['month-select'];
                    $getAverageSQL = "";

                    if($att == 'measurement') {
                        $getAverageSQL = "SELECT 
                                            u.UserID,
                                            u.Username,
                                            COALESCE(SUM(MeasurementsPerMonth), 0) AS TotalMeasurements, 
                                            ROUND(NVL(SUM(MeasurementsPerMonth) / :numMonths, 0), 2) AS AverageMeasurements 
                                        FROM UserInfo u
                                        LEFT JOIN (
                                            SELECT 
                                                tm.UserID,
                                                COUNT(tm.MDate) AS MeasurementsPerMonth
                                            FROM TrackMeasurement tm 
                                            WHERE tm.MDate >= ADD_MONTHS(TRUNC(SYSDATE, 'MONTH'), -:numMonths) 
                                            GROUP BY tm.UserID
                                        ) measurement ON u.UserID = measurement.UserID 
                                        WHERE u.Username != 'admin' 
                                        GROUP BY u.UserID, u.Username 
                                        ORDER BY AverageMeasurements DESC";
                    } else {
                        $getAverageSQL = "SELECT
                                            u.UserID,
                                            u.Username,
                                            COALESCE(SUM(ClassesPerMonth), 0) AS TotalClasses, 
                                            ROUND(NVL(SUM(ClassesPerMonth) / :numMonths, 0), 2) AS AverageClasses 
                                        FROM UserInfo u 
                                        LEFT JOIN (
                                            SELECT 
                                                u.UserID,
                                                SUM(CASE WHEN gc.WID IS NOT NULL THEN 1 ELSE 0 END) AS ClassesPerMonth
                                            FROM UserInfo u 
                                            LEFT JOIN DoesWorkout dw on u.UserID = dw.UserID 
                                            LEFT JOIN Workout w ON dw.WID = w.WID AND w.WorkoutDate >= ADD_MONTHS(TRUNC(SYSDATE, 'MONTH'), -:numMonths) 
                                            LEFT JOIN GroupClass gc ON w.WID = gc.WID 
                                            GROUP BY u.UserID 
                                        ) groupclass ON u.UserID = groupclass.UserID 
                                        WHERE u.Username != 'admin' 
                                        GROUP BY u.UserID, u.Username 
                                        ORDER BY AverageClasses DESC";
                    }

                    $getAverageStmt = oci_parse($c, $getAverageSQL);

                    
                    oci_bind_by_name($getAverageStmt, ":numMonths", $month);
                    if(oci_execute($getAverageStmt)) {
                        if($att == "measurement") {
                            echo "<h3>Average Number of Measurements Submitted in Last {$month} Months</h3>";
                        } else {
                            echo "<h3>Average Number of Classes Attended in Last {$month} Months</h3>";
                        }
                        $message = "Average retrieved successfully!";
                        echo "<table>";
                        echo "<thead>";
                        echo "<tr>";
                        echo "<th>User ID</th>";
                        echo "<th>User Name</th>";
                        if($att == "measurement") {
                            echo "<th>Total Measurements</th>";
                            echo "<th>Average # Of Measurements</th>";
                        } else {
                            echo "<th>Total Classes</th>";
                            echo "<th>Average # Of Classes</th>";
                        }
                        echo "</tr>";
                        echo "</thead>";

                        echo "<tbody>";
                        while($row = oci_fetch_assoc($getAverageStmt)) {
                            echo "<tr>";
                            echo "<td>{$row['USERID']}</td>";
                            echo "<td>{$row['USERNAME']}</td>";
                            if($att == "measurement") {
                                echo "<td>{$row['TOTALMEASUREMENTS']}</td>";
                                echo "<td>{$row['AVERAGEMEASUREMENTS']}</td>";
                            } else {
                                echo "<td>{$row['TOTALCLASSES']}</td>";
                                echo "<td>{$row['AVERAGECLASSES']}</td>";
                            }
                            
                            echo "</tr>";
                        }
                        echo "</tbody>";
                        echo "</table>";
                    } else {
                        $error = oci_error($getAverageStmt);
                        $message = "Issue retrieving averages: ". $error['message'];
                    }

                    
                    
                    echo "<script>alert('$message');</script>";
                    
                }
            ?>  
        </table>
    </div>

    <script>
        var classSelect = document.getElementById('table-select'); 
        var attributeDiv = document.getElementById('attributes');
        var allTables = <?php echo json_encode($tables); ?>;

        classSelect.addEventListener('change', function() {
            attributeDiv.innerHTML = "";
            var selectedTable = this.value;
            var thisTable = allTables.find(function(table) {
                return table.name == selectedTable;
            });

            if(thisTable) {
                var tableAtts = thisTable.attributes;
                for(var i = 0; i < tableAtts.length; i++) {
                    var checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'selectedAttributes[]';
                    checkbox.value = tableAtts[i];
                    checkbox.checked = true;

                    var label = document.createElement('label');
                    label.appendChild(checkbox);
                    label.appendChild(document.createTextNode(tableAtts[i]));

                    attributeDiv.appendChild(label);
                }
            }
        });


    </script>
</body>

</html>