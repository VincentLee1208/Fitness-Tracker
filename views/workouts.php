<?php
session_start();
include('../db_config.php');

//Grab Classes
class classObj {
    public $name;
    public $id;
    public $caloriesBurned;
    public $date;
    public $classDuration;
}
$classes = array();

$getClassSQL = "SELECT GroupClass.WID, GroupClass.ClassTitle, Workout.TotalCaloriesBurned, Workout.WorkoutDate, Workout.TotalDuration 
                FROM GroupClass
                JOIN Workout ON GroupClass.WID = Workout.WID";

$getClassStmt = oci_parse($c, $getClassSQL);
if(oci_execute($getClassStmt)) {
    while($row = oci_fetch_assoc($getClassStmt)) {
        $newClass = new classObj();
        $newClass->name = $row['CLASSTITLE'];
        $newClass->id = $row['WID'];
        $newClass->caloriesBurned = $row['TOTALCALORIESBURNED'];
        $newClass->date = $row['WORKOUTDATE'];
        $newClass->classDuration = $row['TOTALDURATION'];
        array_push($classes, $newClass);
    }
} else {
    echo "Failed to get classes";
}

oci_free_statement($getClassStmt);

$pastClasses = array();
$currentDate = new Datetime();

foreach($classes as $class) {
    $classDate = new DateTime($class->date);
    if($classDate < $currentDate) {
        $pastClasses[] = $class;
    }
}

//Grab Exercises
class exerciseObj {
    public $name;
    public $calories;
    public $intensity;
}


$exercises = array();

$getExercisesSQL = "SELECT * FROM Exercises";
$getExercisesStmt = oci_parse($c, $getExercisesSQL);

if(oci_execute($getExercisesStmt)) {
    while($row = oci_fetch_assoc($getExercisesStmt)) {
        $exercise = new exerciseObj();
        $exercise->name = $row['EXERCISENAME'];
        $exercise->calories = $row['CALORIESBURNED'];
        $exercise->intensity = $row['INTENSITY'];
        array_push($exercises, $exercise);
    }
}

oci_free_statement($getExercisesStmt);

//DELETE WORKOUT
if(isset($_POST['delete_workout'])) {
    $wid = $_POST['wid-select'];

    //check if WID is a group class
    $checkWorkoutClassSQL = "SELECT * FROM GroupClass WHERE WID = :id";
    $checkWorkoutClassStmt = oci_parse($c, $checkWorkoutClassSQL);

    oci_bind_by_name($checkWorkoutClassStmt, ":id", $wid);
    oci_execute($checkWorkoutClassStmt);
    
    //Not a group class
    if(!oci_fetch_assoc($checkWorkoutClassStmt)) {
        //Delete workout from Workout
        $deleteWorkoutSQL = "DELETE FROM Workout WHERE WID = :id";
        $deleteWorkoutStmt = oci_parse($c, $deleteWorkoutSQL);

        oci_bind_by_name($deleteWorkoutStmt, ":id", $wid);
        if(oci_execute($deleteWorkoutStmt)) {
            $message = "Successfully deleted workout " . $wid;
        } else {
            $error = oci_error($deleteWorkoutStmt);
            $message = "Issue deleting workout " . $wid . ": " . $error;
        }
    } else {
        //Only delete workout from DoesWorkout
        $deleteWorkoutSQL = "DELETE FROM DoesWorkout WHERE WID = :id AND UserID = :userid";
        $deleteWorkoutStmt = oci_parse($c, $deleteWorkoutSQL);

        oci_bind_by_name($deleteWorkoutStmt, ":id", $wid);
        oci_bind_by_name($deleteWorkoutStmt, ":userid", $_SESSION['id']);

        if(oci_execute($deleteWorkoutStmt)) {
            $message = "Successfully deleted workout " . $wid;
        } else {
            $error = oci_error($deleteWorkoutStmt);
            $message = "Issue deleting workout " . $wid . ": " . $error;
        }
    }

    echo "<script>alert('$message');</script>";

}

