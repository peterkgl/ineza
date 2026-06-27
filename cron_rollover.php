<?php
// CLI script to manually or periodically run the daily rollover logic
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

require_once __DIR__ . '/config/db.php';

echo "Executing daily stock rollover...\n";
ensureDailyRollover($conn);
echo "Rollover completed successfully.\n";
?>
