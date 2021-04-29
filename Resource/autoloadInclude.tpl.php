<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2020.08.22 at 21:37
 */

use LaborDigital\T3BA\Core\Kernel;

if (! defined("BETTER_API_TYPO3_VENDOR_PATH")) {
    define("BETTER_API_TYPO3_VENDOR_PATH", dirname(__DIR__));
}
if (! defined("BETTER_API_TYPO3_VAR_PATH")) {
    define("BETTER_API_TYPO3_VAR_PATH", BETTER_API_TYPO3_VENDOR_PATH . "/{{varPath}}");
}

$composerClassLoader = require BETTER_API_TYPO3_VENDOR_PATH . "/autoload.php";
if (class_exists(Kernel::class)) {
    Kernel::init($composerClassLoader);
}
