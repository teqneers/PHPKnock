<?php

use PHPKnock\Form;
use PHPKnock\Form\Element\Text as ElementText;
use PHPKnock\Form\Element\Dropdown as ElementDropdown;
use PHPKnock\Form\Element\Hidden as ElementHidden;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testFactoryCreatesTextElement(): void
    {
        $form = new Form('test');
        $el   = $form->factory('Text', 'username', 'Username');

        $this->assertInstanceOf(ElementText::class, $el);
        $this->assertSame('username', $el->name());
    }

    public function testFactoryCreatesDropdownElement(): void
    {
        $form = new Form('test');
        $el   = $form->factory('Dropdown', 'country', 'Country', ['us' => 'USA']);

        $this->assertInstanceOf(ElementDropdown::class, $el);
    }

    public function testFactoryThrowsOnUnknownType(): void
    {
        $form = new Form('test');

        $this->expectException(\InvalidArgumentException::class);
        $form->factory('NonExistent', 'field');
    }

    public function testElementReturnsCreatedElement(): void
    {
        $form = new Form('test');
        $form->factory('Text', 'email', 'Email');

        $this->assertInstanceOf(ElementText::class, $form->element('email'));
    }

    public function testElementReturnsNullForUnknown(): void
    {
        $form = new Form('test');

        $this->assertNull($form->element('missing'));
    }

    public function testValidateReturnsTrueWhenAllValid(): void
    {
        $form = new Form('test');
        $form->factory('Text', 'name', 'Name')->setNotNull()->setValue('Alice');

        $this->assertTrue($form->validate());
    }

    public function testValidateReturnsFalseOnInvalidElement(): void
    {
        $form = new Form('test');
        $form->factory('Text', 'name', 'Name')->setNotNull()->setValue('');

        $this->assertFalse($form->validate());
    }

    public function testDbValuesReturnsAllValues(): void
    {
        $form = new Form('test');
        $form->factory('Text', 'first', 'First')->setValue('Alice');
        $form->factory('Text', 'last', 'Last')->setValue('Smith');

        $values = $form->dbValues();

        $this->assertSame('Alice', $values['first']);
        $this->assertSame('Smith', $values['last']);
    }

    public function testHtmlFormBodyPutsHiddenFieldsBeforeFormFields(): void
    {
        $form = new Form('test');
        $form->factory('Text', 'name', 'Name')->setValue('Alice');
        $form->factory('Hidden', 'token')->setValue('abc');

        $html = $form->htmlFormBody();

        // hidden input must appear before the form-fields div
        $this->assertLessThan(strpos($html, 'form-fields'), strpos($html, 'type="hidden"'));
    }

    public function testHtmlFormBodyDoesNotPutHiddenInsideFormFields(): void
    {
        $form = new Form('test');
        $form->factory('Hidden', 'token')->setValue('abc');

        $html = $form->htmlFormBody();

        // The hidden input should not be inside the form-fields div
        $formFieldsStart = strpos($html, 'form-fields');
        $hiddenPos       = strpos($html, 'type="hidden"');
        $this->assertLessThan($formFieldsStart, $hiddenPos);
    }

    public function testHtmlFormHeaderContainsFormTag(): void
    {
        $form = new Form('knock');

        $html = $form->htmlFormHeader();

        $this->assertStringContainsString('<form ', $html);
        $this->assertStringContainsString('name="knock"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testHtmlFormFooterContainsClosingTag(): void
    {
        $form = new Form('test');

        $this->assertStringContainsString('</form>', $form->htmlFormFooter());
    }

    public function testAttributeIsCaseInsensitive(): void
    {
        $form = new Form('test');
        $form->setAttribute('Action', '/submit');

        $this->assertSame('/submit', $form->attribute('action'));
        $this->assertSame('/submit', $form->attribute('ACTION'));
    }

    public function testAttributeReturnsNullForUnknown(): void
    {
        $form = new Form('test');

        $this->assertNull($form->attribute('nonexistent'));
    }

    public function testAttributeWithNullKeyReturnsArray(): void
    {
        $form = new Form('test');

        $this->assertIsArray($form->attribute());
        $this->assertArrayHasKey('method', $form->attribute());
    }
}
