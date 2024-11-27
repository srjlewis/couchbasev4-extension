## Contributing [![PHP Tests](https://github.com/PHPSocialNetwork/couchbasev4-extension/actions/workflows/php.yml/badge.svg)](https://github.com/PHPSocialNetwork/couchbasev4-extension/actions/workflows/php.yml)
Merge requests are welcome but will require the tests plus the quality tools to pass:

_(Commands must be run from the repository root)_
### PHPCS, PHPMD, PHPSTAN (Level 6), unit tests:

```bash
composer run-script quality
composer run-script tests

# In case you want to fix the code style automatically: 
./vendor/bin/phpcbf lib/ --report=summary
```

## Support & Security

Support for this extension must be posted to the main [Phpfastcache repository](https://github.com/PHPSocialNetwork/phpfastcache/issues).

## Composer installation:

```php
composer install phpfastcache/couchbasev4-extension
```

#### ⚠️ This extension requires:

1️⃣ The PHP `Couchbase` extension 4.x at least

2️⃣ The composer `Couchbase/Couchbase` library 4.x at least

#### ⚠️ This extension optionally requires: 
1️⃣ The PHP `Posix` extension is needed use `pcntl_fork()` for process forking.  

To fork a php process correctly you will need to tell the Couchbase diver to prepare for the fork.

⚠️ __WARNING__ You **must** call the drivers `Phpfastcache\Drivers\Couchbasev4\Driver::prepareToFork()` 
just before the `pcntl_fork()` call or the child process will lock up and then call `handleNotifyFork()` to avoid further 
errors.

#### Example
```php
try {
    \Phpfastcache\Drivers\Couchbasev4\Driver::prepareToFork();
    $pid = pcntl_fork();
    \Phpfastcache\Drivers\Couchbasev4\Driver::handleNotifyFork();
    if ($pid == -1) {
        // There was a problem with forking the process
    } else if ($pid) {
        // continue parent process operations
    } else {
        // continue child process operations
    }
} catch (PhpfastcacheDriverCheckException) {
    // the driver did not allow you to fork the process
}
```

2️⃣ Also the PHP `Pcntl` if you plan to contribute to this project and run the tests before pushing your Merge Request.



