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
 * Last modified: 2019.09.24 at 14:17
 */

namespace LaborDigital\Typo3BetterApiComposerPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Neunerlei\FileSystem\Fs;
use Neunerlei\PathUtil\Path;

class Plugin implements PluginInterface, EventSubscriberInterface {
	
	/**
	 * The event we dispatch to execute our custom php script
	 */
	public const EVENT_FIND_VAR_DIR = "betterApi__install--findVarDir";
	
	/**
	 * The path to the include template file, relative to __DIR__
	 */
	protected const INCLUDE_TEMPLATE_FILE = "/../Resource/autoloadInclude.tpl.php";
	
	/**
	 * The path to the rendered include file relative to $vendorPath
	 */
	protected const INCLUDE_FILE = "/labor-digital/betterApiAutoloadInclude.php";
	
	/**
	 * @var Composer
	 */
	protected $composer;
	
	/**
	 * @var IOInterface
	 */
	protected $io;
	
	/**
	 * @inheritDoc
	 */
	public static function getSubscribedEvents() {
		return [
			"pre-autoload-dump" => ["run", -500],
		];
	}
	
	/**
	 * @inheritDoc
	 */
	public function activate(Composer $composer, IOInterface $io) {
		$this->composer = $composer;
		$this->io = $io;
	}
	
	/**
	 * The main entry point for our plugin
	 */
	public function run() {
		// Find the autoload file name
		$config = $this->composer->getConfig();
		$vendorPath = Path::normalize(realpath($config->get("vendor-dir")));
		
		// Find the var directory path
		$autoloadFilePath = $vendorPath . "/autoload.php";
		$varPath = $this->findVarPath($autoloadFilePath);
		
		// Build and register autoload file
		$this->buildAutoloadFile($vendorPath, $varPath);
		$this->registerAutoloadFile($vendorPath);
	}
	
	/**
	 * Loads the template of the autoload file, injects the required placeholders
	 * and dumps it on the final location
	 *
	 * @param string $vendorPath
	 * @param string $varPath
	 */
	protected function buildAutoloadFile(string $vendorPath, string $varPath): void {
		
		// Make var path relative to the vendor path
		$varPathRelative = Path::makeRelative($varPath, $vendorPath);
		
		// Load and build the template
		$tpl = Fs::readFile(__DIR__ . static::INCLUDE_TEMPLATE_FILE);
		$tpl = str_replace("{{varPath}}", $varPathRelative, $tpl);
		
		// Write the template into the output file
		$filePath = $vendorPath . static::INCLUDE_FILE;
		Fs::writeFile($filePath, $tpl);
		$this->io->write("<info>Better API - Composer Plugin: Built dynamic autoloading file at: $filePath</info>", TRUE, IOInterface::VERBOSE);
		
	}
	
	/**
	 * Registers the autoload file as last possible include in the composer autoloader
	 *
	 * @param string $vendorPath
	 */
	protected function registerAutoloadFile(string $vendorPath): void {
		// Register the file in the root package
		$rootPackage = $this->composer->getPackage();
		$autoloadDefinition = $rootPackage->getAutoload();
		$autoloadDefinition['files'][] = $vendorPath . static::INCLUDE_FILE;
		$rootPackage->setAutoload($autoloadDefinition);
		$this->io->write("<info>Better API - Composer Plugin: Injected dynamic autoload file into root package</info>", TRUE, IOInterface::VERBOSE);
	}
	
	/**
	 * Finds the var directory by calling the varDirFinder.php we ship in the bin directory.
	 * It will call the TYPO3 SystemEnvironmentBuilder and send the directory back to us.
	 *
	 * @param string $autoloadFilePath
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function findVarPath(string $autoloadFilePath): string {
		// Dispatch the event to call our custom script
		$temporaryFilePath = sys_get_temp_dir() . "/typo3-var-dir.txt";
		$dispatcher = $this->composer->getEventDispatcher();
		$dispatcher->addListener(static::EVENT_FIND_VAR_DIR, "@php " . __DIR__ . "/../bin/varDirFinder.php \"$autoloadFilePath\" \"$temporaryFilePath\"");
		$dispatcher->dispatch(static::EVENT_FIND_VAR_DIR);
		
		// Load the var directory
		if (!fs::isReadable($temporaryFilePath)) {
			$this->io->write("<error>Better API - Composer Plugin: Could not read the interchange file at: $temporaryFilePath</error>");
			return "";
		}
		$varPath = Fs::readFile($temporaryFilePath);
		Fs::remove($temporaryFilePath);
		$varPath = rtrim($varPath, "/\\") . "/";
		$varPath = str_replace("\\", "/", $varPath);
		
		// Create the directory if required
		try {
			Fs::mkdir($varPath);
			if (!fs::isReadable($varPath)) throw new \Exception();
		} catch (\Exception $e) {
			$this->io->write("<error>Better API - Composer Plugin: There seems to be an issue with the var directory at: $varPath</error>");
		}
		
		// Done
		return $varPath;
	}
}