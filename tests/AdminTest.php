<?php
declare(strict_types=1);

use KokoAnalytics\Admin;
use PHPUnit\Framework\TestCase;

final class AdminTest extends TestCase
{
    public function testCanInstantiate() : void
    {
        $i = new Admin();
        self::assertTrue($i instanceof Admin);
    }
}
