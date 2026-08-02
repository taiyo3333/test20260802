<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        // デモ用にわざと失敗させている（本来は assertTrue(true)）
        $this->assertTrue(true);
    }
}
