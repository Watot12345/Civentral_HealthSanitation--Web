<?php
// database/seeders/run.php
/**
 * CLI Runner for Civentral LGU Professional Data Seeder
 * 
 * Usage:
 *   php database/seeders/run.php
 */

if (PHP_SAPI !== 'cli') {
    die("This script can only be executed from the command line interface.\n");
}

require_once __DIR__ . '/ProfessionalDataSeeder.php';

use Database\Seeders\ProfessionalDataSeeder;

$seeder = new ProfessionalDataSeeder();
$seeder->run();
