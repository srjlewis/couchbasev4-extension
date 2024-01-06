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
use Phpfastcache\Helper\Psr16Adapter;
use Webmozart\Assert\Assert;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';

$configFileName = __DIR__ . '/Configs/' . ($argv[1] ?? 'github-actions') . '.php';
if (!file_exists($configFileName)) {
    $configFileName = __DIR__ . '/Configs/github-actions.php';
}

$config = (new CouchbaseConfig(include $configFileName))
    ->setDoPosixCheck(true)
    ->setUseStaticItemCaching(false);

$cacheInstance = CacheManager::getInstance('Couchbasev4', $config);

Assert::isInstanceOf($cacheInstance, \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface::class);

$cache = new Psr16Adapter($cacheInstance);
$value = random_int(0, 254);

$cache->set('key1', $value);

$pid = pcntl_fork();
if ($pid == -1) {
    die('could not fork');
} else if ($pid) {
    pcntl_wait($status);
} else {
    exit($cache->get('key1'));
}

Assert::true($value === pcntl_wexitstatus($status));

$pool = $cacheInstance;

$poolClear = true;

if ($poolClear) {
    $pool->clear();
}

$cacheKey = 'cache_key_' . bin2hex(random_bytes(8) . '_' . random_int(100, 999));
$cacheValue = 'cache_data_' . random_int(1000, 999999);
$cacheTag = 'cache_tag_' . bin2hex(random_bytes(8) . '_' . random_int(100, 999));
$cacheTag2 = 'cache_tag_' . bin2hex(random_bytes(8) . '_' . random_int(100, 999));
$cacheItem = $pool->getItem($cacheKey);

Assert::false(
    $cacheItem->getTtl() < $pool->getConfig()->getDefaultTtl() - 1,
    \sprintf(
        'The expected TTL of the cache item was ~%ds, got %ds',
        $pool->getConfig()->getDefaultTtl(),
        $cacheItem->getTtl()
    )
);

$cacheItem->set($cacheValue)
    ->addTags([$cacheTag, $cacheTag2]);

Assert::true($pool->save($cacheItem), 'The pool failed to save an item.');

unset($cacheItem);
$pool->detachAllItems();

$cacheItems = $pool->getItemsByTags([$cacheTag, $cacheTag2, 'unknown_tag'], $pool::TAG_STRATEGY_ALL);
Assert::false(isset($cacheItems[$cacheKey]), 'The pool unexpectedly retrieved the cache item.');
unset($cacheItems);
$pool->detachAllItems();

$cacheItems = $pool->getItemsByTags([$cacheTag, $cacheTag2], $pool::TAG_STRATEGY_ALL);
Assert::true(isset($cacheItems[$cacheKey]), 'The pool failed to retrieve the cache item.');
unset($cacheItems);
$pool->detachAllItems();

$cacheItems = $pool->getItemsByTags([$cacheTag, $cacheTag2, 'unknown_tag'], $pool::TAG_STRATEGY_ONLY);
Assert::false(isset($cacheItems[$cacheKey]), 'The pool unexpectedly retrieved the cache item.');
unset($cacheItems);
$pool->detachAllItems();

$cacheItems = $pool->getItemsByTags([$cacheTag, $cacheTag2], $pool::TAG_STRATEGY_ONLY);
Assert::true(isset($cacheItems[$cacheKey]), 'The pool failed to retrieve the cache item.');
unset($cacheItems);
$pool->detachAllItems();

$cacheItems = $pool->getItemsByTags([$cacheTag, 'unknown_tag'], $pool::TAG_STRATEGY_ONE);
Assert::true(
    isset($cacheItems[$cacheKey]) && $cacheItems[$cacheKey]->getKey() === $cacheKey,
    'The pool failed to retrieve the cache item.'
);

$cacheItem = $cacheItems[$cacheKey];
Assert::true($cacheItem->get() === $cacheValue, 'The pool failed to retrieve the expected value.');

$cacheItem->append('_appended');
$cacheValue .= '_appended';
$pool->saveDeferred($cacheItem);
Assert::true($pool->commit(), 'The pool failed to commit deferred cache item.');
$pool->detachAllItems();
unset($cacheItem);

$cacheItem = $pool->getItem($cacheKey);
Assert::true($cacheItem->get() === $cacheValue, 'The pool failed to retrieve the expected new value.');

if ($poolClear) {
    Assert::true(
        $pool->deleteItem($cacheKey) && !$pool->getItem($cacheKey)->isHit(),
        'The pool failed to delete the cache item.'
    );

    Assert::true(
        $pool->clear(),
        'The cluster failed to clear.'
    );
    $pool->detachAllItems();
    unset($cacheItem);

    $cacheItem = $pool->getItem($cacheKey);
    Assert::true(
        !$cacheItem->isHit(),
        'The cache item still exists in pool.'
    );
}