//ADD WORKOUT
if(isset($_POST['add_workout'])) {
    // User didn't enter a class or any exercises
    if(!isset($_POST['class-select-dropdown']) && !isset($_POST['exercise-dropdown'])) {
        $message = "Please enter class or exercises";
    // Workout is a class
    } else if(isset($_POST['class-select-dropdown'])){
        $uid = $_SESSION['id'];
        $cID = $_POST['class-date-dropdown'];

        $checkDuplicateWorkoutSQL = "SELECT * FROM DoesWorkout WHERE UserID = :id AND WID = :cID";
        $checkDuplicateWorkoutStmt = oci_parse($c, $checkDuplicateWorkoutSQL);

        oci_bind_by_name($checkDuplicateWorkoutStmt, ":id", $uid);
        oci_bind_by_name($checkDuplicateWorkoutStmt, ":cID", $cID);
        
        if(!oci_fetch_assoc($checkDuplicateWorkoutStmt)) {
            $insertWorkoutSQL = "INSERT INTO DoesWorkout (UserID, WID) VALUES (:id, :cID)";
            $insertWorkoutStmt = oci_parse($c, $insertWorkoutSQL);

            oci_bind_by_name($insertWorkoutStmt, ":id", $uid);
            oci_bind_by_name($insertWorkoutStmt, ":cID", $cID);
            
            if(oci_execute($insertWorkoutStmt)) {
                $message = "Successfully added workout";
            } else {
                $error = oci_error($insertWorkoutStmt);
                $message = "Failed to add class workout. Error: " . $error['message'];
            }

            oci_free_statement($insertWorkoutStmt);
        } else {
            $message = "Failed to add workout: duplicate WID";
        }

        oci_free_statement($checkDuplicateWorkoutStmt);
        
    } else { 
        // User didn't enter a date
        if($_POST['mdate'] == "") {
            $message = "Please select a date"; 
        } else {
            $id = $_SESSION['id'];
            $date = $_POST['mdate'];
            $wID = rand(1,1000000);
            
            //Need to check if Nutrition with same date already exists
            $checkNDateExistSQL = "SELECT Nutrition.NID, HasNutrition.UserID
                                    FROM Nutrition 
                                    JOIN HasNutrition ON Nutrition.NID = HasNutrition.NID 
                                    WHERE Nutrition.NDate = TO_DATE(':date', 'YYYY-MM-DD') AND HasNutrition.UserID = :id";
            $checkNDateExistStmt = oci_parse($c, $checkNDateExistStmt);

            oci_bind_by_name($checkNDateExistStmt, ":date", $date);
            oci_bind_by_name($checkNDateExistStmt, ":id", $id);
            oci_execute($checkNDateExistStmt);
            $nID = null;
            // no existing date for current user
            if(!oci_fetch_assoc($checkNDateExistStmt)) {
                //Create nutrition entry
                $nID = rand(1, 1000000);
                $createNewNutritionSQL = "INSERT INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal) 
                                            VALUES (:nid, TO_DATE(:ndate, 'YYYY-MM-DD'), 0, 0)";
                $createNewNutritionStmt = oci_parse($c, $createNewNutritionSQL);
                oci_bind_by_name($createNewNutritionStmt, ":nid", $nID);
                oci_bind_by_name($createNewNutritionStmt, ":ndate", $date);

                if(!oci_execute($createNewNutritionStmt)) {
                    $message = "failed to add Nutrition";
                } else {
                    //Connect new Nutrition entry to User
                    $addUserNutritionSQL = "INSERT INTO HasNutrition (UserID, NID) VALUES (:id, :nid)";
                    $addUserNutritionStmt = oci_parse($c, $addUserNutritionSQL);

                    oci_bind_by_name($addUserNutritionStmt, ":id", $id);
                    oci_bind_by_name($addUserNutritionStmt, ":nid", $nID);

                    oci_execute($addUserNutritionStmt);
                }
            }

            $totalDuration = 0;
            $totalCalories = 0;

            $addedExercises = $_POST['exercise-dropdown'];
            $exerciseDurations = $_POST['exercise-duration'];

            $insertWorkoutExerciseArray = array();
            for($i = 0; $i < count($addedExercises); $i++) {
                $matchingExercise = null;
                $totalDuration += $exerciseDurations[$i];

                foreach($exercises as $exercise) {
                    if($exercise->name === $addedExercises[$i]) {
                        $matchingExercise = $exercise;
                        break;
                    }
                }

                if($matchingExercise !== null) {
                    $insertWorkoutExerciseStmt = "";
                    $calories = $matchingExercise->calories;
                    $totalCalories += (($exerciseDurations[$i]/30) * $calories);

                    $insertWorkoutExerciseSQL = "INSERT INTO WorkoutIncludeExercise (WID, ExerciseName, Duration) VALUES(:wid, :exerciseName, :exerciseDuration)";
                    $insertWorkoutExerciseStmt = oci_parse($c, $insertWorkoutExerciseSQL);
                    oci_bind_by_name($insertWorkoutExerciseStmt, ":wid", $wID);
                    oci_bind_by_name($insertWorkoutExerciseStmt, ":exerciseName", $matchingExercise->name);
                    oci_bind_by_name($insertWorkoutExerciseStmt, ":exerciseDuration", $exerciseDurations[$i]);
                    
                    array_push($insertWorkoutExerciseArray, $insertWorkoutExerciseStmt);
                }
            }
            //Create new Workout entry
            $insertNewWorkoutSQL = "INSERT INTO WORKOUT (WID, NID, TotalCaloriesBurned, WorkoutDate, TotalDuration) 
                                VALUES (:wid, :nid, :calories, TO_DATE(:wdate, 'YYYY-MM-DD'), :duration)";
            $insertNewWorkoutStmt = oci_parse($c, $insertNewWorkoutSQL);
            
            if(!$insertNewWorkoutStmt) {
                $message = "Broken SQL Statement";
            }
            oci_bind_by_name($insertNewWorkoutStmt, ":wid", $wID);
            oci_bind_by_name($insertNewWorkoutStmt, ":nid", $nID);
            oci_bind_by_name($insertNewWorkoutStmt, ":calories", $totalCalories);
            oci_bind_by_name($insertNewWorkoutStmt, ":wdate", $date);
            oci_bind_by_name($insertNewWorkoutStmt, ":duration", $totalDuration);
            
            if(oci_execute($insertNewWorkoutStmt)) {
                $message = "Workout added to workout table";
                $addUserDoesWorkoutSQL = "INSERT INTO DoesWorkout (UserID, WID) VALUES (:id, :wid)";
                $addUserDoesWorkoutStmt = oci_parse($c, $addUserDoesWorkoutSQL);

                oci_bind_by_name($addUserDoesWorkoutStmt, ":id", $id);
                oci_bind_by_name($addUserDoesWorkoutStmt, ":wid", $wID);

                if(oci_execute($addUserDoesWorkoutStmt)) {
                    $message = "Successfully added user workout";
                } else {
                    $message = "Failed to add user workout";
                }
            } else {
                $error = oci_error($insertNewWorkoutStmt);
                $message = "Couldn't add to workout table. Error: " . $error['message'];
            }
            
            foreach($insertWorkoutExerciseArray as $Stmt) {
                if(!oci_execute($Stmt)) {
                    $error = oci_error($Stmt);
                    echo "Couldn't add exercises to workout" . $error['message'];
                }
            }
        }
    }

    echo "<script>alert('$message');</script>";
}   
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../css/workouts.css">
    <title>Workouts</title>
