<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Extensions\Drivers\Couchbasev4\Config as CouchbaseConfig;
use Webmozart\Assert\Assert;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';

$config = new CouchbaseConfig();
$cacheInstance = CacheManager::getInstance('Couchbasev4',  $config);

Assert::isInstanceOf($cacheInstance, \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface::class);


