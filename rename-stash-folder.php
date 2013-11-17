<?php
// Load composer autoloader
require 'vendor/autoload.php';

if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    trigger_error("This example only supports PHP Version 5.3.0 or higher. You are using " . phpversion());
}

const CLIENT_ID = '0'; // OAuth 2.0 client_id
const CLIENT_SECRET = ''; // OAuth 2.0 client_secret
const REDIRECT_URI = 'http://url/to/this/file'; // Change this!

const NEW_FOLDER_NAME = 'New Folder Name';

const AUTHORIZATION_ENDPOINT = 'https://www.deviantart.com/oauth2/authorize';
const TOKEN_ENDPOINT = 'https://www.deviantart.com/oauth2/token';
const SUBMIT_API = "https://www.deviantart.com/api/oauth2/stash/submit";
const FOLDER_API = "https://www.deviantart.com/api/oauth2/stash/folder";
const APPNAME = 'App.Name';

echo '<a href="' . REDIRECT_URI . '">Reload</a><br>';

/**
 * Oauth2 Process
 *
 * 1. Ask user to authorize by redirecting them to the authorization endpoint on DA
 * 2. Once user authorizes DA will send back an authoirzation code ($_GET['code'])
 * 3. We then use the code to get an access_token
 * 4. We use the access_token to access an API endpoint
 */
try {
    $client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET, OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
    if (!isset($_REQUEST['code'])) {
        $auth_url = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, REDIRECT_URI);
        header('Location: ' . $auth_url);
        die('Redirecting ...');
    } else {
        $params = array('code' => $_REQUEST['code'], 'redirect_uri' => REDIRECT_URI);
        $response = $client->getAccessToken(TOKEN_ENDPOINT, OAuth2\Client::GRANT_TYPE_AUTH_CODE, $params);

        $val = (object) $response['result'];

        if (!$val->access_token) {
            throw new Exception("No access token returned: " . $val->error_description);
        }

        $client->setAccessToken($val->access_token);

        $client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_OAUTH);

        // Submit a file first
        $response = $client->fetch(
            SUBMIT_API,
            array(
                'title' => 'Fella Sample Image',
                'artist_comments' => 'Fella Sample Image',
                'keywords' => 'fella sample image',
                'folder' => APPNAME,
                'file' => "@fella.png"
            ),
            OAuth2\Client::HTTP_METHOD_POST
        );

        $result = (object) $response['result'];

        if (!$result) {
            throw new Exception('No valid JSON response returned');
        }

        if ($result->status != 'success') {
            throw new Exception($result->error_description);
        }

        // Rename the folder we just created
        $new_folder = NEW_FOLDER_NAME . uniqid();
        $target_folderid = $result->folderid;
        $response = $client->fetch(
            FOLDER_API,
            array(
                'folderid' => $target_folderid,
                'folder' => $new_folder,
            ),
            OAuth2\Client::HTTP_METHOD_POST
        );

        $result = (object) $response['result'];

        if (!$result) {
            throw new Exception('No valid JSON response returned');
        }

        if ($result->status == 'success') {
            echo "Great Success! Your folder '".$target_folderid."' has been renamed to '".$new_folder."'.";
        } else {
            throw new Exception($result->error_description);
        }
    }
} catch (Exception $e) {
    echo "Fatal Error: ".$e->getMessage();
}
?>
