<?php
declare(strict_types=1);

use PetProjectAnalytics\Stats;
use PHPUnit\Framework\TestCase;

final class StatsTest extends TestCase
{
    public function testCanInstantiate() : void
    {
        $i = new Stats();
        self::assertTrue($i instanceof Stats);
    }
}
