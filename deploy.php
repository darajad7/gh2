<?php
/**
 * GitHub Webhook Receiver for cPanel Auto-Deploy
 * 
 * Upload this file to: public_html/deploy.php
 * Then set GitHub webhook URL to: https://fistudio.pro/deploy.php
 */

// === CONFIGURATION ===
$secret = '6e8f7d012010b57b52fb2822f74c9c60f0a9a65b32dba7b22ec14a31797f7ef5';
$repo_path = '/home/fist9591/repositories/gh2';
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

// === PULL & DEPLOY ===
$commands = [
    "cd $repo_path && git pull origin $branch 2>&1",
    "cd $repo_path && /usr/local/cpanel/bin/jailshell -c '/usr/local/cpanel/bin/uapi VersionControl update repository_root=$repo_path' 2>&1"
];

$output = date('Y-m-d H:i:s') . " - Deploy triggered\n";

// Try git pull first
$result = shell_exec("cd $repo_path && git pull origin $branch 2>&1");
$output .= $result . "\n";

// Log result
file_put_contents($log_file, $output, FILE_APPEND | LOCK_EX);

http_response_code(200);
echo 'Deployed successfully';
