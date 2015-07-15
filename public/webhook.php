<?php

//FORMSTACK FIELDS TO PASS AS TEXT, RATHER THAN RADIO/SELECT/ETC
$textFields = ["Hire Status", "Eligible for Rehire"];

//SET UP MYSQL CONNECTION FOR ERROR LOGGING
//include("mysql_connection.php");
include("functions.php");

//READS REQUEST SENT FROM FORMSTACK
$data = json_decode(file_get_contents('php://input'),true);

if($data)
{
	
	

	//REMOVE FormID & UniqueID FIELDS AUTOMATICALLY SENT BY FORMSTACK
	unset($data['FormID']);
	unset($data['UniqueID']);

	//LOOP THROUGH EACH FIELD SENT BY FORMSTACK
	//MODIFY EACH FIELD BASED ON TYPE TO MATCH JIRA FORMATTING AND STORE INTO NEW VARIABLE
	foreach($data as $fieldname => $arrayvalue)
	{
		if($arrayvalue['type'] == 'checkbox')
		{
			
			if(in_array($fieldname,$textFields))
			{
				$formatteddata[$fieldname] = $arrayvalue['value'];
				continue;
			}
			$checkarray = Array();
			if(!is_array($arrayvalue['value'])) //SINGLE OR NO OPTION IS CHECKED
			{
				if($arrayvalue['value']!="") {$formatteddata[$fieldname] = Array(Array("value" => $arrayvalue['value']));}  //SINGLE
			}
			else //MORE THAN ONE OPTION IS CHECKED
			{
				foreach($arrayvalue['value'] as $checkentry)
				{
					$checkarray[] = Array("value" => $checkentry);
				}
				
				
				$formatteddata[$fieldname] = $checkarray;
				
			}
		}
		
		if($arrayvalue['type'] == 'textarea')
		{
			if(!is_array($arrayvalue['value'])) //SINGLELINE
			{
			$formatteddata[$fieldname] = $arrayvalue['value'];
			}
			else //MULTILINE
			{
			$textstring = "";
			foreach($arrayvalue['value'] as $textline)
			{
				$textstring .= str_replace("\r","\\\\ ",$textline);
			}
			$formatteddata[$fieldname] = $textstring;
			}
		}
		if($arrayvalue['type'] == 'address')
		{
			$addstring = "";
			foreach($arrayvalue['value'] as $addline)
			{
				$addstring .= $addline . "\n";
			}
			$formatteddata[$fieldname]=$addstring;
		}
		
		if($arrayvalue['type'] == 'select')
		{
			if(in_array($fieldname,$textFields))
			{
				$formatteddata[$fieldname] = $arrayvalue['value'];
				continue;
			}
			if($arrayvalue['value'] && $arrayvalue['value']!="") //FILTERS OUT NULL AND BLANK FIELDS
			{
				$formatteddata[$fieldname]=Array("value" => $arrayvalue['value']);
			}
		}
		
		if($arrayvalue['type'] == 'radio')
		{
			if(in_array($fieldname,$textFields))
			{
				$formatteddata[$fieldname] = $arrayvalue['value'];
				continue;
			}
			if($arrayvalue['value'] && $arrayvalue['value']!="") //FILTERS OUT NULL AND BLANK FIELDS
			{
				$formatteddata[$fieldname]=Array("value" => $arrayvalue['value']);
			}
		}
		if($arrayvalue['type'] == 'number')
		{$formatteddata[$fieldname] = floatval($arrayvalue['value']);}
	
	
		if($arrayvalue['type'] == 'text')
		{$formatteddata[$fieldname] = $arrayvalue['value'];}
		
		if($arrayvalue['type'] == 'datetime')
		{$formatteddata[$fieldname] = $arrayvalue['value'];}
		if($arrayvalue['type'] == 'email')
		{$formatteddata[$fieldname] = $arrayvalue['value'];}
		

	}

	if($data['Request Type']['value'] != "Support")
	{$formatteddata['summary'] = "No Summary";}
	
	if($data['Change Type']['value'] == "Add Vendor")
	{$formatteddata['summary'] = "Add Vendor - " . $formatteddata['Vendor Name'];}
	if($data['Change Type']['value'] == "Add Item")
	{$formatteddata['summary'] = "Add Item - " . $formatteddata['Item Name'];}
	if($data['Request Type']['value'] == "User Access")
	{$formatteddata['summary'] = $formatteddata['Hire Status'] . " - " . $formatteddata['First Name'] . " " . $formatteddata['Last Name'];}

	

	//MAP JIRA FIELD NAME TO FORMSTACK FIELD NAME
	$mapping = [
	"customfield_10119" => "First Name",
	"customfield_10120" => "Last Name",
	"customfield_10703" => "Effective Date",
	"customfield_10401" => "Properties",
	"customfield_10118" => "Systems Requested",
	
	"customfield_10503" => "Email Address", 
	"customfield_10602" => "Job Title", 
	
	"customfield_10604" => "Adaco Roles",
	
	"customfield_10706" => "Hire Status",
	"customfield_10630" => "ADP Number",
	"customfield_10702" => "Date of Birth", 
	"customfield_10701" => "Date of Hire", 
	"customfield_10644" => "Primary Position",
	"customfield_10646" => "Pay Rate", 
	//"customfield_10707" => "Secondary Position?",
	"customfield_10645" => "Secondary Job Title",
	"customfield_10648" => "Secondary Pay Rate",
	"customfield_10635" => "Termination Reason",
	"customfield_10806" => "Eligible for Rehire",

	"customfield_10713" => "Your Email Address",
	"customfield_10712" => "Great Plains #",
	"customfield_10614" => "Vendor Name",
	"customfield_10722" => "Vendor Email",
	"customfield_10619" => "Vendor Phone",
	"customfield_10620" => "Vendor Contact Name",
	"customfield_10621" => "Vendor Contact Title",
	"customfield_10622" => "Vendor Contact Email",
	"customfield_10714" => "Vendor Address",

	"customfield_10627" => "Item Name",
	"customfield_10608" => "Purchase Pack Size",
	"customfield_10610" => "Purchase Price",
	"customfield_10609" => "Catch Weight Item?",
	"customfield_10611" => "Inventory Unit",
	"customfield_10612" => "Add to Maintain Guide",
	"customfield_10803" => "Add to Purchase Template",

	"customfield_10654" => "Price",
	"customfield_10652" => "SLU Group",
	"customfield_10653" => "Printer",
	"customfield_10721" => "Submenu",
	"customfield_10655" => "Special Mods",

	"description" => "Additional Comments",
	"summary" => "summary"];

	//CREATE NEW ARRAY WITH JIRA FIELD NAMES
	foreach($mapping as $k => $v)
	{	
		if($formatteddata[$v]!="")
		{
			$mappeddata[$k] = $formatteddata[$v];
		}
	}

	//ADD REPORTER - SEARCHES USERS AND ASSIGNS IF USER EXISTS - Doesn't work yet
	
	/*
	$reporter = array_search($data['Your Email Address']['value'],getUsername());
	if($reporter)
	{$mappeddata['reporter'] = Array("name" => $reporter);}
	*/


	//ADD JIRA PROJECT NAME
	$mappeddata = array_reverse($mappeddata, true);
	$mappeddata['project']=Array('key'=>'BS');
	$mappeddata = array_reverse($mappeddata, true);

	//ADD ISSUE TYPE
	if($data['Change Type']['value'] == "Add Item" && $data['Change Item in which system']['value']=="Adaco")
	{
		$mappeddata['issuetype']=Array('name'=>'Add Adaco Item');
	}
	else
	{
		$mappeddata['issuetype']=Array('name'=>'Formstack Issue');
	}
	
	
	
	$mappeddata = Array('fields' => $mappeddata);


	$url = "https://hakkasan.atlassian.net/rest/api/2/issue/";

	//ENCODE DATA AS JSON
	$content = json_encode($mappeddata);


	//SET SEND OPTIONS AND SEND TO JIRA
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER,
	array("Content-type: application/json",
	"Authorization: Basic Y2JyYWR5QGhha2thc2FuLmNvbTpKdWljZWIweEohcmE="));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

	$json_response = curl_exec($curl);
	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	//INSERT DATA SENT AND RESPONSE FROM JIRA SERVER INTO DATABASE

	$escapedContent = $conn->real_escape_string($content);
	$escapedResponse = $conn->real_escape_string($json_response);
	$escapedData = $conn->real_escape_string(json_encode($data));
/*
	$sql = "INSERT INTO Requests (FormstackRequest,SentRequest,Response,Status) VALUES ('$escapedData','$escapedContent','$escapedResponse','$status')";
	if (!$conn->query($sql)) 
	{
		printf("Error: %s", $conn->error);
	}
*/

	//MYSQL DB NOT WORKING - SAVE TO TEXT FILE INSTEAD
	
	//$appendText = "\r\n" . $escapedData . "|" . $escapedContent . "|" . $escapedResponse;
	//file_put_contents("responses.txt",$appendText, FILE_APPEND);


	curl_close($curl);

	$response = json_decode($json_response, true);

}
else
{
	echo "No data";
}
?>