<html>
<head>
<title>Apple Device Enrollment Program</title>

<link rel="stylesheet" type="text/css" href="../admin/showcase-admin.css"/>
<style type="text/css">
body {
	margin:40px;
	font-size:.9em;
}
input, select {
	padding:.5em;
	font-size:inherit;
}
input[type=submit] {
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
input[type=submit]:hover {
	background:#567daa;
}
</style>
<script type="text/javascript">
function checkform() {
	if(!document.depform.serialNumber.value) { 
		alert("Please enter the device serial number"); document.depform.serialNumber.focus(); return false; }
	if(!document.depform.orderNumber.value) { 
		alert("Please enter the RMS sales transaction number"); document.depform.orderNumber.focus(); return false; } 
}
</script>

</head>

<body>
<?php 
$uniqname = $_SERVER["REMOTE_USER"];
if ($uniqname == "mrschlei") {
	$env = "uat";	
}
else {$env = "dev";}
//echo "<p><strong>Logged in as:</strong> ".$uniqname."</p>";
?>
<h1>Apple Device Enrollment Program</h1>

<form name="depform" action="submitToDep.php" method="post" onSubmit="return checkform()">

<p>Device Serial Number:<br/>
<input type="text" name="serialNumber"/></p>

<p>RMS Sales Transaction Number:<br/>
<input type="text" name="orderNumber"/></p>

<p>Transaction Type:<br/>
<select name="transactionType">
 <option value="OR">Purchase</option>
 <option value="RE">Return</option>
 <!--<option value="OV">Override</option>-->
</select></p>
<input type="hidden" value="<?php echo $env; ?>" id="env" name="env" />
<p><input type="submit" value="Submit to Apple"/></p>

</form>

</body>
</html>

