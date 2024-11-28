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
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Helper\Psr16Adapter;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$extensionVersion = \phpversion('couchbase');
$testHelper       = new TestHelper('Couchbasev4 driver - Extension Version ' . $extensionVersion);

$configFileName = __DIR__ . '/Configs/' . ($argv[1] ?? 'github-actions') . '.php';
if (!\file_exists($configFileName)) {
    $configFileName = __DIR__ . '/Configs/github-actions.php';
}

$testHelper->printInfoText('Running forking failure process test');

$config = (include $configFileName)
    ->setUseStaticItemCaching(false);
$cacheInstance = CacheManager::getInstance('Couchbasev4', $config);

$cache = new Psr16Adapter($cacheInstance);
$value = \random_int(1, 254);

$cache->set('forkFailTestKey', $value);

$pid = \pcntl_fork();
if ($pid == -1) {
    $testHelper->assertFail('Unable to fork');
} else if ($pid) {
    $testHelper->runAsyncProcess('php "' . __DIR__ . '/Scripts/monitor_fork.php" ' . $pid);
    \pcntl_wait($status);
} else {
    exit($cache->get('forkFailTestKey'));
}

if ($value === \pcntl_wexitstatus($status)) {
    $testHelper->assertFail('The fork was a success was meant to lockup');
} else {
    $testHelper->assertPass('The forked process locked up has expected');
}

$testHelper->printInfoText('Running forking success process test');

$config1 = (include $configFileName)->setUseStaticItemCaching(false);
$config2 = (include $configFileName)->setUseStaticItemCaching(false)->setUsername('test2');

$cache1 = new Psr16Adapter(CacheManager::getInstance('Couchbasev4', $config1));
$cache2 = new Psr16Adapter(CacheManager::getInstance('Couchbasev4', $config2));

$value1 = \random_int(1, 125);
$value2 = \random_int(1, 125);

$cache1->set('forkSuccessTestKey1', $value1);
$cache2->set('forkSuccessTestKey2', $value2);

// 1576800000 is the int limit within Couchbase for the ttl before the need to use DateTime
// so using an int like '\time() + 3600' would produce an error, to reproduce the error
// within phpFastCache we need to push the date 1576800001s in the future to overflow the ttl int
if($cache->set('bigTTL', 'test', new DateInterval('PT1576800001S'))) {
    $testHelper->assertPass('Set with large ttl succeeded');
} else {
    $testHelper->assertFail('Set with large ttl failed');
}

try {
    \Phpfastcache\Extensions\Drivers\Couchbasev4\Driver::prepareToFork();
    $pid = \pcntl_fork();
    \Phpfastcache\Extensions\Drivers\Couchbasev4\Driver::handleNotifyFork();
    if ($pid == -1) {
        $testHelper->assertFail('Unable to fork');
    } else if ($pid) {
        $testHelper->runAsyncProcess('php "' . __DIR__ . '/Scripts/monitor_fork.php" ' . $pid);
        \pcntl_wait($status);
    } else {
        exit($cache1->get('forkSuccessTestKey1') + $cache2->get('forkSuccessTestKey2'));
    }

    $testHelper->printDebugText('Child returned <green>' . \pcntl_wexitstatus($status) . '</green>');

    if (($value1 + $value2) === \pcntl_wexitstatus($status)) {
        $testHelper->assertPass('The success fork was a success and returned correctly');
    } else {
        $testHelper->assertFail('The success fork failed');
    }
} catch (PhpfastcacheDriverCheckException) {
    if(\version_compare($extensionVersion, '4.2.0', '=')) {
        $testHelper->assertSkip('extension version 4.2.0 detected and can\'t be forked safely');
    } else {
        $testHelper->assertFail('Something as broken again');
    }
}


try {
    $testHelper->runCRUDTests($cacheInstance);
} catch (PhpfastcacheDriverConnectException $e) {
    $testHelper->assertSkip('Couchbase server unavailable: ' . $e->getMessage());
}
$testHelper->terminateTest();


