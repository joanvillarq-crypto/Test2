
<?php

function isValidM3U8($url) {
    // Initialize a cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Execute the cURL request
    curl_exec($ch);

    // Get the HTTP status code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close the cURL session
    curl_close($ch);

    // Return true if status code is 200
    return $httpCode === 200;
}

function fetchM3U8Link($id) {
    $sources = [
        "https://vidify.top/hyper.php?id=" . urlencode($id),
        "https://autoembed.cc/embed/mlplayer.php?id=" . urlencode($id) . "&sub=hindi",
        "https://autoembed.cc/embed/player.php?id=" . urlencode($id) . "&sub=hindi",
        "https://autoembed.cc/embed/player.php?id=" . urlencode($id) . "&lang=english",
        "https://autoembed.cc/embed/mlplayer.php?id=" . urlencode($id) . "&lang=english"
    ];

    foreach ($sources as $url) {
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
        if ($response !== false) {
            // Use regex to find the m3u8 link in the response
            preg_match('/(https?:\/\/[^\s]+\.m3u8)/', $response, $matches);

            // Check if an m3u8 link was found
            if (isset($matches[1]) && isValidM3U8($matches[1])) {
                // Close the cURL session
                curl_close($ch);
                return $matches[1]; // Return the valid m3u8 link
            }
        }

        // Close the cURL session
        curl_close($ch);
    }

    return ['error' => 'Valid m3u8 link not found in any source.'];
}

// Get the query parameter
$id = $_GET['id'] ?? null;

// Validate the input
if ($id) {
    $m3u8Link = fetchM3U8Link($id);
    
    // Prepare the result
    if (is_array($m3u8Link) && isset($m3u8Link['error'])) {
        $result = $m3u8Link; // Return the error in the response
    } else {
        $result = ['m3u8_link' => $m3u8Link]; // Return the found m3u8 link
    }
} else {
    $result = ['error' => 'Missing parameter. Please provide an id.'];
}

// Output the result as JSON
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Prepare to render the HTML
$m3u8Link = $result['m3u8_link'] ?? '';

?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>⟨FREEMBED⟩ | <?php echo htmlspecialchars($id); ?></title>
    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
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
                title: '<?php echo htmlspecialchars($id); ?>', // Movie title
                poster: '', // Add poster URL if available
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