## Support & Security

Support for this extension must be posted to the main [Phpfastcache repository](https://github.com/PHPSocialNetwork/phpfastcache/issues).

## Composer installation:

```php
composer install phpfastcache/couchbasev4-extension
```

This extension requires:

- The PHP `Couchbase` extension 4.x at least
- The composer `Couchbase/Couchbase` library 4.x at least
- The PHP `Posix` to fix a known bug [PCBC-886](https://issues.couchbase.com/projects/PCBC/issues/PCBC-886)


## Contributing
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
