PHPKnock
========

***PHPKnock*** is a web frontend for the port knocking service [fwknop](http://cipherdyne.org/fwknop/) . It let you shoot a Single Packet Authorization (SPA) or a port knocking request to any server you configured and ask this server to open a certain port for you or any given IP address.

What is this web frontend good for? You can use it to support any OS or platform that has a browser. E.g., you can install it on an intranet server to open up other servers in your extranet or internet. Another use-case could be to install it in your extranet, so your support staff has the ability to send SPA or port knocking requests from home or somewhere else without installing fwknop to their computer.

If you like to know more about [port knocking](http://en.wikipedia.org/wiki/Port_knocking) or [Single Packet Authorization](http://en.wikipedia.org/wiki/Single_Packet_Authorization) checkout Wikipedia.


Requirements
------------

- Web server like Apache, Nginx, IIS, or alike
- PHP > 8.1.x running as SAPI, FastCGI, or CGI
- fwknop client 2.x


Installation
------------
Download or clone **PHPKnock** to your web server. You can put **PHPKnock** into your document root, but for security reasons, it is NOT a good idea to put your whole Knock directory into your document root of your web server. Instead, you should put it somewhere else and use an alias to **PHPKnock**'s htdocs folder.

Example for your Apache configuration:
<pre>"Alias /phpknock /opt/phpknock/htdocs"</pre>


For more information, visit [Apache's mod_alias](http://httpd.apache.org/docs/2.2/mod/mod_alias.html#alias).

Copy template configuration file and change configuration as needed:

<pre>
# cp phpknock/local_config.php.dist phpknock/local_config.php
# vi phpknock/local_config.php
</pre>

Make temporary directory writable for web server user:

<pre>
# chown www-data:www-data phpknock/tmp
# chmod 770 phpknock/tmp
</pre>

Now you should be able to use **PHPKnock** through your web browser. Enter something like <pre>https://your-domain.com/path-to/phpknock/</pre>


Contribute
----------

Please feel free to use the Git issue tracking to report back any problems or errors. You're encouraged to clone the repository and send pull requests if you'd like to contribute actively in developing the library.


License
-------

Copyright (C) 2012-2024 by TEQneers GmbH & Co. KG

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal with the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

