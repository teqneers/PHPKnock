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
 * Knock view template
 *
 * Expects the following variables from the controller:
 * @var Html      $html
 * @var Message   $message
 * @var Form      $form
 * @var ButtonBar $button
 */

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