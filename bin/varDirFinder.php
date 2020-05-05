<?php
/**
 * Copyright 2019 LABOR.digital
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Last modified: 2019.09.24 at 11:47
 */

// Exit early if php requirement is not satisfied.
use Neunerlei\FileSystem\Fs;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;

if (PHP_VERSION_ID < 70200) die('This version of TYPO3 CMS requires PHP 7.2 or above');

// Ignore non cli calls
if (php_sapi_name() !== 'cli') die("This is only for CLI execution!");

// Get arguments
$autoLoadFile = $argv[1];
if (empty($autoLoadFile) || !file_exists($autoLoadFile)) {
	echo "Failed to find \"var\" directory: Missing autoload file declaration!" . PHP_EOL;
	exit(1);
}
$tempFile = $argv[2];
if (empty($tempFile)) {
	echo "Failed to find \"var\" directory: Missing temporary file declaration!" . PHP_EOL;
	exit(1);
}

// Mark this request
define("BETTER_API_COMPOSER_PLUGIN_VAR_DIR_FINDER", TRUE);

// Include the autoloader
include $autoLoadFile;

// Simulate that we are accessing the frontend entry point
$_SERVER['argv'][0] = "/index.php";

// Build the environment and store the var path
try {
	SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
	Fs::writeFile($tempFile, Environment::getVarPath());
} catch (Exception $e) {
	echo $e->getMessage() . PHP_EOL;
	exit(1);
}
