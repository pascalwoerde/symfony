<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Clock\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MonotonicClock;

class MonotonicClockTest extends TestCase
{
    public function testConstruct()
    {
        $clock = new MonotonicClock('UTC');
        $this->assertSame('UTC', $clock->now()->getTimezone()->getName());

        $tz = date_default_timezone_get();
        $clock = new MonotonicClock();
        $this->assertSame($tz, $clock->now()->getTimezone()->getName());

        $clock = new MonotonicClock(new \DateTimeZone($tz));
        $this->assertSame($tz, $clock->now()->getTimezone()->getName());
    }

    public function testNow()
    {
        $clock = new MonotonicClock();
        $before = new \DateTimeImmutable();
        usleep(10);
        $now = $clock->now();
        usleep(10);
        $after = new \DateTimeImmutable();

        $this->assertGreaterThan($before, $now);
        $this->assertLessThan($after, $now);
    }

    public function testSleep()
    {
        $clock = new MonotonicClock();
        $tz = $clock->now()->getTimezone()->getName();

        $before = microtime(true);
        $clock->sleep(1.5);
        $now = (float) $clock->now()->format('U.u');
        usleep(10);
        $after = microtime(true);

        $this->assertGreaterThan($before + 1.5, $now);
        $this->assertLessThan($after, $now);
        $this->assertLessThan(1.9, $now - $before);
        $this->assertSame($tz, $clock->now()->getTimezone()->getName());
    }

    public function testWithTimeZone()
    {
        $clock = new MonotonicClock();
        $utcClock = $clock->withTimeZone('UTC');

        $this->assertNotSame($clock, $utcClock);
        $this->assertSame('UTC', $utcClock->now()->getTimezone()->getName());
    }
}
