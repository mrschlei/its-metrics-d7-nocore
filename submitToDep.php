<html>
<head>
<title>Apple Device Enrollment Program</title>

<link rel="stylesheet" type="text/css" href="../admin/showcase-admin.css"/>
<style type="text/css">
body {
	margin:40px;
	font-size:.9em;
}
a.process {
	display:inline-block;
	padding:10px 2em 10px 2em;
	border:0;
	background:#40658f;
	color:#ffffff;
	-webkit-appearance:none;
	text-shadow: 0 1px rgba(0,0,0,0.1);
	-webkit-border-radius:2px;
	border-radius:2px;
	font-size:inherit;
	font-weight:bold;
	text-align:center;
	text-decoration:none;
	white-space:nowrap;
	cursor:pointer;
}
a.process:hover {
	background:#567daa;
}
</style>

</head>
<body>

<h1>Apple Device Enrollment Program</h1>

<p>Your Order Number: <?php echo $_POST["orderNumber"]; ?><br/>
Your Device Serial Number is: <?php echo $_POST["serialNumber"]; ?><br/>
Your Transaction Type is: <?php echo $_POST["transactionType"]; ?></p>

<?php
// Added for development so errors display on page.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// include database connect string
include "../db-inc.php";

// insert fields into database table
function addResultToTable($db, $result){
  $order_number = $_POST["orderNumber"]; 
  $transaction_type = $_POST["transactionType"];
  $device_serial = $_POST["serialNumber"];
  $transaction_id = $result['deviceEnrollmentTransactionId'];
  $status_code = $result['enrollDevicesResponse']['statusCode'];
  $status_message = $result['enrollDevicesResponse']['statusMessage'];	
  $user_id = $_SERVER["REMOTE_USER"];

  $insert_sql = "insert into apple_dep
			fields (appledep_id, date_time, order_number, transaction_type, device_serial, transaction_id, status_code, status_message, userid)
			values (appledep_seq.nextval, to_date(SYSDATE,'MM/DD/YYYY HH24:MI'), '$order_number', '$transaction_type', '$device_serial', '$transaction_id', '$status_code', '$status_message', '$user_id')";
  $insert_stmt = OCIParse($db, $insert_sql);
  OCIExecute($insert_stmt);
  OCICommit($db);
}

function buildRequest(){

	$orders  = explode(",", $_POST["serialNumber"]);	
	$orders = (object) $orders;
	
	echo "<pre>".$_POST["env"]."</pre>";
	if ($_POST["env"] == "dev") {
		$shipto = '0000046475';
		$resellerid = "0000046475";
		$customerID = "19827";
	}
	elseif ($_POST["env"] == "uat") {
		$shipto = '0000046475';
		$resellerid = "14658A10";
		$customerID = "10000";
	}
	
// $dateTime = date("Y-m-d\TH:i:s\Z");  // Double check.
 //per script
 $dateTime = date("Y-m-d\TH:i:s\Z", mktime(0, 0, 0, 1, 1, 2018));
 $shipDate = date("Y-m-d\TH:i:s\Z", mktime(0, 0, 0, 1, 2, 2018));
 $data_array = array(
   'requestContext' =>
  	array(
     'shipTo' => $shipto,
     'timeZone' => '300',
     'langCode' => 'en',
  ),
   'transactionId' => 'TXN_UofM_01',  // Required value. Not in list provided.
   'depResellerId' => $resellerid,
   'orders' =>
  array (
    0 =>
    array(
       'orderNumber' => $_POST["orderNumber"],
       'orderDate' => $dateTime,
       'orderType' => $_POST["transactionType"],
       'customerId' => $customerID,
       'deliveries' =>
      array (
        0 =>
        array(
           'deliveryNumber' => 'D1.2',  // How to increment this according to requirement.
           'shipDate' => $shipDate,
           'devices' =>
          array (
            0 =>
            $orders,
          ),
        ),
      ),
    ),
  ),
);
  echo "<hr /><h2>Request json</h2><pre>".var_dump($data_array)."</pre><hr />";
  return $data_array;

}


$dir = "/usr/local/webhosting/etc/openssl/certs";

