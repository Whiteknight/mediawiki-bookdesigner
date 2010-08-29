<?php
class BookDesignerOptions {
    protected $createleaves = true;
    protected $useheader = true;
    protected $usefooter = false;
    protected $pagelinktmpl = "* [[$1|$2]]";
    protected $chapterlinktmpl = "* [[$1|$2]]";
    protected $sectionheadtmpl = "== $1 ==";

    function getOptions() {
        global $wgRequest;
        $this->createleaves = $wgRequest->getCheck("optCreateLeaves");
        $this->useheader = $wgRequest->getCheck("optHeaderTemplate");
        $this->usefooter = $wgRequest->getCheck("optFooterTemplate");

        $tmpl = $wgRequest->getText('optPageLinks');
        if (isset($tmpl) && strlen($tmpl) > 0)
            $this->pagelinktmpl = $tmpl;

        $tmpl = $wgRequest->getText('optChapterLinks');
        if (isset($tmpl) && strlen($tmpl) > 0)
            $this->chapterlinktmpl = $tmpl;

        $tmpl = $wgRequest->getText('optHeaderStyle');
        if (isset($tmpl) && strlen($tmpl) > 0)
            $this->sectionheadtmpl = $tmpl;
    }

    function pageLinkTemplate() {
        return $this->pagelinktmpl;
    }

    function chapterLinkTemplate() {
        return $this->chapterlinktmpl;
    }

    function sectionHeaderTemplate() {
        return $this->sectionheadtmpl;
    }

    function checkbox($b) {
        return $b ? "checked" : "";
    }

    function createLeaves($check = false) {
        if ($check)
            return $this->checkbox($this->createleaves);
        return $this->createleaves;
    }

    function useHeader($check = false) {
        if ($check)
            return $this->checkbox($this->useheader);
        return $this->useheader;
    }

    function useFooter($check = false) {
        if ($check)
            return $this->checkbox($this->usefooter);
        return $this->usefooter;
    }
}