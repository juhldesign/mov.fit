<?php
// Check if the session cookie is already set
if (!isset($_COOKIE['session_id'])) {
    $session_id = uniqid();
    setcookie('session_id', $session_id, time() + (86400 * 30), "/");
} else {
    $session_id = $_COOKIE['session_id'];
}
?>
<!DOCTYPE html>
<html>
    <meta name="viewport" content="width=device-width, initial-scale=1">
<head>
    <title>Movie Workout 1.0</title>
    <style>
    /* General styles */
    body {
        font-family: Helvetica, sans-serif;
        background-color: #f4f4f4;
        color: #f00;
        padding: 20px;
    }

    h1 {
        color: #333;
    }

    button {
        background-color: #00A3FF;
        color: #fff;
        border: none;
        height: 30px;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
    }

    textarea {
    width: 100%; /* Set the width to 100% of the container */
    max-width: 800px; /* Set a maximum width */
    min-width: 200px; /* Set a minimum width */
    height: 100px;
    font-size: 14px;
    padding: 5px;
    box-sizing: border-box; /* Include padding and border in the width */
    resize: vertical; /* Allow vertical resizing */
}


    /* Styles for the instructions pop-up */
    .instructions-popup {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #fff;
        padding: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        max-width: 400px;
        z-index: 9999;
    }

    /* Styles for the advanced options modal */
    .modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #fff;
        padding: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        width: auto;
        min-width: 200px; /* Adjust as needed */
        z-index: 9999;
    }

    .dropdown {
        width: 100%;
        box-sizing: border-box;
        /* Add other dropdown styles as needed */
    }

    @media (max-width: 768px) {
        form {
            font-size: 2em;
            margin: 1em;
        }
    }
    
    .close {
    position: absolute; /* Position the close button absolutely within the modal */
    top: 10px;          /* Set the distance from the top of the modal */
    right: 10px;        /* Set the distance from the right of the modal */
    cursor: pointer;    /* Change the cursor to a pointer when hovering over the close button */
    font-size: 18px;    /* Set the font size */
    color: #333;        /* Set the text color */
}

.close:hover {
    color: #f00;        /* Change the text color when hovering */
}

button:disabled {
    background-color: #ccc; /* Light grey background */
    cursor: not-allowed; /* Cursor indicating not clickable */
}

</style>
   <script type='text/javascript'>
    // Index Functions
    function toggleInstructions() {
        var popup = document.getElementById("instructions-popup");
        popup.style.display = popup.style.display === "block" ? "none" : "block";
    }

    function toggleAdvancedOptions() {
        var popup = document.getElementById("advanced-options-modal");
        popup.style.display = popup.style.display === "block" ? "none" : "block";
    }
    
    function toggleClaudeModal() {
        var modal = document.getElementById("claude-modal");
        modal.style.display = modal.style.display === "block" ? "none" : "block";
    }

    function saveAdvancedOptions(event) {
        event.preventDefault();
        let intensityLevel = document.getElementById('intensity-level').value;
        let workoutType = document.getElementById('workout-type').value;
        let mainForm = document.querySelector('form[action="server.php"]');
        mainForm.appendChild(createHiddenField('intensity-level', intensityLevel));
        mainForm.appendChild(createHiddenField('workout-type', workoutType));
        document.getElementById('advanced-options-modal').style.display = 'none';
    }

    window.onload = function() {
        document.getElementById('searchButton').addEventListener('click', function(event) {
            event.preventDefault();
            var mediaType = document.getElementById('selectedMediaType').value;
            if (mediaType !== 'movie' && mediaType !== 'tv') {
                alert("Invalid media type. Please select either a movie or TV show.");
                return;
            }

            var searchTerm = mediaType === 'movie' ? document.getElementById('searchMovieTerm').value : document.getElementById('searchTVShowTerm').value;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'search.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
        var results = JSON.parse(this.responseText);
        var dropdown = document.getElementById('movie-results');

        // Check if results are empty
        if (results.results.length === 0) {
            dropdown.classList.add('hidden'); // Hide the dropdown
        } else {
            dropdown.classList.remove('hidden'); // Show the dropdown
        }

        dropdown.innerHTML = '';
        results.results.forEach(function(result) {
            var option = document.createElement('option');
            var title = mediaType === 'movie' ? result.title : result.name;
            var year = mediaType === 'movie' ? result.release_date.split('-')[0] : result.first_air_date.split('-')[0];
            var tmdbId = result.id; // Store the TMDB ID
            option.value = tmdbId;
            option.textContent = title + ' (' + year + ')';
            dropdown.appendChild(option);
        });

        // Enable the download button after the results are populated
        document.getElementById('downloadButton').disabled = false;
    }
};

            xhr.send('mediaType=' + mediaType + '&searchTerm=' + searchTerm);
        });

        document.getElementById('movie').addEventListener('change', function() {
            document.getElementById('selectedMediaType').value = 'movie';
            document.getElementById('movieSearch').style.display = 'block';
            document.getElementById('tvSearch').style.display = 'none';
        });
        document.getElementById('tv').addEventListener('change', function() {
            document.getElementById('selectedMediaType').value = 'tv';
            document.getElementById('tvSearch').style.display = 'block';
            document.getElementById('movieSearch').style.display = 'none';
        });

        document.getElementById('downloadButton').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default behavior
            var selectedTMDBId = document.getElementById('movie-results').value;
            var selectedMediaType = document.getElementById('selectedMediaType').value;
            var seasonNumber = selectedMediaType === 'tv' ? document.getElementById('tvSeason').value : null;
            var episodeNumber = selectedMediaType === 'tv' ? document.getElementById('tvEpisode').value : null;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'download.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.responseType = 'blob';
            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                    var blob = new Blob([this.response], { type: 'text/plain;charset=UTF-8' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'subtitle.txt'; // Set the download filename with .txt extension
                    a.click();
                    window.URL.revokeObjectURL(url);
                }
            };
            xhr.send('selectedTMDBId=' + selectedTMDBId + '&mediaType=' + selectedMediaType + '&seasonNumber=' + seasonNumber + '&episodeNumber=' + episodeNumber);
        });
    };

    window.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.close').addEventListener('click', function() {
            document.querySelector('.modal').style.display = 'none'; // Hide the modal
        });
    });

    // The actual prompt text you want to copy
    var promptText = "Divide this subtitle file into the major acts or movements of the full video, include a descriptive name and 4-5 sentence description for each act, and the timecodes marking the beginning and end. Include 1 unique act per 15 minutes.";

    function copyPromptToClipboard() {
        // Create a temporary textarea element to hold the text
        var textarea = document.createElement('textarea');
        textarea.value = promptText; // Use the promptText variable here
        document.body.appendChild(textarea);

        // Select the text in the textarea
        textarea.select();
        document.execCommand('copy');

        // Remove the temporary textarea
        document.body.removeChild(textarea);

        // Optional: Show a message to the user
        alert('Prompt copied to clipboard!');
    }
