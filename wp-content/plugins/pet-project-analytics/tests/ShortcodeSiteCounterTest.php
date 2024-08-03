<?php
declare(strict_types=1);

use PetProjectAnalytics\ShortCode_Site_Counter;
use PHPUnit\Framework\TestCase;

final class ShortcodeSiteCounterTest extends TestCase
{
    public function testCanInstantiate() : void
    {
        $i = new ShortCode_Site_Counter();
        self::assertTrue($i instanceof ShortCode_Site_Counter);
    }
}
