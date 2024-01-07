<?php

declare(strict_types=1);

namespace Phpfastcache;

use Phpfastcache\Extensions\Drivers\Couchbasev4\{Config, Driver, Item};

// Semver Compatibility until v10
class_alias(Config::class, Drivers\Couchbasev4\Config::class);
class_alias(Driver::class, Drivers\Couchbasev4\Driver::class);
class_alias(Item::class, Drivers\Couchbasev4\Item::class);

ExtensionManager::registerExtension('Couchbasev4', Driver::class);
