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
$PATH_FS_LOG         = ($v = getenv('PHPKNOCK_PATH_FS_LOG')) !== false ? $v : dirname($PATH_FS_APPLICATION) . '/log';
$PATH_APPLICATION    = ($v = getenv('PHPKNOCK_PATH_APPLICATION')) !== false ? $v : '/knock';
$USE_HTTPS_ONLY      = ($v = getenv('PHPKNOCK_USE_HTTPS_ONLY')) !== false ?
    filter_var($v, FILTER_VALIDATE_BOOLEAN) : false;
$ERRORS_VERBOSE      = ($v = getenv('PHPKNOCK_ERRORS_VERBOSE')) !== false ?
    filter_var($v, FILTER_VALIDATE_BOOLEAN) : false;
$ERRORS_LOG          = ($v = getenv('PHPKNOCK_ERRORS_LOG')) !== false ? $v : $PATH_FS_LOG . '/error.log';
$AUDIT_LOG           = ($v = getenv('PHPKNOCK_AUDIT_LOG'))  !== false ? $v : $PATH_FS_LOG . '/audit.log';
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

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../functions.php';

use PHPKnock\ButtonBar;
use PHPKnock\Form;
use PHPKnock\Html;
use PHPKnock\KnockService;
use PHPKnock\Message;

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
$composerJson = json_decode(file_get_contents($PATH_FS_APPLICATION . '/composer.json'), true);
define('PRODUCT_VERSION', $composerJson['version'] ?? 'unknown');

define('PATH_FS_APPLICATION', $PATH_FS_APPLICATION);
define('PATH_FS_TMP', $PATH_FS_TMP);
define('PATH_APPLICATION', $PATH_APPLICATION);
const PATH_FS_PASSWORD = PATH_FS_TMP . '/.fwknop.pass';

define('USE_HTTPS_ONLY', $USE_HTTPS_ONLY);
define('URL_SCHEME', $URL_SCHEME);
define('URL_DOMAIN', $URL_DOMAIN);
define('URL', $URL);

const CHARSET = 'UTF-8';


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
$knockService = new KnockService(
    fwknopCli: $FWKNOP_CLI,
    tmpPath: PATH_FS_TMP,
    passwordFilePath: PATH_FS_PASSWORD,
    verbose: $ERRORS_VERBOSE,
    auditLogPath: $AUDIT_LOG,
);

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
    if (!$error && !$knockService->checkRateLimit($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $RATE_LIMIT, $RATE_WINDOW)) {
        $message->addError('Too many requests. Please wait before trying again.');
        $error = true;
    }
}

if (!$error && $form->element('doKnock')->value() === '1' && $form->validate()) {
    $encryptionKey = $ENCRYPTION_KEY ?? $form->element('encryptionKey')->dbValue();

    $execute = $knockService->buildCommand(
        allowIp: $form->element('allowIp')->dbValue(),
        configServerPort: $SERVER_PORT,
        formServerPort: $form->element('serverPort')?->dbValue(),
        configAccessPortList: $ACCESS_PORT_LIST,
        formAccessPortList: $form->element('accessPortList')?->dbValue(),
    );

    $formValue = $form->element('destination')?->dbValue();
    $hosts = KnockService::resolveHosts($DESTINATION, $formValue);

    foreach ($hosts as $target) {
        $knockService->execute(
            $target,
            $encryptionKey,
            $execute,
            $message,
            CHARSET,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $form->element('allowIp')->dbValue(),
        );
    }
}


#############################################################################
###	VIEW
#############################################################################
require __DIR__ . '/../views/knock.php';
