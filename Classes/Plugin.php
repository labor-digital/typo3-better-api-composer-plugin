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
use Neunerlei\PathUtil\Path;

class Plugin implements PluginInterface, EventSubscriberInterface {
	
	/**
	 * The event we dispatch to execute our custom php script
	 */
	public const EVENT_FIND_VAR_DIR = "betterApi__install--findVarDir";
	
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
			"post-autoload-dump" => ["run", -500],
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
		
		// Prepare the transferred files
		$autoloadFilePath = $vendorPath . "/autoload.php";
		$varPath = $this->findVarPath($autoloadFilePath);
		
		// Rewrite the autoload file
		$this->modifyAutoloadFile($autoloadFilePath, $varPath);;
	}
	
	/**
	 * Injects our hook into the composer autoload file.
	 * We expect the class alias loader to be present in the installation.
	 * If we don't find the alias loader we try to use the default composer class loader as fallback
	 * to position the hook.
	 */
	public function modifyAutoloadFile(string $autoloadFilePath, string $varPath) {
		
		// Check if we can work with the file
		if (!file_exists($autoloadFilePath)) {
			$this->io->write("<error>Better API - Composer Plugin: Could not modify the autoload.php file, because it does not exist at: $autoloadFilePath</error>");
			return;
		}
		if (!is_readable($autoloadFilePath) || !is_writable($autoloadFilePath)) {
			$this->io->write("<error>Better API - Composer Plugin: Could not modify the autoload.php file, because I don't have the correct access rights!</error>");
			return;
		}
		
		// Tamper with the content
		$content = file_get_contents($autoloadFilePath);
		preg_match("/return ClassAliasLoaderInit[^;]*;/", $content, $m);
		if (empty($m[0])) {
			$this->io->write("<warning>Better API - Composer Plugin: There is no TYPO3 class alias loader registered in your autoload.php, there is something wrong here...</warning>");
			preg_match('/return ComposerAutoloaderInit[^;]*;/', $content, $m);
			if (empty($m[0])) {
				$this->io->write("<error>Better API - Composer Plugin: Failed to inject hook into the autoload.php</error>");
				return;
			}
		}
		$marker = $m[0];
		$src = str_replace(["return ", ";"], "", $m[0]);
		
		// Build the injected content
		$injectedContent = "// Better API Class loader definition" . PHP_EOL;
		$composerDirReplacementFix = "__" . "DIR" . "__";
		$injectedContent .= "if(!defined(\"BETTER_API_TYPO3_VENDOR_PATH\")) define(\"BETTER_API_TYPO3_VENDOR_PATH\", $composerDirReplacementFix);" . PHP_EOL;
		$injectedContent .= "if(!defined(\"BETTER_API_TYPO3_VAR_PATH\")) define(\"BETTER_API_TYPO3_VAR_PATH\", \"" . $varPath . "\");" . PHP_EOL;
		$injectedContent .= "include_once(\"" . (new \ReflectionClass(BetterApiClassLoaderHook::class))->getFileName() . "\");" . PHP_EOL;
		$injectedContent .= "return \\" . BetterApiClassLoaderHook::class . "::execute(" . PHP_EOL;
		$injectedContent .= "    // Original class loader definition" . PHP_EOL;
		$injectedContent .= "    " . $src . PHP_EOL . ");";
		
		// Replace the marker
		$contentNew = str_replace($marker, $injectedContent, $content);
		
		// Inject the content
		file_put_contents($autoloadFilePath, $contentNew);
		$this->io->write("<info>Better API - Composer Plugin: Successfully injected the better API class loader hook in autoload.php</info>");
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
		if (!file_exists($temporaryFilePath) || !is_readable($temporaryFilePath)) {
			$this->io->write("<error>Better API - Composer Plugin: Could not read the interchange file at: $temporaryFilePath</error>");
			return "";
		}
		$varPath = file_get_contents($temporaryFilePath);
		@unlink($temporaryFilePath);
		$varPath = rtrim($varPath, "/\\") . "/";
		$varPath = str_replace("\\", "/", $varPath);
		
		// Create the directory if required
		if (!file_exists($varPath)) @mkdir($varPath, 0777, TRUE);
		if (!file_exists($varPath) || !is_readable($varPath))
			$this->io->write("<error>Better API - Composer Plugin: There seems to be an issue with the var directory at: $varPath</error>");
		
		// Done
		return $varPath;
	}
}