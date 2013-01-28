<?php

//File containing the admin credentials to the Google Apps domain
require_once "gapps_domain_credentials.php";

//Zend PHP Framework
//Update the path to suit
require_once "Zend/Loader.php";

Zend_Loader::loadClass("Zend_Http_Client");
Zend_Loader::loadClass("Zend_Gdata");
Zend_Loader::loadClass("Zend_Gdata_ClientLogin");
Zend_Loader::loadClass("Zend_Gdata_Gapps");

$arrSingle = array();

if($_SERVER['REQUEST_METHOD'] == "POST")
{

	if((!empty($_POST["submit_single"])) && ($_POST["submit_single"] === "Submit Single"))
	{

		$input = filter_var($_POST["txtUsername"], FILTER_SANITIZE_STRING);

		try {
		  $client = Zend_Gdata_ClientLogin::getHttpClient($email, $passwd, Zend_Gdata_Gapps::AUTH_SERVICE_NAME);
		} catch (Zend_Gdata_App_CaptchaRequiredException $cre) {
		     echo "URL of CAPTCHA image: " . $cre->getCaptchaUrl() . "\n";
		     echo "Token ID: " . $cre->getCaptchaToken() . "\n";
		} catch (Zend_Gdata_App_AuthException $ae) {
		     echo "Problem authenticating: " . $ae->exception() . "\n";
		}
		$gdata = new Zend_Gdata_Gapps($client, $domain);

		//$updateUser = $gdata->retrieveUser($user);

		if (empty($input))
		{
			$error =  "<span style=\"color: red;\"><strong>Please enter a username to check</strong></span>";
		}
		else
		{
			$updateUser = $gdata->retrieveUser($input);

			if (!is_null($updateUser))
			{
				global $arrSingle;

				$arrSingle[] = $updateUser->login->userName;
				$arrSingle[] = $updateUser->name->givenName;
				$arrSingle[] = $updateUser->name->familyName;
			}
		}
	}
	elseif ((!empty($_POST["submit_file"])) && ($_POST["submit_file"] === "Submit File")) {
		try {
		  $client = Zend_Gdata_ClientLogin::getHttpClient($email, $passwd, Zend_Gdata_Gapps::AUTH_SERVICE_NAME);
		} catch (Zend_Gdata_App_CaptchaRequiredException $cre) {
		     echo "URL of CAPTCHA image: " . $cre->getCaptchaUrl() . "\n";
		     echo "Token ID: " . $cre->getCaptchaToken() . "\n";
		} catch (Zend_Gdata_App_AuthException $ae) {
		     echo "Problem authenticating: " . $ae->exception() . "\n";
		}
		$gdata = new Zend_Gdata_Gapps($client, $domain);

		$arrUsers = array();

		//Create temp file
		$datafile = tempnam(sys_get_temp_dir(), "luminis_") . ".csv";
		$datafile_handle = fopen($datafile, "w");

		//write header row
		fwrite($datafile_handle, "username,found\r\n");

		$file_handle = fopen($_FILES["file"]["tmp_name"], "r");
		while (!feof($file_handle)) {
			global $arrUsers;

			$line = filter_var(trim(fgets($file_handle)), FILTER_SANITIZE_STRING);
			$arrUsers[] = trim($line);
		}
		fclose($file_handle);

		foreach($arrUsers as $value)
		{
			
			$updateUser = $GLOBALS["gdata"]->retrieveUser($value);

			if (empty($updateUser))
			{
				fwrite($GLOBALS["datafile_handle"], $value . ",false\r\n");
			}
			else
			{
				fwrite($GLOBALS["datafile_handle"], $value . ",true\r\n");
			}
		}

		fclose($datafile_handle);

		if (file_exists($datafile)) {
		    header('Content-Description: File Transfer');
		    header('Content-Type: application/octet-stream');
		    header('Content-Disposition: attachment; filename=' . basename($datafile));
		    header('Content-Transfer-Encoding: binary');
		    header('Expires: 0');
		    header('Cache-Control: must-revalidate');
		    header('Pragma: public');
		    header('Content-Length: ' . filesize($datafile));
		    ob_clean();
		    flush();
		    readfile($datafile);
			//exit;
		}

		fclose($datafile);
		unlink($datafile);
		unlink(preg_replace("/\\.[^.\\s]{3,4}$/", "", $datafile));

		//Reset $_POST values
		foreach($_POST as $key => $value)
		{
			unset($_POST[$key]);
		}
	}
}
?>

<html>
<body>

<h1>Google Apps Provision Checker</h1>

<h2>Single User Check</h2>
<p>Enter a single user name and click the <strong>Submit Single</strong> button.<br/><strong><emphasis>Note:</emphasis></strong> if you submit a blank entry the system returns the first entry when sorted ascending by e-mail address.</p>
<form name="frmCheckUserExists" id="frmCheckUserExists" method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">
<strong>Username:</strong>&nbsp;<input type="text" name="txtUsername" id="txtUsername">
<br/>
<input type="submit" name="submit_single" value="Submit Single">&nbsp;<input type="reset" name="reset" value="Reset">
</form>
<?php
	if((!empty($_POST["submit_single"])) && ($_POST["submit_single"] === "Submit Single"))
	{
		echo "<h3>Single Check Results</h3>";
		if(count($GLOBALS["arrSingle"]) > 0)
		{
			echo "<strong>User Exists</strong><br/>";
			echo "<strong>Username:</strong>&nbsp;&nbsp;" . $GLOBALS["arrSingle"][0] . "<br/>";
			echo "<strong>Firstname:</strong>&nbsp;&nbsp;" . $GLOBALS["arrSingle"][1] . "<br/>";
			echo "<strong>Lastname:</strong>&nbsp;&nbsp;" . $GLOBALS["arrSingle"][2] . "<br/>";
		}
		elseif(isset($error))
		{
			echo $error;
		}
		elseif((count($GLOBALS["arrSingle"]) <= 0) || is_null(count($GLOBALS["arrSingle"])))
		{
			echo "<strong>User Not Found</strong><br/>";
		}
	}
?>

<hr/>
<h2>Batch Mode</h2>
<p>Click the <strong>Choose File</strong> button and browse for your batch user file.  Then click the <strong>Submit File</strong> button to initiate the batch check process.</p>
<p>This mode takes a file where each username is on a separate line.  The file is read into the system memory and the results are written out to the system temp space as a csv.  This file is then submitted back to the user for download.  After the file is submitted, the temporary files on the web server are then deleted.<p>
<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
<input type="file" name="file" id="file"><br/>
<input type="submit" name="submit_file" value="Submit File">&nbsp;<input type="reset" name="reset" value="Reset">
</form>

</body>
</html>