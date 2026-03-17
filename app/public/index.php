<?php
/*
 * Copyright (C) 2012-2026 by TEQneers GmbH & Co. KG
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Main application entry script
 *
 * This script will display all necessary form elements to configure
 * port knocking or Single Package Authorization SPA. It will trigger
 * fwknop client on console to actually initiate port knocking.
 *
 * @author         Oliver G. Mueller <mueller@teqneers.de>
 * @package        PHPKnock
 * @subpackage     base
 * @copyright      Copyright (C) 2003-2026 TEQneers GmbH & Co. KG. All rights reserved.
 */

#############################################################################
###	DEFAULT CONFIG VALUES
#############################################################################
const CLI_CALL = (PHP_SAPI === 'cli');

// basic path settings
$PATH_FS_APPLICATION = dirname(__DIR__);
$PATH_FS_TMP         = ($v = getenv('PHPKNOCK_PATH_FS_TMP')) !== false ? $v : $PATH_FS_APPLICATION . '/tmp';
$PATH_APPLICATION    = ($v = getenv('PHPKNOCK_PATH_APPLICATION')) !== false ? $v : '/knock';
$USE_HTTPS_ONLY      = ($v = getenv('PHPKNOCK_USE_HTTPS_ONLY')) !== false ?
    filter_var($v, FILTER_VALIDATE_BOOLEAN) : false;
$ERRORS_VERBOSE      = ($v = getenv('PHPKNOCK_ERRORS_VERBOSE')) !== false ?
    filter_var($v, FILTER_VALIDATE_BOOLEAN) : false;
$ERRORS_LOG          = ($v = getenv('PHPKNOCK_ERRORS_LOG')) !== false ? $v : null;
$ENCRYPTION_KEY      = ($v = getenv('PHPKNOCK_ENCRYPTION_KEY')) !== false ? $v : null;
$FWKNOP_CLI          = ($v = getenv('PHPKNOCK_FWKNOP_CLI')) !== false ? $v : '/usr/bin/fwknop';
$SERVER_PORT         = ($v = getenv('PHPKNOCK_SERVER_PORT')) !== false ? (int)$v : 62201;
$ACCESS_PORT_LIST    = ($v = getenv('PHPKNOCK_ACCESS_PORT_LIST')) !== false ? $v : 'tcp/22';
$DESTINATION         = null;
if (($v = getenv('PHPKNOCK_DESTINATION')) !== false) {
    try {
        $DESTINATION = json_decode($v, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        $DESTINATION = $v;
    }
}
$RATE_LIMIT          = ($v = getenv('PHPKNOCK_RATE_LIMIT'))  !== false ? (int)$v : 10;
$RATE_WINDOW         = ($v = getenv('PHPKNOCK_RATE_WINDOW')) !== false ? (int)$v : 60;

// local_config.php is optional — overrides any value set above
$pathLocalConfig = __DIR__ . '/../local_config.php';
if (file_exists($pathLocalConfig)) {
    require $pathLocalConfig;
}

require __DIR__ . '/../functions.php';

#############################################################################
###	LOAD DEFAULT FUNCTIONS AND CLASSES
#############################################################################
// Generate all parts of SoFi URL (protocol, domain, path, ...)
if ($USE_HTTPS_ONLY || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
    $URL_SCHEME = 'https';
} else {
    $URL_SCHEME = 'http';
}
if (empty($URL_DOMAIN)) {
    $URL_DOMAIN = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : die('Unknown URL.');
} // if
$URL = $URL_SCHEME . '://' . $URL_DOMAIN . $PATH_APPLICATION;


if ($USE_HTTPS_ONLY && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    // this is not a CLI call and HTTPS is required!
    // try to forward user to HTTPS page automatically
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://' . $URL_DOMAIN . $PATH_APPLICATION);
    exit;
}

// convert variables into constans in order to have them globally
// available and to increase security
const PRODUCT_NAME    = 'PHPKnock';
const PRODUCT_VERSION = '0.3';

define('PATH_FS_APPLICATION', $PATH_FS_APPLICATION);
define('PATH_FS_TMP', $PATH_FS_TMP);
define('PATH_APPLICATION', $PATH_APPLICATION);
const PATH_FS_PASSWORD = PATH_FS_TMP . '/.fwknop.pass';

define('USE_HTTPS_ONLY', $USE_HTTPS_ONLY);
define('URL_SCHEME', $URL_SCHEME);
define('URL_DOMAIN', $URL_DOMAIN);
define('URL', $URL);

const CHARSET = 'UTF-8';


#############################################################################
###	LOAD DEFAULT FUNCTIONS AND CLASSES
#############################################################################
spl_autoload_register('autoload');

// Start session for CSRF protection (web only)
if (!CLI_CALL && session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure'   => USE_HTTPS_ONLY,
    ]);
}
if (!CLI_CALL && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

#############################################################################
###	FUNCTIONS
#############################################################################
// catch app interruption and clean-up password file
if (function_exists('pcntl_signal')) {
    declare(ticks=1);
    pcntl_signal(SIGINT, static function () {
        if (file_exists(PATH_FS_PASSWORD)) {
            unlink(PATH_FS_PASSWORD);
        }
        exit(1);
    });
} else {
    class CleanUp
    {
        public function __destruct()
        {
            if (file_exists(PATH_FS_PASSWORD)) {
                unlink(PATH_FS_PASSWORD);
            }
        }
    }

    $cleanup = new CleanUp();
}

/**
 * Checks per-IP rate limit using a file lock in PATH_FS_TMP.
 *
 * Returns true when the request is within the allowed limit, false when exceeded.
 */
function checkRateLimit(string $ip, int $limit, int $window): bool
{
    $file = PATH_FS_TMP . '/rl_' . md5($ip) . '.json';
    $fh   = @fopen($file, 'c+');
    if ($fh === false) {
        return true; // fail open: don't block if tmp is unexpectedly unwritable
    }

    flock($fh, LOCK_EX);

    $content = stream_get_contents($fh);
    $data    = $content ? json_decode($content, true) : null;
    $now     = time();

    if (!$data || ($now - $data['since']) >= $window) {
        $data = ['count' => 0, 'since' => $now];
    }
    $data['count']++;

    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data));
    flock($fh, LOCK_UN);
    fclose($fh);

    return $data['count'] <= $limit;
}


