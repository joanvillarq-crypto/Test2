
<?php

$blocklistFile = 'blocklist.json';
$htaccessFile = '.htaccess';
$exemplistFile = 'exemplist.json'; // New file for IPs to remove

// Read the blocklisted IPs
$blocklistData = file_exists($blocklistFile) ? json_decode(file_get_contents($blocklistFile), true) : [];
if (!is_array($blocklistData)) {
    die("Invalid blocklist data");
}

// Read the exemplist IPs to remove
$exemplistData = file_exists($exemplistFile) ? json_decode(file_get_contents($exemplistFile), true) : [];
if (!is_array($exemplistData)) {
    die("Invalid exemplist data");
}

// Read existing .htaccess content
$existingRules = file_exists($htaccessFile) ? file_get_contents($htaccessFile) : '';

// Prepare an array to accumulate new rules
$newIPs = [];

// Extract existing IP rules from .htaccess
if (preg_match_all('/Require not ip ([0-9\.]+)/', $existingRules, $matches)) {
    $existingIPs = array_map('trim', $matches[1]);
} else {
    $existingIPs = [];
}

// Check and accumulate new IPs to add
foreach ($blocklistData as $ip) {
    if (!in_array($ip, $existingIPs) && filter_var($ip, FILTER_VALIDATE_IP)) {
        $newIPs[] = $ip;
    }
}

// If there are new IPs to add, construct the new block
if (!empty($newIPs)) {
    $newRules = "\n<RequireAll>\n";
    $newRules .= "    Require all granted\n";

    foreach ($newIPs as $ip) {
        $newRules .= "    Require not ip $ip\n";
    }

    $newRules .= "</RequireAll>\n";

    // Only append if there are new rules to add
    if (trim($existingRules) !== trim($newRules)) {
        file_put_contents($htaccessFile, $newRules, FILE_APPEND);
    }
}

// Remove IPs from the exemplist
if (!empty($exemplistData)) {
    foreach ($exemplistData as $ipToRemove) {
        if (filter_var($ipToRemove, FILTER_VALIDATE_IP)) {
            $existingIPs = array_diff($existingIPs, [$ipToRemove]);
        }
    }

    // Reconstruct the <RequireAll> block without the removed IPs
    $updatedRules = "\n<RequireAll>\n";
    $updatedRules .= "    Require all granted\n";

    foreach ($existingIPs as $existingIP) {
        $updatedRules .= "    Require not ip $existingIP\n";
    }

    $updatedRules .= "</RequireAll>\n";

    // Update the .htaccess file only if the content has changed
    if (trim($existingRules) !== trim($updatedRules)) {
        file_put_contents($htaccessFile, $updatedRules);
    }
}

?>