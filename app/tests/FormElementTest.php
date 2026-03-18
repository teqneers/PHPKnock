<?php

use PHPKnock\Form\Element\Text as ElementText;
use PHPUnit\Framework\TestCase;

class FormElementTest extends TestCase
{
    public function testNotNullFailsOnEmptyValue(): void
    {
        $el = new ElementText('field', 'Field');
        $el->setNotNull();
        $el->setValue('');

        $this->assertFalse($el->validate());
        $this->assertTrue($el->error());
    }

    public function testNotNullPassesOnNonEmptyValue(): void
    {
        $el = new ElementText('field', 'Field');
        $el->setNotNull();
        $el->setValue('hello');

        $this->assertTrue($el->validate());
        $this->assertFalse($el->error());
    }

    public function testValidRegExpPassesOnMatch(): void
    {
        $el = new ElementText('field', 'Field');
        $el->setValidRegExp('/^[0-9]+$/');
        $el->setValue('12345');

        $this->assertTrue($el->validate());
        $this->assertFalse($el->error());
    }

    public function testValidRegExpFailsOnMismatch(): void
    {
        $el = new ElementText('field', 'Field');
        $el->setValidRegExp('/^[0-9]+$/');
        $el->setValue('abc');

        $this->assertFalse($el->validate());
        $this->assertTrue($el->error());
    }

    public function testValidRegExpSkipsValidationWhenEmpty(): void
    {
        $el = new ElementText('field', 'Field');
        $el->setValidRegExp('/^[0-9]+$/');
        $el->setValue('');

        // empty + no notNull = valid (regexp not checked on empty values)
        $this->assertTrue($el->validate());
    }

    public function testValidRegExpTrimsBeforeCheck(): void
    {
        // The text element trims the value before regexp matching,
        // so " 123 " should match /^[0-9]+$/
        $el = new ElementText('field', 'Field');
        $el->setValidRegExp('/^[0-9]+$/');
        $el->setValue(' 123 ');

        $this->assertTrue($el->validate());
    }

    public function testDbValueReturnsSetValue(): void
    {
        $el = new ElementText('field', 'Field');
        $el->setDbValue('stored');

        $this->assertSame('stored', $el->dbValue());
    }

    public function testDefaultValueUsedWhenEmpty(): void
    {
        $el = new ElementText('field', 'Field');
        $el->setDefaultValue('default');

        $this->assertSame('default', $el->defaultValue());
    }

    public function testIsEmptyTrueWhenNoValue(): void
    {
        $el = new ElementText('field', 'Field');

        $this->assertTrue($el->isEmpty());
    }

    public function testIsEmptyFalseWhenValueSet(): void
    {
        $el = new ElementText('field', 'Field');
        $el->setValue('hello');

        $this->assertFalse($el->isEmpty());
    }

    public function testHtmlValueEscapesSpecialChars(): void
    {
        $el = new ElementText('field', 'Field');
        $el->setValue('<b>bold</b> & "quoted"');

        $this->assertStringContainsString('&lt;b&gt;', $el->htmlValue());
        $this->assertStringContainsString('&amp;', $el->htmlValue());
        $this->assertStringContainsString('&quot;', $el->htmlValue());
    }

    public function testHtmlFormRowContainsFormGroup(): void
    {
        $el = new ElementText('field', 'Field');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('form-group', $html);
        $this->assertStringContainsString('form-input', $html);
    }

    public function testHtmlFormRowContainsLabel(): void
    {
        $el = new ElementText('myfield', 'My Label');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('My Label', $html);
    }

    public function testHtmlFormRowContainsInputName(): void
    {
        $el = new ElementText('myfield', 'My Label');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('name="data[myfield]"', $html);
    }

    public function testHtmlFormRowShowsErrorMessageOnError(): void
    {
        $el = new ElementText('field', 'Field');
        $el->setNotNull();
        $el->setValue('');
        $el->validate();

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('Invalid value', $html);
        $this->assertStringContainsString('form-error', $html);
    }

    public function testSetDbValueFromArrayExtractsNamedKey(): void
    {
        $el = new ElementText('username', 'Username');
        $el->setDbValue([['username' => 'alice']]);

        $this->assertSame('alice', $el->dbValue());
    }
}
