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
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

class Config extends ConfigurationOption
{
    protected const DEFAULT_VALUE = '_default';
    protected const DEFAULT_HOST  = '127.0.0.1';

    /**
     * @var array<string>
     */
    protected array $servers           = [];
    protected string $username          = '';
    protected string $password          = '';
    protected string $bucketName        = self::DEFAULT_VALUE;
    protected string $scopeName         = self::DEFAULT_VALUE;
    protected string $collectionName    = self::DEFAULT_VALUE;
    protected bool $secure            = false;
    protected bool $allowFlush        = true;
    protected bool $flushFailSilently = false;
    protected bool $doPosixCheck      = false;

    protected ?ClusterOptions $clusterOptions = null;

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
    }

    /**
     * @return array<string>
     */
    public function getServers(): array
    {
        return $this->servers ?: [self::DEFAULT_HOST];
    }

    /**
     * @param array<string> $servers
     * @throws PhpfastcacheLogicException
     */
    public function setServers(array $servers): static
    {
        return $this->setProperty('servers', $servers);
    }

    /**
     * @param string $host
     * @param int|null $port
     * @throws PhpfastcacheLogicException
     */
    public function addServer(string $host, ?int $port = null): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->servers[] = $host . ($port ? ':' . $port : '');
        return $this;
    }

    /**
     * @param bool $secure
     * @throws PhpfastcacheLogicException
     */
    public function setSecure(bool $secure): static
    {
        return $this->setProperty('secure', $secure);
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
     * @throws PhpfastcacheLogicException
     */
    public function setUsername(string $username): static
    {
        return $this->setProperty('username', $username);
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
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): static
    {
        return $this->setProperty('password', $password);
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
     * @throws PhpfastcacheLogicException
     */
    public function setBucketName(string $bucketName): static
    {
        return $this->setProperty('bucketName', $bucketName);
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
     * @throws PhpfastcacheLogicException
     */
    public function setScopeName(string $scopeName): static
    {
        return $this->setProperty('scopeName', $scopeName);
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
     * @throws PhpfastcacheLogicException
     */
    public function setCollectionName(string $collectionName): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->collectionName = $collectionName;
        return $this;
    }

    /**
     * @param bool $allow
     * @throws PhpfastcacheLogicException
     */
    public function setAllowFlush(bool $allow): static
    {
        return $this->setProperty('enforceLockedProperty', $allow);
    }

    /**
     * @return bool
     */
    public function isAllowFlush(): bool
    {
        return $this->allowFlush;
    }

    /**
     * @param bool $silent
     * @throws PhpfastcacheLogicException
     */
    public function setFlushFailSilently(bool $silent): static
    {
        return $this->setProperty('flushFailSilently', $silent);
    }

    /**
     * @return bool
     */
    public function isFlushFailSilently(): bool
    {
        return $this->flushFailSilently;
    }

    public function setClusterOptions(?ClusterOptions $clusterOptions): static
    {
        return $this->setProperty('clusterOptions', $clusterOptions);
    }

    public function getClusterOptions(): ClusterOptions
    {
        if (is_null($this->clusterOptions)) {
            $this->clusterOptions = new ClusterOptions();
        }
        return $this->clusterOptions;
    }

    public function isDoPosixCheck(): bool
    {
        return $this->doPosixCheck;
    }

    public function setDoPosixCheck(bool $doPosixCheck): static
    {
        if ($doPosixCheck && !extension_loaded('posix')) {
            throw new PhpfastcacheInvalidArgumentException('Posix extension is required to enable the doPosixCheck config entry.');
        }

        return $this->setProperty('doPosixCheck', $doPosixCheck);
    }
}
