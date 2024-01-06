## Contributing [![PHP Tests](https://github.com/PHPSocialNetwork/couchbasev4-extension/actions/workflows/php.yml/badge.svg)](https://github.com/PHPSocialNetwork/couchbasev4-extension/actions/workflows/php.yml)
Merge requests are welcome but will require the tests plus the quality tools to pass:

_(Commands must be run from the repository root)_
### PHPCS:

```bash
# Fixer + Linter
./vendor/bin/phpcbf lib/ --report=summary

# Linter only
./vendor/bin/phpcs lib/ --report=summary
```
### PHPMD:
```bash
./vendor/bin/phpmd lib/ ansi phpmd.xml
```

### PHPSTAN (Level 6):
```bash
./vendor/bin/phpstan analyse lib/ -l 6 -c phpstan.neon 2>&1
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
1️⃣ The PHP `Posix` to fix a known Couchbase Extension bug [PCBC-886](https://issues.couchbase.com/projects/PCBC/issues/PCBC-886).  
Once this bug has been fixed the dependency suggestion will be removed. 
If your application do manipulate processes with `Posix` extension, and you want the fix to be enabled, set up the config like this:
```php
$config = (new CouchbaseConfig())->setDoPosixCheck(true);
```

2️⃣ Also the PHP `Pcntl` if you plan to contribute to this project and run the tests before pushing your Merge Request.