</script>



</head>
<body>
    <body style="background-color:#fff2ff;">
    <h1>Create Movie Workout</h1>

    <div>
        <h2><a href="javascript:void(0)" onclick="toggleInstructions()">Instructions</a></h2><br>
        <a href="javascript:void(0)" onclick="toggleAdvancedOptions()">Get Subtitle File Here</a>&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" onclick="toggleClaudeModal()">Talk to Claude.ai</a><br>
<br>
        <form action="server.php" method="post">
            
            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>"> <!-- Pass the session ID -->
            <textarea name="promptInput" placeholder="Input summary from Claude here"></textarea><br><br> <!-- Replaced input with textarea -->
            <h3>Workout Options:</h3>
<p>Intensity Level: 
    <select id="intensity-level" name="intensity-level">
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
    </select>
</p>
<p>Workout Type:
    <select id="workout-type" name="workout-type">
        <option value="bodyweight">Bodyweight</option>
        <option value="cardio">Cardio</option>
        <option value="stretching">Stretching</option>
    </select>
</p><br><br>
            <button type="submit">Create</button>
        </form>

        
    </div>

    <!-- Instructions popup -->
    <div id="instructions-popup" class="instructions-popup">
        <h2>How to use:</h2>
        <p>Find and download subtitle</p><br>
        <p>Copy prompt</p><br>
        <p>go to claude and upload doc and paste prompt for summary</p><br>
        <p>Insert summary, select workout options and click create</p><br>
        <p>Wait patiently while workout is being workout</p><br>
        <p>Begin workout</p><br>
        
        <button onclick="toggleInstructions()">Close</button>
    </div>
<br><br>
    <!-- Advanced options modal -->
    <div id="advanced-options-modal" class="modal">
        <span class="close">&times;</span>
        <h2>Get subtitle file</h2>
        <form id="mediaForm" action="indexw.php" method="post" enctype="multipart/form-data">
            <p>Type of Media: 
            <input type="radio" id="movie" name="mediaType" value="movie">
<label for="movie">Movie</label>
<input type="radio" id="tv" name="mediaType" value="tv">
<label for="tv">TV Show</label>
            </p>
            <input type="hidden" id="selectedMediaType" name="selectedMediaType">
            <div id="movieSearch">
                <p>Movie Title: <input type="text" id="searchMovieTerm" name="searchMovieTerm"></p>
            </div>
            <div id="tvSearch" style="display: none;">
                <p>TV Show Title: <input type="text" id="searchTVShowTerm" name="searchTVShowTerm"></p>
                <p>Season: <input type="number" id="tvSeason" name="tvSeason" min="1" class="small-input"></p>
                <p>Episode: <input type="number" id="tvEpisode" name="tvEpisode" min="1" class="small-input"></p>
            </div>
            <select id="movie-results" name="selectedMovie"></select><br><br>
            <div><br>
                <button type="submit" id="searchButton">Search</button>&nbsp;&nbsp;&nbsp;<button type="button" id="downloadButton" disabled>Download</button><br><br>
            </div>
        </form>
    </div>
<!-- Claude Modal -->
<div id="claude-modal" class="modal">
    <span class="close" onclick="toggleClaudeModal()">&times;</span>
    <h2>Talk to Claude</h2>
    <!-- <p>Instructions: Follow these steps to interact with Claude.</p><br><p>1. Find and download the subtitles</p><br><p>2. Copy the Prompt</p><br><p>3. Upload the file and paste the prompt to CLaude, paste the summary on the main form</p> -->
    <button id="copyPromptButton" onclick="copyPromptToClipboard()">Click to Copy Prompt to Clipboard</button><br><br><br>
    <button onclick="window.open('https://claude.ai/chats', '_blank')">Talk to Claude.ai in a New Tab</button>
</div>


</body>
</html>
