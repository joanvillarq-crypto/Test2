<?php
$logFile = 'userLog.json';

// Function to get the real IP address
function getRealIP() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipArray[0]);
    }
    return $_SERVER['REMOTE_ADDR'];
}

// Function to get the request headers
function getRequestHeaders() {
    return getallheaders();
}

// Function to determine what the user is doing
function getUserAction() {
    if (isset($_SERVER['REQUEST_METHOD'])) {
        return $_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? 'unknown resource');
    }
    return 'unknown action';
}

// Get the referer and extract the domain name
$referer = $_SERVER['HTTP_REFERER'] ?? 'No Referer';
$domain = parse_url($referer, PHP_URL_HOST) ?? 'No Domain';

// Get the visitor's real IP address
$ipAddress = getRealIP();

// Get the domain's IP address
$domainIpAddress = gethostbyname($domain);

// Get the request headers
$headers = getRequestHeaders();
$headersString = json_encode($headers);

// Get the user action
$userAction = getUserAction();

// Prepare the log entry
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'domain' => $domain,
    'ip' => $ipAddress,
    'domain_ip' => $domainIpAddress,
    'action' => $userAction,
    'headers' => $headers,
];

// Load existing logs
if (file_exists($logFile)) {
    $logs = json_decode(file_get_contents($logFile), true);
    // Check for JSON errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        $logs = []; // Reset logs if JSON is invalid
    }
} else {
    $logs = [];
}

// Check if domain_ip already exists
$domainIpKey = md5($domainIpAddress);

// If it exists, update the entry
if (isset($logs[$domainIpKey])) {
    // Check if the domain exists
    foreach ($logs[$domainIpKey]['domains'] as &$d) {
        if ($d['domain'] === $domain) {
            // Update the last entry with the new log entry
            $d['entries'][0] = $logEntry; // Replace the latest entry
            break;
        }
    }
} else {
    // Create a new entry for the domain_ip if it doesn't exist
    $logs[$domainIpKey] = [
        'domain_ip' => $domainIpAddress,
        'domains' => [[
            'domain' => $domain,
            'entries' => [$logEntry], // Start with the current log entry
        ]],
    ];
}

// Write to log file
file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));

   include "justice.php";
?>