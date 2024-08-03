<?php
declare(strict_types=1);

use PetProjectAnalytics\Aggregator;
use PHPUnit\Framework\TestCase;

final class AggregatorTest extends TestCase
{
    public function testCanInstantiate() : void
    {
        $i = new Aggregator();
        self::assertTrue($i instanceof Aggregator);
    }
}
