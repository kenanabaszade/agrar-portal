<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Process;

echo "Testing Python command...\n";

$result = Process::run('python certificate_generator.py --file test_data.json');

echo "Exit code: " . $result->exitCode() . "\n";
echo "Output: " . $result->output() . "\n";
echo "Error: " . $result->errorOutput() . "\n";