// Open a known directory, and proceed to read its contents
//if (is_dir($dir)) {
//    if ($dh = opendir($dir)) {
//        while (($file = readdir($dh)) !== false) {
//            echo "filename: $file : <br />";
//        }
//        closedir($dh);
//    }
//}


function callDepApi($data){
	//var_dump($_POST);
	if ($_POST["env"] == "dev"){
		$location = "https://acc-ipt.apple.com/enroll-service/1.0/bulk-enroll-devices";

	}
	elseif ($_POST["env"] == "uat"){
		$location = "https://api-applecareconnect-ept.apple.com/enroll-service/1.0/bulk-enroll-devices";
	}
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $location); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
	));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	//adds from schleif for uat env 
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow_location);
	//end adds
	
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	if ($_POST["env"] == "uat"){
		curl_setopt($ch, CURLOPT_SSLCERT, "/etc/ssl/certs/GRX-0000046475.ACC1914.Test.AppleCare.cert.pem");
		curl_setopt($ch, CURLOPT_SSLKEY, "/etc/ssl/private/GRX-0000046475.ACC1914.Test.AppleCare.cert.private.pem");
		//curl_setopt($ch, CURLOPT_SSLCERT, "/etc/ssl/certs/GRX-0000046475.ACC1914.Test.AppleCare.cert.pem");
		curl_setopt($ch, CURLOPT_SSLCERTPASSWD,$_ENV["sslpass"]);
	}
	//curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	$data = '{"requestContext": {"shipTo": "0000046475","timeZone": "300","langCode": "en"},"transactionId": "TXN_UofM_01","depResellerId": "14658A10","orders": [{"orderNumber": "ORDER_000001","orderDate": "2018-01-01T00:00:00Z","orderType": "OR","customerId": "10000","deliveries": [{"deliveryNumber": "D1.2","shipDate": "2018-01-02T00:00:00Z","devices": [{"deviceId": "33645004YAM","assetTag": "A123456"},{"deviceId": "33645006YAM","assetTag": "A123456"}]}]}]}';
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
	
	$response = curl_exec($ch); 
	echo "<hr/>";
	echo "<p style='border-bottom:1px dashed #999;padding-bottom:14px;margin-bottom:14px;'>Response: " . $response . "</p>";

	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//  echo "HTTPStatus: ". $http_status. "<br>";

if(curl_exec($ch) === false)
{
    echo 'Curl error: ' . curl_error($ch);
}
else
{
    echo 'Operation completed without any errors';
}

  curl_close($ch);
  return $response;
  
}

function processResult($db, $result){
	if (!isset($result)) {echo "<p>no results!</p>";}
	
	else {
		if (array_key_exists('errorCode', $result)) {
        	echo "ERROR CODE: ". $result['errorCode']. "<br>";
        	echo "ERROR MESSAGE: ". $result['errorMessage']. "<br>";
        	echo "ERROR TRANSACTION ID: ". $result['transactionId']. "<br>";
        	echo "<br><br>";
		}
 		elseif (array_key_exists('enrollDeviceErrorResponse', $result)){
        	echo "ERROR CODE: ". $result['enrollDeviceErrorResponse']['errorCode']. "<br>";
        	echo "ERROR MESSAGE: ". $result['enrollDeviceErrorResponse']['errorMessage']. "<br>";
        	echo "<br><br>";
		}
		else {
        	echo "<p><strong>Device Enrollment Transaction Id: ". $result['deviceEnrollmentTransactionId']. "</strong></p>";
        	echo "<p><strong>Operation Status Code: ". $result['enrollDevicesResponse']['statusCode']. "</strong></p>";
        	echo "<p><strong>Operation Status Message: ". $result['enrollDevicesResponse']['statusMessage']. "</strong></p>";
		addResultToTable($db, $result); // Only add to table if result is not an error.
		}
	}
}



$request = buildRequest();

$result = json_decode(callDepApi($request), true);

processResult($db, $result);

//addResultToTable($db, $result);
?>

<p>&nbsp;</p>

<p><a class="process" href="index.php">Submit Another</a></p>

<p><a class="process" href="checkTransactionStatus.php">Check Transaction Status</a></p>

<p><a class="process" href="/admin/appledep">Admin Transactions Dashboard</a></p>

</body>
</html>
