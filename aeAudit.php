<html>
<head>
<title>AE Hosted Customer Status</title>
</head>
<body>

<form action="aeAudit.php" method="post" enctype="multipart/form-data">
    Select file to upload:
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Upload File" name="submit">
</form>


<?php
//Variables
//==========
$filePath = "/home/tonywp/tonywp.idxsandbox.com/testing/";


//Upload File On Form Submit
//===========================
$target_file = $filePath . basename($_FILES["fileToUpload"]["name"]);
$FileType = pathinfo($target_file,PATHINFO_EXTENSION);

// Check if image file is a actual CSV
if(isset($_POST["submit"])) {
    if($FileType == "csv") {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
			rename($target_file, 'clients.csv'); //Rename the actual file
        echo "The file <b>". basename( $_FILES["fileToUpload"]["name"]). "</b> has been uploaded.<p>";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
    } else {
        echo "File is not a csv.";
        $uploadOk = 0;
    }
}

$custFile = explode(",", file_get_contents('clients.csv')); //File Contents Into Array
$custList = array(); //Instantiate Array

foreach($custFile as $key => $value){ 
	$name = substr($value, 1); //Strip first letter from each name
	array_push($custList,$name);//Push Last Name only Into Final Array
}
	
	
//API Call Function
//-------------------------------------------------------------
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://www.agentevolution.com/wp-json/tk/v1/orders?verbose=true",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache",
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
$decode = json_decode($response,true);
}

//Loop Through Customer List and Match Woo Info

	foreach($decode as $wooKey => $wooValue){ 
	
	preg_match('/\w+$/m',$wooValue["customer_name"],$wooLastName); //Get Only Last Name
	

	$custListLower = array_map('strtolower',$custList);//Make Customer Lower Case
	$wooLastNameLower = array_map('strtolower',$wooLastName);//Make Woo Last Name Lower Case
	

	if(in_array($wooLastNameLower[0],$custListLower) && $wooValue["subscription_status"] !== "active"){ //Do the matching and output only those on hosting
		
			
			$billed = strtotime($wooValue["paid_date"]."+30 days");
			$newbilled = Date("m-d",$billed);
						
			$check = strtotime("now");
			$newcheck = Date("m-d",$check);
			
			if($newbilled <= $newcheck){
				echo "<font color=red>Past Due - Kill with Fire</font><br>";
			}
			

			echo "<b>Customer:</b> ".$wooValue["customer_name"]."<br>";
			echo "<b>Status:</b> ".$wooValue["subscription_status"]."<br>";
			echo "<b>Order Date: </b> ".$wooValue["paid_date"]."<br>";
			echo "<b>AID:</b> ".$wooValue["idx_aid"]."<p>";
		}
	}
	

?>

</body>
</html>
