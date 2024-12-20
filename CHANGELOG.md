## 9.2.3
#### 27 November 2024
- __Driver Core__
  - `Couchbasev4` Allow call to `handleNotifyFork()` after process has been forked, this is due 
  - to a change in the couchbase SDK that needs parent and child to be notified at the same time.


## 9.2.2
#### 29 July 2024
- __Driver Core__
  - `Couchbasev4` Allow calling of `prepareToFork()` when driver is not initialized to allow switch between drivers.
  - `Couchbasev4` Tell couchbase to reattach its threads if needed, while preparing to forking and on destruct.

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
