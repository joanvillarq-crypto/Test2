<?php
// Allow all domains (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Get the ID from the query parameters, ensuring it's sanitized
$movie_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
$tmdb_api_key = '8baba8ab6b8bbe247645bcae7df63d0d'; // TMDb API key

// Ensure the ID is provided
if (empty($movie_id)) {
    header("Location: movieRequest.html?error=TMDB+ID+is+missing!");
    exit;
}

// Movie URL formats
$sources = [
    "https://autoembed.cc/embed/player.php?id=" . $movie_id . "&lang=english",
    "https://autoembed.cc/embed/mlplayer.php?id=" . $movie_id . "&lang=english",
    "https://vidsrc.su/embed/movie/" . $movie_id,
    "https://freembed.site/server/movie_vidify_hyper.php?id=" . $movie_id, // New source URL
];
$tmdb_url = "https://api.themoviedb.org/3/movie/{$movie_id}?api_key={$tmdb_api_key}";

// Function to fetch .m3u8 link from embed/movie/m3u8.json
function fetchM3U8FromJson($tmdb_id) {
    $filePath = 'embed/movie/m3u8.json'; // Updated path
    if (file_exists($filePath)) {
        $existingLinks = json_decode(file_get_contents($filePath), true);
        foreach ($existingLinks as $entry) {
            if (isset($entry['tmdb_id']) && $entry['tmdb_id'] === $tmdb_id) {
                return $entry['link']; // Return the found link
            }
        }
    }
    return null; // Return null if not found
}

// Function to fetch the .m3u8 link from a URL
function fetchM3U8Link($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
    $response = curl_exec($ch);
    curl_close($ch);

    // Ensure the response is valid
    if ($response === false) {
        return null; // Handle error state
    }

    // Attempt to extract the .m3u8 link using regex
    preg_match('/https:\/\/.*?\.m3u8/', $response, $matches);

    return !empty($matches[0]) ? $matches[0] : null;
}

// Function to save .m3u8 links to a JSON file
function saveM3U8Link($link, $title, $tmdb_id) {
    $filePath = 'embed/movie/m3u8.json'; // Updated path

    // Read existing data
    $existingLinks = [];
    if (file_exists($filePath)) {
        $existingLinks = json_decode(file_get_contents($filePath), true);
    }

    // Ensure $existingLinks is an array
    if (!is_array($existingLinks)) {
        $existingLinks = [];
    }

    // Prepare new data entry
    $newData = [
        'link' => $link,
        'title' => $title,
        'tmdb_id' => $tmdb_id
    ];

    // Replace existing entry if TMDB ID matches, otherwise add new
    $found = false;
    foreach ($existingLinks as &$entry) {
        if (isset($entry['tmdb_id']) && $entry['tmdb_id'] === $tmdb_id) {
            $entry = $newData; // Replace existing entry
            $found = true;
            break;
        }
    }
    if (!$found) {
        $existingLinks[] = $newData; // Add new entry if not found
    }

    // Save updated data back to the JSON file
    if (file_put_contents($filePath, json_encode($existingLinks, JSON_PRETTY_PRINT)) === false) {
        header("Location: movieRequest.html?error=Failed+to+write+to+file.");
        exit;
    }
}

// Fetch movie data from TMDb
$ch_tmdb = curl_init();
curl_setopt($ch_tmdb, CURLOPT_URL, $tmdb_url);
curl_setopt($ch_tmdb, CURLOPT_RETURNTRANSFER, true);
$tmdb_response = curl_exec($ch_tmdb);
curl_close($ch_tmdb);

// Decode the TMDb response
$tmdb_data = json_decode($tmdb_response, true);

// Check if TMDb API returned an error
if (isset($tmdb_data['status_code'])) {
    header("Location: movieRequest.html?error=TMDb+API+error:+" . urlencode($tmdb_data['status_message']));
    exit;
}

// Ensure the TMDb data was decoded successfully
if (json_last_error() !== JSON_ERROR_NONE) {
    header("Location: movieRequest.html?error=Failed+to+decode+TMDb+response.");
    exit;
}

// Check if TMDb data contains the necessary information
$title = $tmdb_data['title'] ?? $tmdb_data['name'] ?? 'Untitled';

// Try to fetch the .m3u8 link from embed/movie/m3u8.json first
$m3u8_url = fetchM3U8FromJson($movie_id);

// If not found, try other sources in an auto-switching manner
if (!$m3u8_url) {
    foreach ($sources as $source) {
        $m3u8_url = fetchM3U8Link($source);
        if ($m3u8_url) {
            break; // Exit loop if a valid .m3u8 link is found
        }
    }
}

// Check if a .m3u8 URL is found
if ($m3u8_url) {
    // Ensure $m3u8_url is a string before passing to htmlspecialchars
    $safe_m3u8_url = is_string($m3u8_url) ? htmlspecialchars($m3u8_url) : '';

    // Save the m3u8 URL if found
    saveM3U8Link($safe_m3u8_url, $title, $movie_id);

    // Redirect with success message
    header("Location: movieRequest.html?success=Uploading+movie+successfully!");
} else {
    header("Location: movieRequest.html?error=No+.streaming+link+found.");
}
?>