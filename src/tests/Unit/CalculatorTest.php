<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Calculator;  // ← まだ存在しないクラス！

class CalculatorTest extends TestCase
{
    /**
     * 2つの数を足し算できることをテストする
     */
    #[Test]
    public function it_can_add_two_numbers()
    {
        // Arrange（準備）: テストに必要なオブジェクトを用意
        $calculator = new Calculator();

        // Act（実行）: テストしたい機能を実行
        $result = $calculator->add(2, 3);

        // Assert（検証）: 期待通りの結果になっているか確認
        $this->assertEquals(5, $result);
    }

}