/**
 * Returns true when $host is a valid IPv4/IPv6 address or a valid RFC 1123 hostname.
 */
function isValidHost(string $host): bool
{
    if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
        return true;
    }
    // RFC 1123: each label 1-63 chars (letters, digits, hyphens), total ≤ 253
    return strlen($host) <= 253
        && (bool)preg_match(
            '/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?$/',
            $host
        );
}


/**
 * Builds and returns form
 *
 * @return Form        Html Form
 * @throws ReflectionException
 */
function form(): Form
{
    global $ACCESS_PORT_LIST, $DESTINATION, $ENCRYPTION_KEY, $SERVER_PORT;

    $form = new Form('knock');

    if (is_array($DESTINATION)) {
        $form->factory('Dropdown', 'destination', 'Server', $DESTINATION)
             ->setMaximumSize(20)
             ->setIsMultiple(true)
             ->setNotNull();
    } elseif ($DESTINATION === null) {
        $form->factory('Text', 'destination', 'Server IP/Hostname')
             ->setHint('You may enter multiple server IPs or hostnames separated by a semicolon.')
             ->setNotNull();
    }


    if ($SERVER_PORT === null) {
        $form->factory('Integer', 'serverPort', 'Server port')
             ->setMinimum(1)
             ->setMaximum(65535);
    }

    if ($ACCESS_PORT_LIST === null) {
        $form->factory('Text', 'accessPortList', 'Access port list')
             ->setHint(
                 'Provide a list of ports and protocols to access on a remote computer. The format of this list is "<proto>/<port>...<proto>/<port>", e.g. "tcp/22,udp/53".'
             )
             ->setValidRegExp('(^(tcp|udp)/[0-9]+( *, *(tcp|udp)/[0-9]+)*$)i');
    }

    if ($ENCRYPTION_KEY === null) {
        $form->factory('Password', 'encryptionKey', 'Encryption key');
    }

    $form->factory('Text', 'allowIp', 'Source IP')
         ->setDefaultValue($_SERVER['REMOTE_ADDR'])
         ->setValidRegExp(
             '(^(?P<first>[1-9]?\d|1\d\d|2[0-4]\d|25[0-5])\.(?P<second>[1-9]?\d|1\d\d|2[0-4]\d|25[0-5])\.(?P<third>[1-9]?\d|1\d\d|2[0-4]\d|25[0-5])\.(?P<fourth>[1-9]?\d|1\d\d|2[0-4]\d|25[0-5])$)'
         )
         ->setNotNull();


    $form->factory('Hidden', 'doKnock')
         ->setDefaultValue(1);

    $form->factory('Hidden', 'csrfToken')
         ->setDefaultValue(!CLI_CALL ? ($_SESSION['csrf_token'] ?? '') : '');

    return $form;
}


