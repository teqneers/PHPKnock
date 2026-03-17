<?php

use PHPKnock\Form\Element\Dropdown as ElementDropdown;
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
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setValue('de');

        $this->assertSame('de', $el->value());
    }

    public function testMultipleValueSelection(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setIsMultiple();
        $el->setValue(['us', 'de']);

        $this->assertSame(['us', 'de'], $el->value());
    }

    public function testNotNullFailsOnEmpty(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setNotNull();
        $el->setValue([]);

        $this->assertFalse($el->validate());
    }

    public function testNotNullPassesWithValue(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setNotNull();
        $el->setValue('us');

        $this->assertTrue($el->validate());
    }

    public function testHtmlFormRowNoSelfClosingSelect(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setValue('de');

        $html = $el->htmlFormRow();

        // Must have a proper closing tag
        $this->assertStringContainsString('</select>', $html);
        // Must not use self-closing syntax
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*\/>/', $html);
    }

    public function testHtmlFormRowContainsOptions(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setValue('de');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('United States', $html);
        $this->assertStringContainsString('Germany', $html);
        $this->assertStringContainsString('France', $html);
    }

    public function testHtmlFormRowMarksSelectedOption(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setValue('de');

        $html = $el->htmlFormRow();

        $this->assertMatchesRegularExpression('/value="de"[^>]*selected|selected[^>]*value="de"/', $html);
    }

    public function testOptionsSpecialCharsEscaped(): void
    {
        $el = new ElementDropdown('test', 'Test', ['<key>' => 'Val & "ue"']);
        $el->setValue('');

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('&lt;key&gt;', $html);
        $this->assertStringContainsString('Val &amp; &quot;ue&quot;', $html);
    }

    public function testIsEmptyTrueWhenNoValue(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setValue([]);

        $this->assertTrue($el->isEmpty());
    }

    public function testIsEmptyFalseWhenHasValue(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setValue('us');

        $this->assertFalse($el->isEmpty());
    }

    public function testSetDbValueFlatArray(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setDbValue(['us', 'de']);

        $this->assertSame(['us', 'de'], $el->value());
    }

    public function testSetDbValueMatrixExtractsColumn(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setDbValue([
            ['country' => 'us'],
            ['country' => 'de'],
        ]);

        $this->assertSame(['us', 'de'], $el->value());
    }

    public function testValidateRemovesEmptyStringFromArray(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setValue(['us', '', 'de']);

        $el->validate();

        // after validate the empty string should be gone
        $this->assertNotContains('', (array)$el->value());
    }

    public function testValidateConvertsEmptyStringValueToNull(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setValue('');

        $el->validate();

        $this->assertTrue($el->isEmpty());
    }

    public function testSetIsMultipleAutoSetsMaximumSize(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setIsMultiple(true);

        $this->assertNotNull($el->maximumSize());
        $this->assertGreaterThan(1, $el->maximumSize());
    }

    public function testSetSizeOneDisablesMultiple(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setIsMultiple(true);
        $el->setSize(1);

        $this->assertFalse($el->isMultiple());
    }

    public function testHtmlFormRowMultipleSelectHasArrayName(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setIsMultiple(true);

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('name="data[country][]"', $html);
    }

    public function testDynamicSizeCappedAtMaximumSize(): void
    {
        $el = new ElementDropdown('country', 'Country', $this->options);
        $el->setMaximumSize(2); // 3 options, capped at 2

        $html = $el->htmlFormRow();

        $this->assertStringContainsString('size="2"', $html);
    }

    public function testZeroValueOptionNotMistakenlSelected(): void
    {
        $el = new ElementDropdown('num', 'Number', ['0' => 'Zero', '1' => 'One']);
        $el->setValue('1');

        $html = $el->htmlFormRow();

        // '0' must NOT be selected
        $this->assertDoesNotMatchRegularExpression('/value="0"[^>]*selected/', $html);
        // '1' must be selected
        $this->assertMatchesRegularExpression('/value="1"[^>]*selected|selected[^>]*value="1"/', $html);
    }
}
