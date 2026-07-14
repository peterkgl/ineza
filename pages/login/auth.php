<?php
require_once __DIR__ . '/../../config/session.php';

if (php_sapi_name() !== 'cli') {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

    if (!isset($_SESSION['user_id'])) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $pos = strpos($script, '/pages/');
        $base_dir = ($pos !== false) ? rtrim(substr($script, 0, $pos), '/') : '';
        
        header("Location: " . $base_dir . "/index");
        exit();
    }
} else {
    $_SESSION['user_id'] = 2;
}
?>