</head>

<body>
    <?php include('../views/header.php'); ?>


    <div class="page-bottom">
        <div id="wrapper-left">
            <div id="form-container">
                <form id="addWorkout" method="post" action="workouts.php">

                    <div id="class-container">
                        <div class="title">
                            <h3>
                                Add Class Workout
                            </h3>
                        </div>

                        <button class="add-element" onclick="addNewClass(event)"> Add Class to workout</button>
                        <table id="class-table"></table>
                    </div>

                    <div id="exercise-container">
                        <div class="input-group">
                            <div class="title">
                                <h3>
                                    Add Custom Workout
                                </h3>
                            </div>

                            <label>Date:
                                <input type="date" id="mdate" name="mdate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                            </label>
                        </div>

                        <button class="add-element" onclick="addNewExerciseField(event)"> Add an Exercise to workout</button>
                        <h3 id="exercise-title">Exercises</h3>
                        <table id="exercise-table"></table>
                    </div>

                    <div class="input-group">
                        <button type="submit" class="add-workout-btn" id="add-workout-btn" name="add_workout">Add New Workout</button>
                    </div>
                </form>
            </div>

            <div class="title">
                <h3>
                    Filters
                </h3>
            </div>

            <div id="form-container">
                <form id="filters" method="post" actions="workouts.php">
                    <div class="input-group">
                        <label for="view_class" id="view_label"> Show Class Title</label>
                        <input type="checkbox" id="view_class" name="view_class" value="ClassTitle"/>   
                    </div>
                    <div class="input-group">
                        <label>Start Date:</label>
                        <input type="date" id="startdate" name="startdate" max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="input-group">
                        <label>End Date:</label>
                        <input type="date" id="enddate" name="enddate" max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="input-group">
                        <label>Sort By:</label>
                        <select class="sort-drop" name="sort-drop" id="sort-drop">
                            <option value="WorkoutDate">Date</option>
                            <option value="TotalCaloriesBurned">Calories Burned</option>
                            <option value="TotalDuration">Duration</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="view_date" id="view_label"> Date</label>
                        <input type="checkbox" id="view_date" name = "view_date" value="WorkoutDate" checked/>

                        <label for="view_calories" id="view_label"> Calories Burned</label>
                        <input type="checkbox" id="view_calories" name = "view_calories" value="TotalCaloriesBurned" checked/>

                        <label for="view_duration" id="view_label"> Duration</label>
                        <input type="checkbox" id="view_duration" name = "view_duration" value="TotalDuration" checked/>

                        <label for="view_exercises" id="view_label"> Exercises</label>
                        <input type="checkbox" id="view_exercises" name="view_exercises" value="ExercisesList" checked/>

                        <label for="view_class" id="view_label"> Class</label>
                        <input type="checkbox" id="view_class" name="view_class" value="ClassTitle" />   
                    </div>

                    <div class="input-group">
                        <div class="input-group">
                            <label for="adv_filter" id="view_label"> View Advanced Display</label>
                            <input type="checkbox" id="adv_filter" name="adv_filter" value="use_adv"/>
                        </div> 

                        <div class="input-group">
                            <select class="att-select" name="att-select" id="att-select">
                                <option value="Exercise Count"> Exercise Count</option>
                                <option value="Exercise Duration"> Exercise Total Duration</option>
                            </select>
                        <!-- </div>  -->

                        <!-- <div class="input-group"> -->
                            <select class="comp-select" name="comp-select" id="comp-select">
                                <option value=">"> > </option>
                                <option value="<"> < </option>
                                <option value="="> = </option>
                            </select>
                        </div> 

                        <div class="input-group">
                            <input type="number" id="value-select" name="value-select" min=0 value=0 />
                        </div> 
                    </div>

                    <div class="input-group">
                        <button type="submit" class="btn" name="apply_filters">Apply</button>
                    </div>
                </form>
            </div>

            <div class="title">
                <h3>
                    Delete Workout
                </h3>
            </div>

            <div id="form-container">
                <form id="delete-workout" method="post" action="workouts.php">
                    <?php
                        $getWorkoutIDSQL = "SELECT WID FROM DoesWorkout WHERE UserID = :id";
                        $getWorkoutsIDStmt = oci_parse($c, $getWorkoutIDSQL);

                        oci_bind_by_name($getWorkoutsIDStmt, ":id", $_SESSION['id']);
                        $workoutIDs = array();
                        if(oci_execute($getWorkoutsIDStmt)) {
                            while($row = oci_fetch_assoc($getWorkoutsIDStmt)) {
                                $workoutIDs[] = $row['WID'];
                            }

                            echo "<select class='wid-select' name='wid-select' id='wid-select'>";
                            foreach($workoutIDs as $ids) {
                                echo "<option value={$ids}> {$ids} </option>";
                            }
                            echo "</select>";
                            
                            
                        } else {
                            $error = oci_error($getWorkoutsIDStmt);
                            $message = "Error retrieving workout IDs" . $error['message'];

                            echo "<script>alert('$message');</script>";
                        }
                    ?>

                    <div class="input-group">
                        <button type="submit" class="btn" name="delete_workout">Delete</button>
                    </div>
                </form>
            </div>
        </div>

        <!--Chart display-->
        <div id="wrapper-right">
            
                <?php
                    include('../db_config.php');
                    function displayWorkouts($c, $id, $filterstart, $filterend, $selectedAttributes, $sortBy, $advDisplayAtts, $showClassTitle) {
                        $getWorkoutsSQL = "SELECT Workout.WID, Workout.TotalCaloriesBurned, Workout.WorkoutDate, Workout.TotalDuration, ";
    
                        if ($showClassTitle) {
                            $getWorkoutsSQL .= "GroupClass.ClassTitle, ";
                        }
                        
                        $getWorkoutsSQL .= "(SELECT LISTAGG(Exercises.ExerciseName, ', ') WITHIN GROUP (ORDER BY Exercises.ExerciseName) 
                                            FROM WorkoutIncludeExercise
                                            JOIN Exercises ON WorkoutIncludeExercise.ExerciseName = Exercises.ExerciseName
                                            WHERE WorkoutIncludeExercise.WID = Workout.WID) AS ExercisesList
                                        FROM DoesWorkout
                                        JOIN Workout ON DoesWorkout.WID = Workout.WID ";

                        if ($showClassTitle) {
                            $getWorkoutsSQL .= " LEFT JOIN GroupClass ON Workout.WID = GroupClass.WID ";
                        }

                        $getWorkoutsSQL .= " WHERE DoesWorkout.UserID = :userID";

                        // $getWorkoutsSQL = "SELECT 
                        //                     Workout.WID, 
                        //                     Workout.TotalCaloriesBurned, 
                        //                     Workout.WorkoutDate, 
                        //                     Workout.TotalDuration,
                        //                     GroupClass.ClassTitle,
                        //                     (SELECT LISTAGG(Exercises.ExerciseName, ', ') WITHIN GROUP (ORDER BY Exercises.ExerciseName) 
                        //                         FROM WorkoutIncludeExercise
                        //                         JOIN Exercises ON WorkoutIncludeExercise.ExerciseName = Exercises.ExerciseName
                        //                         WHERE WorkoutIncludeExercise.WID = Workout.WID) AS ExercisesList
                        //                 FROM 
                        //                     DoesWorkout
                        //                     JOIN Workout ON DoesWorkout.WID = Workout.WID
                        //                     LEFT JOIN GroupClass ON Workout.WID = GroupClass.WID
                        //                 WHERE 
                        //                     DoesWorkout.UserID = :userID";


                        if($filterstart !== "") {
                            $getWorkoutsSQL .= " AND Workout.WorkoutDate >= TO_DATE(:start_date, 'YYYY-MM-DD')";
                        }

                        if($filterend !== "") {
                            $getWorkoutsSQL .= " AND Workout.WorkoutDate <= TO_DATE(:end_date, 'YYYY-MM-DD')";
                        }
                        $getWorkoutsSQL .= " GROUP BY Workout.WID, Workout.TotalCaloriesBurned, Workout.WorkoutDate, Workout.TotalDuration";
                        if ($showClassTitle) {
                            $getWorkoutsSQL .= ", GroupClass.ClassTitle";
                        }
                        if(!empty($advDisplayAtts)) {
                            $attribute = $advDisplayAtts[0];
                            $comparison = $advDisplayAtts[1];
                            $amount = $advDisplayAtts[2];

                            $advDisplaySQL = $getWorkoutsSQL;

                            if($attribute == "Exercise") {
                                $advDisplaySQL .= " HAVING COUNT(WorkoutIncludeExercise.ExerciseName) " .$comparison . " :value";
                            } else {
                                $advDisplaySQL .= " HAVING Workout.TotalDuration " .$comparison . " :value";
                            }

                            $advDisplaySQL .= " ORDER BY Workout.";
                            $advDisplaySQL .= $sortBy;

                            $advDisplayStmt = oci_parse($c, $advDisplaySQL);
                            
                            oci_bind_by_name($advDisplayStmt, ":userID", $id);

                            if($filterstart !== "") {
                                oci_bind_by_name($advDisplayStmt, ":start_date", $filterstart);
                            }
    
                            if($filterend !== "") {
                                oci_bind_by_name($advDisplayStmt, ":end_date", $filterend);
                            }

                            oci_bind_by_name($advDisplayStmt, ":value", $amount);
                            if(oci_execute($advDisplayStmt)) {
                                echo "<h3> Advanced Display: " . $attribute . $comparison . $amount . "</h3>"; 
                                echo "<table>";
                                echo "<tr>";
                                echo "<th> ID </th>";
                                echo "<th> Date </th>";
                                foreach($selectedAttributes as $header) {
                                    if($header == "TotalCaloriesBurned") {
                                        echo "<th> Calories Burned </th>"; 
                                    } else if ($header == "TotalDuration") {
                                        echo "<th> Duration (mins) </th>";
                                    } else if ($header == "ExercisesList") {
                                        echo "<th> Exercises </th>";
                                    } else if ($header == "ClassTitle") {
                                        echo "<th> Class </th>";
                                    }
                                }
                                echo "</tr>";

                                while($row = oci_fetch_assoc($advDisplayStmt)) {
                                    echo "<tr id=" . $row['WID'] . ">";
                                    echo "<td>" . $row['WID'] . "</td>"; 
                                    foreach($selectedAttributes as $value) {
                                        echo "<td>" . $row[strtoupper($value)] . "</td>";
                                    }
                                    echo "</tr>";
                                }
                                echo "</table>";
                            } else {
                                $error = oci_error($advDisplayStmt);
                                echo $error['message'];
                            }
                        }
                        
                        $getWorkoutsSQL .= " ORDER BY Workout.";
                        $getWorkoutsSQL .= $sortBy;

                        $getWorkoutsStmt = oci_parse($c, $getWorkoutsSQL);
                        

                        oci_bind_by_name($getWorkoutsStmt, ":userID", $id);

                        if($filterstart !== "") {
                            oci_bind_by_name($getWorkoutsStmt, ":start_date", $filterstart);
                        }

                        if($filterend !== "") {
                            oci_bind_by_name($getWorkoutsStmt, ":end_date", $filterend);
                        }
                        if(oci_execute($getWorkoutsStmt)) {
                            echo "<h3> Workouts </h3>";
                            echo "<table>";
                            echo "<tr>";
                            echo "<th> ID </th>";
                            echo "<th> Date </th>";
                            foreach($selectedAttributes as $header) {
                                if($header == "TotalCaloriesBurned") {
                                    echo "<th> Calories Burned </th>"; 
                                } else if ($header == "TotalDuration") {
                                    echo "<th> Duration (mins) </th>";
                                } else if ($header == "ExercisesList") {
                                    echo "<th> Exercises </th>";
                                }else if ($header == "ClassTitle") {
                                    echo "<th> Class </th>";
                                }
                            }
                            echo "</tr>";
                            
                            while($row = oci_fetch_assoc($getWorkoutsStmt)) {
                                echo "<tr id=" . $row['WID'] . ">";
                                echo "<td>" . $row['WID'] . "</td>";
                                foreach($selectedAttributes as $value) {
                                    echo "<td>" . $row[strtoupper($value)] . "</td>";
                                }
                                echo "</tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "error";
                        } 
                    } 

                    function buildAdvancedDisplaySQL($attribute, $comparison, $amount, $userID, $filterstart, $filterend) {
                        $advDisplaySQL = "";

                        if ($attribute == "Exercise Count") {
                            $advDisplaySQL = "SELECT we.ExerciseName, COUNT(*) AS ExerciseCount
                                            FROM DoesWorkout 
                                            JOIN Workout ON DoesWorkout.WID = Workout.WID 
                                            LEFT JOIN WorkoutIncludeExercise we ON Workout.WID = we.WID
                                            WHERE DoesWorkout.UserID = :userID";

                            if ($filterstart !== "") {
                                $advDisplaySQL .= " AND Workout.WorkoutDate >= TO_DATE(:start_date, 'YYYY-MM-DD')";
                            }
                            if ($filterend !== "") {
                                $advDisplaySQL .= " AND Workout.WorkoutDate <= TO_DATE(:end_date, 'YYYY-MM-DD')";
                            }

                            $advDisplaySQL .= " GROUP BY we.ExerciseName
                                                HAVING COUNT(*) $comparison :value";
                        } elseif ($attribute == "Exercise Duration") {
                            $advDisplaySQL = "SELECT we.ExerciseName, SUM(we.Duration) AS TotalDuration
                                            FROM DoesWorkout 
                                            JOIN Workout ON DoesWorkout.WID = Workout.WID 
                                            LEFT JOIN WorkoutIncludeExercise we ON Workout.WID = we.WID
                                            WHERE DoesWorkout.UserID = :userID";

                            if ($filterstart !== "") {
                                $advDisplaySQL .= " AND Workout.WorkoutDate >= TO_DATE(:start_date, 'YYYY-MM-DD')";
                            }
                            if ($filterend !== "") {
                                $advDisplaySQL .= " AND Workout.WorkoutDate <= TO_DATE(:end_date, 'YYYY-MM-DD')";
                            }

                            $advDisplaySQL .= " GROUP BY we.ExerciseName
                                                HAVING SUM(we.Duration) $comparison :value";
                        }

                        return $advDisplaySQL;
                    }

                    function displayAdvancedTable($stmt) {
                        echo "<h3>Advanced Display</h3>";
                        echo "<table>";
                        $firstRow = oci_fetch_assoc($stmt);
                        if ($firstRow) {
                            // Generate headers
                            echo "<tr>";
                            foreach (array_keys($firstRow) as $header) {
                                echo "<th>" . htmlspecialchars($header) . "</th>";
                            }
                            echo "</tr>";

                            // Output rows
                            do {
                                echo "<tr>";
                                foreach ($firstRow as $cell) {
                                    echo "<td>" . htmlspecialchars($cell) . "</td>";
                                }
                                echo "</tr>";
                            } while ($firstRow = oci_fetch_assoc($stmt));
                        } else {
                            echo "<tr><td>No data found</td></tr>";
                        }
                        echo "</table>";
                    }

                    if(isset($_POST['apply_filters'])) {
                        $id = $_SESSION['id'];
                        $start = $_POST['startdate'];
                        $end = $_POST['enddate'];
                        $sort = $_POST['sort-drop'];
                        $visibleAtts = array();
                        if(isset($_POST['view_date'])) {
                            array_push($visibleAtts, $_POST['view_date']);
                        }
                        if(isset($_POST['view_calories'])) {
                            array_push($visibleAtts, $_POST['view_calories']);
                        }
                        if(isset($_POST['view_duration'])) {
                            array_push($visibleAtts, $_POST['view_duration']);
                        }
                        if(isset($_POST['view_exercises'])) {
                            array_push($visibleAtts, $_POST['view_exercises']);
                        }
                        if(isset($_POST['view_class'])) {
                            array_push($visibleAtts, $_POST['view_class']);
                        }
                        
                        //Advanced view selected
                        $advDisplayAtts = array();
                        if(isset($_POST['adv_filter'])) {
                            array_push($advDisplayAtts, $_POST['att-select']);
                            array_push($advDisplayAtts, $_POST['comp-select']);
                            array_push($advDisplayAtts, $_POST['value-select']);
                        }
                        $showClassTitle = isset($_POST['view_class']);

                        displayWorkouts($c, $id, $start, $end, $visibleAtts, $sort, $advDisplayAtts, $showClassTitle);
                    } else {
                        $id = $_SESSION['id'];
                        // displayWorkouts($c, $id, "","", array("WorkoutDate", "TotalCaloriesBurned", "TotalDuration", "ExercisesList"), "WorkoutDate", $advDisplayAtts);
                        displayWorkouts($c, $id, "","", array("WorkoutDate", "TotalCaloriesBurned", "TotalDuration", "ExercisesList"), "WorkoutDate", $advDisplayAtts, false);
                    }
                
                ?>
            
        </div>
    </div>

    <script>
        function addNewClass(event) {
            event.preventDefault();
            //make exercise container invis
            var exerciseCont = document.getElementById('exercise-container');
            exerciseCont.style.display = 'none';

            var pastClasses = <?php echo json_encode($pastClasses); ?>;
            var uniqueNames = [... new Set(pastClasses.map(item => item.name))];
            console.log(uniqueNames);

            var classTable = document.getElementById('class-table');

            var classRow = classTable.insertRow(0);
            var classSelectCell = classRow.insertCell(0);

            var classSelectDrop = document.createElement("select");
            classSelectDrop.name = "class-select-dropdown";
            
            var classplaceholderOption = document.createElement("option");   
            classplaceholderOption.value = "";
            
            classplaceholderOption.text = "Select a class name";
            classplaceholderOption.disabled = true;
            classplaceholderOption.selected = true;

            classSelectDrop.appendChild(classplaceholderOption);

            uniqueNames.forEach(function(classObj) {
                var option = document.createElement('option');
                console.log(classObj);
                option.value = classObj;
                option.text = classObj;
                classSelectDrop.appendChild(option);
            });

            classSelectCell.appendChild(classSelectDrop);
            
            classSelectDrop.addEventListener('change', function() {
                showClassDates(classSelectDrop.value, classTable);
            });

            var deleteCell = classRow.insertCell(1);
            var deleteButton = document.createElement("button");
            deleteButton.type="button";

            deleteButton.className ="class-delete-button";
            deleteButton.innerHTML = "X";
            deleteButton.onclick = function(event) {
                event.preventDefault();
                if(classTable.rows.length == 2) {
                    classTable.deleteRow(1);
                }

                classTable.deleteRow(0);
                
                var exerciseCont = document.getElementById('exercise-container');
                exerciseCont.style.display = 'block';
            };
            
            deleteCell.appendChild(deleteButton);
            
        }

        function addNewExerciseField(event) {
            event.preventDefault();
            var classCont = document.getElementById('class-container');
            classCont.style.display = 'none';

            var exerciseTable = document.getElementById('exercise-table');
            if (exerciseTable.rows.length === 0) {
                var title = document.getElementById('exercise-title');
                title.style.display = 'block';
                var titleRow = exerciseTable.insertRow(0);

                var exerciseHeaderCell = titleRow.insertCell(0);
                var intensityHeaderCell = titleRow.insertCell(1);
                var calorieHeaderCell = titleRow.insertCell(2);
                var durationHeaderCell = titleRow.insertCell(3);
                var deleteHeaderCell = titleRow.insertCell(4);

                exerciseHeaderCell.innerHTML = "Exercise";
                intensityHeaderCell.innerHTML = "Intensity";
                calorieHeaderCell.innerHTML = "Calories(/30 mins)";
                durationHeaderCell.innerHTML = "Duration(mins)";
            }

            var row = exerciseTable.insertRow(1);
            var selectCell = row.insertCell(0);
            
            var selectDrop = document.createElement("select");

            selectDrop.name = "exercise-dropdown[]";
            

            <?php
                foreach ($exercises as $exercise) {
                    $text = $exercise->name . ', ' . $exercise->intensity;
                    echo "var option = document.createElement('option');";
                    echo "option.value = '{$exercise->name}';";
                    echo "option.text = '{$text}';";
                    echo "selectDrop.appendChild(option);";
                }
            ?>

            var placeholderOption = document.createElement("option");   
            placeholderOption.value = "";
            
            placeholderOption.text = "Select an exercise";
            placeholderOption.disabled = true;
            placeholderOption.selected = true;

            selectDrop.appendChild(placeholderOption);

            selectCell.appendChild(selectDrop);

            var intensityCell = row.insertCell(1);
            var caloriesCell = row.insertCell(2);
            var durationCell = row.insertCell(3);

            selectDrop.addEventListener('change', function() {
                changeIntensityAndCalories(selectDrop.value, intensityCell, caloriesCell);
            });

            var durationInput = document.createElement("input");
            durationInput.type = "number";
            durationInput.name = "exercise-duration[]";
            durationInput.min = 0;
            durationInput.value = 0;
            durationCell.appendChild(durationInput);

            var deleteCell = row.insertCell(4);
            var deleteButton = document.createElement("button");
            deleteButton.type="button";

            deleteButton.className ="exercise-delete-button";
            deleteButton.innerHTML = "X";
            deleteButton.onclick = function(event) {
                event.preventDefault();
                if(exerciseTable.rows.length === 2) {
                    var title = document.getElementById('exercise-title');
                    title.style.display = 'none';
                    exerciseTable.deleteRow(row.rowIndex);
                    exerciseTable.deleteRow(row.rowIndex)-1;
                    var classCont = document.getElementById('class-container');
                    classCont.style.display = 'block';
                } else {
                    exerciseTable.deleteRow(row.rowIndex);
                }
                

            };
            
            deleteCell.appendChild(deleteButton);
            
            
        }

        function changeIntensityAndCalories(exerciseName, intensityCell, caloriesCell) {
            var selectedExerciseObj = <?php echo json_encode($exercises); ?>.find(function (exercise) {
                return exercise.name === exerciseName;
            });

            if (selectedExerciseObj) {
                intensityCell.innerHTML = selectedExerciseObj.intensity;
                caloriesCell.innerHTML = selectedExerciseObj.calories;
            }
        }

        function showClassDates(className, classTable) {
            if(classTable.rows.length !== 1) {
                classTable.deleteRow(1);
            }
            var row = classTable.insertRow(1);
            var selectCell = row.insertCell(0);

            var dateDrop = document.createElement("select");
            dateDrop.name = "class-date-dropdown";
            dateDrop.id = "class-date-dropdown";
            var classes = <?php echo json_encode($classes); ?>;
            
            classes.forEach(function (classObj) {
                if(classObj.name == className) {
                    var option = document.createElement('option');
                    option.value = classObj.id;
                    option.text = classObj.id + ", " + classObj.date;
                    dateDrop.appendChild(option);
                }
            });

            selectCell.appendChild(dateDrop);
            
        }
    </script>
</body>
</html>