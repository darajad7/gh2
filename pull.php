<?php
/**
 * Manual Deploy Trigger
 * Akses: https://fistudio.pro/gh2/pull.php?key=SECRET
 */

$secret_key = '6e8f7d012010b57b52fb2822f74c9c60f0a9a65b32dba7b22ec14a31797f7ef5';
$repo_path = '/home/fist9591/repositories/gh3';
$log_file = '/home/fist9591/public_html/gh2/deploy.log';

// Verify key
if (($_GET['key'] ?? '') !== $secret_key) {
    http_response_code(403);
    exit('Forbidden');
}

$output = date('Y-m-d H:i:s') . " - Manual deploy\n";

// Pull from remote
$pull = shell_exec("cd $repo_path && git pull origin main 2>&1");
$output .= "Pull: " . ($pull ?: "shell_exec blocked") . "\n";

// Trigger cPanel deploy
$deploy = shell_exec("uapi VersionControl update repository_root=$repo_path 2>&1");
$output .= "Deploy: " . ($deploy ?: "shell_exec blocked") . "\n";

file_put_contents($log_file, $output, FILE_APPEND | LOCK_EX);

echo "<pre>$output</pre>";
