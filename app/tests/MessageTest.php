<?php

use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private Message $message;

    protected function setUp(): void
    {
        $this->message = new Message();
    }

    // Bitflag constants (mirrors protected consts in Message)
    private const ERROR   = 1;
    private const WARNING = 2;
    private const NOTICE  = 4;
    private const MESSAGE = 8;
    private const ALL     = 15;

    public function testGetReturnsOnlyErrors(): void
    {
        $this->message->addError('err');
        $this->message->addWarning('warn');
        $this->message->addNotice('note');
        $this->message->addMessage('msg');

        $result = $this->message->get(self::ERROR, false);

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('warning', $result);
        $this->assertArrayNotHasKey('notice', $result);
        $this->assertArrayNotHasKey('message', $result);
    }

    public function testGetReturnsOnlyWarnings(): void
    {
        $this->message->addError('err');
        $this->message->addWarning('warn');

        $result = $this->message->get(self::WARNING, false);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertArrayHasKey('warning', $result);
    }

    public function testGetReturnsOnlyNotices(): void
    {
        $this->message->addNotice('note');
        $this->message->addMessage('msg');

        $result = $this->message->get(self::NOTICE, false);

        $this->assertArrayHasKey('notice', $result);
        $this->assertArrayNotHasKey('message', $result);
    }

    public function testGetReturnsOnlyMessages(): void
    {
        $this->message->addError('err');
        $this->message->addMessage('msg');

        $result = $this->message->get(self::MESSAGE, false);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testGetReturnsCombinedFlags(): void
    {
        $this->message->addError('err');
        $this->message->addWarning('warn');
        $this->message->addNotice('note');

        $result = $this->message->get(self::ERROR | self::WARNING, false);

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayNotHasKey('notice', $result);
    }

    public function testGetAllReturnsAllTypes(): void
    {
        $this->message->addError('err');
        $this->message->addWarning('warn');
        $this->message->addNotice('note');
        $this->message->addMessage('msg');

        $result = $this->message->get(self::ALL, false);

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayHasKey('notice', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testDuplicateMessagesAreDeduped(): void
    {
        $this->message->addMessage('hello');
        $this->message->addMessage('hello');

        $this->assertCount(1, $this->message->messages(false));
    }

    public function testDuplicateErrorsAreDeduped(): void
    {
        $this->message->addError('oops');
        $this->message->addError('oops');

        $this->assertCount(1, $this->message->errors(false));
    }

    public function testDuplicateWarningsAreDeduped(): void
    {
        $this->message->addWarning('careful');
        $this->message->addWarning('careful');

        $this->assertCount(1, $this->message->warnings(false));
    }

    public function testDuplicateNoticesAreDeduped(): void
    {
        $this->message->addNotice('fyi');
        $this->message->addNotice('fyi');

        $this->assertCount(1, $this->message->notices(false));
    }

    public function testAddArrayOfMessages(): void
    {
        $this->message->addMessage(['one', 'two', 'three']);

        $this->assertCount(3, $this->message->messages(false));
    }

    public function testClearMessages(): void
    {
        $this->message->addMessage('msg');
        $this->message->clearMessages();

        $this->assertFalse($this->message->hasMessages());
    }

    public function testClearErrors(): void
    {
        $this->message->addError('err');
        $this->message->clearErrors();

        $this->assertFalse($this->message->hasErrors());
    }

    public function testClearWarnings(): void
    {
        $this->message->addWarning('warn');
        $this->message->clearWarnings();

        $this->assertFalse($this->message->hasWarnings());
    }

    public function testClearNotices(): void
    {
        $this->message->addNotice('note');
        $this->message->clearNotices();

        $this->assertFalse($this->message->hasNotices());
    }

    public function testClearAll(): void
    {
        $this->message->addError('err');
        $this->message->addWarning('warn');
        $this->message->addNotice('note');
        $this->message->addMessage('msg');

        $this->message->clear();

        $this->assertFalse($this->message->hasErrors());
        $this->assertFalse($this->message->hasWarnings());
        $this->assertFalse($this->message->hasNotices());
        $this->assertFalse($this->message->hasMessages());
    }

    public function testGetCleanupRemovesMessages(): void
    {
        $this->message->addError('err');
        $this->message->get(self::ALL, true);

        $this->assertFalse($this->message->hasErrors());
    }

    public function testHasMessagesReturnsTrueWhenPresent(): void
    {
        $this->message->addMessage('hello');

        $this->assertTrue($this->message->hasMessages());
    }

    public function testHasMessagesReturnsFalseWhenEmpty(): void
    {
        $this->assertFalse($this->message->hasMessages());
    }

    public function testHasErrorsReturnsTrueWhenPresent(): void
    {
        $this->message->addError('oops');

        $this->assertTrue($this->message->hasErrors());
    }

    public function testHasErrorsReturnsFalseWhenEmpty(): void
    {
        $this->assertFalse($this->message->hasErrors());
    }

    public function testHasWarningsReturnsTrueWhenPresent(): void
    {
        $this->message->addWarning('careful');

        $this->assertTrue($this->message->hasWarnings());
    }

    public function testHasWarningsReturnsFalseWhenEmpty(): void
    {
        $this->assertFalse($this->message->hasWarnings());
    }

    public function testHasNoticesReturnsTrueWhenPresent(): void
    {
        $this->message->addNotice('fyi');

        $this->assertTrue($this->message->hasNotices());
    }

    public function testHasNoticesReturnsFalseWhenEmpty(): void
    {
        $this->assertFalse($this->message->hasNotices());
    }

    public function testEmptyMessageIsNotAdded(): void
    {
        $this->message->addMessage('');

        $this->assertFalse($this->message->hasMessages());
    }

    public function testGetWithNullDefaultsToAll(): void
    {
        $this->message->addError('err');
        $this->message->addMessage('msg');

        $result = $this->message->get(null, false);

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('message', $result);
    }
}
