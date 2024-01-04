<?php
/*
 * Copyright (C) 2012-2024 by TEQneers GmbH & Co. KG
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
 * Standard Functions
 *
 * @author         Oliver G. Mueller <mueller@teqneers.de>
 * @package        PHPKnock
 * @subpackage     base
 * @copyright      Copyright (C) 2003-2024 TEQneers GmbH & Co. KG. All rights reserved.
 */


/**
 * This method will automatically load classes
 *
 * @param  string  $class  Name of class
 * @return    bool                TRUE on successful inclusion, FALSE otherwise
 */
function autoload(string $class): bool
{
    if (preg_match_all('/([A-Z][^A-Z]+)/', $class, $path)) {
        if (end($path[1]) !== 'Interface') {
            $type = 'class';
        } else {
            $type = 'interface';
            array_pop($path[1]);
        }

        $i        = count($path[1]);
        $filename = '';
        $found    = false;
        do {
            $filename = empty($filename) ? array_pop($path[1]) : array_pop($path[1]) . '_' . $filename;
            $include  = __DIR__ . '/classes/' . strtolower(
                    implode(
                        '/',
                        $path[1]
                    ) . '/' . $filename
                ) . '_' . $type . '.php';
            $found    = file_exists($include);
        } while (!$found && count($path[1]));

        if ($found) {
            require $include;
        }

        return $found;

    } // if

    return false;
}


/**
 * Var_dump replacement
 *
 * @param  mixed        $dump  Dump var
 * @param  string|null  $name  Dump name
 */
function vd(mixed $dump, ?string $name = null): void
{
    if ($GLOBALS['ERRORS_VERBOSE']) {
        // TODO: this output should only happen with teqneers internal servers
        if (empty($GLOBALS['jsDebugDragNDropHandling']) || !is_array($GLOBALS['jsDebugDragNDropHandling'])) {
            $GLOBALS['jsDebugDragNDropHandling'] = [];
        }
        $uniqueId                              = count($GLOBALS['jsDebugDragNDropHandling']);
        $GLOBALS['jsDebugDragNDropHandling'][] = 'jsDebug' . $uniqueId;

        if ($name === null) {
            $backtrace = debug_backtrace();
            $tmp       = explode(DIRECTORY_SEPARATOR, $backtrace[0]['file']);
            $lastDir   = (count($tmp) > 1) ? ($tmp[count($tmp) - 2] . '/') : '';
            $name      = $lastDir . basename($backtrace[0]['file']) . ' [line ' . $backtrace[0]['line'] . '] ';
            $name      .= $uniqueId + 1;
        } else {
            $name .= ' [' . ($uniqueId + 1) . ']';
        }

        if (!CLI_CALL) {

            echo '
			<div id="jsDebug' . $uniqueId . '" class="debug">
				<div class="debugTitle">
					<div onClick="document.getElementById(\'jsDebug' . $uniqueId . '\').style.display=\'none\';" class="debugClose">X</div>
					' . $name . '
				</div>
				<pre>' . "\n";
            // @codingStandardsIgnoreStart
            var_dump($dump);
            // @codingStandardsIgnoreEnd
            echo '
				</pre>
			</div>';

        } else {
            echo "\n############################### " . $name . " ###############################\n";
            // @codingStandardsIgnoreStart
            var_dump($dump);
            // @codingStandardsIgnoreEnd
            echo "############################### /" . $name . " ##############################\n";
        }
    } // if
}

/**
 * Print r
 *
 * Print_r replacement
 *
 * @param  mixed        $dump  Dump var
 * @param  string|null  $name  Dump name
 */
function pr(mixed $dump, ?string $name = null): void
{
    if ($GLOBALS['ERRORS_VERBOSE']) {
        // TODO: this output should only happen with teqneers internal servers
        if (empty($GLOBALS['jsDebugDragNDropHandling']) || !is_array($GLOBALS['jsDebugDragNDropHandling'])) {
            $GLOBALS['jsDebugDragNDropHandling'] = [];
        }
        $uniqueId                              = count($GLOBALS['jsDebugDragNDropHandling']);
        $GLOBALS['jsDebugDragNDropHandling'][] = 'jsDebug' . $uniqueId;

        if ($name === null) {
            $backtrace = debug_backtrace();
            $tmp       = explode(DIRECTORY_SEPARATOR, $backtrace[0]['file']);
            $lastDir   = (count($tmp) > 1) ? ($tmp[count($tmp) - 2] . '/') : '';
            $name      = $lastDir . basename($backtrace[0]['file']) . ' [line ' . $backtrace[0]['line'] . '] ';
            $name      .= $uniqueId + 1;
        } else {
            $name .= ' [' . ($uniqueId + 1) . ']';
        }

        if (!CLI_CALL) {

            echo '
			<div id="jsDebug' . $uniqueId . '\').style.display=\'none\';" class="debugClose">X</div>
					' . $name . '
				</div>
				<pre>' . "\n";
            // @codingStandardsIgnoreStart
            print_r($dump);
            // @codingStandardsIgnoreEnd
            echo '
				</pre>
			</div>';

        } else {
            echo "\n############################### " . $name . " ###############################\n";
            // @codingStandardsIgnoreStart
            print_r($dump);
            // @codingStandardsIgnoreEnd
            echo "############################### /" . $name . " ##############################\n";
        }
    } // if
}
