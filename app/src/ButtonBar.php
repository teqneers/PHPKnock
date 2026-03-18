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

namespace PHPKnock;

/**
 * Button Bar Class
 *
 * This class represents an HTML button bar.
 */
class ButtonBar
{

    ##################################################
    # attributes
    ##################################################
    /**
     * Button list
     *
     * @var array<int, array{key: string, type: string, url: string, target: string|null, attributes: array<string, string|null>}>
     */
    protected array $_buttons = [];


    ##################################################
    # methods
    ##################################################
    /**
     * Display button bar as HTML code
     */
    public function display(): void
    {
        foreach ($this->_buttons as $button) {
            if ($button['type'] === 'html') {
                echo '<input class="button" type="submit" ' . Html::array2attributes($button['attributes']) . '>';
            } else {
                echo '<input class="button" type="submit" ' . Html::array2attributes(
                        $button['attributes']
                    ) . ' onclick="javascript:' . $button['url'] . '">';
            }
        }
    }

    /**
     * Add an element
     *
     * @param  string       $key     Key used as unique identifier
     * @param  string       $name    Name
     * @param  string       $type    Display element type (html|js|ajax)
     * @param  string|null  $hint    Hint text
     * @param  string       $url     URL or type-specific linking
     * @param  string|null  $target  Target
     */
    public function add(
        string $key,
        string $name,
        string $type = 'html',
        ?string $hint = null,
        string $url = '#',
        ?string $target = null
    ): void {

        // get a new element using the analog factory method
        $this->_buttons[] = [
            'key'        => $key,
            'type'       => $type,
            'url'        => $url,
            'target'     => $target,
            'attributes' => [
                'value' => $name,
                'name'  => $hint,
                'alt'   => $hint,
            ],
        ];
    }

    /**
     * Alias for add() specialized for html elements
     *
     * @param  string       $key     Key used as unique identifier
     * @param  string       $name    Name
     * @param  string|null  $hint    Hint text
     * @param  string       $url     URL or type-specific linking
     * @param  string|null  $target  Target
     */
    public function addHtml(
        string $key,
        string $name,
        ?string $hint = null,
        string $url = '#',
        ?string $target = null
    ): void {
        $this->add($key, $name, 'html', $hint, $url, $target);
    }

    /**
     * Alias for add() specialized for javascript elements
     *
     * @param  string       $key     Key used as unique identifier
     * @param  string       $name    Name
     * @param  string|null  $hint    Hint text
     * @param  string       $url     URL or type-specific linking
     * @param  string|null  $target  Target
     */
    public function addJs(
        string $key,
        string $name,
        ?string $hint = null,
        string $url = '#',
        ?string $target = null
    ): void {
        $this->add($key, $name, 'js', $hint, $url, $target);
    }

}
