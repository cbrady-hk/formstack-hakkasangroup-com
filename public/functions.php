<?php

function getUsername() {
	

	$userList=Array();
	for($i=0;$i<=200;$i+=50)
	{
		$url="https://hakkasan.atlassian.net/rest/api/2/group?groupname=jira-users&expand=users[" . $i . ":" . ($i+50) . "100]";

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FAILONERROR, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER,
		array("Authorization: Basic Y2JyYWR5QGhha2thc2FuLmNvbTpKdWljZWIweEohcmE="));
		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		$response = json_decode($json_response, true);

		foreach($response['users']['items'] as $user)
		{
			$userList[$user['name']] = $user['emailAddress'];
		}
	}
	
	return $userList;
}
?>