<?php
/*
 * Parsedown (MIT License)
 * https://parsedown.org
 *
 * Copyright (c) 2013-2019 Emanuil Rusev
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

class Parsedown
{
    const version = '1.7.4';

    function text($text)
    {
        $text = str_replace(["\r\n", "\r"], "\n", (string) $text);
        $text = trim($text, "\n");

        $markup = $this->lines(explode("\n", $text));

        // trim line breaks
        $markup = trim($markup, "\n");

        return $markup;
    }

    protected $BreaksEnabled = false;
    function setBreaksEnabled($breaksEnabled)
    {
        $this->BreaksEnabled = (bool) $breaksEnabled;
        return $this;
    }

    protected function lines(array $lines)
    {
        $CurrentBlock = null;
        $Blocks = [];

        foreach ($lines as $line)
        {
            if (chop($line) === '')
            {
                if (isset($CurrentBlock))
                {
                    $CurrentBlock['interrupted'] = true;
                }
                continue;
            }

            $marker = $line[0];

            // ATX heading
            if ($marker === '#' && preg_match('/^(#{1,6})\s*(.+?)\s*#*$/', $line, $m))
            {
                $level = strlen($m[1]);
                $text = $this->inline($m[2]);
                $Blocks[] = "<h$level>".$text."</h$level>";
                $CurrentBlock = null; continue;
            }

            // fenced code
            if (($marker === '`' || $marker === '~') && preg_match('/^([`~]{3,})\s*(\w+)?\s*$/', $line, $m))
            {
                $fence = $m[1]; $lang = isset($m[2])?$m[2]:'';
                $codeLines = [];
                while (($l = current($lines)) !== null){ next($lines); if (preg_match('/^'.preg_quote($fence,'/').'\s*$/',$l)) break; $codeLines[]=$l; }
                $html = htmlspecialchars(implode("\n", $codeLines), ENT_NOQUOTES, 'UTF-8');
                $attr = $lang? ' class="language-'.htmlspecialchars($lang, ENT_QUOTES, 'UTF-8').'"':'';
                $Blocks[] = "<pre><code$attr>$html</code></pre>";
                $CurrentBlock = null; continue;
            }

            // list item (unordered)
            if (preg_match('/^\s*([*+-])\s+(.+)$/', $line, $m))
            {
                $items = [];
                $items[] = $this->inline($m[2]);
                while (($l = current($lines)) !== null)
                {
                    if (preg_match('/^\s*([*+-])\s+(.+)$/', $l, $mm)) { $items[] = $this->inline($mm[2]); next($lines); }
                    else break;
                }
                $lis = '<li>'.implode('</li><li>', $items).'</li>';
                $Blocks[] = '<ul>'.$lis.'</ul>';
                $CurrentBlock = null; continue;
            }

            // list item (ordered)
            if (preg_match('/^\s*\d+[.)]\s+(.+)$/', $line, $m))
            {
                $items = [];
                $items[] = $this->inline($m[1]);
                while (($l = current($lines)) !== null)
                {
                    if (preg_match('/^\s*\d+[.)]\s+(.+)$/', $l, $mm)) { $items[] = $this->inline($mm[1]); next($lines); }
                    else break;
                }
                $lis = '<li>'.implode('</li><li>', $items).'</li>';
                $Blocks[] = '<ol>'.$lis.'</ol>';
                $CurrentBlock = null; continue;
            }

            // blockquote
            if ($marker === '>' && preg_match('/^>\s?(.*)$/', $line, $m))
            {
                $quote = [$m[1]];
                while (($l = current($lines)) !== null)
                {
                    if (preg_match('/^>\s?(.*)$/', $l, $mm)){ $quote[]=$mm[1]; next($lines);} else break;
                }
                $Blocks[] = '<blockquote><p>'.$this->inline(implode("\n", $quote)).'</p></blockquote>';
                $CurrentBlock = null; continue;
            }

            // paragraph (collect consecutive lines)
            $para = [$line];
            while (($l = current($lines)) !== null)
            {
                if (chop($l) === '') break;
                if (preg_match('/^(#{1,6})\s+|^\s*([*+-])\s+|^\s*\d+[.)]\s+|^>\s?/', $l)) break;
                $para[] = $l; next($lines);
            }
            $text = implode($this->BreaksEnabled?"<br>\n":"\n", $para);
            $Blocks[] = '<p>'.$this->inline($text).'</p>';
            $CurrentBlock = null; continue;
        }

        return implode("\n\n", $Blocks);
    }

    protected function inline($text)
    {
        // code spans
        $text = preg_replace_callback('/`([^`]+)`/', function($m){
            return '<code>'.htmlspecialchars($m[1], ENT_NOQUOTES, 'UTF-8').'</code>';
        }, $text);

        // links [label](url)
        $text = preg_replace_callback('/\[(.+?)\]\(([^\s\)]+)(?:\s+\"([^\"]*)\")?\)/', function($m){
            $label = $m[1]; $url = $m[2]; $title = isset($m[3])?$m[3]:'';
            $href = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $titleAttr = $title!==''? ' title="'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'"':'';
            return '<a href="'.$href.'"'.$titleAttr.'>'.htmlspecialchars($label, ENT_NOQUOTES, 'UTF-8').'</a>';
        }, $text);

        // strong
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);
        // em
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text);
        $text = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/s', '<em>$1</em>', $text);

        return $text;
    }
}

