<?php

$data = json_decode(file_get_contents('php://input'),true);

//FIELDS THAT WILL MAP TO JIRA FIELDS,  (FORMSTACK FIELD => JIRA FIELD)
$fieldsToMap = [
"Summary" => "summary", 
"Properties" => "customfield_10401",
"Your Email Address" => "customfield_10713",
"Effective Date" => "customfield_10703",
"Systems Requested" => "customfield_10118",
"UniqueID" => "customfield_10630"];

$fieldsNotToAdd = ["Items to add","FormID","Request Type","Change Type","Change Item in which system"];  //FIELDS NOT TO COPY OVER, CASE SENSITIVE

//DEFINE PROPERTIES BY POS SYSTEM
$microsProperties = ["Hakkasan - Las Vegas", "Hakkasan - New York", " Hakkasan - San Francisco", "Ivory - Los Angeles"];
$infogenesisProperties = ["Omnia - Las Vegas", "Omnia - San Diego", "Hakkasan Nightclub"];
$simphonyProperties = ["Searsucker - Las Vegas", "Searsucker - San Diego", "Herringbone - Santa Monica", "Herringbone - La Jolla", "Searsucker - Del Mar", "Searsucker - Austin"];

//CONFIGURES ARRAY OF EMAIL ADDRESSES AND JIRA USERS TO USE TO SET REPORTER
$requestorEmail = strtolower($data['Your Email Address']['value']);
$userMap=array();
$fileContents = file_get_contents('reporterMap.csv');
$lineArray = explode("\r\n",$fileContents);
foreach($lineArray as $line)
{
	$user = explode(",",$line);
	$userMap[strtolower($user[0])] = strtolower($user[1]);
}






//SETS SYSTEM FIELD BASED ON "CHANGE ITEM IN WHICH SYSTEM" FIELD
if($data['Change Item in which system']['value'] && $data['Change Item in which system']['value']!="-")
{
	if($data['Change Item in which system']['value']=="Point of Sale")
	{
		//LOGIC TO SET CORRECT POS BY PROPERTY - DEFAULTS TO MICROS OR IF MULTIPLE PROPERTIES ARE SELECTED		
		if(in_array($data['Properties']['value'],$alohaProperties,TRUE)) {$data['Systems Requested']['value']="Aloha";}
		elseif(in_array($data['Properties']['value'],$microsProperties,TRUE)) {$data['Systems Requested']['value']="Micros";}
		elseif(in_array($data['Properties']['value'],$infogenesisProperties,TRUE)) {$data['Systems Requested']['value']="Infogenesis";}
		elseif(in_array($data['Properties']['value'],$simphonyProperties,TRUE)) {$data['Systems Requested']['value']="Simphony";}
		else {$data['Systems Requested']['value']="Micros";}
	}
	else
	{
		$data['Systems Requested']['value']=$data['Change Item in which system']['value'];
		//NEED TO CONVERT ADD/CHANGE VENDOR OPTIONS
	}
}
if(is_array($data['Systems Requested']['value']))
{
	
	if(in_array($data['Properties']['value'],$alohaProperties,TRUE)) {$replacementSystem="Aloha";}
	elseif(in_array($data['Properties']['value'],$microsProperties,TRUE)) {$replacementSystem="Micros";}
	elseif(in_array($data['Properties']['value'],$infogenesisProperties,TRUE)) {$replacementSystem="Infogenesis";}
	elseif(in_array($data['Properties']['value'],$simphonyProperties,TRUE)) {$replacementSystem="Simphony";}
	else {$replacementSystem="Micros";}
	
	$data['Systems Requested']['value']=str_replace("Point of Sale",$replacementSystem,$data['Systems Requested']['value']);
	
}	
elseif($data['Systems Requested']['value']=="Point of Sale")
{
		//LOGIC TO SET CORRECT POS BY PROPERTY - DEFAULTS TO MICROS OR IF MULTIPLE PROPERTIES ARE SELECTED		
		if(in_array($data['Properties']['value'],$alohaProperties,TRUE)) {$data['Systems Requested']['value']="Aloha";}
		elseif(in_array($data['Properties']['value'],$microsProperties,TRUE)) {$data['Systems Requested']['value']="Micros";}
		elseif(in_array($data['Properties']['value'],$infogenesisProperties,TRUE)) {$data['Systems Requested']['value']="Infogenesis";}
		elseif(in_array($data['Properties']['value'],$simphonyProperties,TRUE)) {$data['Systems Requested']['value']="Simphony";}
		else {$data['Systems Requested']['value']="Micros";}
} 





if($data['FormID']=="2436597")  //IF SENT FROM VENDOR SETUP FORMSTACK
{
	$jiraData["summary"] = "Vendor Setup - " . $data["Vendor Check Name"]["value"];
	$jiraData['issuetype']=array('name'=>'Configure');
	unset($fieldstoMap["Summary"]);
}






