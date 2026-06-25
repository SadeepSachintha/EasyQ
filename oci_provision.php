<?php

// Check if run in CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line (CLI).\n");
}

// ---------------------------------------------------------
// CONFIGURATION
// ---------------------------------------------------------

// --- OCI API Connection Config ---
$tenancy_id = "ocid1.tenancy.oc1..aaaaaaaa3nmsa2sty5qs6n3smz6ucmxnpeb2vgprhpxi6dw3bvt4mno67qza";
$user_id = "ocid1.user.oc1..aaaaaaaahod5v3d3jdd2qm45qdmfgwvr2tmue3ghs3wnt7lgngknbswk2rmq";
$region = "ap-mumbai-1"; // Mumbai region

// Key paths (use forward slashes for safety)
$private_key_path = "D:/Oracle/sadeepsachintha98@gmail.com-2026-06-25T17_22_01.335Z.pem";
$public_key_path  = "D:/Oracle/sadeepsachintha98@gmail.com-2026-06-25T17_21_53.803Z_public.pem";

// --- VM Infrastructure Details ---
$compartment_id = "ocid1.tenancy.oc1..aaaaaaaa3nmsa2sty5qs6n3smz6ucmxnpeb2vgprhpxi6dw3bvt4mno67qza"; // Root compartment
$availability_domain = "CHbU:AP-MUMBAI-1-AD-1";
$subnet_id = "ocid1.subnet.oc1.ap-mumbai-1.aaaaaaaart32qetimj36wlhgh5ajhzgozzjrqeqz6z7kzd6u7yqqxuqqy4zq";
$ssh_key_path = "D:/xampp/htdocs/BIT-Announcement-Tracker/SSH keys/ssh-key-2026-06-13.key.pub";

// Image OCID for ARM shape (Ubuntu 24.04 ARM Minimal)
// Note: You can change this to 22.04 or Oracle Linux if needed
$image_id = "ocid1.image.oc1.ap-mumbai-1.aaaaaaaa3nqci7foay635jm45ozbt4bfb44bavheamw7rdz4izwuobbnql6a";

// VM Instance Spec (Maximum Always Free: 4 OCPUs, 24 GB RAM)
$display_name = "Always-Free-Ampere";
$ocpus = 2;
$memory_in_gb = 12;
$boot_volume_size = 100; // Size of boot volume in GB (free tier max is 200GB total)

// Execution settings
$retry_interval = 30; // Seconds to wait between attempts

// ---------------------------------------------------------
// INITIAL CHECK & READ FILES
// ---------------------------------------------------------

if (!file_exists($private_key_path)) {
    die("Error: Private key file not found at: $private_key_path\n");
}
if (!file_exists($public_key_path)) {
    die("Error: Public key file not found at: $public_key_path\n");
}
if (!file_exists($ssh_key_path)) {
    die("Error: SSH Public Key file not found at: $ssh_key_path\n");
}

$private_key_content = file_get_contents($private_key_path);
$ssh_key_content = trim(file_get_contents($ssh_key_path));

// Calculate Key Fingerprint
try {
    $fingerprint = getOciFingerprint($public_key_path);
    echo "Calculated Public Key Fingerprint: $fingerprint\n";
} catch (Exception $e) {
    die("Error parsing public key: " . $e->getMessage() . "\n");
}

// ---------------------------------------------------------
// HELPER FUNCTIONS
// ---------------------------------------------------------

/**
 * Calculates MD5 fingerprint of a public key.
 */
function getOciFingerprint($pub_key_path) {
    $pemKey = file_get_contents($pub_key_path);
    $pkey = openssl_pkey_get_public($pemKey);
    if (!$pkey) {
        throw new Exception("Invalid public key file format");
    }
    $details = openssl_pkey_get_details($pkey);
    $derKey = $details['key'];
    $der = base64_decode(preg_replace(
        '/^-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\n|\r|\s/', 
        '', 
        $derKey
    ));
    $md5Hash = hash('md5', $der);
    return implode(':', str_split($md5Hash, 2));
}

/**
 * Signs and sends the LaunchInstance request
 */
