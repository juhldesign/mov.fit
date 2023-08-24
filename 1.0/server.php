<?php

// Retrieve the session name and the prompt input from the POST request
$session_id = $_COOKIE['session_id'] ?? 'default_session'; // Retrieve session ID from cookie
$promptInput = $_POST['promptInput'];

// If the prompt input is empty, try to retrieve the existing workout
if (empty($promptInput)) {
    $xmlFile = $sessionName . '_workout.xml';
    if (file_exists($xmlFile)) {
        // If the XML file exists, redirect to workout.php
        header("Location: workout.php?xmlFile=" . urlencode($xmlFile));
        exit;
    } else {
        http_response_code(404);
        echo 'No existing workout found for the given session name.';
        exit;
    }
}

// Set the API endpoint
$apiEndpoint = 'https://api.openai.com/v1/chat/completions';

// Advanced options from index.php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $intensityLevel = $_POST['intensity-level'] ?? '';
    $workoutType = $_POST['workout-type'] ?? '';
    // Now you can use $intensityLevel and $workoutType as variables in your PHP script.
}

// Set the request payload
$requestPayload = [
    'model' => 'gpt-3.5-turbo-16k-0613',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'You are an expert at creating specific workouts. Your next task is to create a workout for someone who will be watching a film/TV show while they exercise. The workout should be at a decent intensity and should focus on bodyweight exercises. Please generate exercises with funny and creative names and descriptions. All exercises should be designed to be performed without the need for equipment, unless specifically mentioned otherwise. Each exercise should be formatted in XML with the following tags: <Exercise><Name>Exercise Name</Name><Description>Exercise Description</Description><Sets>Number of Sets</Sets><Reps>Number of Reps</Reps><Rest>Rest Period</Rest><TimestampStart>Start Time</TimestampStart><TimestampEnd>End Time</TimestampEnd></Exercise>. The timestamps should correspond to the timing of the film/TV show and should provide ample time for both the exercise and rest periods (for example if you say 3 sets 10 jumping jacks rest period 10secs, understand that an average human will take ~10-12 seconds for 10 jumnping jacks, so 30 secs for all 30 and with an additional 10 seconds between each set means at the absolute minimum 1 minute should be given for that exercise combination). The timestamps shall be sequential, meaning, the end timestamp of one exercise should be the start timestamp of the next exercise, and the first timestamp should start at 3 seconds.'
                        // Delete sample below above for funny descriptions but worse display time range (need to troubleshoot that to have both)
                        // (for example if you say 3 sets 10 jumping jacks rest period 10secs, understand that an average human will take ~10-12 seconds for 10 jumnping jacks, so 30 secs for all 30 and with an additional 10 seconds between each set means at the absolute minimum 1 minute should be given for that exercise combination)
        ],
        [
            'role' => 'user',
            'content' => $promptInput
        ]
    ],
    'max_tokens' => 9000,
    'temperature' => 0.85,
    'n' => 1,
    'stop' => '\n',
    'frequency_penalty' => 0.1,
    'presence_penalty' => 0.2,
];

// Set the request headers
$headers = [
    'Authorization: Bearer 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',
    'Content-Type: application/json',
];

// Send the request to OpenAI API
$ch = curl_init($apiEndpoint);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if ($response === false) {
    // Error in API request
    http_response_code(500);
    echo 'Error generating movie workout: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    // API returned an error response
    http_response_code($httpCode);
    echo 'Error generating movie workout: ' . $response;
    exit;
}

// Debug: Print the API response
//echo 'API Response:<br>';
//echo $response;
//echo '<br><br>';

// Parse the response
$responseData = json_decode($response, true);

if ($responseData === null) {
    // Error parsing API response
    http_response_code(500);
    echo 'Error parsing API response: ' . json_last_error_msg();
    exit;
}

/**
 * Format a timestamp in hh:mm:ss.MMM format to milliseconds.
 *
 * @param string $timestamp The timestamp in hh:mm:ss.MMM format.
 * @return int The timestamp in milliseconds.
 */
function formatTimestamp($timestamp)
{
    $timeComponents = explode(':', $timestamp);
    $hours = intval($timeComponents[0]);
    $minutes = intval($timeComponents[1]);
    $seconds = floatval($timeComponents[2]);

    $milliseconds = (($hours * 60 * 60) + ($minutes * 60) + $seconds) * 1000;

    return $milliseconds;
}

// Create the XML document
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Exercises></Exercises>');

// Parse the response and add exercises to the XML document
foreach ($responseData['choices'] as $choice) {
    $message = $choice['message']['content'];
    preg_match_all('/<Name>(.*?)<\/Name>.*?<Description>(.*?)<\/Description>.*?<Sets>(.*?)<\/Sets>.*?<Reps>(.*?)<\/Reps>.*?<Rest>(.*?)<\/Rest>.*?<TimestampStart>(.*?)<\/TimestampStart>.*?<TimestampEnd>(.*?)<\/TimestampEnd>/s', $message, $matches, PREG_SET_ORDER);
    if (!empty($matches)) {
        foreach ($matches as $match) {
            $exercise = $xml->addChild('Exercise');
            $exercise->addChild('Name', $match[1]);
            $exercise->addChild('Description', $match[2]);
            $exercise->addChild('Sets', $match[3]);
            $exercise->addChild('Reps', $match[4]);
            $exercise->addChild('Rest', $match[5]);
            $exercise->addChild('TimestampStart', formatTimestamp($match[6]));
            $exercise->addChild('TimestampEnd', formatTimestamp($match[7]));
        }
    }
}

// Debug: Print the XML data
//echo 'XML Data:<br>';
//echo $xml->asXML();
//echo '<br><br>';

// Save the XML data to a file
$file = __DIR__ . '/closedcaptions/' . $session_id . '_workout.xml';

if ($xml->asXML($file) === false) {
    // Error saving the XML file
    http_response_code(500);
    echo 'Error saving the XML file: ' . error_get_last()['message'];
    exit;
}

// Redirect to workout.php
header("Location: workout.php?xmlFile=" . urlencode($file));
exit;

?>
