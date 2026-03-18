<?php

use PHPKnock\Form\Element\Password as ElementPassword;
use PHPUnit\Framework\TestCase;

class FormElementPasswordTest extends TestCase
{
    public function testHtmlFormRowUsesPasswordType(): void
    {
        $el = new ElementPassword('encKey', 'Encryption Key');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('type="password"', $html);
    }

    public function testHtmlFormRowHasNoValueAttribute(): void
    {
        $el = new ElementPassword('encKey', 'Encryption Key');
        $el->setValue('secret');

        $html = $el->htmlFormRow();

        // value attribute must be absent (null → omitted by array2attributes)
        $this->assertDoesNotMatchRegularExpression('/value="[^"]*"/', $html);
    }

    public function testHtmlFormRowContainsLabel(): void
    {
        $el = new ElementPassword('encKey', 'Encryption Key');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('Encryption Key', $html);
    }

    public function testHtmlFormRowContainsNameAttribute(): void
    {
        $el = new ElementPassword('encKey', 'Encryption Key');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('name="data[encKey]"', $html);
    }

    public function testHtmlFormRowRenderedInFormGroup(): void
    {
        $el = new ElementPassword('encKey', 'Encryption Key');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('form-group', $html);
        $this->assertStringContainsString('form-input', $html);
    }

    public function testHintShowsInSpan(): void
    {
        $el = new ElementPassword('encKey', 'Encryption Key');
        $el->setHint('Your pre-shared key');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('cursor:help', $html);
        $this->assertStringContainsString('Your pre-shared key', $html);
    }

    public function testValidatePassesWithValue(): void
    {
        $el = new ElementPassword('encKey', 'Encryption Key');
        $el->setNotNull();
        $el->setValue('secret');

        $this->assertTrue($el->validate());
    }

    public function testValidateFailsOnEmptyWithNotNull(): void
    {
        $el = new ElementPassword('encKey', 'Encryption Key');
        $el->setNotNull();
        $el->setValue('');

        $this->assertFalse($el->validate());
    }
}
