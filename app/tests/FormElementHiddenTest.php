<?php

use PHPKnock\Form\Element\Hidden as ElementHidden;
use PHPUnit\Framework\TestCase;

class FormElementHiddenTest extends TestCase
{
    public function testHtmlFormRowUsesHiddenType(): void
    {
        $el = new ElementHidden('doKnock');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('type="hidden"', $html);
    }

    public function testHtmlFormRowIsNotInsideTableRow(): void
    {
        $el = new ElementHidden('doKnock');

        $html = $el->htmlFormRow();

        $this->assertStringNotContainsString('<tr>', $html);
        $this->assertStringNotContainsString('</tr>', $html);
    }

    public function testHtmlFormRowContainsNameAttribute(): void
    {
        $el = new ElementHidden('doKnock');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('name="data[doKnock]"', $html);
    }

    public function testHtmlFormRowReflectsValue(): void
    {
        $el = new ElementHidden('doKnock');
        $el->setValue('1');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('value="1"', $html);
    }

    public function testDefaultValueUsedWhenEmpty(): void
    {
        $el = new ElementHidden('doKnock');
        $el->setDefaultValue('1');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('value="1"', $html);
    }

    public function testValueIsHtmlEscaped(): void
    {
        $el = new ElementHidden('field');
        $el->setValue('<script>');

        $html = $el->htmlFormRow();

        // Raw tag must never appear literally in the output
        $this->assertStringNotContainsString('<script>', $html);
    }
}
