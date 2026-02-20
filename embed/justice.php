
<?php

// Read data from userLog.json
$userLogFile = 'userLog.json';

// Check if the userLog.json file exists and is readable
if (file_exists($userLogFile) && is_readable($userLogFile)) {
    $userLogData = json_decode(file_get_contents($userLogFile), true);
    
    // Check if JSON decoding was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error: Invalid JSON format in userLog.json");
    }
} else {
    die("Error: Unable to read userLog.json");
}

$blocklistFile = 'blocklist.json';
$exemptListFile = 'exemptlist.json';

// Initialize blocklist data or create the file if it doesn't exist
if (!file_exists($blocklistFile)) {
    file_put_contents($blocklistFile, json_encode([], JSON_PRETTY_PRINT)); // Create an empty blocklist
}

$blocklistData = json_decode(file_get_contents($blocklistFile), true);

// Initialize exempt list data or create the file if it doesn't exist
if (!file_exists($exemptListFile)) {
    file_put_contents($exemptListFile, json_encode([], JSON_PRETTY_PRINT)); // Create an empty exempt list
}

$exemptListData = json_decode(file_get_contents($exemptListFile), true);

// Function to add an item to the blocklist
function addToBlocklist($item) {
    global $blocklistData, $blocklistFile;
    if (!in_array($item, $blocklistData)) {
        $blocklistData[] = $item;
        file_put_contents($blocklistFile, json_encode($blocklistData, JSON_PRETTY_PRINT));
    }
}

// Function to add multiple items to the exempt list
function addToExemptList($items) {
    global $exemptListData, $exemptListFile;
    foreach ($items as $item) {
        if (!in_array($item, $exemptListData)) {
            $exemptListData[] = $item;
        }
    }
    file_put_contents($exemptListFile, json_encode($exemptListData, JSON_PRETTY_PRINT));
}

// Function to remove IPs from blocklist that are in the exempt list
function removeExemptedFromBlocklist() {
    global $blocklistData, $exemptListData, $blocklistFile;
    $blocklistData = array_diff($blocklistData, $exemptListData); // Remove exempted IPs from blocklist
    file_put_contents($blocklistFile, json_encode(array_values($blocklistData), JSON_PRETTY_PRINT)); // Reindex array and save
}

// Call the function to remove exempted IPs from the blocklist
removeExemptedFromBlocklist();

// Iterate over each entry in the userLogData
foreach ($userLogData as $entry) {
    // Check if 'domains' and 'entries' exist in the current entry
    if (isset($entry['domains'])) {
        foreach ($entry['domains'] as $domainInfo) {
            if (isset($domainInfo['entries'])) {
                foreach ($domainInfo['entries'] as $logEntry) {
                    // Ensure the log entry contains the necessary fields
                    if (!isset($logEntry['ip'], $logEntry['domain_ip'], $logEntry['domain'], $logEntry['headers'])) {
                        continue; // Skip this entry if any critical field is missing
                    }

                    $headers = $logEntry['headers'];
                    $domainIp = $logEntry['domain_ip'];
                    $domain = $logEntry['domain'];
                    $ip = $logEntry['ip']; // Get the IP from the entry

                    // Check if both domain_ip and domain are "No Domain"
                    if ($domainIp === "No Domain" && $domain === "No Domain") {
                        addToBlocklist($ip); // Add only the IP to the blocklist
                    }

                    // Check if sec-fetch-dest is missing
                    if (!isset($headers['sec-fetch-dest'])) {
                        addToBlocklist($domainIp); // Add domain_ip to the blocklist
                        addToBlocklist($ip); // Add IP to the blocklist
                    } 
                    
                    // Check if sec-fetch-dest is present but not an 'iframe'
                    elseif (isset($headers['sec-fetch-dest']) && $headers['sec-fetch-dest'] !== 'iframe') {
                        addToBlocklist($domainIp); // Add domain_ip to the blocklist
                        addToBlocklist($ip); // Add IP to the blocklist
                    }
                }
            }
        }
    }
}

// Function to check if a request should be blocked
function isBlocked($ip) {
    global $blocklistData, $exemptListData;
    return in_array($ip, $blocklistData) && !in_array($ip, $exemptListData);
}

// Example usage: Check if a request should be blocked
$requestIp = $_SERVER['REMOTE_ADDR'];

if (isBlocked($requestIp)) {
    // Block the request
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// Example: Add multiple IPs to the exempt list
$exemptedIps = [
    '198.251.89.168',
    '149.154.161.198',
    '10.0.0.5'
];
addToExemptList($exemptedIps); // Add your exempted IPs here

include "blocker.php";
?>