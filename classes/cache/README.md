[Русская версия](README.ru.md)

# Cache Engines Directory

This directory contains cache engine adapters used by the Eleanor PHP Library. Each adapter wraps a concrete storage backend and exposes the shared `Eleanor\Interfaces\Cache` contract. The higher-level cache facade can then work with different engines through the same `Put()`, `Get()`, and `Delete()` methods.

## Namespace

All cache adapters in this directory belong to the `Eleanor\Classes\Cache` namespace. For example:

```php
namespace Eleanor\Classes\Cache;

class Serialized implements \Eleanor\Interfaces\Cache
{
    // ...
}
```

## Available Engines

The directory currently contains:

- `memcache.php` - adapter for the legacy `Memcache` extension;
- `memcached.php` - adapter for the `Memcached` extension;
- `serialized.php` - file-based cache with serialized values;
- `shmop.php` - cache based on shared memory functions.

## Required Interface

Every cache adapter must implement `Eleanor\Interfaces\Cache`. The interface requires three methods:

```php
function Put(string$k,mixed$v,int$ttl=86400):void;
function Get(string$k):mixed;
function Delete(string$k):void;
```

`Put()` stores a value by key. `Get()` retrieves a value by key. `Delete()` removes a value by key.

## Keys and TTL

Cache keys are strings. It is recommended to compose keys from logical tags, for example:

```text
module_section_item
```

The `$ttl` argument is the time-to-live in seconds. The default value is `86400` seconds.

## Cache Misses

Cache engines do not all report misses in the same way. Some return `null`, while others return `false`. Code using cache adapters should treat both `null` and `false` as cache miss or read failure markers. Avoid storing `false` when a cache miss must be distinguishable from a cached value.

## Adding a New Engine

To add a new cache engine:

1. Create a lowercase file named after the engine, for example `redis.php`.
2. Declare a class in the `Eleanor\Classes\Cache` namespace, for example `Redis`.
3. Implement `Eleanor\Interfaces\Cache`.
4. Return the class name at the end of the file if required by the autoloader.

Example:

```php
<?php
namespace Eleanor\Classes\Cache;

class Redis implements \Eleanor\Interfaces\Cache
{
    function Put(string$k,mixed$v,int$ttl=86400):void
    {
        // Store value.
    }

    function Get(string$k):mixed
    {
        // Return cached value, or null/false on miss.
    }

    function Delete(string$k):void
    {
        // Remove value.
    }
}

return Redis::class;
```
