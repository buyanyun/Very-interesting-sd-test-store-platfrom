<?php

//this tells the system that it's no longer just parsing
//html; it's now parsing PHP

$success = True; //keep track of errors so it redirects the page only if there are no errors
$db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");

// TODO: for Call SQL for each functionality
// Connect Oracle...
if ($db_conn) {

  // 按重置按钮：SQL initialization
	if (array_key_exists('reset', $_POST)) {
    echo "<br>Creating the table and Loading the initialization data...";
    $sql = file_get_contents('../cs304/PROJECT/SDDB_initalization.sql');
    $list = split(';', $sql);
    foreach ($list as $i => $item) {
        if ($i !== sizeof($list) - 1) {
            executePlainSQL($item);
        }
    }
    OCICommit($db_conn);

	} else if (array_key_exists('addaccount', $_POST)) {
			//Getting the values from user and insert data into the table
      if (is_numeric($_POST['password'])) {
        $accountNo = getNewID("account");
        $newtestdriver = $_POST['testdriver'];

  			executePlainSQL("INSERT INTO Developer VALUES (
                                {$accountNo},
                                {$_POST['password']},
                                '{$_POST['name']}'
                                )");
        if ($newtestdriver) {
          executePlainSQL("INSERT INTO TestDriver VALUES (
                                {$accountNo},
                                '{$_POST['driverID']}',
                                {$_POST['phonenumber']}
                                )");
        }
        OCICommit($db_conn);
        popWindow("add account successfully, your accountNo is $accountNo");
      }

	} else if (array_key_exists('removeaccount', $_POST)) {
      //Remove account from Developer table and TestDriver table
      if (is_numeric($_POST['accountNo']) && is_numeric($_POST['password'])) {
        $findAccount = executePlainSQL("SELECT Developer.accountNo
                                        FROM Developer
                                        WHERE Developer.accountNo = {$_POST['accountNo']}");
        $findPassword = executePlainSQL("SELECT Developer.password
                                         FROM Developer
                                         WHERE Developer.accountNo = {$_POST['accountNo']}");
        if ($findAccount && OCI_Fetch_Array($findPassword) == {$_POST['password']}) {
          executePlainSQL("DELETE FROM Developer
                           WHERE Developer.accountNo = {$_POST['accountNo']}");
          OCICommit($db_conn);
          popWindow("delete account successfully");
        }
      }


	} else if (array_key_exists('addcarwithdevice', $_POST)) {
      if (is_numeric($_POST['deviceID']) && is_numeric($_POST['carID']) && is_numeric($_POST['versionID'])) {
        $findCarID = executePlainSQL("SELECT CarWithDevice.carID
                                      FROM CarWithDevice
                                      WHERE CarWithDevice.carID = {$_POST['carID']}");
				$findversionID = executePlainSQL("SELECT SelfDrivingSoftware.versionID
																			FROM SelfDrivingSoftware
																			WHERE SelfDrivingSoftware.versionID = {$_POST['versionID']}");

        if (!$findCarID && !$findversionID) {
          executePlainSQL("INSERT INTO CarWithDevice VALUES (
                                {$_POST['carID']},
                                '{$_POST['cartype']}',
                                {$_POST['deviceID']},
																{$_POST['versionID']}
                                )");
          OCICommit($db_conn);
          popWindow("add car with device successfully");
        }
      }

  } else if (array_key_exists('removecarwithdevice', $_POST)) {
      if (empty($_POST['CarWithDevice'])) {
        executePlainSQL("DELETE FROM CarWithDevice
                         WHERE CarWithDevice.carID = {$_POST['CarWithDevice']}");
        OCICommit($db_conn);
        popWindow("remove car with device successfully");
      }

  } else if (array_key_exists('changedeviceofcar', $_POST)) {
      if (is_numeric($_POST['deviceID'])){
        $findCarID = executePlainSQL("SELECT CarWithDevice.carID
                                      FROM CarWithDevice
                                      WHERE CarWithDevice.carID = {$_POST['carID']}");
        if ($findCarID) {
          executePlainSQL("UPDATE CarWithDevice
                           SET CarWithDevice.deviceID = {$_POST['deviceID']}
                           WHERE CarWithDevice.carID = {$_POST['carID']}");
          OCICommit($db_conn);
          popWindow("update device successfully");
        }
      }

  } else if (array_key_exists('addsdsoftware', $_POST)) {
      if (is_numeric($_POST['versionID'])){
        $findversionID = executePlainSQL("SELECT SelfDrivingSoftware.versionID
                                      FROM SelfDrivingSoftware
                                      WHERE SelfDrivingSoftware.versionID = {$_POST['versionID']}");
        if (!$findversionID) {
          executePlainSQL("INSERT INTO SelfDrivingSoftware VALUES (
                                {$_POST['versionID']},
                                '{$_POST['updatetime']}',
                                '{$_POST['comment_content']}'
                                )");
          OCICommit($db_conn);
          popWindow("add self-driving software successfully");
        }
      }

  } else if (array_key_exists('changesdsoftwareforcar', $_POST)) {
      if (is_numeric($_POST['versionID']) && is_numeric($_POST['carID'])){
        $findCarID = executePlainSQL("SELECT CarWithDevice.carID
                                      FROM CarWithDevice
                                      WHERE CarWithDevice.carID = {$_POST['carID']}");
        $findversionID = executePlainSQL("SELECT SelfDrivingSoftware.versionID
                                          FROM SelfDrivingSoftware
                                          WHERE SelfDrivingSoftware.versionID = {$_POST['versionID']}");

      if ($findCarID && $findversionID) {
          executePlainSQL("UPDATE CarWithDevice
													 SET CarWithDevice.versionID = {$_POST['versionID']}
                           WHERE CarWithDevice.carID = {$_POST['carID']}");
          OCICommit($db_conn);
          popWindow("add self-driving software successfully");
        }
      }

  } else if (array_key_exists('addpath', $_POST)) {
      if (is_numeric($_POST['pathcondID'])) {
        $findpathcondID = executePlainSQL("SELECT PathCondition.pathcondID
                                           FROM PathCondition
                                           WHERE PathCondition.pathcondID = {$_POST['pathcondID']}");
        if ($findpathcondID) {
          $pathID = getNewID("path");
          executePlainSQL("INSERT INTO Path VALUES (
                                {$pathID}.
                                '{$_POST['city']}',
                                '{$_POST['location']}',
                                '{$_POST['startpoint']}',
                                '{$_POST['endpoint']}',
                                {$_POST['pathcondID']}
                                )");
          OCICommit($db_conn);
          popWindow("add path successfully");
        }

      }

  } else if (array_key_exists('addpathcondition', $_POST)) {
      $pathcondID = getNewID("pathcondID");
      executePlainSQL("INSERT INTO PathCondition VALUES (
                            {$pathcondID}.
                            '{$_POST['roadtype']}',
                            '{$_POST['weather']}',
                            '{$_POST['climate']}',
                            '{$_POST['dayornight']}',
                            )");
      OCICommit($db_conn);
      popWindow("add path condtion successfully");

  } else if (array_key_exists('createtest', $_POST)) {
      if (is_numeric($_POST['carID']) &&
          is_numeric($_POST['versionID']) &&
          is_numeric($_POST['pathID']) &&
          is_numeric($_POST['accountNo'])) {

        $findCarID = executePlainSQL("SELECT CarWithDevice.carID
                                      FROM CarWithDevice
                                      WHERE CarWithDevice.carID = {$_POST['carID']}");
        $findversionID = executePlainSQL("SELECT SelfDrivingSoftware.versionID
                                          FROM SelfDrivingSoftware
                                          WHERE SelfDrivingSoftware.versionID = {$_POST['versionID']}");
        $findinstalled = executePlainSQL("SELECT ListSoftwareInCar.carID
                                          FROM ListSoftwareInCar
                                          WHERE ListSoftwareInCar.versionID = {$_POST['versionID']}
                                          AND ListSoftwareInCar.carID = {$_POST['carID']}");
        $findpathcondID = executePlainSQL("SELECT Path.pathID
                                           FROM Path
                                           WHERE Path.pathID = {$_POST['pathID']}");
        $findAccount = executePlainSQL("SELECT TestDriver.accountNo
                                        FROM TestDriver
                                        WHERE TestDriver.accountNo = {$_POST['accountNo']}
                                        AND TestDriver.driverID = '{$_POST['driverID']}'");

        if ($findCarID && $findversionID && $findinstalled && $findpathcondID && $findAccount) {
          $recordID = getNewID("test");
          $newstatus = "new";
          executePlainSQL("INSERT INTO SelfDrivingTest VALUES (
                          {$recordID},
                          '{$newstatus}',
                          {$_POST['carID']},
                          {$_POST['versionID']},
                          NULL,
                          {$_POST['pathID']},
                          {$_POST['accountNo']},
                          '{$_POST['driverID']}',
                          NULL,
                          NULL
                          )");
          OCICommit($db_conn);
          popWindow("add test successfully");
        }
    }
  } else if (array_key_exists('starttest', $_POST)) {
      if (is_numeric($_POST['recordID'])) {
        $newstatus = "new";
        $findversionID = executePlainSQL("SELECT SelfDrivingTest.versionID
                                     FROM SelfDrivingTest
                                     WHERE SelfDrivingTest.recordID = {$_POST['recordID']}
                                     AND SelfDrivingTest.status = {$newstatus}");
        if ($findversionID) {
          $testdone = "done";
          $newswrecord = getNewSWRecordContent();
          $swrecordID = getNewID("swrecord");
          // TODO: parsing the versionID result
          executePlainSQL("INSERT INTO SelfDrivingSoftwareRecord VALUES (
                          {$swrecordID},
                          '{$newswrecord}',
                          {$findversionID}
                        )");
          $fromdatetime = getStartTime();
          $todatetime = getEndTime();
          executePlainSQL("UPDATE SelfDrivingTest
                           SET SelfDrivingTest.status = {$testdone},
                               SelfDrivingTest.swrecordID = {$swrecordID},
                               SelfDrivingTest.fromdatetime = {$fromdatetime},
                               SelfDrivingTest.todatetime = {$todatetime}
                           WHERE SelfDrivingTest.recordID = {$_POST['recordID']}");
          OCICommit($db_conn);
          popWindow("run test successfully");
        }
      }

  } else if (array_key_exists('edittest', $_POST)) {
			if (is_numeric($_POST["recordID"])) {


				$findpathid = executePlainSQL("SELECT Path.pathID
																			 FROM Path
																			 WHERE Path.pathID = {$_POST["pathID"]}
																			 ");
				$findrecordid = executePlainSQL("SELECT SelfDrivingTest.recordID
																				 FROM SelfDrivingTest
																				 WHERE SelfDrivingTest.recordID = {$_POST['recordID']}");
				if ($findpathid && $findrecordid) {
					executePlainSQL("UPDATE SelfDrivingTest
                           SET SelfDrivingTest.pathID = {$_POST["pathID"]}
                           WHERE SelfDrivingTest.recordID = {$_POST['recordID']}");
          OCICommit($db_conn);
					popWindow("edit the path of the test successfully");
				}
			}


  } else if (array_key_exists('extractsinglerecord', $_POST)) {
			if (is_numeric($_POST["recordID"])) {
					$extractrecord = executePlainSQL("SELECT *
																					 FROM SelfDrivingTest, CarWithDevice, SelfDrivingSoftware, SelfDrivingSoftwareRecord, TestDriver, Path, PathCondition
																					 WHERE SelfDrivingTest.recordID = {$_POST['recordID']}
																					 AND SelfDrivingTest.carID = CarWithDevice.carID
																					 AND SelfDrivingTest.versionID = SelfDrivingSoftware.versionID
																					 AND SelfDrivingTest.swrecordID = SelfDrivingSoftwareRecord.swrecordID
																					 AND SelfDrivingTest.accountNo = TestDriver.accountNo
																					 AND SelfDrivingTest.pathID = Path.pathID
																					 AND Path.pathcondID = PathCondition.pathcondID
																					 ");
					printResult($extractrecord); //TODO: frontend

			}



  } else if (array_key_exists('testwithpathcondtion', $_POST)) {
			if (is_numeric($_POST["pathcondID"])) {
				$findpathcondid = executePlainSQL("SELECT SelfDrivingTest.recordID, SelfDrivingSoftwareRecord.consolg
																					 FROM SelfDrivingTest, SelfDrivingSoftwareRecord, Path, PathCondition
																					 WHERE SelfDrivingTest.pathID = Path.pathID
																					 AND Path.pathcondID = {$_POST["pathcondID"]}
																					 AND Path.pathcondID = PathCondition.pathcondID
																					 ");

				printResult($extractrecord); //TODO: frontend
			}


  } else if (array_key_exists('testwithsoftware', $_POST)) {
			if (is_numeric($_POST["versionID"])) {
				$findversionid = executePlainSQL("SELECT SelfDrivingSoftware.versionID
																				 FROM SelfDrivingSoftware
																				 WHERE SelfDrivingSoftware.versionID = {$_POST['versionID']}");
				if ($findversionid){
						//TODO

				}
			}

  } else if (array_key_exists('testwithdevice', $_POST)) {
			if (is_numeric($_POST["deviceID"])) {
				$findversionid = executePlainSQL("SELECT CarWithDevice.deviceID
																				 FROM CarWithDevice
																				 WHERE CarWithDevice.deviceID = {$_POST['deviceID']}");
				if ($findversionid){
						//TODO

				}
			}

  } else if (array_key_exists('numoftestwithsoftware', $_POST)) {
			if (is_numeric($_POST["versionID"])) {
				$findversionid = executePlainSQL("SELECT SelfDrivingSoftware.versionID
																				 FROM SelfDrivingSoftware
																				 WHERE SelfDrivingSoftware.versionID = {$_POST['versionID']}");
				if ($findversionid){
						//TODO

				}
			}

  } else if (array_key_exists('theminimalnumoftestwithsoftware', $_POST)) {
			if (is_numeric($_POST["versionID"])) {
				$findversionid = executePlainSQL("SELECT SelfDrivingSoftware.versionID
																				 FROM SelfDrivingSoftware
																				 WHERE SelfDrivingSoftware.versionID = {$_POST['versionID']}");
				if ($findversionid){
						//TODO

				}
			}

  }






	if ($_POST && $success) {
		//POST-REDIRECT-GET -- See http://en.wikipedia.org/wiki/Post/Redirect/Get
		header("location: oracle-test.php");
	} else {
		// Select data...
		$result = executePlainSQL("select * from tab1");
		printResult($result);
	}

	//Commit to save changes...
	OCILogoff($db_conn);
} else {
	echo "cannot connect";
	$e = OCI_Error(); // For OCILogon errors pass no handle
	echo htmlentities($e['message']);
}







///////////////////////////////////////////
// TODO: Helper Function:
///////////////////////////////////////////

function executePlainSQL($cmdstr) { //takes a plain (no bound variables) SQL command and executes it
	//echo "<br>running ".$cmdstr."<br>";
	global $db_conn, $success;
	$statement = OCIParse($db_conn, $cmdstr); //There is a set of comments at the end of the file that describe some of the OCI specific functions and how they work

	if (!$statement) {
		echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
		$e = OCI_Error($db_conn); // For OCIParse errors pass the
		// connection handle
		echo htmlentities($e['message']);
		$success = False;
	}

	$r = OCIExecute($statement, OCI_DEFAULT);
	if (!$r) {
		echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
		$e = oci_error($statement); // For OCIExecute errors pass the statementhandle
		echo htmlentities($e['message']);
		$success = False;
	} else {

	}
	return $statement;

}

function executeBoundSQL($cmdstr, $list) {
	/* Sometimes the same statement will be executed for several times ... only
	 the value of variables need to be changed.
	 In this case, you don't need to create the statement several times;
	 using bind variables can make the statement be shared and just parsed once.
	 This is also very useful in protecting against SQL injection.
      See the sample code below for how this functions is used */

	global $db_conn, $success;
	$statement = OCIParse($db_conn, $cmdstr);

	if (!$statement) {
		echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
		$e = OCI_Error($db_conn);
		echo htmlentities($e['message']);
		$success = False;
	}

	foreach ($list as $tuple) {
		foreach ($tuple as $bind => $val) {
			//echo $val;
			//echo "<br>".$bind."<br>";
			OCIBindByName($statement, $bind, $val);
			unset ($val); //make sure you do not remove this. Otherwise $val will remain in an array object wrapper which will not be recognized by Oracle as a proper datatype

		}
		$r = OCIExecute($statement, OCI_DEFAULT);
		if (!$r) {
			echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
			$e = OCI_Error($statement); // For OCIExecute errors pass the statement handle
			echo htmlentities($e['message']);
			echo "<br>";
			$success = False;
		}
	}

}

function printResult($result) { //prints results from a select statement
	echo "<br>Got data from table tab1:<br>";
	echo "<table>";
	echo "<tr><th>ID</th><th>Name</th></tr>";

	while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
		echo "<tr><td>" . $row["NID"] . "</td><td>" . $row["NAME"] . "</td></tr>"; //or just use "echo $row[0]"
	}
	echo "</table>";

}

?>
