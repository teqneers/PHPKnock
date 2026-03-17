<?php

use PHPUnit\Framework\TestCase;

class FormElementTest extends TestCase
{
    public function testNotNullFailsOnEmptyValue(): void
    {
        $el = new FormElementText('field', 'Field');
        $el->setNotNull();
        $el->setValue('');

        $this->assertFalse($el->validate());
        $this->assertTrue($el->error());
    }

    public function testNotNullPassesOnNonEmptyValue(): void
    {
        $el = new FormElementText('field', 'Field');
        $el->setNotNull();
        $el->setValue('hello');

        $this->assertTrue($el->validate());
        $this->assertFalse($el->error());
    }

    public function testValidRegExpPassesOnMatch(): void
    {
        $el = new FormElementText('field', 'Field');
        $el->setValidRegExp('/^[0-9]+$/');
        $el->setValue('12345');

        $this->assertTrue($el->validate());
        $this->assertFalse($el->error());
    }

    public function testValidRegExpFailsOnMismatch(): void
    {
        $el = new FormElementText('field', 'Field');
        $el->setValidRegExp('/^[0-9]+$/');
        $el->setValue('abc');

        $this->assertFalse($el->validate());
        $this->assertTrue($el->error());
    }

    public function testValidRegExpSkipsValidationWhenEmpty(): void
    {
        $el = new FormElementText('field', 'Field');
        $el->setValidRegExp('/^[0-9]+$/');
        $el->setValue('');

        // empty + no notNull = valid (regexp not checked on empty values)
        $this->assertTrue($el->validate());
    }

    public function testValidRegExpTrimsBeforeCheck(): void
    {
        // The text element trims the value before regexp matching,
        // so " 123 " should match /^[0-9]+$/
        $el = new FormElementText('field', 'Field');
        $el->setValidRegExp('/^[0-9]+$/');
        $el->setValue(' 123 ');

        $this->assertTrue($el->validate());
    }

    public function testDbValueReturnsSetValue(): void
    {
        $el = new FormElementText('field', 'Field');
        $el->setDbValue('stored');

        $this->assertSame('stored', $el->dbValue());
    }

    public function testDefaultValueUsedWhenEmpty(): void
    {
        $el = new FormElementText('field', 'Field');
        $el->setDefaultValue('default');

        $this->assertSame('default', $el->defaultValue());
    }
}
