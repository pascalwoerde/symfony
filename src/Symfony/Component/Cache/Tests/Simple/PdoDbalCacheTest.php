<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Tests\Simple;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Cache\Simple\PdoCache;

/**
 * @group time-sensitive
 */
class PdoDbalCacheTest extends CacheTestCase
{
    protected static $dbFile;

    public static function setupBeforeClass()
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new \PHPUnit_Framework_SkippedTestError('Extension pdo_sqlite required.');
        }

        self::$dbFile = tempnam(sys_get_temp_dir(), 'sf_sqlite_cache');

        $pool = new PdoCache(DriverManager::getConnection(array('driver' => 'pdo_sqlite', 'path' => self::$dbFile)));
        $pool->createTable();
    }

    public static function tearDownAfterClass()
    {
        @unlink(self::$dbFile);
    }

    public function createSimpleCache($defaultLifetime = 0)
    {
        return new PdoCache(DriverManager::getConnection(array('driver' => 'pdo_sqlite', 'path' => self::$dbFile)), '', $defaultLifetime);
    }
}
