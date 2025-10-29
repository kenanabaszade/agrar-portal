<?php

echo "Testing Python command...\n";

$command = 'python certificate_generator.py --file test_data.json';
$output = shell_exec($command . ' 2>&1');

echo "Output: " . $output . "\n";

