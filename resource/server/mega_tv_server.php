
<?php

function fetchJSONSources($jsonFiles) {
    $data = null; // To hold the data from JSON files
    foreach ($jsonFiles as $jsonFile) {
        $url = "https://freembed.site/resource/dbjson/tv/" . $jsonFile; // Update with the actual URL

        // Initialize a cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            echo "Error fetching $jsonFile: " . curl_error($ch) . "<br>";
            curl_close($ch);
            continue; // Skip to next file if there is an error
        }

        // Close the cURL session
        curl_close($ch);

        // Decode the JSON response
        $data = json_decode($response, true);

        // Check if JSON decoding was successful
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data; // Return the decoded JSON data
        } else {
            echo "JSON decode error for $jsonFile: " . json_last_error_msg() . "<br>";
        }
    }
    return ['error' => 'All JSON sources failed to fetch.'];
}

function getM3U8Link($data, $tmdbId, $season, $episode) {
    foreach ($data as $show) {
        if ($show['tmdb_id'] == $tmdbId) {
            foreach ($show['seasons'] as $seasonData) {
                if ($seasonData['season_number'] == $season) {
                    foreach ($seasonData['episodes'] as $episodeData) {
                        if ($episodeData['episode_number'] == $episode) {
                            return [
                                'm3u8_link' => $episodeData['m3u8_link'],
                                'episode_name' => $episodeData['episode_name'],
                                'poster' => $episodeData['poster']
                            ]; // Return the M3U8 link and related info
                        }
                    }
                }
            }
        }
    }
    return null; // Return null if not found
}

// Define the JSON files to fetch
$jsonFiles = ['towl.json', 'darylDixon.json', 'twd.json', 'ftwd.json']; // List of JSON files

// Initialize result variable
$m3u8LinkData = null;

// Attempt to fetch and get M3U8 link from all JSON sources
foreach ($jsonFiles as $jsonFile) {
    $jsonSource = fetchJSONSources([$jsonFile]); // Fetch each source
    if (isset($jsonSource['error'])) {
        continue; // Skip if there's an error
    }

    // Attempt to get M3U8 link from the current source
    $m3u8LinkData = getM3U8Link($jsonSource, (int)($_GET['tmdb_id'] ?? 0), (int)($_GET['s'] ?? 0), (int)($_GET['e'] ?? 0));
    if ($m3u8LinkData !== null) {
        break; // Stop if we find a valid M3U8 link
    }
}

// Prepare the result
$result = $m3u8LinkData ?: ['error' => 'M3U8 link not found.'];

// Output the result as JSON if requested
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Prepare to render the HTML
$m3u8Link = $result['m3u8_link'] ?? '';
$episodeName = $result['episode_name'] ?? '';
$poster = $result['poster'] ?? '';

// HTML output
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>⟨FREEMBED⟩ | <?php echo htmlspecialchars($_GET['tmdb_id'] ?? ''); ?> • <?php echo htmlspecialchars($episodeName); ?></title>
    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
        }
        #poster {
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div id='player' style='width:100%;height:100%;'></div>
    <script src='tvplayer_v1.js'></script>
    <script>
        // Use the fetched m3u8 link
        const m3u8Link = "<?php echo htmlspecialchars($m3u8Link); ?>";

        console.log('m3u8 Link:', m3u8Link); // Debugging output

        if (m3u8Link) {
            const player = new Playerjs({
                id: 'player',
                width: '100%',
                height: '100%',
                autoplay: false,
                title: '<?php echo htmlspecialchars($episodeName); ?>',
                poster: "<?php echo htmlspecialchars($poster); ?>", // Add poster URL if available
                file: m3u8Link,
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
        } else {
            console.error('m3u8 link is not available.');
        }
    </script>
</body>
</html>