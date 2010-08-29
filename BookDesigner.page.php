<?php
class BookDesignerPage {
    function __constructor($name, $full) {
        $this->name = $name;
        $this->fullname = $full;
    }

    protected $name;
    protected $fullname;
    protected $children = "";
    protected $text = "";

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

    function text($set = null) {
        if ($set != null)
            $this->text = $set;
        return $this->text;
    }

    function addText($txt) {
        $this->text .= $txt;
    }
}