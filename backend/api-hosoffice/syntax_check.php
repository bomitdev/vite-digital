<?php
// Simple syntax check helper
function lint($file)
{
    echo "Checking $file ...\n";
    $output = shell_exec("C:\\xampp\\php\\php.exe -l " . escapeshellarg($file));
    echo $output . "\n";
}

lint('c:/xampp/htdocs/vue/vite-digital/backend/api-hosoffice/get_authorized_staff.php');
lint('c:/xampp/htdocs/vue/vite-digital/backend/api-hosoffice/get_finger_scan.php');
