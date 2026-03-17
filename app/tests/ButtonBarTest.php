<?php

use PHPKnock\ButtonBar;
use PHPUnit\Framework\TestCase;

class ButtonBarTest extends TestCase
{
    public function testAddHtmlRendersSubmitButton(): void
    {
        $bar = new ButtonBar();
        $bar->addHtml('save', 'Save');

        ob_start();
        $bar->display();
        $html = ob_get_clean();

        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('value="Save"', $html);
    }

    public function testAddHtmlButtonHasNoOnclick(): void
    {
        $bar = new ButtonBar();
        $bar->addHtml('save', 'Save');

        ob_start();
        $bar->display();
        $html = ob_get_clean();

        $this->assertStringNotContainsString('onclick', $html);
    }

    public function testAddJsButtonHasOnclick(): void
    {
        $bar = new ButtonBar();
        $bar->addJs('save', 'Save', null, 'doSomething()');

        ob_start();
        $bar->display();
        $html = ob_get_clean();

        $this->assertStringContainsString('onclick="javascript:doSomething()"', $html);
    }

    public function testButtonHasButtonClass(): void
    {
        $bar = new ButtonBar();
        $bar->addHtml('save', 'Save');

        ob_start();
        $bar->display();
        $html = ob_get_clean();

        $this->assertStringContainsString('class="button"', $html);
    }

    public function testMultipleButtonsRendered(): void
    {
        $bar = new ButtonBar();
        $bar->addHtml('save', 'Save');
        $bar->addHtml('cancel', 'Cancel');

        ob_start();
        $bar->display();
        $html = ob_get_clean();

        $this->assertStringContainsString('value="Save"', $html);
        $this->assertStringContainsString('value="Cancel"', $html);
    }

    public function testEmptyBarRendersNothing(): void
    {
        $bar = new ButtonBar();

        ob_start();
        $bar->display();
        $html = ob_get_clean();

        $this->assertSame('', $html);
    }

    public function testHintStoredAsNameAndAlt(): void
    {
        $bar = new ButtonBar();
        $bar->addHtml('save', 'Save', 'Submit the form');

        ob_start();
        $bar->display();
        $html = ob_get_clean();

        $this->assertStringContainsString('Submit the form', $html);
    }
}
