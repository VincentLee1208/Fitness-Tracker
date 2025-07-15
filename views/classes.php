<?php
    session_start();
    include('../db_config.php');

    //Grab classes
    class classObj {
        public $name;
        public $intensity;
        public $id;
        public $instructor;
        public $instructor_id;
        public $price;
        public $date;
        public $duration;
        public $capacity;
    }
     

    $classes = array();
    
    $getClassSQL = "SELECT 
            GroupClass.WID, 
            GroupClass.ClassTitle, 
            GroupClass.Intensity, 
            Trainer.TrainerName,
            Trainer.TID,
            Class_Price.ClassPrice,
            Workout.WorkoutDate, 
            Workout.TotalDuration 
            FROM GroupClass
            JOIN Class_Price ON GroupClass.ClassTitle = Class_Price.ClassTitle
            JOIN Workout ON GroupClass.WID = Workout.WID
            JOIN Class_Trainer ON GroupClass.ClassTitle = Class_Trainer.ClassTitle
            JOIN Trainer ON Class_Trainer.TID = Trainer.TID";

    $getClassStmt = oci_parse($c, $getClassSQL);
    
    if(oci_execute($getClassStmt)) {
        while($row = oci_fetch_assoc($getClassStmt)) {
            $newClass = new classObj();
            $newClass->name = $row['CLASSTITLE'];
            $newClass->intensity = $row['INTENSITY'];
            $newClass->id = $row['WID'];
            $newClass->instructor = $row['TRAINERNAME'];
            $newClass->instructor_id = $row['TID'];
            $newClass->price = $row['CLASSPRICE'];
            $newClass->date = $row['WORKOUTDATE'];
            $newClass->duration = $row['TOTALDURATION'];
            $newClass->capacity = 30;
            array_push($classes, $newClass);
        }
    } else {
        echo "Failed to get classes";
    }
    

    oci_free_statement($getClassStmt);

    $currentDate = new Datetime();
    $futureClasses = array();
    $pastClasses = array();

    foreach($classes as $class) {
        $classDate = new DateTime($class->date);
        if($classDate > $currentDate) {
            $futureClasses[] = $class;
        } else {
            $pastClasses[] = $class;
        }
    }

    // Get Trainers
    class trainerObj {
        public $name;
        public $tID;
    }

    $trainers = array();
    $getTrainersSQL = "SELECT * FROM Trainer";
    $getTrainersStmt = oci_parse($c, $getTrainersSQL);

    if(oci_execute($getTrainersStmt)) {
        while($row = oci_fetch_assoc($getTrainersStmt)) {
            $newTrainer = new trainerObj();
            $newTrainer->name = $row['TRAINERNAME'];
            $newTrainer->tID = $row['TID'];
            array_push($trainers, $newTrainer);
        }
    } else{
        echo "Failed to get trainers";
    }

    //Button Handlers
    $message = '';
    
    $updatePerformed = false;

    if(isset($_POST['update_class'])) {
        
        $selectedClass = null;
        $cID = $_POST['class-select'];
        foreach($classes as $class) {
            if($class->id === $_POST['class-select']) {
                $selectedClass = $class;
                break;
            }
        }
        
        $newClassName = $_POST['class-name'];
        $newClassIntensity = $_POST['class-intensity'];
        $newClassInstructor = $_POST['trainer-select'];
        $newClassPrice = $_POST['class-price'];
        $newClassDate = $_POST['class-date'];
        $newClassDuration = $_POST['class-duration'];
        
        $endBool = False;
        $className = $selectedClass->name;

        if($newClassName !== $selectedClass->name) {
            //Check if newClassName already exists in Class_Price or Class_Trainer
            $checkNameExistsSQL = "SELECT ClassTitle FROM Class_Price WHERE ClassTitle = :cName";
            $checkNameExistsStmt = oci_parse($c, $checkNameExistsSQL);

            oci_bind_by_name($checkNameExistsStmt, ":cName", $newClassName);
            oci_execute($checkNameExistsStmt);

            //Name doesn't already exist
            if(!oci_fetch_assoc($checkNameExistsStmt)) {
                $className = $newClassName;

                //delete old Class_Trainer and Class_Price entry
                $deleteClassTrainerSQL = "DELETE FROM Class_Trainer WHERE ClassTitle = :selectedClass";
                $deleteClassTrainerStmt = oci_parse($c, $deleteClassTrainerSQL);

                oci_bind_by_name($deleteClassTrainerStmt, ":selectedClass", $selectedClass->name);
                oci_execute($deleteClassTrainerStmt);

                $deleteClassPriceSQL = "DELETE FROM Class_Price WHERE ClassTitle = :selectedClass";
                $deleteClassPriceStmt = oci_parse($c, $deleteClassPriceSQL);

                oci_bind_by_name($deleteClassPriceStmt, ":selectedClass", $selectedClass->name);
                oci_execute($deleteClassPriceStmt);

                //create new Class_Trainer, Class_Price, and Group Class Entry
                $newClassTrainerSQL = "INSERT INTO Class_Trainer (ClassTitle, TID) VALUES (:className, :instructor_id)";
                $newClassTrainerStmt = oci_parse($c, $newClassTrainerSQL);

                oci_bind_by_name($newClassTrainerStmt, ":className", $className);
                oci_bind_by_name($newClassTrainerStmt, ":instructor_id", $selectedClass->instructor_id);

                oci_execute($newClassTrainerStmt);

                $newClassPriceSQL = "INSERT INTO Class_Price (ClassTitle, ClassPrice) VALUES (:className, :classPrice)";
                $newClassPriceStmt = oci_parse($c, $newClassPriceSQL);

                oci_bind_by_name($newClassPriceStmt, ":className", $className);
                oci_bind_by_name($newClassPriceStmt, ":classPrice", $selectedClass->price);

                oci_execute($newClassPriceStmt);

                $newGroupClassSQL = "INSERT INTO GroupClass (WID, ClassTitle, Intensity) VALUES (:wID, :className, :classIntensity)";
                $newGroupClassStmt = oci_parse($c, $newGroupClassSQL);

                oci_bind_by_name($newGroupClassStmt, ":wID", $selectedClass->id);
                oci_bind_by_name($newGroupClassStmt, ":className", $className);
                oci_bind_by_name($newGroupClassStmt, ":classIntensity", $selectedClass->intensity);

                if(!oci_execute($newGroupClassStmt)) {
                    $message = "Failed to change GroupClass Name";
                }else{
                    $message = "Successfully updated";
                    $updatePerformed = true;
                }
            } else {
                $updateGroupClassSQL = "UPDATE GroupClass SET ClassTitle = :newClassName WHERE WID = :wID";
                $updateGroupClassStmt = oci_parse($c, $updateGroupClassSQL);
                oci_bind_by_name($updateGroupClassStmt, ":newClassName", $newClassName);
                oci_bind_by_name($updateGroupClassStmt, ":wID", $selectedClass->id);

                if (!oci_execute($updateGroupClassStmt)) {
                    $message = "Failed to update GroupClass with new class title.";
                } else {
                    $message = "Successfully updated";
                    $updatePerformed = true;
                }
                // $message = "Class Title is already in use! Please choose a new name";
                $endBool = True;
            }
            
        }
        //Update class intensity
        if ($newClassIntensity !== $selectedClass->intensity) {
            $updateIntensitySQL = "UPDATE GroupClass SET Intensity = :intensity WHERE WID = :wID";
            
            $updateIntensityStmt = oci_parse($c, $updateIntensitySQL);

            // Binding the variables to the placeholders in the SQL statement
            oci_bind_by_name($updateIntensityStmt, ":intensity", $newClassIntensity);
            oci_bind_by_name($updateIntensityStmt, ":wID", $selectedClass->id);

            if (!oci_execute($updateIntensityStmt)) {
                $error = oci_error($updateIntensityStmt);
                $message = "Failed to update intensity" . $error['message'];
            }else{
                $message = "Successfully updated";
                $updatePerformed = true;
            }
        }
        
        //Update class price
        if($newClassPrice !== $selectedClass->price) {
            $updatePriceSQL = "UPDATE Class_Price SET ClassPrice = :newPrice WHERE ClassTitle = :className";
            
            $updatePriceStmt = oci_parse($c, $updatePriceSQL);

            oci_bind_by_name($updatePriceStmt, ":newPrice", $newClassPrice);
            oci_bind_by_name($updatePriceStmt, ":className", $className);

            if(!oci_execute($updatePriceStmt)) {
                $message = "Failed to update price";
            }else{
                $message = "Successfully updated";
                $updatePerformed = true;
            }
        }

        //Update class date
        if($newClassDate !== date('Y-m-d', strtotime($selectedClass->date))) {
            $updateDateSQL = "UPDATE Workout SET WorkoutDate = TO_DATE(:newDate, 'YYYY-MM-DD') WHERE WID = :selectedID";
            $updateDateStmt = oci_parse($c, $updateDateSQL);

            oci_bind_by_name($updateDateStmt, ":newDate", $newClassDate);
            oci_bind_by_name($updateDateStmt, ":selectedID", $selectedClass->id);

            if(!oci_execute($updateDateStmt)) {
                $error = oci_error($updateDateStmt);
                $message = "Failed to update date" . $error['message'];
            }else{
                $message = "Successfully updated";
                $updatePerformed = true;
            }
        }

        //Update class duration
        if($newClassDuration !== $selectedClass->duration) {
            $updateDurationSQL = "UPDATE Workout SET TotalDuration = :duration WHERE WID = :selectedID";
            $updateDurationStmt = oci_parse($c, $updateDurationSQL);

            oci_bind_by_name($updateDurationStmt, ":duration", $newClassDuration);
            oci_bind_by_name($updateDurationStmt, ":selectedID", $selectedClass->id);

            if(!oci_execute($updateDurationStmt)) {
                $error = oci_error($updateDurationStmt);
                $message = "Failed to update duration" . $error['message'];
            }else{
                $message = "Successfully updated";
                $updatePerformed = true;
            }
        }
        
        //Update class instructor
        if($newClassInstructor !== $selectedClass->instructor_id) {
            $updateClassTrainerSQL = "UPDATE Class_Trainer SET TID = :tID WHERE ClassTitle = :selectedTitle";
            $updateClassTrainerStmt = oci_parse($c, $updateClassTrainerSQL);

            oci_bind_by_name($updateClassTrainerStmt, ":tID", $newClassInstructor);
            oci_bind_by_name($updateClassTrainerStmt, ":selectedTitle", $selectedClass->name);

            if(!oci_execute($updateClassTrainerStmt)) {
                $error = oci_error($updateClassTrainerStmt);
                $message = "Failed to update trainer" . $error['message'];
            }else{
                $message = "Successfully updated";
                $updatePerformed = true;
            }
        }
        
        echo "<script>alert('$message');</script>";
    }


    // Fetch Data Logic
    if ($updatePerformed) {
        $classes = array();
        $getClassSQL = "SELECT 
            GroupClass.WID, 
            GroupClass.ClassTitle, 
            GroupClass.Intensity, 
            Trainer.TrainerName,
            Trainer.TID,
            Class_Price.ClassPrice,
            Workout.WorkoutDate, 
            Workout.TotalDuration 
            FROM GroupClass
            JOIN Class_Price ON GroupClass.ClassTitle = Class_Price.ClassTitle
            JOIN Workout ON GroupClass.WID = Workout.WID
            JOIN Class_Trainer ON GroupClass.ClassTitle = Class_Trainer.ClassTitle
            JOIN Trainer ON Class_Trainer.TID = Trainer.TID";

        $getClassStmt = oci_parse($c, $getClassSQL);
        
        if(oci_execute($getClassStmt)) {
            while($row = oci_fetch_assoc($getClassStmt)) {
                $newClass = new classObj();
                $newClass->name = $row['CLASSTITLE'];
                $newClass->intensity = $row['INTENSITY'];
                $newClass->id = $row['WID'];
                $newClass->instructor = $row['TRAINERNAME'];
                $newClass->instructor_id = $row['TID'];
                $newClass->price = $row['CLASSPRICE'];
                $newClass->date = $row['WORKOUTDATE'];
                $newClass->duration = $row['TOTALDURATION'];
                $newClass->capacity = 30;
                array_push($classes, $newClass);
            }
        } else {
            echo "Failed to get classes";
        }
        

        oci_free_statement($getClassStmt);

        $currentDate = new Datetime();
        $futureClasses = array();
        $pastClasses = array();

        foreach($classes as $class) {
            $classDate = new DateTime($class->date);
            if($classDate > $currentDate) {
                $futureClasses[] = $class;
            } else {
                $pastClasses[] = $class;
            }
        }
        $updatePerformed = false;
    }
    
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../css/classes.css">
    <title>Classes</title>