function launchInstance($tenancy_id, $user_id, $fingerprint, $private_key_content, $region, $body) {
    $host = "iaas.{$region}.oraclecloud.com";
    $path = "/20160918/instances";
    $date_header = gmdate('D, d M Y H:i:s \G\M\T');
    $sha256_header = base64_encode(hash('sha256', $body, true));
    $content_length = strlen($body);
    $content_type = "application/json";

    // Headers list for signing
    $headers_to_sign = [
        "(request-target)" => "post $path",
        "date" => $date_header,
        "host" => $host,
        "x-content-sha256" => $sha256_header,
        "content-type" => $content_type,
        "content-length" => $content_length
    ];

    // Build the string to sign
    $signing_parts = [];
    foreach ($headers_to_sign as $key => $val) {
        $signing_parts[] = "$key: $val";
    }
    $signing_string = implode("\n", $signing_parts);

    // Sign the string with RSA-SHA256
    $private_key = openssl_pkey_get_private($private_key_content);
    if (!$private_key) {
        throw new Exception("Unable to load private key for signing.");
    }
    openssl_sign($signing_string, $signature, $private_key, OPENSSL_ALGO_SHA256);
    $signature_b64 = base64_encode($signature);

    // Build Authorization header
    $headers_list = implode(" ", array_keys($headers_to_sign));
    $keyId = "{$tenancy_id}/{$user_id}/{$fingerprint}";
    $authorization = "Signature version=\"1\",keyId=\"$keyId\",algorithm=\"rsa-sha256\",headers=\"$headers_list\",signature=\"$signature_b64\"";

    // Prepare HTTP request headers
    $http_headers = [
        "Date: $date_header",
        "Host: $host",
        "x-content-sha256: $sha256_header",
        "Content-Type: $content_type",
        "Content-Length: $content_length",
        "Authorization: $authorization"
    ];

    // Execute via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://{$host}{$path}");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    // Disable SSL verification issues if curl cert bundle is outdated
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    return [
        'code' => $http_code,
        'headers' => $headers,
        'body' => $body
    ];
}

// ---------------------------------------------------------
// MAIN EXECUTION LOOP
// ---------------------------------------------------------

// Define the payload body
$payload = [
    "availabilityDomain" => $availability_domain,
    "compartmentId" => $compartment_id,
    "displayName" => $display_name,
    "shape" => "VM.Standard.A1.Flex",
    "shapeConfig" => [
        "ocpus" => $ocpus,
        "memoryInGBs" => $memory_in_gb
    ],
    "sourceDetails" => [
        "sourceType" => "image",
        "imageId" => $image_id,
        "bootVolumeSizeInGBs" => $boot_volume_size
    ],
    "createVnicDetails" => [
        "subnetId" => $subnet_id,
        "assignPublicIp" => true
    ],
    "metadata" => [
        "ssh_authorized_keys" => $ssh_key_content
    ]
];

$json_body = json_encode($payload);
$attempt = 1;

echo "\n=============================================\n";
echo "Starting OCI VM Automator for Ampere VM\n";
echo "Press Ctrl+C to terminate script\n";
echo "=============================================\n\n";

while (true) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] Attempt #$attempt: Sending request... ";

    try {
        $result = launchInstance($tenancy_id, $user_id, $fingerprint, $private_key_content, $region, $json_body);
        $code = $result['code'];
        $body = json_decode($result['body'], true);

        if ($code == 201) {
            echo "\n\n🎉 SUCCESS! VM Created successfully.\n";
            echo "Response Details:\n";
            print_r($body);
            // Play a console beep (Windows support)
            echo "\x07\x07\x07"; 
            break;
        } elseif ($code == 500 && isset($body['code']) && $body['code'] == 'InternalError' && strpos($body['message'], 'capacity') !== false) {
            echo "FAILED (HTTP 500: Out of host capacity)\n";
        } elseif ($code == 400 || $code == 401 || $code == 404) {
            echo "TERMINATED (HTTP $code)\n";
            echo "Error Details from OCI:\n";
            print_r($body ? $body : $result['body']);
            echo "\nScript stopped automatically to avoid account rate-limiting/suspension due to bad config.\n";
            exit(1);
        } else {
            echo "FAILED (HTTP $code)\n";
            if (!empty($body)) {
                echo "Response Message: " . ($body['message'] ?? json_encode($body)) . "\n";
            }
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }

    $attempt++;
    sleep($retry_interval);
}
