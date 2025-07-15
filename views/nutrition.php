<?php
    include('../db_config.php');
    session_start();
    $userID = $_SESSION['id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../css/nutrition.css">
    <title>Login</title>
</head>

<body>
    <?php include('../views/header.php'); ?>
    <div id="wrapper-left">

        <div class="page-top">
            <div class="title">
                <h2>
                    Nutrition
                </h2>
            </div>

            <div id="bmr-container">
                <?php
                    $getUserMeasurementSQL = "SELECT u.gender, u.age, tm.weight, tm.height
                                                FROM userinfo u
                                                JOIN trackmeasurement tm ON u.userid = tm.userid
                                                WHERE u.userid = :userID
                                                AND tm.mdate = (SELECT MAX(tm_inner.mdate)
                                                                FROM trackmeasurement tm_inner
                                                                WHERE tm_inner.userid = :userID)";

                    $getUserMeasurementStmt = oci_parse($c, $getUserMeasurementSQL);

                    oci_bind_by_name($getUserMeasurementStmt, ":userID", $userID);

                    if (oci_execute($getUserMeasurementStmt)) {
                    $row = oci_fetch_assoc($getUserMeasurementStmt);
                    $gender = $row['GENDER'];
                    $age = $row['AGE'];
                    $weight = $row['WEIGHT'];
                    $height = $row['HEIGHT'];

                    if ($gender = 'Female') {
                        $BMR = 447.593 + (9.247 * $weight) + (3.098 * $height) - (4.330 * $age);
                    } else {
                        $BMR = 88.362 + (13.397 * $weight) + (4.799 * $height) - (5.677 * $age);
                    }

                    oci_free_statement($getUserMeasurementStmt);

                    } else {
                    $error = oci_error($getUserMeasurementStmt);
                    echo "SQL Error: " . $error['message'];
                    return false;
                    }
                ?>
                <p>BMR: <?php echo round($BMR); ?></p>

            </div>        

            <div id="table-container">
                <form id="addCalorieGoal" method="post" action="nutrition.php">
                    <div class="input-group">
                            <label>Select Date:</label>
                            <input type="date" id="selected_date" name="selected_date" max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="input-group">
                            <label>Set Daily calories goal:</label>
                            <input type="text" name="calorie_goal">
                    </div>

                    <div class="input-group">
                            <button type="submit" class="btn" name="add_calorie_goal">Add Daily Calorie Goal</button>
                    </div>
                </form>

            </div>

            <div class="title">
                <h2>
                    View Nutrition
                </h2>
            </div>


            <div id="view-nutrition-form">
                <form method="post" action="nutrition.php">
                    <div class="input-group">
                        <label>Start Date:</label>
                        <input type="date" id="start_date" name="start_date" max="<?php echo date('Y-m-d'); ?>">

                        <label>End Date:</label>
                        <input type="date" id="end_date" name="end_date" max="<?php echo date('Y-m-d'); ?>">


                        <button type="submit" class="btn" name="view_nutrition_range">View Nutrition</button>
                    </div>
                </form>
                
                <?php
                    if (isset($_POST['view_nutrition_range'])) {
                        $start_date = $_POST['start_date'];
                        $end_date = $_POST['end_date'];

                        $nutritionData = getNutritionInRange($c, $userID, $start_date, $end_date);

                    } else {
                        $nutritionData = getNutritionInRange($c, $userID, "", "");

                    }

                    // check if any Nutrition exists or not
                    if (!empty($nutritionData)) {
                        echo "<table>";
                        echo "<tr><th>Date</th><th>Daily Consumed Calories</th><th>Daily Calories Goal</th></tr>";

                        foreach ($nutritionData as $data) {
                            echo "<tr>";
                            echo "<td>" . $data['NDATE'] . "</td>";
                            echo "<td>" . $data['DAILYCONSUMEDCALORIES'] . "</td>";
                            echo "<td>" . $data['DAILYCALORIESGOAL'] . "</td>";
                            echo "</tr>";
                        }

                        echo "</table>";
                    } else {
                        echo "<p>No nutrition data found for the selected range.</p>";
                    }

                    function getNutritionInRange($c, $userID, $start_date, $end_date) {
                        $getNutritionInRangeSQL = "SELECT n.NDate, n.DailyConsumedCalories, n.DailyCaloriesGoal
                                                FROM userinfo u
                                                JOIN hasnutrition hn ON u.userid = hn.userid
                                                JOIN nutrition n ON hn.nid = n.nid
                                                WHERE u.userid = :userID";

                        if($start_date !== "") {
                            $getNutritionInRangeSQL .= " AND n.NDate >= TO_DATE(:start_date, 'YYYY-MM-DD')";
                        }

                        if($end_date !== "") {
                            $getNutritionInRangeSQL .= " AND n.NDate <= TO_DATE(:end_date, 'YYYY-MM-DD')";
                        }

                        $getNutritionInRangeSQL .= " ORDER BY n.NDate";

                        $getNutritionInRangeStmt = oci_parse($c, $getNutritionInRangeSQL);

                        oci_bind_by_name($getNutritionInRangeStmt, ":userID", $userID);
                        oci_bind_by_name($getNutritionInRangeStmt, ":start_date", $start_date);
                        oci_bind_by_name($getNutritionInRangeStmt, ":end_date", $end_date);

                        if (oci_execute($getNutritionInRangeStmt)) {
                            $nutritionData = array();

                            while ($row = oci_fetch_assoc($getNutritionInRangeStmt)) {
                                $nutritionData[] = $row;
                            }

                            oci_free_statement($getNutritionInRangeStmt);
                            return $nutritionData;
                        } else {
                            $error = oci_error($getNutritionInRangeStmt);
                            echo "SQL Error: " . $error['message'];
                            return false;
                        }
                    }
                ?>
            </div>

            <div id="add-container">
                <div class="title">
                    <h2>Add a Meal</h2>
                </div>
                <form method='post' action='nutrition.php'>

                <div class="input-group">
                        <label>Date:</label>
                        <input type="date" id="new_date" name="new_date" max="<?php echo date('Y-m-d'); ?>">



                <label>Meal:
                <select name='meal' id='meal'>
                <option value='Select'>Select</option>
                <option value='Breakfast'>Breakfast</option>
                <option value='Lunch'>Lunch</option>
                <option value='Dinner'>Dinner</option>
                <option value='Snack'>Snack</option>
                </select>
                </label>



                <label>Food:
                <select name='food' id='food'>
                <option value='Select'>Select</option>
                <option value='Medium Green Apple'>Medium Green Apple</option>
                <option value='Banana'>Banana</option>
                <option value="Chicken Fried Thigh">Chicken Fried Thigh</option>
                <option value='Pizza'>Pizza</option>
                <option value='Black Coffee'>Black Coffee</option>
                </select>
                </label>



                <label>Quantity:
                <select name='quantity' id='quantity'>
                <option value='Select'>Select</option>
                <option value='1'>1</option>
                <option value='2'>2</option>
                <option value='3'>3</option>
                <option value='4'>4</option>
                <option value='5'>5</option>
                </select>
                </label>



                <button type='submit' class='btn' name='add_food'>Add Food</button>
                </div>

                </form>
            </div>

            <div class="table-container">    
                <div class="title">
                    <h2>Meal Table</h2>
                </div>
                <div id="form-container">
                    <form method="post">
                        <label for="selected_date">Select a Date: </label>
                        <input type="date" name="selected_date" id="selected_date">
                        <input type="submit" name="view_table" value="Show Meals">
                    </form>
                </div>
            </div>

            <?php
            if (isset($_POST['add_calorie_goal'])) {
                if (isset($_POST['selected_date'])) {
                    if (isset($_POST['calorie_goal'])) {
                        $selected_date = $_POST['selected_date'];
                        $calorie_goal = $_POST['calorie_goal'];

                        // check if Nutrition on that date exists or not
                        $checkGoalSQL = "SELECT count(*)   
                                        FROM nutrition n, hasnutrition hn
                                        WHERE n.nid = hn.nid and hn.userID = :userID and n.ndate = to_date(:selected_date, 'YYYY-MM-DD')";
                        $checkGoalStmt = oci_parse($c, $checkGoalSQL);

                        oci_bind_by_name($checkGoalStmt, ':userID', $userID);
                        oci_bind_by_name($checkGoalStmt, ':selected_date', $selected_date);

                        oci_execute($checkGoalStmt);
                        $goalExists = oci_fetch_row($checkGoalStmt);

                        // insert Nutrition and HasNutrition if it does not exist on that date
                        if($goalExists[0] == 0) {
                            // generate a random NID
                            $randNID = rand(1, 9999999999);

                            $insertNutritionSQL = "INSERT INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
                            VALUES (:randNID, TO_DATE(:selected_date, 'YYYY-MM-DD'), 0, :calorie_goal)";
                            $insertHasNutritionSQL = "INSERT INTO HasNutrition (UserID, NID)
                            VALUES (:userID, :randNID)";

                            $insertNutritionStmt = oci_parse($c, $insertNutritionSQL);
                            $insertHasNutritionStmt = oci_parse($c, $insertHasNutritionSQL);

                            oci_bind_by_name($insertNutritionStmt, ":randNID", $randNID);
                            oci_bind_by_name($insertNutritionStmt, ":selected_date", $selected_date);
                            oci_bind_by_name($insertNutritionStmt, ":calorie_goal", $calorie_goal);
                            oci_bind_by_name($insertHasNutritionStmt, ':userID', $userID);
                            oci_bind_by_name($insertHasNutritionStmt, ":randNID", $randNID);

                            if(oci_execute($insertNutritionStmt)) {
                                $message = "New Nutrition added";
                            } else {
                                $error = oci_error($insertNutritionStmt);
                                $message = "Failed to add new nutrition. Error: " . $error['message'];
                            }

                            if(oci_execute($insertHasNutritionStmt)) {
                                $message = "New HasNutrition added";
                            } else {
                                $error = oci_error($insertHasNutritionStmt);
                                $message = "Failed to add new HasNutrition. Error: " . $error['message'];
                            }

                            oci_free_statement($insertNutritionStmt);
                            oci_free_statement($insertHasNutritionStmt);
                        } else {
                            $updateCalSQL = "UPDATE Nutrition
                                            SET DailyCaloriesGoal = :calorie_goal
                                            WHERE ndate = TO_DATE(:selected_date, 'YYYY-MM-DD')";

                            $updateCalStmt = oci_parse($c, $updateCalSQL);

                            oci_bind_by_name($updateCalStmt, ":calorie_goal", $calorie_goal);
                            oci_bind_by_name($updateCalStmt, ":selected_date", $selected_date);

                            if (oci_execute($updateCalStmt)) {                    
                                oci_free_statement($updateCalStmt);
                                return;

                            } else {
                                $error = oci_error($updateCalStmt);
                                echo "SQL Error: " . $error['message'];
                                return false;
                            }

                        }
                        
                    } else {
                        echo "<p>Please enter all the information.</p>";
                    }
                }
            }

            // ADD A MEAL
            if (isset($_POST['add_food'])) {
                $new_date = $_POST['new_date'];
                $new_meal = $_POST['meal'];
                $new_food_name = $_POST['food'];
                $new_food_quantity = $_POST['quantity'];

                // check if Nutrition on that date exists or not
                $checkNutritionSQL = "SELECT count(*) 
                                    FROM nutrition n, hasnutrition hn 
                                    WHERE hn.userid = :userID and n.ndate = TO_DATE(:new_date, 'YYYY-MM-DD') and n.nid = hn.nid";
                $checkNutritionStmt = oci_parse($c, $checkNutritionSQL);

                oci_bind_by_name($checkNutritionStmt, ':userID', $userID);
                oci_bind_by_name($checkNutritionStmt, ':new_date', $new_date);

                oci_execute($checkNutritionStmt);
                $nutritionExists = oci_fetch_row($checkNutritionStmt);

                // // insert Nutrition and HasNutrition if it does not exist on that date
                if($nutritionExists[0] == 0) {
                    // generate a random NID
                    $randNID = rand(1, 9999999999);

                    $insertNutritionSQL = "INSERT INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
                    VALUES (:randNID, TO_DATE(:new_date, 'YYYY-MM-DD'), 0, 0)";
                    $insertHasNutritionSQL = "INSERT INTO HasNutrition (UserID, NID)
                    VALUES (:userID, :randNID)";

                    $insertNutritionStmt = oci_parse($c, $insertNutritionSQL);
                    $insertHasNutritionStmt = oci_parse($c, $insertHasNutritionSQL);

                    oci_bind_by_name($insertNutritionStmt, ":randNID", $randNID);
                    oci_bind_by_name($insertNutritionStmt, ":new_date", $new_date);
                    oci_bind_by_name($insertHasNutritionStmt, ':userID', $userID);
                    oci_bind_by_name($insertHasNutritionStmt, ":randNID", $randNID);

                    if(oci_execute($insertNutritionStmt)) {
                        $message = "New Nutrition added";
                    } else {
                        $error = oci_error($insertNutritionStmt);
                        $message = "Failed to add new nutrition. Error: " . $error['message'];
                    }

                    if(oci_execute($insertHasNutritionStmt)) {
                        $message = "New HasNutrition added";
                    } else {
                        $error = oci_error($insertHasNutritionStmt);
                        $message = "Failed to add new HasNutrition. Error: " . $error['message'];
                    }

                    oci_free_statement($insertNutritionStmt);
                    oci_free_statement($insertHasNutritionStmt);
                } 

                $resultNID = getNID($c, $userID, $new_date);

                // generate a random MID
                $randMID = rand(1, 9999999999);

                $MealCaloriesConsumed = getMealCalorieConsumed($c, $new_food_name, $new_food_quantity);

                // insert Meal and HasMeal
                $insertMealSQL = "INSERT INTO Meal (MID, NID, Type, MealCaloriesConsumed)
                VALUES (:randMID, :resultNID, :new_meal, :MealCaloriesConsumed)";
                $insertHasMealSQL = "INSERT INTO HasMeal (UserID, MID)
                VALUES (:userID, :randMID)";

                $insertMealStmt = oci_parse($c, $insertMealSQL);
                $insertHasMealStmt = oci_parse($c, $insertHasMealSQL);

                oci_bind_by_name($insertMealStmt, ":randMID", $randMID);
                oci_bind_by_name($insertMealStmt, ":resultNID", $resultNID);
                oci_bind_by_name($insertMealStmt, ":new_meal", $new_meal);
                oci_bind_by_name($insertMealStmt, ":MealCaloriesConsumed", $MealCaloriesConsumed);
                oci_bind_by_name($insertHasMealStmt, ':userID', $userID);
                oci_bind_by_name($insertHasMealStmt, ':randMID', $randMID);

                if (oci_execute($insertMealStmt)) {
                    $message = "New Meal added";
                } else {
                    $error = oci_error($insertMealStmt);
                    $message = "Failed to add new meal. Error: " . $error['message'];
                }

                if (oci_execute($insertHasMealStmt)) {
                    $message = "New HasMeal added";
                } else {
                    $error = oci_error($insertHasMealStmt);
                    $message = "Failed to add new HasMeal. Error: " . $error['message'];
                }

                oci_free_statement($insertMealStmt);
                oci_free_statement($insertHasMealStmt);

                // insert MealContainFood
                $insertMealContainFoodSQL = "INSERT INTO MealContainFood (MID, FoodName, Quantity)
                VALUES (:randMID, :new_food_name, :new_food_quantity)";

                $insertMealContainFoodStmt = oci_parse($c, $insertMealContainFoodSQL);

                oci_bind_by_name($insertMealContainFoodStmt, ":randMID", $randMID);
                oci_bind_by_name($insertMealContainFoodStmt, ":new_food_name", $new_food_name);
                oci_bind_by_name($insertMealContainFoodStmt, ":new_food_quantity", $new_food_quantity);

                if (oci_execute($insertMealContainFoodStmt)) {
                    $message = "New MealContainFood added";
                } else {
                    $error = oci_error($insertMealContainFoodStmt);
                    $message = "Failed to add new MealContainFood. Error: " . $error['message'];
                }

                oci_free_statement($insertMealContainFoodStmt);

                updateNutritionTable($c, $resultNID);

                echo "<script>alert('$message');</script>";
            }

            // MEAL TABLE
            if (isset($_POST['view_table'])) {
                if (isset($_POST['selected_date'])) {
                    $selected_date = $_POST['selected_date'];

                    $meals = getMealsForDate($c, $userID, $selected_date);

                    if (count($meals) > 0) {
                        echo "<h2>Meals for Date: $selected_date</h2>";

                        foreach ($meals as $meal) {
                            echo "<h3>{$meal['name']}</h3>";
                            echo "<table>";
                            echo "<tr><th>Food Name</th><th>Calories</th></tr>";

                            foreach ($meal['foods'] as $food) {
                                echo "<tr>";
                                echo "<td>" . $food['name'] . "</td>";
                                echo "<td>" . $food['calories'] . "</td>";
                                echo "</tr>";
                            }

                            echo "</table>";
                            
                        }
                    } else {
                        echo "<p>No meals found for the selected date.</p>";
                    }
                } else {
                    echo "<p>Please select a date to display meals.</p>";
                }
            }

            function updateNutritionTable($c, $nid) {
                $getSumMealCalSQL = "SELECT SUM(m.mealcaloriesconsumed) AS SUM_MEAL_CAL
                                    FROM   meal m,
                                            nutrition n,
                                            hasnutrition hn,
                                            hasmeal hm,
                                            mealcontainfood mcf
                                    WHERE  hn.nid = n.nid
                                            AND m.nid = n.nid
                                            AND m.mid = hm.mid
                                            AND n.nid = :nid
                                            AND hm.userid = hn.userid
                                            AND m.mid = mcf.mid";

                $getSumMealCalStmt = oci_parse($c, $getSumMealCalSQL);

                oci_bind_by_name($getSumMealCalStmt, ":nid", $nid);

                if (oci_execute($getSumMealCalStmt)) {
                    $row = oci_fetch_assoc($getSumMealCalStmt);
                    $resultSumMealCal = $row['SUM_MEAL_CAL'];
                    
                    oci_free_statement($getSumMealCalStmt);
                    return updateNutrition($c, $nid, $resultSumMealCal);

                } else {
                    $error = oci_error($getSumMealCalStmt);
                    echo "SQL Error: " . $error['message'];
                    return false;
                }
            }

            function updateNutrition($c, $nid, $resultSumMealCal) {
                $updateMealCalSQL = "UPDATE Nutrition
                                    SET DailyConsumedCalories = $resultSumMealCal
                                    WHERE nid = :nid";

                $updateMealCalStmt = oci_parse($c, $updateMealCalSQL);

                oci_bind_by_name($updateMealCalStmt, ":resultSumMealCal", $resultSumMealCal);
                oci_bind_by_name($updateMealCalStmt, ":nid", $nid);

                if (oci_execute($updateMealCalStmt)) {                    
                    oci_free_statement($updateMealCalStmt);
                    return;

                } else {
                    $error = oci_error($updateMealCalStmt);
                    echo "SQL Error: " . $error['message'];
                    return false;
                }
            }

            function getNID($c, $userID, $ndate) {
                $getNIDSQL = "SELECT n.NID
                            FROM UserInfo u
                            JOIN HasNutrition hn ON u.UserID = hn.UserID
                            JOIN Nutrition n ON hn.NID = n.NID
                            WHERE u.UserID = :userID AND n.NDate = TO_DATE(:ndate, 'YYYY-MM-DD')";

                $getNIDStmt = oci_parse($c, $getNIDSQL);

                oci_bind_by_name($getNIDStmt, ":userID", $userID);
                oci_bind_by_name($getNIDStmt, ":ndate", $ndate);

                if (oci_execute($getNIDStmt)) {
                    $row = oci_fetch_assoc($getNIDStmt);
                    $resultNID = $row['NID'];
                    
                    oci_free_statement($getNIDStmt);
                    return $resultNID;

                } else {
                    $error = oci_error($stmt);
                    echo "SQL Error: " . $error['message'];
                    return false;
                }
            }

            function getMealCalorieConsumed($c, $food_name, $quantity) {
                $getFoodCaloriesSQL = "SELECT foodcalories
                            FROM food
                            WHERE foodname = :food_name";

                $getFoodCaloriesStmt = oci_parse($c, $getFoodCaloriesSQL);

                oci_bind_by_name($getFoodCaloriesStmt, ":food_name", $food_name);

                if (oci_execute($getFoodCaloriesStmt)) {
                    $row = oci_fetch_assoc($getFoodCaloriesStmt);
                    $resultFoodCalories = $row['FOODCALORIES'] * $quantity;
                    
                    oci_free_statement($getFoodCaloriesStmt);
                    return $resultFoodCalories;

                } else {
                    $error = oci_error($getFoodCaloriesStmt);
                    echo "SQL Error: " . $error['message'];
                    return false;
                }

            }


            function getMealsForDate($c, $userID, $selected_date) {
                $getMealsSQL = "SELECT m.Type AS MEALNAME, f.FoodName AS FOODNAME, m.MealCaloriesConsumed AS MEALCALORIES
                                FROM HasNutrition hn
                                JOIN Nutrition n ON hn.NID = n.NID
                                JOIN Meal m ON m.NID = n.NID
                                JOIN HasMeal hm ON hn.UserID = hm.UserID AND m.MID = hm.MID
                                JOIN MealContainFood mcf ON mcf.MID = m.MID
                                JOIN Food f ON mcf.FoodName = f.FoodName
                                WHERE hn.UserID = :userID AND n.NDate = TO_DATE(:selected_date, 'YYYY-MM-DD')                
                            ";
                $stmt = oci_parse($c, $getMealsSQL);
                oci_bind_by_name($stmt, ':userID', $userID);
                oci_bind_by_name($stmt, ':selected_date', $selected_date);

                if (oci_execute($stmt)) {
                    $meals = array();
            
                    while ($row = oci_fetch_assoc($stmt)) {
                        $mealName = $row['MEALNAME'];
            
                        if (!isset($meals[$mealName])) {
                            $meals[$mealName] = array('name' => $mealName, 'foods' => array());
                        }
            
                        $food = array(
                            'name' => $row['FOODNAME'],
                            'calories' => $row['MEALCALORIES']
                        );
            
                        $meals[$mealName]['foods'][] = $food;
                    }
            
                    oci_free_statement($stmt);
                    return array_values($meals);
                } else {
                    $error = oci_error($stmt);
                    echo "SQL Error: " . $error['message'];
                    return false;
                }
            }
        ?>

        </div>
                    
    </div>
</body>

</html>