//MAP FIELDS TO JIRA FIELDS
foreach($fieldsToMap as $key => $value)
{
	if($key == "UniqueID")
	{$jiraData[$value] = "https://www.formstack.com/admin/submission/view/" . $data[$key]; unset($data[$key]); continue;}
	if(!isset($data[$key]) || !isset($data[$key]["value"]) || $data[$key]["value"]=="") {continue;} //IF KEY OR VALUE IS EMPTY GO TO NEXT ITEM IN ARRAY
	if($data[$key]['type']=="checkbox") //CHECKBOX FIELDS NEEDS TO BE PROCESSED DIFFERENTLY
	{
		if(is_array($data[$key]["value"])) //IF MULTIPLE OPTIONS ARE SELECTED
		{
			$checkboxArray = array();
			foreach($data[$key]["value"] as $optionName)
			{
				array_push($checkboxArray,array("value" => $optionName));
			}
			$jiraData[$value] = $checkboxArray;
		}
		else  //SINGLE OPTION IS CHECKED
		{	
			$jiraData[$value] = array(array("value" => $data[$key]["value"]));
		}
	}
	else
	{
		$jiraData[$value] = $data[$key]["value"];
	}
	
	unset($data[$key]);
}


$jiraData["summary"]=substr($jiraData["summary"],0,254); //LIMITS SUMMARY FIELD TO 254 CHARACTERS


//MAP ALL NON-NULL FIELDS TO DESCRIPTION
$descText="";
foreach($data as $key => $value)
{
	if(in_array($key,$fieldsNotToAdd)) {continue;} //IF FIELD NAME IS IN FIELDSNOTTOADD, SKIP FIELD AND PROCESS NEXT FIELD
	if($value["value"])
	{
		$keyText = rtrim(rtrim($key,"1234567890"));
		if($keyText=="Item Name") {$descText .= "\\\\";} //ADDS EXTRA LINE BREAK IN BETWEEN ITEM GROUPS
		
		$descText .= " *" . $keyText . ":* ";
		if($value["type"]=="textarea") {$descText .= "\\\\";}
		if(is_array($value["value"]))  //IF THERE ARE MULTIPLE SELECTIONS OR LINES IN TEXTAREA
		{
			if($value["type"]=="checkbox" || $value["type"]=="address" || $value["type"]=="select")
			{$descText .= implode(", ",$value["value"]);}
			if($value["type"]=="textarea")
			{$descText .= implode(" ",$value["value"]);}
		}
		else
		{
			$descText .= $value["value"];
		}	
		
		$descText .= "\\\\ ";
		if($value["type"]=="textarea") {$descText .= "\\\\";}
	}
}
$jiraData["description"] = $descText;

//SET ASSIGNEE TO SPECIFIC USER BASED ON SYSTEMS REQUESTED
foreach($jiraData["customfield_10118"] as $systems) //LOOP THROUGH INDEXED ARRAY OF SYSTEMS
{
	foreach($systems as $key => $value) //SYSTEMS ARE IN ARRAY AS "value" => "system"
	{
	if($value=="Adaco/Eatec")
		{
			$jiraData["assignee"]=Array("name"=>"ctalia@hakkasan.com");
		}
	}
}

//SET PROJECT
$jiraData = array_reverse($jiraData, true);
$jiraData['project']=array('key'=>'BIS');
$jiraData = array_reverse($jiraData, true);

//SET ISSUE TYPE

switch($data["Request Type"]["value"])
{
	case "User Access":
		$jiraData['issuetype']=array('name'=>'User Access');
		break;
	
	case "Configure Items/Vendor":
		$jiraData['issuetype']=array('name'=>'Configure');
		break;
		
	case "Configure Systems":
		$jiraData['issuetype']=array('name'=>'Configure');
		break;
		
	case "Gift Card Order":
		$jiraData['issuetype']=array('name'=>'Configure');
		break;
		
	case "Training Request":
		$jiraData['issuetype']=array('name'=>'Configure');
		break;
		
	case "Promo/Trade Card Activation":
		$jiraData['issuetype']=array('name'=>'Configure');
		break;

	case "Micros Card Reorder":
		$jiraData['issuetype']=array('name'=>'Configure');
		break;
		
	case "Support":
		$jiraData['issuetype']=array('name'=>'Support'); 
		$jiraData['priority']=array('id'=>'2');		//SET PRIORITY TO HIGH FOR SUPPORT ISSUES
		break;
	
	case "New Feature Request":
		$jiraData['issuetype']=array('name'=>'Feature Request');
		break;
}





//IF EMAIL ADDRESS IS IN REPORTER ARRAY, SET REPORTER - USE jiraadmin OTHERWISE
if($userMap[$requestorEmail]) {$reporter = $userMap[$requestorEmail];}
else {$reporter = "jiraadmin";}
$jiraData['reporter']=array('name'=>$reporter);


//ENCAPSUATE ALL FIELDS INTO "fields" ARRAY
$jiraData = array('fields' => $jiraData);

//$url= "http://requestb.in/16ztmjy1";  //REQUESTBIN USED FOR TESTING
$url = "https://hakkasan.atlassian.net/rest/api/2/issue/";



//CONFIGURE CURL OPTIONS AND USE CURL TO SEND TO JIRA
$content = json_encode($jiraData);

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_FAILONERROR, true);
curl_setopt($curl, CURLOPT_HTTPHEADER,
array("Content-type: application/json",
"Authorization: Basic SklSQUFkbWluOlR3aXR0ZXJzMjBTdGlteSEo"));


curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

$json_response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

?>