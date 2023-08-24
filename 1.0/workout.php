<!DOCTYPE html>
<html>
    <meta name="viewport" content="width=device-width, initial-scale=1">
<head>
    <title>Movie Workout</title>
    <style>
        /* Define your color theme here */
        body {
            background-color: black;
            color: red;
        }
        button {
            background-color: #727272;
            color: #003cff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        @media (max-width: 768px) {
        .your-element-class {
            width: 100%;
        }
            }
    </style>
    <script type='text/javascript'>
    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
            name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

    
        // Workout Functions
        
        let exerciseData;
        let currentExerciseIndex = -1;
        let masterClockInterval;

        function loadXML(xmlFile) {
            let xhttp;
            if (window.XMLHttpRequest) {
                xhttp = new XMLHttpRequest();
            } else {
                xhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xhttp.onreadystatechange = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let xmlDoc = this.responseXML;
                    exerciseData = xmlDoc.getElementsByTagName("Exercise");
                    updateExercise();
                }
            };
            xhttp.open("GET", xmlFile, true);
            xhttp.send();
        }


    // Checks and displays current movements
        function updateExercise() {
            let currentExercise = document.getElementById("exercise");
            let currentExerciseName = document.getElementById("exercise-name");
            let currentExerciseDescription = document.getElementById("exercise-description");
            let currentExerciseSets = document.getElementById("exercise-sets");
            let currentExerciseReps = document.getElementById("exercise-reps");
            let currentExerciseRest = document.getElementById("exercise-rest");

            let currentTime = Date.now();
            let elapsedTime = currentTime - masterClockInterval.startTime;
            currentTime = elapsedTime;
            console.log("Current Time (ms): " + currentTime);  // Debugging output

            let exerciseFound = false;

            for (let i = 0; i < exerciseData.length; i++) {
                let exercise = exerciseData[i];
                let timestampStart = parseInt(exercise.getElementsByTagName("TimestampStart")[0].textContent);
                let timestampEnd = parseInt(exercise.getElementsByTagName("TimestampEnd")[0].textContent);

                console.log("Exercise " + (i+1));  // Debugging output
                console.log("Timestamp Start (ms): " + timestampStart);  // Debugging output
                console.log("Timestamp End (ms): " + timestampEnd);  // Debugging output

                if (currentTime >= timestampStart && currentTime <= timestampEnd) {
                    currentExercise.style.display = "block";
                    currentExerciseName.innerText = exercise.getElementsByTagName("Name")[0].textContent;
                    currentExerciseDescription.innerText = exercise.getElementsByTagName("Description")[0].textContent;
                    currentExerciseSets.innerText = exercise.getElementsByTagName("Sets")[0].textContent;
                    currentExerciseReps.innerText = exercise.getElementsByTagName("Reps")[0].textContent;
                    currentExerciseRest.innerText = exercise.getElementsByTagName("Rest")[0].textContent;
                    exerciseFound = true;
                    break;
                }
            }

            if (!exerciseFound) {
                currentExercise.style.display = "none";
                clearInterval(masterClockInterval);
            }
        }

    // Tickrate of master clock

        function updateMasterClock() {
            let masterClock = document.getElementById("master-clock");
            let currentTime = Date.now();
            let elapsedTime = currentTime - masterClockInterval.startTime;
            let minutes = Math.floor(elapsedTime / 60000);
            let seconds = Math.floor((elapsedTime % 60000) / 1000);
            let milliseconds = elapsedTime % 1000;
            masterClock.textContent = `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}.${String(milliseconds).padStart(3, "0")}`;
        }

    // Toggles Play/Pause Button

    function toggleMasterClock() {
    if (masterClockInterval && masterClockInterval.interval) {
        // If the clock is running, pause it
        clearInterval(masterClockInterval.interval);
        let currentTime = Date.now();
        let elapsedTime = currentTime - masterClockInterval.startTime;
        masterClockInterval.elapsedTime = elapsedTime;
        delete masterClockInterval.interval;
        document.getElementById('toggle-button').innerText = 'Start';
    } else {
        // If the clock is paused or hasn't been started yet, start it
        if (masterClockInterval && masterClockInterval.elapsedTime) {
            masterClockInterval.startTime = Date.now() - masterClockInterval.elapsedTime;
        } else {
            masterClockInterval.startTime = Date.now();
        }
        updateExercise();
        updateMasterClock();
        masterClockInterval.interval = setInterval(function () {
            updateExercise();
            updateMasterClock();
        }, 10);
        document.getElementById('toggle-button').innerText = 'Pause';
    }
}

    </script>
</head>
<body onload="masterClockInterval = { startTime: Date.now() }; loadXML(getParameterByName('xmlFile'));">


    <h1>Movie Workout</h1>
    <button id="toggle-button" onclick="toggleMasterClock()">Start</button>

    <div>
        <p id="master-clock">00:00:00.000</p>
    </div>
    <div id="exercise" style="display: none;">
        <h2 id="exercise-name"></h2>
        <p id="exercise-description"></p>
        <p>Sets: <span id="exercise-sets"></span></p>
        <p>Reps: <span id="exercise-reps"></span></p>
        <p>Rest: <span id="exercise-rest"></span></p>
    </div>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <a href="https://mov.fit/1.0">Create your next Workout</a>
</body>
</html>