#############################################################################
###	INIT
#############################################################################
$error = false;

$html = new Html();
$html->setTitle('Knock PHP');
$html->addStyleSheet('static/default.css');

$message = new Message();

$button = new ButtonBar();
$button->addhtml('knock', 'knock knock', 'start knocking');

$form = form();
$form->fetch();

#############################################################################
###	CHECKS
#############################################################################
if (!is_writable(PATH_FS_TMP)) {
    $message->addError('Temporary directory "' . PATH_FS_TMP . '" is not writable.');
    $error = true;
}


#############################################################################
###	ACTION
#############################################################################
if (!$error && $form->element('doKnock')->value() === '1') {
    // CSRF check
    if (!CLI_CALL && !hash_equals(
        (string)($_SESSION['csrf_token'] ?? ''),
        (string)$form->element('csrfToken')->value()
    )) {
        $message->addError('Invalid or expired request token. Please reload the page and try again.');
        $error = true;
    }

    // Rate limit check
    if (!$error && !checkRateLimit($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $RATE_LIMIT, $RATE_WINDOW)) {
        $message->addError('Too many requests. Please wait before trying again.');
        $error = true;
    }
}

if (!$error && $form->element('doKnock')->value() === '1' && $form->validate()) {
    $encryptionKey = $ENCRYPTION_KEY ?? $form->element('encryptionKey')->dbValue();

    $execute = [$FWKNOP_CLI];

    if ($ERRORS_VERBOSE) {
        $execute['verbose'] = '--verbose';
    }

    $execute['G'] = '-G ' . escapeshellarg(escapeshellcmd(PATH_FS_PASSWORD));

    if ($SERVER_PORT !== null) {
        $execute['server-port'] = '--server-port ' . $SERVER_PORT;
    } elseif (!$form->element('serverPort')->isEmpty()) {
        $execute['server-port'] = '--server-port ' . escapeshellarg(
                escapeshellcmd($form->element('serverPort')->dbValue())
            );
    }

    if ($ACCESS_PORT_LIST !== null) {
        $execute['A'] = '-A ' . escapeshellarg($ACCESS_PORT_LIST);
    } elseif (!$form->element('accessPortList')->isEmpty()) {
        $execute['A'] = '-A ' . escapeshellarg(
                escapeshellcmd(str_replace(' ', '', $form->element('accessPortList')->dbValue()))
            );
    }

    $execute['a'] = '-a ' . escapeshellarg(escapeshellcmd($form->element('allowIp')->dbValue()));

    $hosts = [];
    if (is_string($DESTINATION)) {
        // destination is given as fix value by configuration
        if (!str_contains($DESTINATION, ';')) {
            $hosts = [$DESTINATION];
        } else {
            $hosts = array_map('trim', explode(';', $DESTINATION));
        }
    } elseif (!$form->element('destination')->isEmpty()) {
        $value = $form->element('destination')->dbValue();
        if (is_array($DESTINATION)) {
            // destinations are in dropdown
            $hosts = [];
            foreach ($value as $key) {
                if ((string)(int)$key === $key) {
                    $hosts[] = $DESTINATION[$key];
                } else {
                    $hosts[] = $key;
                }
            }
        } elseif (!str_contains($value, ';')) {
            $hosts = [$value];
        } else {
            $hosts = array_map('trim', explode(';', $value));
        }
    }

    $descriptorspec = [
        0 => ["pipe", "r+"],    // STDIN ist eine Pipe, von der das Child liest
        1 => ["pipe", "w"],    // STDOUT ist eine Pipe, in die das Child schreibt
        2 => ["pipe", "w"],    // STDERR
    ];

    $env = [
        'HOME' => PATH_FS_TMP,
    ];

    foreach ($hosts as $target) {
        if (!isValidHost($target)) {
            $message->addError('Invalid destination: "' . htmlspecialchars($target, ENT_QUOTES, CHARSET) . '".');
            continue;
        }

        file_put_contents(PATH_FS_PASSWORD, $target . ':' . $encryptionKey);
        chmod(PATH_FS_PASSWORD, 0600);

        $execute['D'] = '-D ' . escapeshellarg(escapeshellcmd($target));

        // execute command on CLI and check return code
        // forward errors to stdout to see them in output
        $cmd = implode(' ', $execute) . ' 2>&1';

        $process = proc_open($cmd, $descriptorspec, $pipes, PATH_FS_TMP, $env);

        if (is_resource($process)) {
            //fwrite( $pipes[0], $encryptionKey );
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            $error  = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            // it's important to close all pipes before doing
            // a proc_close to prevent deadlocks
            $return = proc_close($process);


            if ($return === 0) {
                $message->addMessage(
                    'Knock send successfully to "' . $target . '". With correct settings, you should be able to access the server for a limited time now.'
                );
            } else {
                if (!empty($output) && !empty($error)) {
                    $output .= "\n$error";
                }
                $output = preg_replace("(\n$)", '', $output);
                $message->addError(
                    'Unable to execute fwknop. It says: "' . str_replace(
                        "\n",
                        "<br />\n",
                        htmlspecialchars($output) . '".'
                    )
                );
            }

            if ($ERRORS_VERBOSE) {
                $message->addMessage(
                    'Command:<br />' . htmlspecialchars(str_replace($encryptionKey, '****', $cmd)) .
                    '<br /><br />Output:<br />' . str_replace("\n", "<br />\n", htmlspecialchars($output))
                );
            }
        }
    }
}


