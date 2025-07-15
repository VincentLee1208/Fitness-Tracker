<?php
session_start();
include('../db_config.php');

if(isset($_POST['add_measurements'])) {
    //Check user inputs are valid
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    $date = $_POST['mdate'];
    $id = $_SESSION['id'];
    $message = "";

    if(empty($weight) || empty($height)) {
        $message = "Please fill in all of the fields";
    } else {
        //Check if measurement for that day already exists
        $checkMeasurementSQL = "SELECT COUNT(*) FROM TrackMeasurement WHERE UserID = :user_id AND MDate = TO_DATE(:m_date, 'YYYY-MM-DD')";
        $checkMeasurmentStmt = oci_parse($c, $checkMeasurementSQL);

        oci_bind_by_name($checkMeasurmentStmt, ":user_id", $id);
        oci_bind_by_name($checkMeasurmentStmt, ":m_date", $date);

        oci_execute($checkMeasurmentStmt);
        $measurementExists = oci_fetch_row($checkMeasurmentStmt);

        if($measurementExists[0] > 0) {
            $updateMeasurementSQL = "UPDATE TrackMeasurement SET Weight = :new_weight, Height = :new_height WHERE UserID = :user_id AND MDate = TO_DATE(:m_date, 'YYYY-MM-DD')";
            $updateMeasurementStmt = oci_parse($c, $updateMeasurementSQL);

            oci_bind_by_name($updateMeasurementStmt, ":new_weight", $weight);
            oci_bind_by_name($updateMeasurementStmt, ":new_height", $height);
            oci_bind_by_name($updateMeasurementStmt, ":user_id", $id);
            oci_bind_by_name($updateMeasurementStmt, ":m_date", $date);

            if(oci_execute($updateMeasurementStmt)) {
                $message = "Measurement updated successfully!";
            } else {
                $message = "Failed to update measurement!";
            }
        } else {
            $insertMeasurementSQL = "INSERT INTO TrackMeasurement (UserID, MDate, Weight, Height) 
            VALUES (:user_id, TO_DATE(:m_date, 'YYYY-MM-DD'), :m_weight, :m_height)";
            $insertMeasurementStmt = oci_parse($c, $insertMeasurementSQL);

            oci_bind_by_name($insertMeasurementStmt, ":user_id", $id);
            oci_bind_by_name($insertMeasurementStmt, ":m_date", $date);
            oci_bind_by_name($insertMeasurementStmt, ":m_weight", $weight);
            oci_bind_by_name($insertMeasurementStmt, ":m_height", $height);

            if(oci_execute($insertMeasurementStmt)) {
                $message = "New measurement added";
            } else {
                $message = "Failed to add new measurement";
            }

            oci_free_statement($insertMeasurementStmt);
        }
    }

    echo "<script>alert('$message');</script>";
}

