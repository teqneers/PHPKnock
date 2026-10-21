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
        // Clean up tmp files (including hidden files like .fwknop.pass)
        $files = glob($this->tmpDir . '/{,.}*', GLOB_BRACE);
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
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
        // String keys are the actual hostnames; values are just display labels
        $this->assertSame(['srv1'], $hosts);
    }

    public function testResolveHostsWithDropdownIpKeysAndLabels(): void
    {
        $config = ['10.0.0.1' => 'server (prod)', '10.0.0.2' => 'server (dev)'];
        $hosts = KnockService::resolveHosts($config, ['10.0.0.1', '10.0.0.2']);
        $this->assertSame(['10.0.0.1', '10.0.0.2'], $hosts);
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

    public function testResolveHostsIgnoresValuesNotInDropdown(): void
    {
        $config = ['10.0.0.1' => 'prod', 'server2.com'];
        $hosts = KnockService::resolveHosts($config, ['attacker.example.com', '10.0.0.1', '5', '0']);
        $this->assertSame(['10.0.0.1', 'server2.com'], $hosts);
    }

    // ── clientIp ─────────────────────────────────────────────────

    public function testClientIpIgnoresProxyHeadersWithoutTrustedProxies(): void
    {
        $server = [
            'REMOTE_ADDR'          => '203.0.113.5',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
            'HTTP_X_REAL_IP'       => '5.6.7.8',
        ];
        $this->assertSame('203.0.113.5', KnockService::clientIp($server, []));
    }

    public function testClientIpIgnoresProxyHeadersFromUntrustedPeer(): void
    {
        $server = ['REMOTE_ADDR' => '203.0.113.5', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4'];
        $this->assertSame('203.0.113.5', KnockService::clientIp($server, ['10.0.0.0/8']));
    }

    public function testClientIpUsesRightmostUntrustedForwardedAddress(): void
    {
        // client spoofed "6.6.6.6", the real client 1.2.3.4 was appended by the trusted proxies
        $server = ['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_FOR' => '6.6.6.6, 1.2.3.4, 10.0.0.1'];
        $this->assertSame('1.2.3.4', KnockService::clientIp($server, ['10.0.0.0/8']));
    }

    public function testClientIpFallsBackToRealIpHeader(): void
    {
        $server = ['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_REAL_IP' => '1.2.3.4'];
        $this->assertSame('1.2.3.4', KnockService::clientIp($server, ['10.0.0.2']));
    }

    public function testClientIpDefaultsWithoutRemoteAddr(): void
    {
        $this->assertSame('0.0.0.0', KnockService::clientIp([], []));
    }

    public function testIsTrustedProxyMatchesIpsAndCidrs(): void
    {
        $proxies = ['127.0.0.1', '172.16.0.0/12', '10.1.2.0/23', 'fd00::/8'];
        $this->assertTrue(KnockService::isTrustedProxy('127.0.0.1', $proxies));
        $this->assertTrue(KnockService::isTrustedProxy('172.31.255.1', $proxies));
        $this->assertTrue(KnockService::isTrustedProxy('10.1.3.7', $proxies));
        $this->assertTrue(KnockService::isTrustedProxy('fd12::1', $proxies));
        $this->assertFalse(KnockService::isTrustedProxy('127.0.0.2', $proxies));
        $this->assertFalse(KnockService::isTrustedProxy('172.32.0.1', $proxies));
        $this->assertFalse(KnockService::isTrustedProxy('10.1.4.1', $proxies));
        $this->assertFalse(KnockService::isTrustedProxy('fe80::1', $proxies));
        $this->assertFalse(KnockService::isTrustedProxy('not-an-ip', $proxies));
    }

    // ── isValidHttpHost ──────────────────────────────────────────

    public function testIsValidHttpHost(): void
    {
        $this->assertTrue(KnockService::isValidHttpHost('knock.example.com'));
        $this->assertTrue(KnockService::isValidHttpHost('knock.example.com:8443'));
        $this->assertTrue(KnockService::isValidHttpHost('10.0.0.1:80'));
        $this->assertTrue(KnockService::isValidHttpHost('[::1]:8080'));
        $this->assertFalse(KnockService::isValidHttpHost('evil.com/path'));
        $this->assertFalse(KnockService::isValidHttpHost("evil.com\r\nX-Injected: 1"));
        $this->assertFalse(KnockService::isValidHttpHost('example.com:99999'));
        $this->assertFalse(KnockService::isValidHttpHost('user@example.com'));
        $this->assertFalse(KnockService::isValidHttpHost(''));
    }

    // ── auditLog ─────────────────────────────────────────────────

    public function testAuditLogEscapesControlCharacters(): void
    {
        $log = $this->tmpDir . '/audit.log';
        $svc = new KnockService('/usr/bin/true', $this->tmpDir, $this->tmpDir . '/.pass', auditLogPath: $log);

        $svc->auditLog('1.2.3.4', '1.2.3.4', "evil\n[2026-01-01] remote=forged", 'invalid');

        $lines = file($log);
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('dst=evil\n[2026-01-01] remote=forged', $lines[0]);
    }

    // ── buildCommand ─────────────────────────────────────────────

    public function testBuildCommandBasic(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');

        $cmd = $svc->buildCommand('192.168.1.100', null, null, null, null);

        $this->assertSame('/usr/bin/fwknop', $cmd['cli']);
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
        $this->assertSame([], glob($passFile . '*'));
    }

    public function testExecuteUsesUniquePrivatePasswordFile(): void
    {
        $passFile = $this->tmpDir . '/.fwknop.pass';
        $copyFile = $this->tmpDir . '/.pass_copy';
        // a stale file at the configured path must not be used or touched
        file_put_contents($passFile, 'stale');

        $script = $this->tmpDir . '/copy_pass.sh';
        file_put_contents($script, "#!/bin/sh\necho \"\$2\" > '$copyFile'\n");
        chmod($script, 0755);

        $svc = new KnockService($script, $this->tmpDir, $passFile);
        $svc->execute('127.0.0.1', 'mykey', [$script], new Message());

        $usedFile = trim((string)file_get_contents($copyFile));
        $this->assertStringStartsWith('.fwknop.pass.', basename($usedFile));
        $this->assertNotSame($passFile, $usedFile);
        $this->assertFileDoesNotExist($usedFile);
        $this->assertSame('stale', file_get_contents($passFile));
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

    // ── HMAC digest type validation ─────────────────────────────

    public function testIsValidHmacDigestTypeAcceptsValid(): void
    {
        foreach (['md5', 'sha1', 'sha256', 'sha384', 'sha512'] as $type) {
            $this->assertTrue(KnockService::isValidHmacDigestType($type), "Expected '$type' to be valid");
        }
    }

    public function testIsValidHmacDigestTypeRejectsInvalid(): void
    {
        $this->assertFalse(KnockService::isValidHmacDigestType(''));
        $this->assertFalse(KnockService::isValidHmacDigestType('SHA256'));
        $this->assertFalse(KnockService::isValidHmacDigestType('sha-256'));
        $this->assertFalse(KnockService::isValidHmacDigestType('aes'));
    }

    // ── buildCommand with HMAC ──────────────────────────────────

    public function testBuildCommandWithHmacDigestType(): void
    {
        $svc = new KnockService(
            '/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass',
            hmacDigestType: 'sha256',
        );

        $cmd = $svc->buildCommand('192.168.1.1', null, null, null, null);

        $this->assertArrayHasKey('hmac-digest-type', $cmd);
        $this->assertStringContainsString('--hmac-digest-type', $cmd['hmac-digest-type']);
        $this->assertStringContainsString('sha256', $cmd['hmac-digest-type']);
    }

    public function testBuildCommandWithoutHmacOmitsDigestType(): void
    {
        $svc = new KnockService('/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass');

        $cmd = $svc->buildCommand('192.168.1.1', null, null, null, null);

        $this->assertArrayNotHasKey('hmac-digest-type', $cmd);
    }

    // ── buildCommand with GPG ───────────────────────────────────

    public function testBuildCommandGpgMode(): void
    {
        $svc = new KnockService(
            '/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass',
            encryptionMode: 'gpg',
            gpgRecipientKey: 'ABCD1234',
            gpgSignerKey: 'EFGH5678',
        );

        $cmd = $svc->buildCommand('192.168.1.1', null, null, null, null);

        $this->assertArrayHasKey('gpg-recipient-key', $cmd);
        $this->assertStringContainsString('--gpg-recipient-key', $cmd['gpg-recipient-key']);
        $this->assertStringContainsString('ABCD1234', $cmd['gpg-recipient-key']);
        $this->assertArrayHasKey('gpg-signer-key', $cmd);
        $this->assertStringContainsString('--gpg-signer-key', $cmd['gpg-signer-key']);
        $this->assertStringContainsString('EFGH5678', $cmd['gpg-signer-key']);
    }

    public function testBuildCommandGpgModeWithHomeDir(): void
    {
        $svc = new KnockService(
            '/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass',
            encryptionMode: 'gpg',
            gpgRecipientKey: 'ABCD1234',
            gpgSignerKey: 'EFGH5678',
            gpgHomeDir: '/home/user/.gnupg',
        );

        $cmd = $svc->buildCommand('192.168.1.1', null, null, null, null);

        $this->assertArrayHasKey('gpg-home-dir', $cmd);
        $this->assertStringContainsString('--gpg-home-dir', $cmd['gpg-home-dir']);
        $this->assertStringContainsString('/home/user/.gnupg', $cmd['gpg-home-dir']);
    }

    public function testBuildCommandGpgModeWithoutHomeDir(): void
    {
        $svc = new KnockService(
            '/usr/bin/fwknop', $this->tmpDir, $this->tmpDir . '/.pass',
            encryptionMode: 'gpg',
            gpgRecipientKey: 'ABCD1234',
            gpgSignerKey: 'EFGH5678',
        );

        $cmd = $svc->buildCommand('192.168.1.1', null, null, null, null);

        $this->assertArrayNotHasKey('gpg-home-dir', $cmd);
    }

    // ── execute password file format ────────────────────────────

    public function testExecutePasswordFileFormatWithHmac(): void
    {
        $passFile = $this->tmpDir . '/.fwknop.pass';
        $copyFile = $this->tmpDir . '/.pass_copy';

        // Create a shell script that copies the pass file before it's deleted
        $script = $this->tmpDir . '/copy_pass.sh';
        // fwknop is called as "<cli> -G <passfile> -D <target>"
        file_put_contents($script, "#!/bin/sh\ncp \"\$2\" '$copyFile'\nstat -c %a \"\$2\" > '$copyFile.mode' 2>/dev/null || stat -f %Lp \"\$2\" > '$copyFile.mode'\n");
        chmod($script, 0755);

        $svc = new KnockService(
            $script, $this->tmpDir, $passFile,
            encryptionMode: 'rijndael',
            hmacDigestType: 'sha256',
        );
        $message = new Message();

        $svc->execute('127.0.0.1', 'mykey', [$script], $message, hmacKey: 'myhmac');

        $this->assertFileExists($copyFile);
        $this->assertSame('127.0.0.1:mykey:myhmac', file_get_contents($copyFile));
    }

    public function testExecutePasswordFileFormatWithoutHmac(): void
    {
        $passFile = $this->tmpDir . '/.fwknop.pass';
        $copyFile = $this->tmpDir . '/.pass_copy';

        $script = $this->tmpDir . '/copy_pass.sh';
        // fwknop is called as "<cli> -G <passfile> -D <target>"
        file_put_contents($script, "#!/bin/sh\ncp \"\$2\" '$copyFile'\nstat -c %a \"\$2\" > '$copyFile.mode' 2>/dev/null || stat -f %Lp \"\$2\" > '$copyFile.mode'\n");
        chmod($script, 0755);

        $svc = new KnockService($script, $this->tmpDir, $passFile);
        $message = new Message();

        $svc->execute('127.0.0.1', 'mykey', [$script], $message);

        $this->assertFileExists($copyFile);
        $this->assertSame('127.0.0.1:mykey', file_get_contents($copyFile));
        $this->assertSame('600', trim((string)file_get_contents($copyFile . '.mode')));
    }

    public function testExecuteGpgModeIgnoresHmacKey(): void
    {
        $passFile = $this->tmpDir . '/.fwknop.pass';
        $copyFile = $this->tmpDir . '/.pass_copy';

        $script = $this->tmpDir . '/copy_pass.sh';
        // fwknop is called as "<cli> -G <passfile> -D <target>"
        file_put_contents($script, "#!/bin/sh\ncp \"\$2\" '$copyFile'\nstat -c %a \"\$2\" > '$copyFile.mode' 2>/dev/null || stat -f %Lp \"\$2\" > '$copyFile.mode'\n");
        chmod($script, 0755);

        $svc = new KnockService(
            $script, $this->tmpDir, $passFile,
            encryptionMode: 'gpg',
            gpgRecipientKey: 'ABCD1234',
            gpgSignerKey: 'EFGH5678',
        );
        $message = new Message();

        // Even with hmacKey passed, GPG mode writes 2-field format
        $svc->execute('127.0.0.1', 'mykey', [$script], $message, hmacKey: 'myhmac');

        $this->assertFileExists($copyFile);
        $this->assertSame('127.0.0.1:mykey', file_get_contents($copyFile));
    }
}