</head>

<body>
    <?php include('../views/header.php'); ?>
    <div id="wrapper">

        <div class="page-top">
            <div class="title">
                <h2>
                    Future Classes
                </h2>
            </div>
            <form method="post" action="classes.php">
                <table>
                    <thead>
                        <tr>
                            <th>Class ID</th>
                            <th>Class name</th>
                            <th>Class intensity</th>
                            <th>Class instructor</th>
                            <th>Price</th>
                            <th>Datetime</th>
                            <th>Duration</th>
                            <th>Capacity</th>
                            <?php
                                if($_SESSION['username'] !== 'admin') {
                                    echo "<th>Class register button</th>";
                                } 
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach($futureClasses as $class) {
                                echo "<tr id=" . $class->id . ">";
                                echo "<td>" . $class->id . "</td>";
                                echo "<td>" . $class->name . "</td>";
                                echo "<td>" . $class->intensity . "</td>";
                                echo "<td>" . $class->instructor. "</td>";
                                echo "<td>$" . $class->price . "</td>";
                                echo "<td>" . $class->date . "</td>";
                                echo "<td>" . $class->duration . "</td>";
                                echo "<td> 30 </td>";
                                if($_SESSION['username'] !== 'admin') {
                                    echo "<td><button>Register</button></td>";
                                } 
                                echo "</tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </form>

            <?php
                if($_SESSION['username'] == "admin") {

                    echo '<div class="title">';
                    echo '<h2>';
                    echo 'Update Class';
                    echo '</h2>';
                    echo '</div>';
                    echo '<div id="update-container">';
                    echo '<form id="update" method="post" actions="classes.php">';
                    echo '<label for="class-select"> Choose a class to update: </label>';
                    echo '<select id="class-select" name="class-select">';
                                
                    foreach($futureClasses as $class) {
                        echo "<option value='{$class->id}'>{$class->id}</option>";
                    }
                    
                    echo '</select>';
                            
                    echo '<div class="input-group">';
                    echo '<label for="class-name">Class Name:</label>';
                    echo "<input type='text' id='class-name' name='class-name' value='{$futureClasses[0]->name}'/>";
                    echo '</div>';

                    echo '<div class="input-group">';
                    echo '<label for ="class-intensity">Class Intensity:</label>';
                    echo '<select id="class-intensity" name="class-intensity">';
                    echo '<option value="Low">Low</option>';
                    echo '<option value="Median">Median</option>';
                    echo '<option value="High">High</option>';
                    echo '</select>';
                    echo '</div>';

                    echo '<label for="trainer-select">Class Trainer:</label>';
                    echo '<select id="trainer-select" name="trainer-select">';
                    
                    echo "<option value='{$futureClasses[0]->instructor_id}'>{$futureClasses[0]->instructor},{$futureClasses[0]->instructor_id}</option>";
                    
                    foreach($trainers as $trainer) {
                        $isInstructor = false;
                        foreach($classes as $class) {
                            if($class->instructor_id == $trainer->tID) {
                                $isInstructor = true;
                                break;
                            }
                        }

                        if(!$isInstructor) {
                            echo "<option value='{$trainer->tID}'>{$trainer->name},{$trainer->tID}</option>";
                        }
                    }
                    
                    echo '</select>';
                    echo '<div class ="input-group">';
                    echo '<label for="class-price">Class Price:</label>';
                    
                    echo "<input type='number' id='class-price' name='class-price' value='{$futureClasses[0]->price}'/>";
                    echo '</div>';
                    
                    echo '<div class="input-group">';
                    echo '<label for="class-date">Class Date:</label>';
                    
                    $date = date('Y-m-d', strtotime($futureClasses[0]->date));
                    echo "<input type='date' id='class-date' name='class-date' value='{$date}'/>";
                    echo '</div>';
                    
                    echo '<div class="input-group">';
                    echo '<label for ="class-duration">Class Duration:</label>';
                    
                    echo "<input type='number' id='class-duration' name='class-duration' value='{$futureClasses[0]->duration}'/>";
                    
                    echo '</div>';
                    
                    echo '<button type="submit" class="btn" name="update_class">Update Class</button>';
                    echo '</form>';
                    echo '</div>'; 
                }
            ?>

            <div class="title">
                <h2>
                    Past Classes
                </h2>
            </div>
            <form method="post" actions="classes.php">
                <table>
                    <thead>
                        <tr>
                            <th>Class ID</th>
                            <th>Class name</th>
                            <th>Class intensity</th>
                            <th>Class instructor</th>
                            <th>Price</th>
                            <th>Datetime</th>
                            <th>Duration</th>
                            <th>Capacity</th>
                            <?php
                                if($_SESSION['username'] == 'admin') {
                                    echo "<th></th>";
                                }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach($pastClasses as $class) {
                                echo "<tr id=" . $class->id . ">";
                                echo "<td>" . $class->id . "</td>";
                                echo "<td>" . $class->name . "</td>";
                                echo "<td>" . $class->intensity. "</td>";
                                echo "<td>" . $class->instructor. "</td>";
                                echo "<td>$" . $class->price . "</td>";
                                echo "<td>" . $class->date . "</td>";
                                echo "<td>" . $class->duration . "</td>";
                                echo "<td> 30 </td>";
                                if($_SESSION['username'] == 'admin') {
                                    echo "<td><input type='checkbox' id='{$class->id}_class' name='class_name[]' value='{$class->id}'/></td>";
                                } 
                                echo "</tr>";
                            }
                        ?>
                    </tbody>
                </table>
                
                <?php 
                    if($_SESSION['username'] == "admin") {
                        echo "<h2 class='title'> Class/User Division </h2>";
                        
                        echo '<div class ="input-group">';
                        echo "<label for='see_user_names'>Usernames</label>";
                        echo "<input type='checkbox' id='see_user_names' name='see_user_names' value='Username' checked/>";
                        echo '</div>';

                        echo '<div class ="input-group">';
                        echo "<label for='see_user_ages'>Ages</label>";
                        echo "<input type='checkbox' id='see_user_ages' name='see_user_ages' value='Age' checked/>";
                        echo '</div>'; 

                        echo '<div class ="input-group">';
                        echo "<label for='see_user_gender'>Gender</label>";
                        echo "<input type='checkbox' id='see_user_gender' name='see_user_gender' value='Gender' checked/>";
                        echo '</div>'; 
                        
                        echo '<div class ="input-group">';
                        echo "See user who have attended ";
                        
                        echo "<select name='all_some_select' id='all_some_select'>";
                        echo "<option value='All'>all</option>";
                        echo "<option value='Some'>some</option>";
                        echo "</select>";
                        echo " of the selected classes";
                        echo '</div>'; 

                        echo '<div id="button-container">';
                        echo '<button type="submit" class="btn" name="see_users">See User Attendance</button>';
                        echo '</div>';
                    }
                ?>
            </form>


            <?php
                if(isset($_POST['see_users'])) {
                    $displayAtts = array();

                    if(isset($_POST['see_user_names'])) {
                        $username = "UserInfo." . $_POST['see_user_names'];
                        array_push($displayAtts, $username);
                    }
                    if(isset($_POST['see_user_ages'])) {
                        $userage = "UserInfo." . $_POST['see_user_ages'];
                        array_push($displayAtts, $userage);
                    }
                    if(isset($_POST['see_user_gender'])) {
                        $usergender = "UserInfo." . $_POST['see_user_gender'];
                        array_push($displayAtts, $usergender);
                    }

                    $seeClasses = $_POST['class_name'];
                    $attendanceSQL = null;
                    if(!empty($displayAtts) && !empty($seeClasses)) {
                        if($_POST['all_some_select'] == "All") {
                            $attendanceSQL = "SELECT UserInfo.UserID, " . implode(", ", $displayAtts) . " FROM DoesWorkout 
                            JOIN UserInfo ON DoesWorkout.UserID = UserInfo.UserID 
                            JOIN GroupClass ON GroupClass.WID = DoesWorkout.WID 
                            WHERE DoesWorkout.WID IN ('" . implode("', '", $seeClasses) . "') 
                            GROUP BY UserInfo.UserID, " . implode(", ", $displayAtts) . "
                            HAVING COUNT(DISTINCT GroupClass.ClassTitle) = " . count($seeClasses);
                        } else {
                            $attendanceSQL = "SELECT GroupClass.ClassTitle, 
                            LISTAGG(UserInfo.UserID || ', ' || " . implode(" || ', ' || ", $displayAtts) . " || '|', CHR(10)) 
                            WITHIN GROUP (ORDER BY UserInfo.UserID) AS UserList 
                            FROM DoesWorkout 
                            JOIN UserInfo ON DoesWorkout.UserID = UserInfo.UserID 
                            JOIN GroupClass ON GroupClass.WID = DoesWorkout.WID 
                            WHERE DoesWorkout.WID IN ('" . implode("', '", $seeClasses) . "')
                            GROUP BY GroupClass.ClassTitle";
                            
                        }
                        $attendanceStmt = oci_parse($c, $attendanceSQL);
                        
                        if(oci_execute($attendanceStmt)) {
                            echo "<table>";
                            echo "<thead>";
                            echo "<tr>";
                            
                            if($_POST['all_some_select'] == "Some") {
                                echo "<th>Classes</th>";
                                echo "<th>Users</th>";
                            } 
                            if($_POST['all_some_select'] == "All") {
                                echo "<th>User ID</th>";

                                if(isset($_POST['see_user_names'])) {
                                    echo "<th>Username</th>";

                                }
                                if(isset($_POST['see_user_ages'])) {
                                    echo "<th>User Age</th>";
                                }
                                if(isset($_POST['see_user_gender'])) {
                                    echo "<th>User Gender</th>";
                                }
                            }
                    
                    
                            echo "</tr>";
                            echo "</thead>";
                            
                            echo "<tbody>";
                            while($row = oci_fetch_assoc($attendanceStmt)) {
                                if($_POST['all_some_select'] == "All") {
                                    echo "<tr>";
                                    echo "<td>{$row['USERID']}</td>";
                                    
                                    if(isset($_POST['see_user_names'])) {
                                        echo "<td>{$row['USERNAME']}</td>";
                                    }
                                    if(isset($_POST['see_user_ages'])) {
                                        echo "<td>{$row['AGE']}</td>";
                                    }
                                    if(isset($_POST['see_user_gender'])) {
                                        echo "<td>{$row['GENDER']}</td>";
                                    }
                                    
                                    echo "</tr>";
                                } else {
                                    $users = explode("|", $row['USERLIST']);
                                    var_dump($users);
                                    echo "<tr>";
                                    echo "<td>" . $row['CLASSTITLE'] . "</td>";
                                    
                                    echo "<td><ul>";
                                    foreach($users as $user) {
                                        if($user !== "") {
                                            echo "<li>{$user}</li>";
                                        }
                                    }
                                    echo "</ul></td>";
                                    echo "</tr>";
                                }
                                
                                
                                
                            }
                            echo "</tbody>";
                            echo "</table>";
                        } else {
                            $error = oci_error($attendanceStmt);
                            $message = $error['message'];
                        }
                        
                        
                    } else {
                        $message = "Please select a class and attributes to display";
                    }

                    echo "<script>alert('$message');</script>";
                }
            ?>
        </div>
    </div>
    
    <script>
        var classIDSelect = document.getElementById('class-select');
        var classNameInput = document.getElementById('class-name');
        var classIntensity = document.getElementById('class-intensity');
        var classTrainerSelect = document.getElementById('trainer-select');
        var classPriceInput = document.getElementById('class-price');
        var classDateSelect = document.getElementById('class-date');
        var classDurationInput = document.getElementById('class-duration');
        var allClasses = <?php echo json_encode($classes) ?>;
        var allTrainers = <?php echo json_encode($trainers) ?>;
        if(classIDSelect != null) {
            classIDSelect.addEventListener('change', function() {
                var selectedID = this.value;
                var selectedClass = allClasses.find(function(classObj) {
                    return classObj.id == selectedID;
                });

                if(selectedClass) {
                    classNameInput.value = selectedClass.name;
                    classIntensity.value = selectedClass.intensity;
                    
                    classTrainerSelect.options.length = 0;

                    var trainerOption = document.createElement('option');
                    trainerOption.value = selectedClass.instructor_id;
                    trainerOption.textContent = selectedClass.instructor + ',' + selectedClass.instructor_id;
                    classTrainerSelect.appendChild(trainerOption);

                    
                    for (var i = 0; i < allTrainers.length; i++) {
                        var trainer = allTrainers[i];
                        var isInstructor = false;

                        for(var j = 0; j < allClasses.length; j++) {
                            if(allClasses[j].instructor_id == trainer.tID) {
                                isInstructor = true;
                                break;
                            }
                        }

                        if(!isInstructor) {
                            trainerOption = document.createElement('option');
                            trainerOption.value = trainer.tID;
                            trainerOption.textContent = trainer.name + ',' + trainer.tID;
                            classTrainerSelect.appendChild(trainerOption);
                        }
                    }

                    classTrainerSelect.value = selectedClass.instructor_id;
                    classPriceInput.value = selectedClass.price;

                    var classDate = new Date(selectedClass.date);
                    var formattedDate = classDate.toISOString().split('T')[0];
                    classDateSelect.value = formattedDate;

                    classDurationInput.value = selectedClass.duration;
                }
            });
        }
        

        
    </script>
</body>

</html>