#############################################################################
###	VIEW
#############################################################################
// HEADER
$html->displayHeader();
echo '
<div id="headerwrap">
	<div id="header">
		<table border="0" cellspacing="0" width="100%" class="menu">
		<tr>
			<td class="left"><img src="static/images/phpknock-image.png" alt="PHPKnock" height="40" /></td>
			<td class="middle">' . PRODUCT_NAME . '</td>
			<td class="right">&nbsp;</td>
		</tr>
		</table>
	</div>
</div>';

// BODY
echo '
<div id="middlewrap">
	<div id="middle">
		<div id="content">
			<div class="groupBox">';

if ($message->hasErrors()) {
    echo '
					<div class="failed">' . implode("</div>\n<div class=\"failed\">", $message->errors()) . '</div>';
}

if ($message->hasWarnings()) {
    echo '
					<div class="notice">' . implode(',<br/><li>', $message->warnings()) . '</div>';
}
if ($message->hasMessages()) {
    echo '
					<div class="success">' . implode(
            "</div>\n<div class=\"success\">",
            $message->messages()
        ) . '</div>';
}
echo implode(",<br />\n", $message->messages());
$form->displayFormHeader();
$form->displayFormBody();

$button->display();

$form->displayFormFooter();

echo '
				<h1 class="legend">Legend</h1>

				<div class="noticeHeader"><img src="static/images/notice.png" align="middle" border="0" alt="notice" title="notice" />&nbsp;Origin</div>
				<div class="description">This port knocking client is based on <a href="https://cipherdyne.org/fwknop/" target="_blank">fwknop</a>. For more information read their <a href="https://cipherdyne.org/fwknop/docs/" target="_blank">documentation</a>.
				</div>

				<div class="noticeHeader"><img src="static/images/notice.png" align="middle" border="0" alt="notice" title="notice" />&nbsp;Return message</div>
				<div class="description">It is important to understand, that even if the client send out a correct knock and returns success, you cannot be sure to have an open port. This is based on the fact, that the knocking daemon doesn\'t return anything. So the client is unable to tell, if the request did, what you wanted it to do.
				</div>

			</div>
		</div>
	</div>
</div>
';


// FOOTER
if (defined('PRODUCT_VERSION')) {
    $versionString = PRODUCT_NAME . ' v' . PRODUCT_VERSION;
    echo '
	<div id="footerwrap">
		<div id="footer"><br />
			' . $versionString . '
		</div>
	</div>';
}

$html->displayFooter();
