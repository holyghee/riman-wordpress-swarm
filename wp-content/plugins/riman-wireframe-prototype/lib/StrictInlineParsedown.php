<?php
require_once __DIR__ . '/Parsedown.php';
class StrictInlineParsedown extends Parsedown {
    public function linePublic($text){
        return $this->line($text);
    }
}

