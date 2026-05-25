<?php
// backend/git_sync.php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/auth_utils.php'; // Ensure user is authenticated, though maybe optional for local use.
session_start();

// Optionally protect this endpoint to authenticated users only:
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
//     exit;
// }

// Receive JSON payload
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'No action specified.']);
    exit;
}

$action = $input['action'];
$projectRoot = realpath(__DIR__ . '/..'); // Go up one level to the git root

// Define Git executable path (for Windows XAMPP environments where git is not in PATH)
// Use forward slashes for PHP path resolution compatibility on Windows
$gitPath = '"C:/Program Files/Git/cmd/git.exe"';
// If using Linux/Mac, you can change this back to 'git' or '/usr/bin/git'

// Helper function to execute command and return output
function runGitCommand(string $command, string $cwd) {
    global $gitPath;
    // Replace the default "git " with our resolved path
    $command = preg_replace('/^git /', $gitPath . ' ', $command);
    
    // 2>&1 redirects stderr to stdout so we can capture error messages
    $fullCommand = "cd " . escapeshellarg($cwd) . " && " . $command . " 2>&1";
    $output = shell_exec($fullCommand);
    return $output;
}

$response = [
    'status' => 'success',
    'action' => $action,
    'logs' => ''
];

// Fix for "dubious ownership" error when running as NT AUTHORITY/SYSTEM
// Replace backslashes with forward slashes for Git config
$safePath = str_replace('\\', '/', $projectRoot);
runGitCommand("git config --global --add safe.directory " . escapeshellarg($safePath), $projectRoot);

// Set Git identity for the SYSTEM user so commits don't fail
runGitCommand("git config user.name \"System Web UI\"", $projectRoot);
runGitCommand("git config user.email \"system@vite-digital.local\"", $projectRoot);

if ($action === 'push') {
    $folders = isset($input['folders']) && is_array($input['folders']) ? $input['folders'] : [];
    
    if (empty($folders)) {
        echo json_encode(['status' => 'error', 'message' => 'No folders selected for push.']);
        exit;
    }

    $logOutput = "--- Starting Git Push ---\n";
    
    // 1. Add specific folders
    $addCommand = "git add";
    foreach ($folders as $folder) {
        // Simple security check to prevent command injection
        if (preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $folder)) {
            $addCommand .= " " . escapeshellarg($folder);
        }
    }
    
    $logOutput .= "> " . $addCommand . "\n";
    $logOutput .= runGitCommand($addCommand, $projectRoot) . "\n";
    
    // 2. Commit
    $commitMsg = "Update from Web UI: " . implode(", ", $folders) . " at " . date('Y-m-d H:i:s');
    $commitCommand = "git commit -m " . escapeshellarg($commitMsg);
    
    $logOutput .= "> " . $commitCommand . "\n";
    $logOutput .= runGitCommand($commitCommand, $projectRoot) . "\n";
    
    // 3. Push
    $pushCommand = "git push origin main"; // Assuming branch is main
    $logOutput .= "> " . $pushCommand . "\n";
    $pushResult = runGitCommand($pushCommand, $projectRoot);
    $logOutput .= $pushResult . "\n";
    
    $response['logs'] = $logOutput;

} else if ($action === 'force_pull') {
    $logOutput = "--- Starting Git Force Pull (Overwrite Local) ---\n";
    
    $fetchCommand = "git fetch --all";
    $logOutput .= "> " . $fetchCommand . "\n";
    $logOutput .= runGitCommand($fetchCommand, $projectRoot) . "\n";
    
    $resetCommand = "git reset --hard origin/main";
    $logOutput .= "> " . $resetCommand . "\n";
    $logOutput .= runGitCommand($resetCommand, $projectRoot) . "\n";
    
    $cleanCommand = "git clean -fd";
    $logOutput .= "> " . $cleanCommand . "\n";
    $logOutput .= runGitCommand($cleanCommand, $projectRoot) . "\n";
    
    $response['logs'] = $logOutput;
    
} else if ($action === 'pull') {
    $logOutput = "--- Starting Git Pull ---\n";
    
    $pullCommand = "git pull origin main --allow-unrelated-histories";
    $logOutput .= "> " . $pullCommand . "\n";
    
    $pullResult = runGitCommand($pullCommand, $projectRoot);
    $logOutput .= $pullResult . "\n";
    
    $response['logs'] = $logOutput;
    
} else if ($action === 'status') {
    $logOutput = "--- Starting Git Status ---\n";
    
    $statusCommand = "git status";
    $logOutput .= "> " . $statusCommand . "\n";
    
    $statusResult = runGitCommand($statusCommand, $projectRoot);
    $logOutput .= $statusResult . "\n";
    
    $response['logs'] = $logOutput;

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    exit;
}

echo json_encode($response);
