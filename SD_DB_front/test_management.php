<?php

$success = True; //keep track of errors so it redirects the page only if there are no errors
$db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");

if ($db_conn) {
  if (array_key_exists('createtest', $_POST)) {
    $findDriverID = executePlainSQL("SELECT TestDriver.driverID
                                    FROM TestDriver
                                    WHERE TestDriver.accountNo = {$_POST['accountNo']}");
    if (countRows($findDriverID) != 0) {
      $recordID = getNewID("record");
      $newstatus = "new";
      $findDriverID = executePlainSQL("SELECT TestDriver.driverID
                                      FROM TestDriver
                                      WHERE TestDriver.accountNo = {$_POST['accountNo']}");
      while ($row = OCI_Fetch_Array($findDriverID, OCI_BOTH)) {

        executePlainSQL("INSERT INTO SelfDrivingTest VALUES (
                        {$recordID},
                        '{$newstatus}',
                        {$_POST['CarWithDevice']},
                        null,
                        {$_POST['Path']},
                        {$_POST['accountNo']},
                        '{$row["DRIVERID"]}',
                        null,
                        null
                        )");
        OCICommit($db_conn);
      }
    }
  } else if (array_key_exists('starttest', $_POST)) {
      if (is_numeric($_POST['recordID'])) {
        $newstatus = "new";
        $findrecordid = executePlainSQL("SELECT SelfDrivingTest.recordID
                                     FROM SelfDrivingTest
                                     WHERE SelfDrivingTest.recordID = {$_POST['recordID']}
                                     AND SelfDrivingTest.status = '{$newstatus}'");
        if (countRows($findrecordid) != 0) {
          $findversionID = executePlainSQL("SELECT CarWithDevice.versionID
                                       FROM SelfDrivingTest, CarWithDevice
                                       WHERE SelfDrivingTest.recordID = {$_POST['recordID']}");
          while ($row = OCI_Fetch_Array($findversionID, OCI_BOTH)) {
            $testdone = "done";
            $fromdatetime = date('c', mktime(date("H"),date("i"),date("s"),date("Y"),date("m"),date("d")));
            $todatetime = date('c', mktime(date("H") + 1,date("i") + 20,date("s"),date("Y"),date("m"),date("d")));
            $swrecordID = getNewSWID("swrecord");
            $newswrecord = getNewSWRecordContent($swrecordID);
            executePlainSQL("INSERT INTO SelfDrivingSoftwareRecord VALUES (
                            {$swrecordID},
                            '{$newswrecord}',
                            {$row[0]}
                          )");

            executePlainSQL("UPDATE SelfDrivingTest
                             SET SelfDrivingTest.status = '{$testdone}',
                                 SelfDrivingTest.swrecordID = {$swrecordID},
                                 SelfDrivingTest.fromdatetime = '{$fromdatetime}',
                                 SelfDrivingTest.todatetime = '{$todatetime}'
                             WHERE SelfDrivingTest.recordID = {$_POST['recordID']}");
            OCICommit($db_conn);
          }
        }
      }
  } else if (array_key_exists('changetestpath', $_POST)) {
        if (is_numeric($_POST["recordID"])) {
          executePlainSQL("UPDATE SelfDrivingTest
                           SET SelfDrivingTest.pathID = {$_POST['pathID']}
                           WHERE SelfDrivingTest.recordID = {$_POST['recordID']}");
          OCICommit($db_conn);
        }
  }
  
  // $result = executePlainSQL("SELECT versionID, COUNT(recordID)
  //                            FROM TestView
  //                            GROUP BY versionID");

  // $ncols = oci_num_fields($result);

  // echo '<br><br><br><br><br><br><select id="numTestSoftware">';
  // while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
  //   for ($i = 0; $i < $ncols; $i++) {
  //       echo  '<option value="' . $row[1] . '">' . $row[0] . 'test</option>';
  //       // echo "<br>hahahahahah<br>";
  //   }
  // }
  // echo '</select>';




  // if ($_POST && $success) {
	// 	//POST-REDIRECT-GET -- See http://en.wikipedia.org/wiki/Post/Redirect/Get
	// 	header("location: test_management.php");
	// }

} else {
	echo "cannot connect";
	$e = OCI_Error(); // For OCILogon errors pass no handle
	echo htmlentities($e['message']);
}

// helper function

function popWindow($message)
{
    echo '<script language="javascript">alert("' . "{$message}" . '");</script>';
    header("Location:test_management.php");
}

function countRows($result)
{
    $rownum = 0;
    while (OCI_Fetch_Array($result, OCI_BOTH)) {
        $rownum++;
    }
    return $rownum;
}

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

// <table class="table table-striped">
//   <thead>
//     <tr>
//       <th>CheckBox</th>
//       <th>Version ID</th>
//       <th>Update Time</th>
//       <th>Comment</th>
//     </tr>
//   </thead>

// body
// <tbody>
//   <tr>
//     <td>
//       <div class="checkbox">
//         <label><input type="checkbox" value=""></label>
//       </div>
//     </td>
//     <td>12123123213</td>
//     <td>2018-11-15</td>
//     <td>test software 1</td>
//   </tr>
// prints a pre-defined table (e.g. Singer, Composer etc.)
function printWhole($tableName)
{
    $result = executePlainSQL("SELECT * FROM {$tableName}");
    OCICommit($db_conn);

    // echo "<br>Table [{$tableName}]:<br>";

    // table head
    $ncols = oci_num_fields($result);
    echo '<table class="table table-striped"><thead><tr>';
    for ($i = 1; $i <= $ncols; $i++) {
        $column_name = oci_field_name($result, $i);
        echo "<th>{$column_name}</th>";
    }
    echo "</tr></thead>";

    echo "<tbody>";
    // table data
    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        echo "<tr>";
        for ($i = 0; $i < $ncols; $i++) {
            echo "<td>{$row[$i]}</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";

    echo "</table>";
}

function printTest($result) {
    //TODO:
}

function printWholeSelect($tableName)
{
    $result = executePlainSQL("SELECT * FROM {$tableName}");
    OCICommit($db_conn);

    // echo "<br>Table [{$tableName}]:<br>";

    // table head
    $ncols = oci_num_fields($result);
    echo '<table class="table table-striped"><thead><tr><th>Select</th>';
    for ($i = 1; $i <= $ncols; $i++) {
        $column_name = oci_field_name($result, $i);
        echo "<th>{$column_name}</th>";
    }
    echo "</tr></thead>";

    echo "<tbody>";
    // table data
    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        echo "<tr>";
        // checkbox
        echo '<td><div class="radio"><label><input type="radio" value='. $row[0] .' name='. $tableName .'></label></div></td>';
        for ($i = 0; $i < $ncols; $i++) {
            echo "<td>{$row[$i]}</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";

    echo "</table>";
}

function getNewID($name)
{
    if ($name = "record") {
      $id = "recordID";
      $table = "SelfDrivingTest";
    } else if ($name = "swrecord") {
      $id = "swrecordID";
      $table = "SelfDrivingSoftwareRecord";
    }
    $listID = 1;
    OCICommit($db_conn);

    while (1) {
        $flag = 0;
        $result = executePlainSQL("SELECT {$id} FROM {$table}");
        while ($row = OCI_Fetch_Array($result)) {
            if ($listID == $row[0]) {
                $flag = 1;
            }
        }
        if ($flag == 0) {
            break;
        }
        $listID++;
    }
    return $listID;
}

function getNewSWID($name)
{
    if ($name = "swrecord") {
      $id = "swrecordID";
      $table = "SelfDrivingSoftwareRecord";
    }
    $listID = 1;
    OCICommit($db_conn);

    while (1) {
        $flag = 0;
        $result = executePlainSQL("SELECT {$id} FROM {$table}");
        while ($row = OCI_Fetch_Array($result)) {
            if ($listID == $row[0]) {
                $flag = 1;
            }
        }
        if ($flag == 0) {
            break;
        }
        $listID++;
    }
    return $listID;
}

function getNewSWRecordContent($swrecordID){
  if ($swrecordID == 1){
    return "brake: speed limit: Set speed to 30km/h;
            throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;
            turn right: change lane;
            turn left: intersaction turn;
            brake: time for stop: 2s;";
  } else if ($swrecordID == 2){
    return "brake: speed limit: Set speed to 30km/h;
            turn left: intersaction turn;
            turn left: intersaction turn;
            throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;
            throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;
            throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;
            brake: time for stop: 2s;";
  } else if ($swrecordID == 3){
    return "throttle: Set speed to 50km/h;
            turn right: change lane;
            turn left: change lane;
            turn left: change lane;
            turn left: change lane;
            turn right: change lane;
            turn right: change lane;
            brake: time for stop: 2s;";

  } else if ($swrecordID == 4){
    return "throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;
            turn right: change lane;
            turn left: change lane;
            turn left: change lane;
            turn right: change lane;
            brake: too close. Set speed to 30km/h;
            throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;";
  } else if ($swrecordID == 5){
    return "brake: speed limit: Set speed to 30km/h;
            throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;
            turn right: change lane;
            brake: too close. Set speed to 30km/h;
            throttle: Set speed to 50km/h;";
  } else if ($swrecordID == 6){
    return "turn left: intersaction turn;
            throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;
            throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;";
  } else if ($swrecordID == 7){
    return "throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;
            throttle: Set speed to 50km/h;
            throttle: Set speed to 60km/h;
            throttle: Set speed to 70km/h;
            throttle: Set speed to 80km/h;
            throttle: Set speed to 90km/h;";
  } else if ($swrecordID == 8){
    return "brake: speed limit: Set speed to 30km/h;
            throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;
            turn right: change lane;
            brake: too close. Set speed to 30km/h;
            brake: speed limit: Set speed to 30km/h;
            throttle: Set speed to 50km/h;
            brake: too close. Set speed to 30km/h;
            turn right: change lane;
            brake: too close. Set speed to 30km/h;";
  } else {
    return "test fail";
  }
}

function printResult($result) { //prints results from a select statement
	echo "<br>Got data from table tab1:<br>";
	echo "<table>";
	echo "<tr><th>ID</th><th>Name</th></tr>";

	while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
		echo "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td></tr>"; //or just use "echo $row[0]"
	}
	echo "</table>";

}

function printSingle($recordID)
{
    $result = executePlainSQL("SELECT *
                               FROM TestView
                               WHERE TestView.recordID = {$recordID}");


    // echo "<br>Table [{$tableName}]:<br>";

    // table head
    echo '<div style="width:100%; height:300px; overflow: auto">';
    $ncols = oci_num_fields($result);
    echo '<table class="table table-striped"><thead><tr>';
    for ($i = 1; $i <= $ncols; $i++) {
        $column_name = oci_field_name($result, $i);
        echo "<th>{$column_name}</th>";
    }
    echo "</tr></thead>";

    echo "<tbody>";
    // table data
    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        echo "<tr>";
        for ($i = 0; $i < $ncols; $i++) {
            echo "<td>{$row[$i]}</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";

    echo "</table>";

    echo '</div>';
}

?>

<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Self Driving Simulator - Test Management</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom fonts for this template -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Merriweather:400,300,300italic,400italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>

    <!-- Plugin CSS -->
    <link href="vendor/magnific-popup/magnific-popup.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/creative.min.css" rel="stylesheet">

  </head>

  <style type="text/css">
  a.tab{
    margin-left:25px;margin-right:27px;
  }

  input.form-control{
    width:20%;
  }

  </style>

  <body id="page-top">

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
      <div class="container">
        <a class="navbar-brand js-scroll-trigger" href="#page-top">Start Bootstrap</a>
        <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link" href="login_page.html">Login/Sign Up</a>
            </li>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#service-window">Insert/Delete</a>
            </li>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#all-data">All Data</a>
            </li>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#services">Other Services</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

<!--     <header class="masthead text-center text-white d-flex">
      <div class="container my-auto">
        <div class="row">
          <div class="col-lg-10 mx-auto">
            <h1 class="text-uppercase">
              <strong>Your Favorite Source of Free Bootstrap Themes</strong>
            </h1>
            <hr>
          </div>
          <div class="col-lg-8 mx-auto">
            <p class="text-faded mb-5">Start Bootstrap can help you build better websites using the Bootstrap CSS framework! Just download your template and start going, no strings attached!</p>
            <a class="btn btn-primary btn-xl js-scroll-trigger" href="#about">Find Out More</a>
          </div>
        </div>
      </div>
    </header> -->

    <!-- <section class="bg-primary" id="about">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto text-center">
            <h2 class="section-heading text-white">We've got what you need!</h2>
            <hr class="light my-4">
            <p class="text-faded mb-4">Start Bootstrap has everything you need to get your new website up and running in no time! All of the templates and themes on Start Bootstrap are open source, free to download, and easy to use. No strings attached!</p>
            <a class="btn btn-light btn-xl js-scroll-trigger" href="#services">Get Started!</a>
          </div>
        </div>
      </div>
    </section> -->
    <section id="service-window">
      <div class="container">
        <ul class="nav nav-tabs">
          <li class="nav-item"><a class="nav-link active" id="test-tab" data-toggle="tab" href="#Test" role="tab" aria-controls="test" aria-selected="true">Test Create/Edit<br/></a></li>
          <li class="nav-item"><a class="nav-link" id="record-tab" data-toggle="tab" href="#Record" role="tab" aria-controls="record" aria-selected="false">Record View<br></a></li>
          <li class="nav-item"><a class="nav-link" id="data-tab" data-toggle="tab" href="#Data" role="tab" aria-controls="data" aria-selected="false">Data View<br></a></li>
        </ul>

        <div class="tab-content">
          <div id="Test" class="tab-pane fade show active" role="tabpanel" aria-labelledby="test-tab">
            <h3>create/delete tests</h3>
            <form method="POST" action="test_management.php">
              <div style="width:100%; height:300px; overflow: auto">
                <?php
                  $db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");
                  if($db_conn){
                    $tableName = "SelfDrivingTest";
                    echo "<h1>TESTING</h1>";
                    printWhole($tableName);
                  }

                ?>
              </div>
            </form>
            <form method="POST" action="test_management.php">
              <div style="width: 48%; float:left; margin:10px">
                <h5>Car List</h5>
                <div style="width:100%; height:300px; overflow: auto">
                  <?php
                    $db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");
                    if($db_conn){
                      $tableName = "CarWithDevice";
                      printWholeSelect($tableName);
                    }

                  ?>
                </div>
              </div>
              <!-- <div class="form-group">
                <label for="car-type-input">Car type:</label>
                <input type="text" class="form-control" name="cartype">
              </div>
              <div class="form-group">
                <label for="device-input">Device ID:</label>
                <input type="text" class="form-control" name="deviceID">
              </div>
              <div class="form-group">
                <label for="software-input">Software:</label>
                <input type="text" class="form-control" name="versionID">
              </div> -->
              <div style="width: 48%; float:right; margin:10px">
                <h5>Path List</h5>
                <div style="width:100%; height:300px; overflow: auto">
                  <?php
                    $db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");
                    if($db_conn){
                      $tableName = "Path";
                      printWholeSelect($tableName);
                    }

                  ?>
                </div>
              </div>
              <div class="form-group">
                <label for="accountNo-input">Account Number:</label>
                <input type="text" class="form-control" name="accountNo">
              </div>
              <input type="submit" class="btn btn-dark" value="ADD" name="createtest">
            </form>
            <form method="POST" action="test_management.php">
              <div style="width: 40%; float:left; margin:10px">
                <h5>Path List</h5>
                <div style="width:100%; height:300px; overflow: auto">
                  <?php
                    $db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");
                    if($db_conn){
                      $tableName = "Path";
                      printWholeSelect($tableName);
                    }

                  ?>
                </div>
              </div>
              <div class="form-group">
                <label for="test-input">Test ID:</label>
                <input type="text" class="form-control" name="testID">
              </div>
              <input type="submit" class="btn btn-dark" value="EDIT" name="edittest">
            </form>
          </div>
          <div id="Record" class="tab-pane fade" role="tabpanel" aria-labelledby="record-tab">
            <h3>Information of the test</h3>
            <form method="POST" action="test_management.php">
              <div class="form-group">
                <label for="recordID-input">Record ID:</label>
                <input type="text" class="form-control" name="recordID">
              </div>
              <input type="submit" class="btn btn-dark" value="VIEW" name="extractsinglerecord">
              <br>
              <br>
              <input type="submit" class="btn btn-dark" value="START" name="starttest">
              <?php
              $success = True; //keep track of errors so it redirects the page only if there are no errors
              $db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");

              if ($db_conn) {
                if (array_key_exists('extractsinglerecord', $_POST)) {
                      if (is_numeric($_POST["recordID"])) {
                          printSingle($_POST["recordID"]);
                      }
                }
                  if ($_POST && $success) {
                    //POST-REDIRECT-GET -- See http://en.wikipedia.org/wiki/Post/Redirect/Get
                    header("location: test_management.php");
                  }

              } else {
                echo "cannot connect";
                $e = OCI_Error(); // For OCILogon errors pass no handle
                echo htmlentities($e['message']);
              }

              ?>
            </form>
          </div>
         <div id="Data" class="tab-pane fade" role="tabpanel" aria-labelledby="data-tab">
          <!-- <div id="Data" class="tab-pane fade" role="tabpanel" aria-labelledby="data-tab"> -->
            <form method="POST" action="test_management.php">
              <div class="form-group">
                <label for="recordID-input">Input:</label>
                <input type="text" class="form-control" name="recordID">
              </div>
              <select class="form-control" id="selectBarT" name="tableName" style="width: 20%">
                <option value='pathCondition'>Path Condition</option>
                <option value='Device'>Self Driving Device</option>
                <option value='Software'>Self Driving Software</option>
              </select>
              <input type="submit" class="btn btn-dark" value="Search" name="searchRecord">
            </form>
          </div>
        </div>
      </div>
    </section>

    <section id="all-data">
      <div class="container">
        <?php

        $success = True; //keep track of errors so it redirects the page only if there are no errors
        $db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");

        if ($db_conn) {
          $result = executePlainSQL("SELECT versionID, COUNT(recordID)
                             FROM TestView
                             GROUP BY versionID");

          $ncols = oci_num_fields($result);

          echo '<select id="numTestSoftware" hidden>';
          while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
            echo  '<option value="' . $row[1] . '">' . $row[0] . '</option>';
            // for ($i = 0; $i < $ncols; $i++) {
                
            //     // echo "<br>hahahahahah<br>";
            // }
          }
          echo '</select>';
        }
        ?>

        <div id="piechart"></div>

      </div>
    </section>

    <section id="services">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center">
            <h2 class="section-heading">At Your Service</h2>
            <hr class="my-4">
          </div>
        </div>
      </div>
      <div class="container">
        <div class="row">
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fas fa-4x fa-code text-primary mb-3 sr-icon-3"></i>
              <!-- <h3 class="mb-3">Add Software</h3> -->
              <p></p>
              <a href="index.php" class="btn btn-outline-dark" role="button" aria-disabled="true">Home</a>
              <p class="text-muted mb-0">Goto home page</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fas fa-4x fa-gem text-primary mb-3 sr-icon-1"></i>
              <!-- <h3 class="mb-3">Add Car</h3> -->
              <p></p>
              <!-- <button type="button" class="btn btn-outline-dark" herf="add_car_device_software.html">Component</button> -->
              <a href="add_car_device_software.php" class="btn btn-outline-dark" role="button" aria-disabled="true">Component</a>
              <p class="text-muted mb-0">Add/Delete components</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fas fa-4x fa-paper-plane text-primary mb-3 sr-icon-2"></i>
              <!-- <h3 class="mb-3">Add Device</h3> -->
              <p></p>
              <a href="add_path_condition.php" class="btn btn-outline-dark" role="button" aria-disabled="true">Path & Condition</a>
              <p class="text-muted mb-0">Add/Delete path and condition</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fas fa-4x fa-heart text-primary mb-3 sr-icon-4"></i>
              <!-- <h3 class="mb-3">Start a Test</h3> -->
              <p></p>
              <a href="test_management.php" class="btn btn-outline-dark" role="button" aria-disabled="true">Test</a>
              <p class="text-muted mb-0">Manage Test</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="p-0" id="portfolio">
      <div class="container-fluid p-0">
        <div class="row no-gutters popup-gallery">
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/1.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/1.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/2.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/2.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/3.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/3.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/4.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/4.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/5.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/5.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/6.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/6.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- java script -->

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

    <script type="text/javascript">
    // Load google charts
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    // Draw the chart and set the chart values
    function drawChart() {
      var dataList = document.getElementById('numTestSoftware').options;
      var my_data_list = [['VersionID', 'Number of tests']];
      for (var i = 0; i < dataList.length; i++){
        // var text = String(data_point.text);
        my_data_list.push([dataList[i].text, dataList[i].value]);
      }
    //   var data = google.visualization.arrayToDataTable([
    //   ['Task', 'Hours per Day'],
    //   ['Work', 8],
    //   ['Eat', 2],
    //   ['TV', 4],
    //   ['Gym', 2],
    //   ['Sleep', 8]
    // ]);
      var data = google.visualization.arrayToDataTable(my_data_list);

      // Optional; add a title and set the width and height of the chart
      var options = {'title':'percentage of test for each software compare to all test', 'width':550, 'height':400};

      // Display the chart inside the <div> element with id="piechart"
      var chart = new google.visualization.PieChart(document.getElementById('piechart'));
      chart.draw(data, options);
    }
    </script>

<!--     <section class="bg-dark text-white">
      <div class="container text-center">
        <h2 class="mb-4">Free Download at Start Bootstrap!</h2>
        <a class="btn btn-light btn-xl sr-button" href="http://startbootstrap.com/template-overviews/creative/">Download Now!</a>
      </div>
    </section> -->

    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Plugin JavaScript -->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="vendor/scrollreveal/scrollreveal.min.js"></script>
    <script src="vendor/magnific-popup/jquery.magnific-popup.min.js"></script>

    <!-- Custom scripts for this template -->
    <script src="js/creative.min.js"></script>

  </body>
</html>
