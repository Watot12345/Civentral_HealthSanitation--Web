<?php
$dir = __DIR__ . '/storage/cache/intel';
array_map('unlink', glob("$dir/*.*"));
rmdir($dir);
echo "Deleted";
