<?php

declare(strict_types=1);

namespace Phpfastcache;

use Phpfastcache\Extensions\Drivers\Couchbasev4\Driver;

ExtensionManager::registerExtension('Couchbasev4', Driver::class);
