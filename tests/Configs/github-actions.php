<?php
use Phpfastcache\Drivers\Couchbasev4\Config as Couchbasev4Config;

return (new Couchbasev4Config())
    ->setItemDetailedDate(true)
    ->setUsername('test')
    ->setPassword('phpfastcache')
    ->setServers(['127.0.0.1'])
    ->setBucketName('phpfastcache')
    ->setScopeName('_default')
    ->setCollectionName('_default');
