<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
// Sample code showing OnePage CRM API usage

$user_id = 'YOUR_ONEPAGECRM_USER_ID';
$api_key = 'YOUR_ONEPAGECRM_API_KEY';

// Make OnePage CRM API call
function make_api_call($url, $http_method, $post_data = array(), $user_id = null, $api_key = null)
{
	$full_url = 'https://app.onepagecrm.com/api/v3/'.$url;
	$ch = curl_init($full_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);

    $request_headers = array();

    if($http_method == 'POST' || $http_method == 'PUT'){
        $request_headers[] = 'Content-Type: application/json';
        $json_data = json_encode($post_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    }

    $request_headers[] = "X-OnePageCRM-UID: $user_id";
    curl_setopt($ch, CURLOPT_USERPWD, $user_id . ":" . $api_key);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

    $result = json_decode(curl_exec($ch));
    curl_close($ch);

    if($result->status > 99){
        echo "API call error: {$result->message}\n";
        return null;
    }

    return $result;
}

// Get contacts list
echo "Getting contacts list...\n";
$contacts = make_api_call('contacts.json', 'GET', array(), $user_id, $api_key);
if($contacts == null){
    exit;
}
echo "We have {$contacts->data->total_count} contacts.\n";

// Create sample contact and delete it just after
echo "Creating new contact...\n";
$contact_data = array(
    'first_name' => 'Jonh',
    'last_name' => 'Doe',
    'company_name' => 'Acme Inc.',
    'tags' => array('api_test'),
    'emails' => array(
        array('type' => 'work', 'value' => 'john.doe@example.com'),
        array('type' => 'other', 'value' => 'johny@example.com')
        )
    );

$new_contact = make_api_call('contacts.json', 'POST', $contact_data, $user_id, $api_key);
if($new_contact == null){
    exit;
}

$cid = $new_contact->data->contact->id;
echo "Contact created with ID : {$cid}\n";

// Create an action for this contact
echo "Creating action for contact...\n";
$action_data = array(
    'contact_id' => $cid,
    'date' => '2016-05-06',
    'text' => 'Call John with estimate',
    'status' => 'date'
    );

$new_action = make_api_call('actions.json', 'POST', $action_data, $user_id, $api_key);
if($new_action == null){
    exit;
}

$aid = $new_action->data->action->id;
echo "Action created with ID : {$aid}\n";

echo "Deleting this contact...\n";
make_api_call("contacts/$cid.json", 'DELETE', array(), $user_id, $api_key);

echo "Finished...\n";
