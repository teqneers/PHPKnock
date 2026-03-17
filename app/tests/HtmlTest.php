<?php

use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    public function testArray2attributesSimple(): void
    {
        $result = Html::array2attributes(['class' => 'foo', 'id' => 'bar']);

        $this->assertSame('class="foo" id="bar"', $result);
    }

    public function testArray2attributesEscapesSpecialChars(): void
    {
        $result = Html::array2attributes(['title' => 'a "b" & <c>']);

        $this->assertSame('title="a &quot;b&quot; &amp; &lt;c&gt;"', $result);
    }

    public function testArray2attributesSkipsNullValues(): void
    {
        $result = Html::array2attributes(['class' => 'foo', 'selected' => null]);

        $this->assertSame('class="foo"', $result);
    }

    public function testArray2attributesEmptyArray(): void
    {
        $result = Html::array2attributes([]);

        $this->assertSame('', $result);
    }

    public function testArray2attributesNumericValue(): void
    {
        $result = Html::array2attributes(['size' => 5]);

        $this->assertSame('size="5"', $result);
    }

    public function testHeaderContainsHtml5Doctype(): void
    {
        $html = new Html();
        $html->setTitle('Test');

        $header = $html->header();

        $this->assertStringContainsString('<!DOCTYPE html>', $header);
        $this->assertStringNotContainsString('XHTML', $header);
        $this->assertStringNotContainsString('xmlns', $header);
    }

    public function testHeaderContainsLangAttribute(): void
    {
        $html = new Html();
        $html->setTitle('Test');
        $html->setLanguage('de');

        $header = $html->header();

        $this->assertStringContainsString('lang="de"', $header);
        $this->assertStringNotContainsString('xml:lang', $header);
    }
}
