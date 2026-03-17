<p style="text-align:center;">
  <img src="app/public/static/images/phpknock.png" alt="PHPKnock" width="200" />
</p>

**PHPKnock** is a web frontend for the port-knocking client [fwknop](http://cipherdyne.org/fwknop/). It lets you send a Single Packet Authorization (SPA) or port-knocking request to a remote server directly from a browser, without needing fwknop installed locally.

**Why a web frontend?** It lets you trigger port-knocking from any device with a browser. Typical use cases:

- Install it on an intranet server so staff can open ports on other servers in your network.
- Install it in your extranet so support staff can send SPA requests from home without installing fwknop on their own machines.

For background reading, see Wikipedia on [port knocking](http://en.wikipedia.org/wiki/Port_knocking) and [Single Packet Authorization](http://en.wikipedia.org/wiki/Single_Packet_Authorization).


Requirements
------------

- PHP >= 8.1
- fwknop client 2.x
- A web server (Apache, Nginx, etc.) **or** Docker


Installation
------------

### Option A — Docker (recommended)

No config file is required. All settings are controlled via environment variables set in `docker/compose.yaml`.

1. Edit `docker/compose.yaml` and set the environment variables for your setup (see table below).

2. Start the container from the `docker/` directory:
   ```bash
   cd docker
   docker compose up -d
   ```

#### Environment variables

| Variable | Default | Description |
|---|---|---|
| `PHPKNOCK_SERVER_PORT` | `62201` | UDP port fwknopd listens on |
| `PHPKNOCK_ACCESS_PORT_LIST` | `tcp/22` | Ports to request access to (e.g. `tcp/22,udp/53`) |
| `PHPKNOCK_USE_HTTPS_ONLY` | `false` | Redirect HTTP → HTTPS |
| `PHPKNOCK_ENCRYPTION_KEY` | _(none)_ | Fixed encryption key; omit to let the user enter it in the browser |
| `PHPKNOCK_DESTINATION` | _(none)_ | Fixed destination IP/hostname, a semicolon-separated list for multiple hosts, or a JSON object (`{"10.0.0.1":"prod","10.0.0.2":"dev"}`) for a dropdown; omit to show a free-text input |
| `PHPKNOCK_ERRORS_VERBOSE` | `false` | Show fwknop command and raw output in the browser |

### Option B — Traditional web server

1. Clone or download PHPKnock somewhere outside your document root, for example `/opt/phpknock`.

2. Point your web server at the `app/public/` subdirectory. Example Apache alias:
   ```apacheconf
   Alias /phpknock /opt/phpknock/app/public
   ```
   See [Apache mod_alias](http://httpd.apache.org/docs/2.2/mod/mod_alias.html#alias) for details.

3. Copy and configure the config file:
   ```bash
   cp app/local_config.php.dist app/local_config.php
   vi app/local_config.php
   ```

4. Make the `tmp/` directory writable by the web server user:
   ```bash
   chown www-data:www-data tmp
   chmod 770 tmp
   ```

5. Open `https://your-domain.com/phpknock/` in a browser.


Contribute
----------

Bug reports and pull requests are welcome via the [GitHub issue tracker](https://github.com/teqneers/phpknock/issues).


License
-------

Copyright (C) 2012-2026 by TEQneers GmbH & Co. KG

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal with the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
