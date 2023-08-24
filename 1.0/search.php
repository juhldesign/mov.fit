<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . './../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Retrieve the search parameters
$mediaType = $_POST['mediaType'];
$searchTerm = $_POST['searchTerm'];

$tmdbApiKey = $_ENV['TMDB_API_KEY'];
$osApiKey = $_ENV['OS_API_KEY'];

$searchTerm = urlencode($_POST['searchTerm']); // URL-encode the search term
$year = $_POST['year'] ?? null; // You may want to set the year from your form input
$language = "en-US";
$page = 1;

// Determine the URL and additional parameters based on media type
if ($mediaType === "movie") {
    $url = "https://api.themoviedb.org/3/search/movie?api_key={$tmdbApiKey}&query={$searchTerm}";
} else { // Assume TV show
    $url = "https://api.themoviedb.org/3/search/tv?api_key={$tmdbApiKey}&query={$searchTerm}";
}

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache"
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

if ($err) {
    error_log("cURL Error #:" . $err);
    echo "cURL Error #:" . $err;
    exit;
}

// Decode the JSON response
$data = json_decode($response, true);

// Check if the 'results' key exists in the response and it's not empty
if (!isset($data['results']) || empty($data['results'])) {
    echo json_encode(["error" => "No results found."]);
    exit;
}

curl_close($curl);

echo json_encode($data); // Return the full results array in JSON format