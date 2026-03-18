<?php

use PHPKnock\KnockService;
use PHPKnock\Message;
use PHPUnit\Framework\TestCase;

class KnockServiceTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpknock_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up tmp files
        $files = glob($this->tmpDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
    }

    // ── isValidHost ──────────────────────────────────────────────

    public function testIsValidHostAcceptsIPv4(): void
    {
        $this->assertTrue(KnockService::isValidHost('192.168.1.1'));
        $this->assertTrue(KnockService::isValidHost('10.0.0.1'));
        $this->assertTrue(KnockService::isValidHost('255.255.255.255'));
    }

    public function testIsValidHostAcceptsIPv6(): void
    {
        $this->assertTrue(KnockService::isValidHost('::1'));
        $this->assertTrue(KnockService::isValidHost('2001:db8::1'));
    }

    public function testIsValidHostAcceptsHostnames(): void
    {
        $this->assertTrue(KnockService::isValidHost('example.com'));
        $this->assertTrue(KnockService::isValidHost('sub.domain.example.com'));
        $this->assertTrue(KnockService::isValidHost('my-host'));
    }

    public function testIsValidHostRejectsInvalid(): void
    {
        $this->assertFalse(KnockService::isValidHost(''));
        $this->assertFalse(KnockService::isValidHost('not a host'));
        $this->assertFalse(KnockService::isValidHost('-invalid.com'));
        $this->assertFalse(KnockService::isValidHost('invalid-.com'));
        $this->assertFalse(KnockService::isValidHost('host; rm -rf /'));
    }

    public function testIsValidHostRejectsTooLongHostname(): void
    {
        $long = str_repeat('a', 254);
        $this->assertFalse(KnockService::isValidHost($long));
    }

    // ── resolveHosts ─────────────────────────────────────────────

    public function testResolveHostsWithFixedStringConfig(): void
    {
        $hosts = KnockService::resolveHosts('server.example.com', null);
        $this->assertSame(['server.example.com'], $hosts);
    }

    public function testResolveHostsWithSemicolonSeparatedString(): void
    {
        $hosts = KnockService::resolveHosts('host1.com;host2.com', null);
        $this->assertSame(['host1.com', 'host2.com'], $hosts);
    }

    public function testResolveHostsWithDropdownArray(): void
    {
        $config = ['server1.com', 'server2.com'];
        $hosts = KnockService::resolveHosts($config, [0, 1]);
        $this->assertSame(['server1.com', 'server2.com'], $hosts);
    }

    public function testResolveHostsWithDropdownStringKeys(): void
    {
        $config = ['srv1' => 'server1.com', 'srv2' => 'server2.com'];
        $hosts = KnockService::resolveHosts($config, ['srv1']);
        // String keys go through the else branch, so the key itself is the host
        $this->assertSame(['server1.com'], $hosts);
    }

    public function testResolveHostsWithNullConfigFreeText(): void
    {
        $hosts = KnockService::resolveHosts(null, 'myserver.com');
        $this->assertSame(['myserver.com'], $hosts);
    }

    public function testResolveHostsWithNullConfigSemicolonText(): void
    {
        $hosts = KnockService::resolveHosts(null, 'host1.com ; host2.com');
        $this->assertSame(['host1.com', 'host2.com'], $hosts);
    }

    public function testResolveHostsReturnsEmptyOnEmptyFormValue(): void
    {
        $this->assertSame([], KnockService::resolveHosts(null, ''));
        $this->assertSame([], KnockService::resolveHosts(null, null));
        $this->assertSame([], KnockService::resolveHosts(null, []));
    }

    // ── checkRateLimit ───────────────────────────────────────────

    public function testCheckRateLimitAllowsWithinLimit(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');

        $this->assertTrue($svc->checkRateLimit('127.0.0.1', 3, 60));
        $this->assertTrue($svc->checkRateLimit('127.0.0.1', 3, 60));
        $this->assertTrue($svc->checkRateLimit('127.0.0.1', 3, 60));
    }

    public function testCheckRateLimitBlocksExceeded(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');

        $svc->checkRateLimit('127.0.0.1', 2, 60);
        $svc->checkRateLimit('127.0.0.1', 2, 60);
        $this->assertFalse($svc->checkRateLimit('127.0.0.1', 2, 60));
    }

    public function testCheckRateLimitSeparatesIPs(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');

        $svc->checkRateLimit('10.0.0.1', 1, 60);
        // Different IP should still be allowed
        $this->assertTrue($svc->checkRateLimit('10.0.0.2', 1, 60));
    }

    // ── buildCommand ─────────────────────────────────────────────

    public function testBuildCommandBasic(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');

        $cmd = $svc->buildCommand('192.168.1.100', null, null, null, null);

        $this->assertSame('/usr/bin/fwknop', $cmd['cli']);
        $this->assertStringContainsString('-G', $cmd['G']);
        $this->assertStringContainsString('-a', $cmd['a']);
        $this->assertStringContainsString('192.168.1.100', $cmd['a']);
        $this->assertArrayNotHasKey('verbose', $cmd);
        $this->assertArrayNotHasKey('server-port', $cmd);
        $this->assertArrayNotHasKey('A', $cmd);
    }

    public function testBuildCommandWithVerbose(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass', verbose: true);

        $cmd = $svc->buildCommand('192.168.1.1', null, null, null, null);

        $this->assertSame('--verbose', $cmd['verbose']);
    }

    public function testBuildCommandWithConfigServerPort(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');

        $cmd = $svc->buildCommand('192.168.1.1', 62201, '99999', null, null);

        // Config port takes priority over form port
        $this->assertSame('--server-port 62201', $cmd['server-port']);
    }

    public function testBuildCommandWithFormServerPort(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');

        $cmd = $svc->buildCommand('192.168.1.1', null, '55555', null, null);

        $this->assertStringContainsString('--server-port', $cmd['server-port']);
        $this->assertStringContainsString('55555', $cmd['server-port']);
    }

    public function testBuildCommandWithConfigAccessPortList(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');

        $cmd = $svc->buildCommand('192.168.1.1', null, null, 'tcp/22', null);

        $this->assertStringContainsString('-A', $cmd['A']);
        $this->assertStringContainsString('tcp/22', $cmd['A']);
    }

    public function testBuildCommandWithFormAccessPortList(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');

        $cmd = $svc->buildCommand('192.168.1.1', null, null, null, 'tcp/22,udp/53');

        $this->assertStringContainsString('-A', $cmd['A']);
        $this->assertStringContainsString('tcp/22,udp/53', $cmd['A']);
    }

    // ── execute ──────────────────────────────────────────────────

    public function testExecuteRejectsInvalidHost(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');
        $message = new Message();

        $svc->execute('invalid host!', 'key123', ['/usr/bin/fwknop'], $message);

        $this->assertTrue($message->hasErrors());
        $errors = array_values($message->errors());
        $this->assertStringContainsString('Invalid destination', $errors[0]);
    }

    public function testExecuteRejectsHostWithHtmlChars(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');
        $message = new Message();

        $svc->execute('<script>alert(1)</script>', 'key123', ['/usr/bin/fwknop'], $message);

        $this->assertTrue($message->hasErrors());
        $errors = array_values($message->errors());
        // HTML should be escaped in the error message
        $this->assertStringContainsString('&lt;script&gt;', $errors[0]);
    }

    public function testExecuteWritesAndCleansUpPasswordFile(): void
    {
        $passFile = $this->tmpDir . '/.fwknop.pass';
        // Use /usr/bin/true as a command that always succeeds
        $svc = new KnockService('/usr/bin/true', $this->tmpDir, $passFile);
        $message = new Message();

        $svc->execute('127.0.0.1', 'mykey', ['/usr/bin/true'], $message);

        // Password file should be cleaned up after execution
        $this->assertFileDoesNotExist($passFile);
    }

    public function testExecuteSuccessAddsMessage(): void
    {
        $passFile = $this->tmpDir . '/.fwknop.pass';
        $svc = new KnockService('/usr/bin/true', $this->tmpDir, $passFile);
        $message = new Message();

        $svc->execute('127.0.0.1', 'mykey', ['/usr/bin/true'], $message);

        $this->assertTrue($message->hasMessages());
        $messages = array_values($message->messages());
        $this->assertStringContainsString('successfully', $messages[0]);
    }

    public function testExecuteFailureAddsError(): void
    {
        $passFile = $this->tmpDir . '/.fwknop.pass';
        $svc = new KnockService('/usr/bin/false', $this->tmpDir, $passFile);
        $message = new Message();

        $svc->execute('127.0.0.1', 'mykey', ['/usr/bin/false'], $message);

        $this->assertTrue($message->hasErrors());
        $errors = array_values($message->errors());
        $this->assertStringContainsString('Unable to execute fwknop', $errors[0]);
    }
}
