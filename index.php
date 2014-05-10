<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
// Sample code showing OnePage CRM API usage

$api_login = 'OnePageCRM-login';
$api_password = 'OnePageCRM-password';

// Make OnePage CRM API call
function make_api_call($url, $http_method, $post_data = array(), $uid = null, $key = null)
{
	$full_url = 'https://app.onepagecrm.com/api/v3/'.$url;
	$ch = curl_init($full_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);

	$timestamp = time();
	$auth_data = array($uid, $timestamp, $http_method, sha1($full_url));

	// For POST and PUT methods we have to calculate request body hash
	if($http_method == 'POST' || $http_method == 'PUT'){
		$post_query = http_build_query($post_data);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query);
		$auth_data[] = sha1($post_query);
	}

	// Auth headers
	if($uid != null){ // We are logged in
		$hash = hash_hmac('sha256', implode('.', $auth_data), $key);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"X-OnePageCRM-UID: $uid",
			"X-OnePageCRM-TS: $timestamp",
			"X-OnePageCRM-Auth: $hash"
			));
	}

	$result = json_decode(curl_exec($ch));
	curl_close($ch);

	if($result->status > 99){
		echo "API call error: {$result->message}\n";
		return null;
	}

	return $result;
}


// Login
echo "Login action...\n";
$data = make_api_call('login.json', 'POST', array('login' => $api_login, 'password' => $api_password));
if($data == null){
	exit;
}

// Get UID and API key from result
$uid = $data->data->user_id;
$key = base64_decode($data->data->auth_key);
echo "Logged in, our UID is {$uid}\n";

// Get contacts list
echo "Getting contacts list...\n";
$contacts = make_api_call('contacts.json', 'GET', array(), $uid, $key);
if($data == null){
	exit;
}
echo "We have {$contacts->data->total_count} contacts.\n";

// Create sample contact and delete it just after
echo "Creating new contact...\n";
$contact_data = array(
	'first_name' => 'Jonh',
	'last_name' => 'Doe',
	'company' => 'Acme Inc.',
	'tags' => 'api_test'
	);

$new_contact = make_api_call('contacts.json', 'POST', $contact_data, $uid, $key);
if($new_contact == null){
	exit;
}

$cid = $new_contact->data->contact->id;
echo "Contact created. ID {$cid}\n";

echo "Deleting this contact...";
make_api_call("contacts/$cid.json", 'DELETE', array(), $uid, $key);

echo "OK.";