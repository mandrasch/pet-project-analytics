<?php
declare(strict_types=1);

use PetProjectAnalytics\Dates;
use PHPUnit\Framework\TestCase;

final class DatesTest extends TestCase
{
    public function testCanInstantiate() : void
    {
        $i = new Dates();
        self::assertTrue($i instanceof Dates);
    }
}
