## 9.2.1
#### 25 April 2024
- __Driver Core__
  - `Couchbasev4` Update to support couchbase 4.2.1 and above, which need a notify call when forking the process.

## 9.2.0
##### 10 january 2024
- __Driver Core__
    - Driver is co-maintained by @Geolim4 and @srjlewis
    - `Couchbasev4` is now an extension separated from the main Phpfastcache repository.
    - `Couchbasev4` requires Couchbase client `Couchbase/Couchbase` 4.x and PHP Extension `Couchbase` 4.x
    - `Couchbasev4` may requires `posix`/`pcntl` extension if you are manipulating processes and need to fix an [internal Couchbase bug](README.md#-this-extension-optionally-requires-).
