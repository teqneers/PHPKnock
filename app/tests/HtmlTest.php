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

    public function testHeaderContainsTitle(): void
    {
        $html = new Html();
        $html->setTitle('My Page');

        $header = $html->header();

        $this->assertStringContainsString('<title>My Page</title>', $header);
    }

    public function testHeaderContainsStylesheetLink(): void
    {
        $html = new Html();
        $html->setTitle('Test');
        $html->addStyleSheet('static/default.css');

        $header = $html->header();

        $this->assertStringContainsString('static/default.css', $header);
        $this->assertStringContainsString('rel="stylesheet"', $header);
    }

    public function testHeaderContainsFavicon(): void
    {
        $html = new Html();
        $html->setTitle('Test');
        $html->setFavicon('static/favicon.ico');

        $header = $html->header();

        $this->assertStringContainsString('static/favicon.ico', $header);
    }

    public function testFooterContainsClosingHtmlTag(): void
    {
        $html = new Html();

        $this->assertStringContainsString('</html>', $html->footer());
    }

    public function testArray2attributesRecursiveArray(): void
    {
        // Nested array value should itself be rendered as attributes
        $result = Html::array2attributes(['data' => ['foo' => 'bar']]);

        $this->assertStringContainsString('foo="bar"', $result);
    }

    public function testLangAttributeIsEscaped(): void
    {
        $html = new Html();
        $html->setTitle('Test');
        $html->setLanguage('de"evil');

        $header = $html->header();

        $this->assertStringNotContainsString('lang="de"evil"', $header);
        $this->assertStringContainsString('&quot;', $header);
    }
}