if(isset($_POST['delete_measurement'])) {
    $date = $_POST['delete_date'];
    $id = $_SESSION['id'];
    $message = "";

    $deleteMeasurementSQL = "DELETE FROM TrackMeasurement WHERE UserID = :user_id AND MDate = :m_date";
    $deleteMeasurementStmt = oci_parse($c, $deleteMeasurementSQL);

    oci_bind_by_name($deleteMeasurementStmt, ":user_id", $id);
    oci_bind_by_name($deleteMeasurementStmt, ":m_date", $date);

    if(oci_execute($deleteMeasurementStmt)) {
        $message = "Measurement successfully deleted!";
    } else {
        $message = "Measurement could not be deleted!";
    }

    oci_free_statement($deleteMeasurementStmt);

    echo "<script>alert('$message');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../css/measurements.css">
    <title>Measurements</title>
</head>

<body>
    <?php include('../views/header.php'); ?>

    <div id="wrapper">

        <div class="page-top">
            <div class="title">
                <h2>
                    Add Measurement
                </h2>
            </div>

            <div id="form-container">
                <form id="addmeasurement" method="post" action="measurements.php">
                    <div class="input-group">
                        <label>Weight(kg):
                            <input type="number" step="0.01" name="weight">
                        </label>
                    </div>

                    <div class="input-group">
                        <label>Height(cm):
                            <input type="number" step="0.01" name="height">
                        </label>
                    </div>

                    <div class="input-group">
                        <label>Date(YYYY-MM-DD):
                            <input type="date" id="mdate" name="mdate" max="<?php echo date('Y-m-d'); ?>">
                        </label>
                    </div>

                    <div class="input-group">
                        <button type="submit" class="btn" name="add_measurements">Add New Measurement</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Display measurements in chart-->
        <div class="page-bottom">
            <div id="wrapper-left">

                <div class="title-bottom">
                    <h1>
                        Filters
                    </h1>
                </div>
                <form id="filters" method="post" actions="measurements.php">
                    <div class="input-group">
                        <label>Start Date:</label>
                        <input type="date" id="startdate" name="startdate" max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="input-group">
                        <label>End Date:</label>
                        <input type="date" id="enddate" name="enddate" max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <!-- Weight Filter -->
                    <div class="input-group">
                        <label>Weight:</label>
                        <select name="weight_logic">
                            <option value="AND" selected>AND</option>
                            <option value="OR">OR</option>
                        </select>
                        <select name="weight_operator">
                            <option value="=">=</option>
                            <option value="!=">!=</option>
                            <option value="<"><</option>
                            <option value="<="><=</option>
                            <option value=">">></option>
                            <option value=">=">>=</option>
                        </select>
                        <input type="number" name="weightFilter" step="0.01">
                        
                    </div>

                    <!-- Height Filter -->
                    <div class="input-group">
                        <label>Height:</label>
                        <select name="height_logic">
                            <option value="AND" selected>AND</option>
                            <option value="OR">OR</option>
                        </select>
                        <select name="height_operator">
                            <option value="=">=</option>
                            <option value="!=">!=</option>
                            <option value="<"><</option>
                            <option value="<="><=</option>
                            <option value=">">></option>
                            <option value=">=">>=</option>
                        </select>
                        <input type="number" name="heightFilter" step="0.01">
                        
                    </div>

                    <div class="input-group">
                        <label>Sort By:</label>
                        <select name="sort" id="sort">
                            <option value="MDate">Date</option>
                            <option value="Weight">Weight</option>
                            <option value="Height">Height</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="view_weight" id="view_label"> Date</label>
                        <input type="checkbox" id="view_date" name = "view_date" value="MDate" checked/>

                        <label for="view_weight" id="view_label"> Weight</label>
                        <input type="checkbox" id="view_weight" name = "view_weight" value="Weight" checked/>

                        <label for="view_height" id="view_label"> Height</label>
                        <input type="checkbox" id="view_height" name = "view_height" value="Height" checked/>

                        <label for="view_agg" id="view_label">See Aggregate</label>
                        <input type="checkbox" id="view_agg" name="view_agg" value="Visible"/>

                        <select name="agg_options" id="agg_options">
                            <option value="Min">Min Weight</option>
                            <option value="Max">Max Weight</option>
                            <option value="Avg">Avg Weight</option>
                        </select>

                        <select name="agg_period" id="agg_period">
                            <option value="Month">By Month</option>
                            <option value="Year">By Year</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <button type="submit" class="btn" name="apply_filters">Apply</button>
                    </div>
                </form>

                <div class="title-bottom">
                    <h1>
                        Delete
                    </h1>
                </div>

                <form id="filters" method="post" actions="measurements.php">
                    <div class ="input-group">
                        <label>Delete Measurement:</label>
                        <select name="delete_date" id="delete_date">
                        <?php
                            $id = $_SESSION['id'];
                            $getDatesSQL = "SELECT MDate FROM TrackMeasurement WHERE UserID = :user_id";
                            $getDateStmt = oci_parse($c, $getDatesSQL);

                            oci_bind_by_name($getDateStmt, "user_id", $id);
                            if(oci_execute($getDateStmt)) {
                                while($row = oci_fetch_assoc($getDateStmt)) {
                                    echo "<option value='" . $row['MDATE'] . "'>" . $row['MDATE'] . "</option>";
                                }
                            }
                        ?>
                        </select>

                        <div class="input-group">
                            <button type="submit" class="btn" name="delete_measurement"> Delete </button>
                        </div>
                    </div>
                </form>

            </div>

            <!--Chart display-->
            <div id="wrapper-right">
                <?php
                    include('../db_config.php');
                    function displayMeasurements($c, $id, $filterstart = "", $filterend = "", $weightFilter = "", $heightFilter = "", $weightOperator = "=", $heightOperator = "=", $weightLogic = "AND", $heightLogic = "AND", $selectedAttributes = ["MDate", "Weight", "Height"], $sortBy = "MDate", $extraDisplay = "") {
                        // Initialize the conditions array
                        $conditions = [];
                        
                        // Date filter conditions
                        if ($filterstart !== "" && $filterend !== "") {
                            $conditions[] = "(MDate >= TO_DATE(:start_date, 'YYYY-MM-DD') AND MDate <= TO_DATE(:end_date, 'YYYY-MM-DD'))";
                        } elseif ($filterstart !== "") {
                            $conditions[] = "MDate >= TO_DATE(:start_date, 'YYYY-MM-DD')";
                        } elseif ($filterend !== "") {
                            $conditions[] = "MDate <= TO_DATE(:end_date, 'YYYY-MM-DD')";
                        }

                        // Weight filter condition
                        if ($weightFilter !== "") {
                            $weightCondition = "Weight $weightOperator :weightFilter";
                        }

                        // Height filter condition
                        if ($heightFilter !== "") {
                            $heightCondition = "Height $heightOperator :heightFilter";
                        }

                        // Combine date and weight conditions with weight logic
                        if (isset($weightCondition)) {
                            if (!empty($conditions)) {
                                $conditions = ["(" . implode(" AND ", $conditions) . ") $weightLogic $weightCondition"];
                            } else {
                                $conditions[] = $weightCondition;
                            }
                        }

                        // Combine previous conditions with height condition using height logic
                        if (isset($heightCondition)) {
                            if (!empty($conditions)) {
                                $conditions = ["(" . implode(" ", $conditions) . ") $heightLogic $heightCondition"];
                            } else {
                                $conditions[] = $heightCondition;
                            }
                        }

                        // Construct the SQL query
                        $getMeasurementsSQL = "SELECT " . implode(", ", $selectedAttributes) . " FROM TrackMeasurement WHERE UserID = :user_id";
                        if (!empty($conditions)) {
                            $getMeasurementsSQL .= " AND " . implode(" ", $conditions);
                        }
                        $getMeasurementsSQL .= " ORDER BY $sortBy";

                        // Prepare and execute the SQL statement
                        $getMeasurementsStmt = oci_parse($c, $getMeasurementsSQL);
                        oci_bind_by_name($getMeasurementsStmt, ":user_id", $id);
                        if ($filterstart !== "") {
                            oci_bind_by_name($getMeasurementsStmt, ":start_date", $filterstart);
                        }
                        if ($filterend !== "") {
                            oci_bind_by_name($getMeasurementsStmt, ":end_date", $filterend);
                        }
                        if ($weightFilter !== "") {
                            oci_bind_by_name($getMeasurementsStmt, ":weightFilter", $weightFilter);
                        }
                        if ($heightFilter !== "") {
                            oci_bind_by_name($getMeasurementsStmt, ":heightFilter", $heightFilter);
                        }

                        if ($extraDisplay !== "") {
                            // Determine the SQL function for aggregation
                            $aggFunc = ($extraDisplay == "Avg") ? "AVG" : (($extraDisplay == "Max") ? "MAX" : "MIN");

                            // Determine the grouping field (Month/Year)
                            $groupByField = ($aggPeriod == "Year") ? "EXTRACT(YEAR FROM MDate)" : "TO_CHAR(MDate, 'MM/YYYY')";

                            // Construct SQL for aggregation
                            $aggSQL = "SELECT $groupByField AS Period, $aggFunc(Weight) AS AggWeight FROM TrackMeasurement WHERE UserID = :user_id ";
                            if ($filterstart !== "" && $filterend !== "") {
                                $aggSQL .= " AND MDate BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD')";
                            }
                            $aggSQL .= " GROUP BY $groupByField ORDER BY $groupByField";

                            // Prepare and execute the SQL statement for aggregation
                            $aggStmt = oci_parse($c, $aggSQL);
                            oci_bind_by_name($aggStmt, ":user_id", $id);
                            if ($filterstart !== "" && $filterend !== "") {
                                oci_bind_by_name($aggStmt, ":start_date", $filterstart);
                                oci_bind_by_name($aggStmt, ":end_date", $filterend);
                            }

                            if (oci_execute($aggStmt)) {
                                echo "<table>";
                                echo "<tr><th>Period</th><th>{$extraDisplay} Weight</th></tr>";
                                while($row = oci_fetch_assoc($aggStmt)) {
                                    echo "<tr><td>" . htmlspecialchars($row['PERIOD']) . "</td><td>" . htmlspecialchars($row['AGGWEIGHT']) . "</td></tr>";
                                }
                                echo "</table>";
                            } else {
                                $error = oci_error($aggStmt);
                                echo "Failed to retrieve aggregate data. Error: " . $error['message'];
                            }

                            oci_free_statement($aggStmt);
                        }

                        // Execute and display results
                        if (oci_execute($getMeasurementsStmt)) {
                            echo "<table>";
                            echo "<tr>";
                            foreach($selectedAttributes as $header) {
                                if($header == "Weight") {
                                    echo "<th>" . $header . "(kg) </th>";
                                } else if($header == "Height") {
                                    echo "<th>" . $header . "(cm) </th>";
                                } else {
                                    echo "<th>" . $header . "</th>";
                                }
                            }
                            echo "</tr>";

                            while($row = oci_fetch_assoc($getMeasurementsStmt)) {
                                echo "<tr>";
                                foreach($selectedAttributes as $value) {
                                    echo "<td>" . $row[strtoupper($value)] . "</td>";
                                }
                                echo "</tr>";
                            }
                            echo "</table>";
                        } else {
                            // Handle query execution failure
                            echo "Failed to retrieve measurements.";
                        }

                        oci_free_statement($getMeasurementsStmt);
                    }

                    if(isset($_POST['apply_filters'])) {
                        $id = $_SESSION['id'];
                        $start = $_POST['startdate'] ?? '';
                        $end = $_POST['enddate'] ?? '';
                        $weightFilter = $_POST['weightFilter'] ?? '';
                        $heightFilter = $_POST['heightFilter'] ?? '';
                        $weightOperator = $_POST['weight_operator'] ?? '=';
                        $heightOperator = $_POST['height_operator'] ?? '=';
                        $combineLogic = $_POST['combine_logic'] ?? 'AND';
                        $visibleAtts = array();

                        if(isset($_POST['view_date'])) {
                            array_push($visibleAtts, "MDate");
                        }
                        if(isset($_POST['view_weight'])) {
                            array_push($visibleAtts, "Weight");
                        }
                        if(isset($_POST['view_height'])) {
                            array_push($visibleAtts, "Height");
                        }
                        $sort = $_POST['sort'] ?? 'MDate';

                        $extraDisplay = isset($_POST['view_agg']) ? $_POST['agg_options'] : "";
                        
                        // Building the WHERE clause
                        $conditions = [];
                        if ($start !== "") {
                            $conditions[] = "MDate >= TO_DATE(:start_date, 'YYYY-MM-DD')";
                        }
                        if ($end !== "") {
                            $conditions[] = "MDate <= TO_DATE(:end_date, 'YYYY-MM-DD')";
                        }
                        if ($weightFilter !== "") {
                            $conditions[] = "Weight $weightOperator :weightFilter";
                        }
                        if ($heightFilter !== "") {
                            $conditions[] = "Height $heightOperator :heightFilter";
                        }

                        // Combine all conditions into a single WHERE clause
                        $whereClause = implode(" $combineLogic ", $conditions);

                        // Modify the SQL query to include the dynamic WHERE clause
                        $getMeasurementsSQL = "SELECT " . implode(", ", $visibleAtts) . " FROM TrackMeasurement WHERE UserID = :user_id";
                        if (!empty($whereClause)) {
                            $getMeasurementsSQL .= " AND ($whereClause)";
                        }

                        $getMeasurementsStmt = oci_parse($c, $getMeasurementsSQL);
                        oci_bind_by_name($getMeasurementsStmt, ":user_id", $id);
                        if ($start !== "") {
                            oci_bind_by_name($getMeasurementsStmt, ":start_date", $start);
                        }
                        if ($end !== "") {
                            oci_bind_by_name($getMeasurementsStmt, ":end_date", $end);
                        }
                        if ($weightFilter !== "") {
                            oci_bind_by_name($getMeasurementsStmt, ":weightFilter", $weightFilter);
                        }
                        if ($heightFilter !== "") {
                            oci_bind_by_name($getMeasurementsStmt, ":heightFilter", $heightFilter);
                        }

                        $weightLogic = $_POST['weight_logic'] ?? 'AND';
                        $heightLogic = $_POST['height_logic'] ?? 'AND';

                        $extraDisplay = isset($_POST['view_agg']) && $_POST['view_agg'] === "Visible" ? $_POST['agg_options'] : "";
                        displayMeasurements($c, $id, $start, $end, $weightFilter, $heightFilter, $weightOperator, $heightOperator, $weightLogic, $heightLogic, $visibleAtts, $sort, $extraDisplay);
                    } else {
                        $id = $_SESSION['id'];
                        displayMeasurements($c, $id); 
                    }
                ?>
            </div>
        </div>
    </div>
</body>

</html>
