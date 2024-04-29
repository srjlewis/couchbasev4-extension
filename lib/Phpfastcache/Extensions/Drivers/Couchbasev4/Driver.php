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
 *
 * @noinspection PhpMultipleClassDeclarationsInspection
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace Phpfastcache\Extensions\Drivers\Couchbasev4;

use Couchbase\Exception\CouchbaseException;
use Couchbase\Bucket as CouchbaseBucket;
use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\Collection;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\TimeoutException;
use Couchbase\ForkEvent;
use Couchbase\GetResult;
use Couchbase\Scope;
use Couchbase\UpsertOptions;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\CacheItemPoolTrait;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Exceptions\PhpfastcacheUnsupportedMethodException;

/**
 * @property Cluster $instance Instance of driver service
 * @method Config getConfig()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Driver implements AggregatablePoolInterface
{
    use CacheItemPoolTrait {
        CacheItemPoolTrait::__construct as __parentConstruct;
    }
    use TaggableCacheItemPoolTrait;


    protected Scope $scope;

    protected Collection $collection;

    protected CouchbaseBucket $bucketInstance;

    protected int $currentParentPID = 0;

    protected static int $prepareToForkPPID = 0;

    protected static string $extVersion;

    protected static bool $posixLoaded;

    /**
     * Driver constructor.
     * @param ConfigurationOption $config
     * @param string $instanceId
     * @param EventManagerInterface $em
     */
    public function __construct(ConfigurationOption $config, string $instanceId, EventManagerInterface $em)
    {
        static::$extVersion  ??= \phpversion('couchbase');
        static::$posixLoaded ??= \extension_loaded('posix');

        if (\version_compare(static::$extVersion, '4.2.0', '=') && static::$posixLoaded) {
            $this->currentParentPID = \posix_getppid();
        }

        $this->__parentConstruct($config, $instanceId, $em);
    }


    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \extension_loaded('couchbase');
    }

    public function getHelp(): string
    {
        return 'Couchbasev4 requires the `php-couchbase` extension 4.x and optionally the `php-posix` extension if you enabled the config "doForkDetection".';
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverCheckException
     */
    protected function driverConnect(): bool
    {
        if (!\class_exists(ClusterOptions::class)) {
            throw new PhpfastcacheDriverCheckException('You are using the Couchbase PHP SDK 2.x which is no longer supported in Phpfastcache v9');
        }

        if (\version_compare(static::$extVersion, '4.0.0', '<') || \version_compare(static::$extVersion, '5.0.0', '>=')) {
            throw new PhpfastcacheDriverCheckException("You are using Couchbase extension " . static::$extVersion . ", You need to use a Couchbase V4 extension");
        }
        return $this->connect();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function connect(?int $appendPPID = null): bool
    {
        $schema  = $this->getConfig()->getSecure() ? 'couchbases' : 'couchbase';
        $servers = $this->getConfig()->getServers();

        $connectionString = $schema . '://' . implode(',', $servers) . ($appendPPID ? "?ppid=$appendPPID" : '');

        $options = $this->getConfig()->getClusterOptions();
        $options->credentials($this->getConfig()->getUsername(), $this->getConfig()->getPassword());
        $this->instance = new Cluster($connectionString, $options); // @phpstan-ignore-line
        $this->setBucket($this->instance->bucket($this->getConfig()->getBucketName()));
        $this->setScope($this->getBucket()->scope($this->getConfig()->getScopeName()));
        $this->setCollection($this->getScope()->collection($this->getConfig()->getCollectionName()));

        return true;
    }

    /**
     * Needs to be call just before posix_fork() call or the child process will lock up
     *
     * @return void
     * @throws PhpfastcacheDriverCheckException
     */
    public static function prepareToFork(): void
    {
        if (!static::$posixLoaded) {
            throw new PhpfastcacheDriverCheckException('POSIX extension is required to prepare for forking');
        }

        if (\version_compare(static::$extVersion, '4.2.0', '=')) {
            throw new PhpfastcacheDriverCheckException(
                'You are using Couchbase extension ' . static::$extVersion .
                ', This version has a known bug with pcntl_fork() and will lockup child processes.'
            );
        }

        if (\version_compare(static::$extVersion, '4.2.1', '>=')) {
            Cluster::notifyFork(ForkEvent::PREPARE);
        }

        static::$prepareToForkPPID = posix_getpid();
    }

    /**
     * @return void
     * @throws PhpfastcacheDriverCheckException
     */
    protected function handleForkedProcess(): void
    {
        if (static::$posixLoaded) {
            if (\version_compare(static::$extVersion, '4.2.0', '=') && $this->currentParentPID !== \posix_getppid()) {
                // exit() call will fail and lockup, so child process kills itself
                \posix_kill(\posix_getpid(), \SIGKILL);
            }

            if (static::$prepareToForkPPID) {
                if (\version_compare(static::$extVersion, '4.2.0', '<') && static::$prepareToForkPPID !== $this->currentParentPID) {
                    $this->currentParentPID = static::$prepareToForkPPID;
                    $this->connect(\posix_getppid());
                }

                if (\version_compare(static::$extVersion, '4.2.1', '>=')) {
                    if (static::$prepareToForkPPID === \posix_getpid()) {
                        Cluster::notifyFork(ForkEvent::PARENT);
                    } else {
                        Cluster::notifyFork(ForkEvent::CHILD);
                    }
                    static::$prepareToForkPPID = 0;
                }
            }
        }
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     * @throws CouchbaseException
     * @throws PhpfastcacheDriverCheckException
     * @throws TimeoutException
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        try {
            $this->handleForkedProcess();
            /**
             * CouchbaseBucket::get() returns a GetResult interface
             */
            return $this->decodeDocument((array)$this->getCollection()->get($item->getEncodedKey())->content());
        } catch (DocumentNotFoundException) {
            return null;
        }
    }

    /**
     * @param ExtendedCacheItemInterface ...$item
     * @return array<array<string, mixed>>
     * @throws PhpfastcacheDriverCheckException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    protected function driverReadMultiple(ExtendedCacheItemInterface ...$items): array
    {
        try {
            $results = [];
            $this->handleForkedProcess();
            /**
             * CouchbaseBucket::get() returns a GetResult interface
             */
            /** @var GetResult $document */
            foreach ($this->getCollection()->getMulti($this->getKeys($items, true)) as $document) {
                if (!$document->error()) {
                    $content = $document->content();
                    if ($content) {
                        $decodedDocument                                                                     = $this->decodeDocument($content);
                        $results[$decodedDocument[ExtendedCacheItemPoolInterface::DRIVER_KEY_WRAPPER_INDEX]] = $this->decodeDocument($content);
                    }
                }
            }

            return $results;
        } catch (DocumentNotFoundException) {
            return [];
        }
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverCheckException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        try {
            $this->handleForkedProcess();
            $this->getCollection()->upsert(
                $item->getEncodedKey(),
                $this->encodeDocument($this->driverPreWrap($item)),
                (new UpsertOptions())->expiry($item->getTtl())
            );
            return true;
        } catch (CouchbaseException) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param string $encodedKey
     * @return bool
     * @throws PhpfastcacheDriverCheckException
     */
    protected function driverDelete(string $key, string $encodedKey): bool
    {
        try {
            $this->handleForkedProcess();
            return $this->getCollection()->remove($encodedKey)->mutationToken() !== null;
        } catch (DocumentNotFoundException) {
            return true;
        } catch (CouchbaseException) {
            return false;
        }
    }

    /**
     * @param string[] $keys
     * @return bool
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheLogicException
     */
    protected function driverDeleteMultiple(array $keys): bool
    {
        try {
            $this->handleForkedProcess();
            $this->getCollection()->removeMulti(array_map(fn(string $key) => $this->getEncodedKey($key), $keys));
            return true;
        } catch (CouchbaseException) {
            return false;
        }
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheUnsupportedMethodException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    protected function driverClear(): bool
    {
        $this->handleForkedProcess();
        if (!$this->instance->buckets()->getBucket($this->getConfig()->getBucketName())->flushEnabled()) {
            if ($this->getConfig()->isFlushFailSilently()) {
                return false;
            }
            throw new PhpfastcacheUnsupportedMethodException(
                'Flushing operation is not enabled on your Bucket. See https://docs.couchbase.com/server/current/manage/manage-buckets/flush-bucket.html'
            );
        }

        if (!$this->getConfig()->isAllowFlush()) {
            if ($this->getConfig()->isFlushFailSilently()) {
                return false;
            }
            throw new PhpfastcacheUnsupportedMethodException(
                'Flushing operation is disabled in config'
            );
        }
        try {
            $this->instance->buckets()->flush($this->getConfig()->getBucketName());
            return true;
        } catch (CouchbaseException) {
            if ($this->getConfig()->isFlushFailSilently()) {
                return false;
            }
            throw new PhpfastcacheUnsupportedMethodException(
                'Flushing operation is enabled, but you don\'t have permissions to flush'
            );
        }
    }

    /**
     * @return DriverStatistic
     * @throws \Exception
     */
    public function getStats(): DriverStatistic
    {
        $this->handleForkedProcess();
        /**
         * Between SDK 2 and 3 we lost a lot of useful information :(
         * @see https://docs.couchbase.com/java-sdk/current/project-docs/migrating-sdk-code-to-3.n.html#management-apis
         */
        $info = $this->instance->diagnostics(\bin2hex(\random_bytes(16)));

        return (new DriverStatistic())
            ->setSize(0)
            ->setRawData($info)
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo($info['sdk'] . "\n For more information see RawData.");
    }

    /**
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * @param Collection $collection
     * @return Driver
     */
    public function setCollection(Collection $collection): Driver
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @return Scope
     */
    public function getScope(): Scope
    {
        return $this->scope;
    }

    /**
     * @param Scope $scope
     * @return Driver
     */
    public function setScope(Scope $scope): Driver
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @return CouchbaseBucket
     */
    protected function getBucket(): CouchbaseBucket
    {
        return $this->bucketInstance;
    }

    /**
     * @param CouchbaseBucket $couchbaseBucket
     */
    protected function setBucket(CouchbaseBucket $couchbaseBucket): void
    {
        $this->bucketInstance = $couchbaseBucket;
    }


    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function encodeDocument(array $data): array
    {
        $data[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX]  = $this->encode($data[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX]);
        $data[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX] = $data[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX]
            ->format(\DateTimeInterface::ATOM);

        if ($this->getConfig()->isItemDetailedDate()) {
            $data[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] = $data[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX]
                ->format(\DateTimeInterface::ATOM);

            $data[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] = $data[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX]
                ->format(\DateTimeInterface::ATOM);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function decodeDocument(array $data): array
    {
        $data[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX]  = $this->unserialize($data[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX]);
        $data[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX] = \DateTime::createFromFormat(
            \DateTimeInterface::ATOM,
            $data[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX]
        );

        if ($this->getConfig()->isItemDetailedDate()) {
            $data[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] = \DateTime::createFromFormat(
                \DateTimeInterface::ATOM,
                $data[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX]
            );

            $data[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] = \DateTime::createFromFormat(
                \DateTimeInterface::ATOM,
                $data[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX]
            );
        }

        return $data;
    }
}
