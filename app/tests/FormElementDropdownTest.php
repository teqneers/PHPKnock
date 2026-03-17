<?php

use PHPUnit\Framework\TestCase;

class FormElementDropdownTest extends TestCase
{
    private array $options = [
        'us' => 'United States',
        'de' => 'Germany',
        'fr' => 'France',
    ];

    public function testSingleValueSelection(): void
    {
        $el = new FormElementDropdown('country', 'Country', $this->options);
        $el->setValue('de');

        $this->assertSame('de', $el->value());
    }

    public function testMultipleValueSelection(): void
    {
        $el = new FormElementDropdown('country', 'Country', $this->options);
        $el->setIsMultiple();
        $el->setValue(['us', 'de']);

        $this->assertSame(['us', 'de'], $el->value());
    }

    public function testNotNullFailsOnEmpty(): void
    {
        $el = new FormElementDropdown('country', 'Country', $this->options);
        $el->setNotNull();
        $el->setValue([]);

        $this->assertFalse($el->validate());
    }

    public function testNotNullPassesWithValue(): void
    {
        $el = new FormElementDropdown('country', 'Country', $this->options);
        $el->setNotNull();
        $el->setValue('us');

        $this->assertTrue($el->validate());
    }

    public function testHtmlFormRowNoSelfClosingSelect(): void
    {
        $el = new FormElementDropdown('country', 'Country', $this->options);
        $el->setValue('de');

        $html = $el->htmlFormRow();

        // Must have a proper closing tag
        $this->assertStringContainsString('</select>', $html);
        // Must not use self-closing syntax
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*\/>/', $html);
    }

    public function testHtmlFormRowContainsOptions(): void
    {
        $el = new FormElementDropdown('country', 'Country', $this->options);
        $el->setValue('de');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('United States', $html);
        $this->assertStringContainsString('Germany', $html);
        $this->assertStringContainsString('France', $html);
    }

    public function testHtmlFormRowMarksSelectedOption(): void
    {
        $el = new FormElementDropdown('country', 'Country', $this->options);
        $el->setValue('de');

        $html = $el->htmlFormRow();

        $this->assertMatchesRegularExpression('/value="de"[^>]*selected|selected[^>]*value="de"/', $html);
    }

    public function testOptionsSpecialCharsEscaped(): void
    {
        $el = new FormElementDropdown('test', 'Test', ['<key>' => 'Val & "ue"']);
        $el->setValue('');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('&lt;key&gt;', $html);
        $this->assertStringContainsString('Val &amp; &quot;ue&quot;', $html);
    }

    public function testIsEmptyTrueWhenNoValue(): void
    {
        $el = new FormElementDropdown('country', 'Country', $this->options);
        $el->setValue([]);

        $this->assertTrue($el->isEmpty());
    }

    public function testIsEmptyFalseWhenHasValue(): void
    {
        $el = new FormElementDropdown('country', 'Country', $this->options);
        $el->setValue('us');

        $this->assertFalse($el->isEmpty());
    }
}
