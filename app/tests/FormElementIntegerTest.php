<?php

use PHPUnit\Framework\TestCase;

class FormElementIntegerTest extends TestCase
{
    public function testValidIntegerPasses(): void
    {
        $el = new FormElementInteger('port', 'Port');
        $el->setValue('8080');

        $this->assertTrue($el->validate());
    }

    public function testNonIntegerFails(): void
    {
        $el = new FormElementInteger('port', 'Port');
        $el->setValue('abc');

        $this->assertFalse($el->validate());
    }

    public function testMinimumBoundaryExactlyPasses(): void
    {
        $el = new FormElementInteger('port', 'Port');
        $el->setMinimum(1);
        $el->setValue('1');

        $this->assertTrue($el->validate());
    }

    public function testBelowMinimumFails(): void
    {
        $el = new FormElementInteger('port', 'Port');
        $el->setMinimum(1);
        $el->setValue('0');

        $this->assertFalse($el->validate());
    }

    public function testMaximumBoundaryExactlyPasses(): void
    {
        $el = new FormElementInteger('port', 'Port');
        $el->setMaximum(65535);
        $el->setValue('65535');

        $this->assertTrue($el->validate());
    }

    public function testAboveMaximumFails(): void
    {
        $el = new FormElementInteger('port', 'Port');
        $el->setMaximum(65535);
        $el->setValue('65536');

        $this->assertFalse($el->validate());
    }

    public function testWithinRangePasses(): void
    {
        $el = new FormElementInteger('port', 'Port');
        $el->setMinimum(1)->setMaximum(65535);
        $el->setValue('443');

        $this->assertTrue($el->validate());
    }

    public function testNotNullFailsOnEmpty(): void
    {
        $el = new FormElementInteger('port', 'Port');
        $el->setNotNull();
        $el->setValue('');

        $this->assertFalse($el->validate());
    }

    public function testEmptyAllowedWhenNotNullNotSet(): void
    {
        $el = new FormElementInteger('port', 'Port');
        $el->setValue('');

        $this->assertTrue($el->validate());
    }

    public function testNegativeIntegerAllowed(): void
    {
        $el = new FormElementInteger('offset', 'Offset');
        $el->setValue('-5');

        $this->assertTrue($el->validate());
    }

    public function testTrimsWhitespace(): void
    {
        $el = new FormElementInteger('port', 'Port');
        $el->setValue(' 80 ');

        $this->assertTrue($el->validate());
    }
}
