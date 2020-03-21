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
 * Last modified: 2019.09.24 at 12:51
 */

namespace LaborDigital\Typo3BetterApiComposerPlugin;


use LaborDigital\Typo3BetterApi\BetterApiInit;

class BetterApiClassLoaderHook {
	
	/**
	 * True as soon as this hook was executed at least once.
	 * This is a one time event
	 * @var bool
	 */
	protected static $executed = FALSE;
	
	/**
	 * Called in the autoload.php after it was injected by this composer plugin
	 *
	 * @param $composerClassLoader
	 *
	 * @return mixed
	 */
	public static function execute($composerClassLoader) {
		// Ignore if we are already executed
		if (static::$executed) return $composerClassLoader;
		static::$executed = TRUE;
		
		// Initialize the script if possible
		if (class_exists(BetterApiInit::class))
			BetterApiInit::init($composerClassLoader);
		
		// Done
		return $composerClassLoader;
	}
}