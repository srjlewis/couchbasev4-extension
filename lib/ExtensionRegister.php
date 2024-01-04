<?php
declare(strict_types=1);

use Phpfastcache\ExtensionManager as PhpfastcacheExtensionManager;
use Phpfastcache\Extensions\Drivers\Couchbasev4\Driver;

PhpfastcacheExtensionManager::registerExtension('Couchbasev4', Driver::class);
