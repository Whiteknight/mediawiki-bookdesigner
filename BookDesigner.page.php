<?php
class BookDesignerPage {
    protected $debug = false;

    protected $name;
    protected $fullname;
    protected $children = "";
    protected $text = "";
    protected $forcecreate = false;

    function __construct($name, $full) {
        $this->name = $name;
        $this->fullname = $full;
        $this->_dbgl("Creating page $name ($full)");
    }


    function _dbg($word) {
        global $wgOut;
        if($this->debug)
            $wgOut->addHTML($word);
    }
    function _dbgl($word) {
        $this->_dbg($word . "<br/>");
    }

    function name($set = null) {
        if ($set != null)
            $this->name = $set;
        return $this->name;
    }

    function fullname($set = null) {
        if ($set != null)
            $this->fullname = $set;
        return $this->fullname;
    }

    function children($set = null) {
        if ($set != null)
            $this->children = $set;
        return $this->children;
    }

    function forceCreate($force) {
        $this->forcecreate = $force;
    }

    function shouldCreate($force) {
        if ($force || $this->forcecreate)
            return true;
        return ($this->children > 0);
    }

    function text($set = null) {
        if ($set != null) {
            $this->_dbgl("Setting text on page {$this->name} to '$set'");
            $this->text = $set;
        }
        return $this->text;
    }

    function addText($txt) {
        $this->text .= $txt;
        $this->_dbgl("Adding text to {$this->name} to '$txt'");
    }
}