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
 * @author Steven Lewis (srjlewis) https://github.com/srjlewis
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Extensions\Drivers\Couchbasev4\Config as CouchbaseConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Helper\Psr16Adapter;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Couchbasev4 driver');

$configFileName = __DIR__ . '/Configs/' . ($argv[1] ?? 'github-actions') . '.php';
if (!file_exists($configFileName)) {
    $configFileName = __DIR__ . '/Configs/github-actions.php';
}

$testHelper->printInfoText('Running forking failure process test');

$config = (include $configFileName)
    ->setDoForkDetection(false)
    ->setUseStaticItemCaching(false);

$cacheInstance = CacheManager::getInstance('Couchbasev4', $config);

$cache = new Psr16Adapter($cacheInstance);
$value = random_int(0, 254);

$cache->set('forkFailTestKey', $value);

$pid = pcntl_fork();
if ($pid == -1) {
    $testHelper->assertFail('Unable to fork');
} else if ($pid) {
    $testHelper->runAsyncProcess('php "'.__DIR__ . '/Scripts/monitor_fork.php" ' . $pid);
    pcntl_wait($status);
} else {
    exit($cache->get('forkFailTestKey'));
}

if ($value === pcntl_wexitstatus($status)) {
    $testHelper->assertFail('The fork was a success was meant to lockup');
} else {
    $testHelper->assertPass('The forked process locked up has expected');
}

$testHelper->printInfoText('Running forking success process test');

$config = (include $configFileName)
    ->setDoForkDetection(true)
    ->setUseStaticItemCaching(false);

$cacheInstance = CacheManager::getInstance('Couchbasev4', $config);

$cache = new Psr16Adapter($cacheInstance);
$value = random_int(0, 254);

$cache->set('forkSuccessTestKey', $value);

$pid = pcntl_fork();
if ($pid == -1) {
    $testHelper->assertFail('Unable to fork');
} else if ($pid) {
    $testHelper->runAsyncProcess('php "'.__DIR__ . '/Scripts/monitor_fork.php" ' . $pid);
    pcntl_wait($status);
} else {
    exit($cache->get('forkSuccessTestKey'));
}

if ($value === pcntl_wexitstatus($status)) {
    $testHelper->assertPass('The exit code is the one expected');
} else {
    $testHelper->assertFail('The exit code is unexpected');
}

try {
    $testHelper->runCRUDTests($cacheInstance);
} catch (PhpfastcacheDriverConnectException $e) {
    $testHelper->assertSkip('Couchbase server unavailable: ' . $e->getMessage());
}
$testHelper->terminateTest();


