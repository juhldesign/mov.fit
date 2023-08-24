<?php

file_put_contents('request.log', print_r($_REQUEST, true));
file_put_contents('post.log', print_r($_POST, true));


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . './../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$osApiKey = $_ENV['OS_API_KEY'];

$mediaType = $_POST['mediaType'] ?? '';  // Get the media type from the form
//file_put_contents('debug.log', "Assigned mediaType: '{$mediaType}'\n", FILE_APPEND);

$selectedTMDBId = $_POST['selectedTMDBId'] ?? '';


// Debugging: Log POST data
//file_put_contents('debug.log', "POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);

if ($mediaType === 'movie') {
    // Search for subtitles using the TMDB ID for a movie
    $url = "https://api.opensubtitles.com/api/v1/subtitles?tmdb_id={$selectedTMDBId}";
} elseif ($mediaType === 'tv') {
    // Search for subtitles using the parent TMDB ID, season number, and episode number for a TV show
    $seasonNumber = $_POST['seasonNumber'];
    $episodeNumber = $_POST['episodeNumber'];
    $url = "https://api.opensubtitles.com/api/v1/subtitles?parent_tmdb_id={$selectedTMDBId}&season_number={$seasonNumber}&episode_number={$episodeNumber}";
} else {
    // Debugging: Log media type error
    file_put_contents('debug.log', "Error: Invalid media type. Received mediaType: '{$mediaType}'\n", FILE_APPEND);
    die("Invalid media type. Please select either a movie or TV show.");
}


// Debugging: Log URL
//file_put_contents('debug.log', "URL: {$url}\n", FILE_APPEND);

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "Api-Key: {$osApiKey}"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    die("cURL Error #:" . $err);
}

// Debugging: Log the response from OpenSubtitles API
//file_put_contents('debug.log', "Response from OpenSubtitles API: " . print_r($response, true) . "\n", FILE_APPEND);


$responseObj = json_decode($response, true);

if (isset($responseObj['data'][0]['attributes']['files'][0]['file_id'])) {
    $file_id = $responseObj['data'][0]['attributes']['files'][0]['file_id'];
} else {
    die("File ID not found in response.");
}


// Debugging: Log File ID
//file_put_contents('debug.log', "File ID: {$file_id}\n", FILE_APPEND);

// Now you can use the $file_id to build the download URL
$downloadUrl = "https://api.opensubtitles.com/api/v1/download/{$file_id}";

// First request to get the download link
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.opensubtitles.com/api/v1/download",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode(['file_id' => $file_id]),
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Content-Type: application/json",
        "Api-Key: {$osApiKey}"
    ],
]);
$subtitleResponse = curl_exec($curl);
$subtitleErr = curl_error($curl);
curl_close($curl);
if ($subtitleErr) {
    die("cURL Error #:" . $subtitleErr);
}
$subtitleData = json_decode($subtitleResponse, true);
$subtitleLink = $subtitleData['link'];

// Second request to download the subtitle file
$subtitleCurl = curl_init($subtitleLink);
curl_setopt($subtitleCurl, CURLOPT_RETURNTRANSFER, true);
$subtitleFile = curl_exec($subtitleCurl);
$subtitleDownloadErr = curl_error($subtitleCurl);
curl_close($subtitleCurl);
if ($subtitleDownloadErr) {
    die("cURL Error #:" . $subtitleDownloadErr);
}

// Determine the title for the filename
$title = $responseObj['data'][0]['attributes']['feature_details']['title'] ?? '';
$seasonNumber = $_POST['seasonNumber'] ?? '';
$episodeNumber = $_POST['episodeNumber'] ?? '';
$filenameTitle = preg_replace('/[^\w\s.-]/', '_', $title);
if ($mediaType === 'tv') {
    $filenameTitle .= " S" . str_pad($seasonNumber, 2, '0', STR_PAD_LEFT) . " E" . str_pad($episodeNumber, 2, '0', STR_PAD_LEFT);
}
$filenameTitle .= ".txt"; // Add .txt extension

// Save the subtitle file to the server with the .txt extension
$tempTxtPath = __DIR__ . '/subtitles/' . $filenameTitle;
file_put_contents($tempTxtPath, $subtitleFile);

// Set headers to force download as a .txt file
header('Content-Description: File Transfer');
header('Content-Type: text/plain;charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filenameTitle . '"');
header('Content-Length: ' . filesize($tempTxtPath));

// Use readfile to send the file to the browser
readfile($tempTxtPath);

// Optionally, delete the temporary file from the server
unlink($tempTxtPath);

exit;






