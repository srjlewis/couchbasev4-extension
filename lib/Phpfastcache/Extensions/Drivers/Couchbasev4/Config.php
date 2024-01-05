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

declare(strict_types=1);

namespace Phpfastcache\Extensions\Drivers\Couchbasev4;

use Couchbase\ClusterOptions;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

class Config extends ConfigurationOption
{
    protected const DEFAULT_VALUE = '_default';
    protected const DEFAULT_HOST  = '127.0.0.1';

    protected string $username          = '';
    protected string $password          = '';
    protected string $bucketName        = self::DEFAULT_VALUE;
    protected string $scopeName         = self::DEFAULT_VALUE;
    protected string $collectionName    = self::DEFAULT_VALUE;
    protected array  $servers           = [];
    protected bool   $secure            = false;
    protected bool   $allowFlush        = true;
    protected bool   $flushFailSilently = false;

    protected ?ClusterOptions $clusterOptions = null;

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
    }


    public function getServers(): array
    {
        return $this->servers ?: [self::DEFAULT_HOST];
    }

    /**
     * @param array $servers
     * @return $this
     * @throws PhpfastcacheLogicException
     */
    public function setServers(array $servers): Config
    {
        foreach ($servers as $server) {
            $this->addServer($server);
        }
        return $this;
    }

    /**
     * @param string $host
     * @param int|null $port
     * @return $this
     * @throws PhpfastcacheLogicException
     */
    public function addServer(string $host, ?int $port = null): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->servers[] = $host . ($port ? ':' . $port : '');
        return $this;
    }

    /**
     * @param bool $secure
     * @return $this
     * @throws PhpfastcacheLogicException
     */
    public function setSecure(bool $secure): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->secure = $secure;
        return $this;
    }

    public function getSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setUsername(string $username): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * @param string $bucketName
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setBucketName(string $bucketName): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->bucketName = $bucketName;
        return $this;
    }

    /**
     * @return string
     */
    public function getScopeName(): string
    {
        return $this->scopeName;
    }

    /**
     * @param string $scopeName
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setScopeName(string $scopeName): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->scopeName = $scopeName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setCollectionName(string $collectionName): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->collectionName = $collectionName;
        return $this;
    }

    /**
     * @param bool $allow
     * @return $this
     * @throws PhpfastcacheLogicException
     */
    public function setAllowFlush(bool $allow): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->allowFlush = $allow;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowFlush(): bool
    {
        return $this->allowFlush;
    }

    /**
     * @param bool $silent
     * @return $this
     * @throws PhpfastcacheLogicException
     */
    public function setFlushFailSilently(bool $silent): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->flushFailSilently = $silent;
        return $this;
    }

    /**
     * @return bool
     */
    public function getFlushFailSilently(): bool
    {
        return $this->flushFailSilently;
    }

    public function setClusterOptions(?ClusterOptions $clusterOptions): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions = $clusterOptions;
        return $this;
    }

    public function getClusterOptions(): ClusterOptions
    {
        if (is_null($this->clusterOptions)) {
            $this->clusterOptions = new ClusterOptions();
        }
        return $this->clusterOptions;
    }
}
