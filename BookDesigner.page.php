<?php
class BookDesignerPage {
    protected $name;
    protected $fullname;
    protected $children = "";
    protected $text = "";
    protected $forcecreate = false;

    function __construct($name, $full) {
        $this->name = $name;
        $this->fullname = $full;
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
            $this->text = $set;
        }
        return $this->text;
    }

    function addText($txt) {
        $this->text .= $txt;
    }
}