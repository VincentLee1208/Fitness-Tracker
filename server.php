<?php
	session_start();
	
	if ($c=OCILogon("ora_cpan0", "a95483459", "dbhost.students.cs.ubc.ca:1522/stu")) {
		
		echo "Successfully connected to Oracle.\n";
		
		$createDefaultSQL = file_get_contents('sql/initialization.sql');
		$createDefaultArray = explode(";", $createDefaultSQL);
		array_pop($createDefaultArray);
		if(empty($createDefaultArray)) {
			//echo "File not found";
		} else {
			foreach($createDefaultArray as $createStmt){
				$createDefaultStmt = oci_parse($c, $createStmt);
			
				if(oci_execute($createDefaultStmt)) {
					//echo "Data created successfully. Redirecting to Login";
				} else {
					//echo "\nFailed to execute";
				}

				//oci_free_statement($createDefaultStmt);
			}

			header('location: views/login.php');

		}
		
	} else {
		$err = OCIError();
		echo "Oracle Connect Error " . $err['message'];
	}

	OCILogoff($c);
?>