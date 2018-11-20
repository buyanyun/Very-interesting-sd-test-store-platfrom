<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Self Driving Simulator - Login Page</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
    <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>

    <!-- Custom fonts for this template -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Merriweather:400,300,300italic,400italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>

    <!-- Plugin CSS -->
    <link href="vendor/magnific-popup/magnific-popup.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/login_signup.css" rel="stylesheet">

    <link href="css/creative.min.css" rel="stylesheet">

  </head>

  <body id="page-top">

  <div class="container">
    <div class="col-md-6">
    <div id="logbox">
      <form id="signup" method="POST" action="login_page.php">
        <h1>create an account</h1>
        <input name="name" type="text" placeholder="What's your username?" pattern="^[\w]{3,16}$" autofocus="autofocus" required="required" class="input pass">
        <input name="password" type="text" placeholder="Choose a password" required="required" class="input pass">
        <input type="submit" value="Sign me up!" class="inputButton" name="addaccount">
      </form>
    </div>
   </div>
    <!--col-md-6-->

   <div class="col-md-6">
    <div id="logbox">
      <form id="signup" method="POST" action="login_page.php">
        <h1>account login</h1>
        <input name="accountNo" type="text" placeholder="enter your account Number" class="input pass"/>
        <input name="password" type="text" placeholder="enter your password" required="required" class="input pass"/>
        <input type="submit" value="Sign me in!" class="inputButton" name="login"/>
        <div class="text-center">
                    <a href="#" id="">create an account</a> - <a href="#" id="">forgot password</a>
                </div>
      </form>
    </div>
    </div>
  </div>

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

<?php

$success = True; //keep track of errors so it redirects the page only if there are no errors
$db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");

if ($db_conn) {
  if (array_key_exists('addaccount', $_POST)) {
    if (is_numeric($_POST['password'])) {
      $accountNo = getNewID();
      executePlainSQL("INSERT INTO Developer VALUES (
                              {$accountNo},
                              {$_POST['password']},
                              '{$_POST['name']}'
                            )");
      OCICommit($db_conn);
    }
    if (!empty($_POST['driverID'])) {
      executePlainSQL("INSERT INTO TestDriver VALUES (
                            {$accountNo},
                            '{$_POST['driverID']}',
                            {$_POST['phonenumber']}
                            )");
      OCICommit($db_conn);
    }
    echo($accountNo);
  } else if (array_key_exists('removeaccount', $_POST)) {
    $findAccount = executePlainSQL("SELECT Developer.accountNo
                                    FROM Developer
                                    WHERE Developer.accountNo = {$_POST['accountNo']}");
    $findPassword = executePlainSQL("SELECT Developer.password
                                     FROM Developer
                                     WHERE Developer.accountNo = {$_POST['accountNo']}");
    if (countRows($findAccount) != 0 && countRows($findPassword)){
      executePlainSQL("DELETE FROM Developer
                       WHERE Developer.accountNo = {$_POST['accountNo']}");
      OCICommit($db_conn);
    }
  } else if (array_key_exists('login', $_POST)) {
    $findAccount = executePlainSQL("SELECT Developer.accountNo
                                    FROM Developer
                                    WHERE Developer.accountNo = {$_POST['accountNo']}
                                    AND Developer.password = {$_POST['password']}");
    if (countRows($findAccount) != 0) {
     header("location: add_car_device_software.php");

    }
  }





  // if ($_POST && $success) {
	// 	//POST-REDIRECT-GET -- See http://en.wikipedia.org/wiki/Post/Redirect/Get
	// 	header("location: add_car_device_software.php");
	// }

} else {
	echo "cannot connect";
	$e = OCI_Error(); // For OCILogon errors pass no handle
	echo htmlentities($e['message']);
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

function countRows($result)
{
    $rownum = 0;
    while (OCI_Fetch_Array($result, OCI_BOTH)) {
        $rownum++;
    }
    return $rownum;
}

function getNewID()
{
    $id = "accountNo";
    $table = "Developer";
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

function popWindow($message)
{
    echo '<script language="javascript">alert("' . "{$message}" . '");</script>';
    header("Location:login_page.php");
}
?>
