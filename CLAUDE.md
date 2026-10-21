# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Project Is

PHPKnock is a web frontend for the **fwknop** port knocking/Single Packet Authorization (SPA) client. It wraps the `fwknop` CLI tool with a PHP web interface, allowing users to send SPA packets from a browser without needing fwknop installed locally.

**Requirements:** PHP 8.1+, fwknop 2.x CLI installed on the host, a web server, Composer (for autoloading).

## Running the Application

1. Copy `local_config.php.dist` → `local_config.php` and configure it.
2. Point your web server to the `public/` subdirectory (not the project root).
3. Ensure the web server user can write to the `tmp/` directory.

Run `composer install` in the `app/` directory to install dependencies. Run tests with `./vendor/bin/phpunit` from `app/`. Run static analysis with `./vendor/bin/phpstan analyse` (level max).

## Configuration (`local_config.php`)

The key variables that control runtime behavior:

| Variable | Purpose |
|---|---|
| `$PATH_APPLICATION` | URL path (e.g. `/phpknock`) |
| `$USE_HTTPS_ONLY` | Redirect HTTP → HTTPS (default: `true`) |
| `$FWKNOP_CLI` | Absolute path to fwknop binary |
| `$ENCRYPTION_KEY` | Static key or `null` (user provides it) |
| `$DESTINATION` | `null` (free text), `string` (fixed), or `array` (dropdown) |
| `$ACCESS_PORT_LIST` | Ports to knock for; `null` = user input |
| `$SERVER_PORT` | fwknopd UDP port; `null` = user input |
| `$ERRORS_VERBOSE` | Show fwknop output & commands in browser |
| `$ERRORS_LOG` | Path to error log file (default: `log/error.log`) |
| `$AUDIT_LOG` | Path to audit log file (default: `log/audit.log`); `null` = disabled |
| `$ENCRYPTION_MODE` | `'rijndael'` or `'gpg'` (default: `'rijndael'`) |
| `$HMAC_ENABLED` | Enable HMAC authentication for Rijndael mode (default: `false`) |
| `$HMAC_KEY` | Fixed HMAC key or `null` (user provides it) |
| `$HMAC_DIGEST_TYPE` | HMAC digest: `md5`/`sha1`/`sha256`/`sha384`/`sha512` (default: `'sha256'`) |
| `$GPG_RECIPIENT_KEY` | Server's GPG key ID; required for GPG mode |
| `$GPG_SIGNER_KEY` | Client's GPG key ID; required for GPG mode |
| `$GPG_HOME_DIR` | GPG keyring path; `null` = system default |
| `$TRUSTED_PROXIES` | IPs/CIDRs of reverse proxies whose `X-Forwarded-For` is trusted (default: `[]`) |
| `$URL_DOMAIN` | Public host for HTTPS redirects; `null` = validated `Host` header |

## Architecture

Try to use as few dependencies as possible.

The application is a single-page PHP app split into controller and view. `public/index.php` handles bootstrap, form building, validation, and delegates execution to `KnockService`. `views/knock.php` handles all HTML rendering. Flow:

1. **Boot** (`index.php`) — load `local_config.php`, `functions.php`, Composer autoloader.
2. **Build form** (`index.php`) — dynamically add form element fields depending on which config values are fixed vs. user-supplied.
3. **Validate** (`index.php`) — on POST with `data[doKnock]=1`, validate CSRF token, rate limit, and all fields.
4. **Execute** (`index.php` → `KnockService`) — resolve hosts, build command, execute fwknop for each target via `KnockService`.
5. **Render** (`views/knock.php`) — `Html` outputs the page; `Message` displays success/errors.

### Class hierarchy (PSR-4: `PHPKnock\` → `src/`)

- **`PHPKnock\Html`** (`src/Html.php`) — HTML document skeleton, CSS, meta tags.
- **`PHPKnock\Form`** (`src/Form.php`) — form container; `factory('Text'|'Password'|'Integer'|'Dropdown'|'Hidden', ...)` creates elements.
- **`PHPKnock\Form\Element`** (`src/Form/Element.php`) — base class; reads `$_REQUEST['data'][name]`, validates against rules (`notNull`, `regex`, etc.).
  - Subclasses in `src/Form/Element/`: `Text.php`, `Password.php`, `Integer.php`, `Dropdown.php`, `Hidden.php`.
- **`PHPKnock\Message`** (`src/Message.php`) — stores messages/warnings/errors; MD5-deduplicates them.
- **`PHPKnock\ButtonBar`** (`src/ButtonBar.php`) — renders the submit button row.
- **`PHPKnock\KnockService`** (`src/KnockService.php`) — extracted controller logic: host validation, host resolution, rate limiting, command building, and fwknop execution via `proc_open()`.

### Autoloader

Composer PSR-4 autoloading maps `PHPKnock\` to `src/`. Run `composer dump-autoload` after adding new classes.

### Security-sensitive code path

The encryption key is never passed on the command line. It is written to a unique per-request file `tmp/.fwknop.pass.XXXXXX` (created 0600 via `tempnam()`) and passed to fwknop via `-G`. The file format depends on mode: Rijndael without HMAC uses `{hostname}:{key}`, Rijndael with HMAC uses `{hostname}:{key}:{hmac_key}`, and GPG uses `{hostname}:{key}`. The file is deleted after execution and on SIGINT (via `KnockService::__destruct()`). Dropdown destinations only accept configured options. Proxy headers are only honoured from `$TRUSTED_PROXIES`. All shell arguments are escaped with `escapeshellarg()`/`escapeshellcmd()`.

### General

Keep README.md up-to-date. Keep feature list up-to-date.
