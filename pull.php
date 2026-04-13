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

// Check which exec functions are available
$disabled = array_map('trim', explode(',', ini_get('disable_functions')));
$available = null;
foreach (['shell_exec', 'exec', 'system', 'passthru', 'proc_open'] as $fn) {
    if (function_exists($fn) && !in_array($fn, $disabled)) {
        $available = $fn;
        break;
    }
}

if ($available === 'shell_exec') {
    $pull = shell_exec("cd $repo_path && git pull origin main 2>&1");
    $output .= "Pull: $pull\n";
    $deploy = shell_exec("uapi VersionControl update repository_root=$repo_path 2>&1");
    $output .= "Deploy: $deploy\n";
} elseif ($available === 'exec') {
    exec("cd $repo_path && git pull origin main 2>&1", $pullOut, $pullCode);
    $output .= "Pull ($pullCode): " . implode("\n", $pullOut) . "\n";
    exec("uapi VersionControl update repository_root=$repo_path 2>&1", $deployOut, $deployCode);
    $output .= "Deploy ($deployCode): " . implode("\n", $deployOut) . "\n";
} else {
    $output .= "ERROR: No exec functions available on this hosting.\n";
    $output .= "Disabled functions: " . ini_get('disable_functions') . "\n";
    $output .= "\nAuto-deploy is NOT possible on this shared hosting.\n";
    $output .= "Please use cPanel > Git Version Control > Update from Remote > Deploy HEAD Commit.\n";
}

file_put_contents($log_file, $output, FILE_APPEND | LOCK_EX);

header('Content-Type: text/plain');
echo $output;
