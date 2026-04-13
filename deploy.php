<?php
/**
 * GitHub Webhook Receiver for cPanel Auto-Deploy
 * 
 * Upload this file to: public_html/deploy.php
 * Then set GitHub webhook URL to: https://fistudio.pro/deploy.php
 */

// === CONFIGURATION ===
$secret = '6e8f7d012010b57b52fb2822f74c9c60f0a9a65b32dba7b22ec14a31797f7ef5';
$repo_path = '/home/fist9591/repositories/gh3';
$branch = 'main';
$log_file = '/home/fist9591/public_html/gh2/deploy.log';

// === VERIFY REQUEST ===
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!$signature) {
    http_response_code(403);
    exit('No signature');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}

// === CHECK BRANCH ===
$data = json_decode($payload, true);
$ref = $data['ref'] ?? '';
if ($ref !== 'refs/heads/' . $branch) {
    exit('Not target branch');
}

// === PULL & DEPLOY via cPanel API ===
$output = date('Y-m-d H:i:s') . " - Deploy triggered\n";

// Method 1: cPanel UAPI via command line
$result = shell_exec("uapi VersionControl update repository_root=$repo_path 2>&1");
$output .= "UAPI: " . ($result ?: "shell_exec blocked") . "\n";

// Method 2: If shell_exec is blocked, use cPanel API via HTTP
if (!$result) {
    $cpanel_user = 'fist9591';
    $api_url = "https://127.0.0.1:2083/execute/VersionControl/update?repository_root=" . urlencode($repo_path);
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: cpanel ' . $cpanel_user . ':' . getenv('CPANEL_API_TOKEN')
    ]);
    $api_result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $output .= "API ($http_code): " . ($api_result ?: "failed") . "\n";
}

// Log result
file_put_contents($log_file, $output, FILE_APPEND | LOCK_EX);

http_response_code(200);
echo 'Deploy triggered';
