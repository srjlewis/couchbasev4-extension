<?php
use Phpfastcache\Drivers\Couchbasev4\Config as Couchbasev4Config;

return (static fn(Couchbasev4Config $config) => $config->setItemDetailedDate(true)
    ->setUsername('test')
    ->setPassword('phpfastcache')
    ->setServers(['127.0.0.1'])
    ->setBucketName('phpfastcache')
    ->setScopeName('_default')
    ->setCollectionName('collectionName')
)(new Couchbasev4Config());
