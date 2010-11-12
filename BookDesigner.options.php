<?php
class BookDesignerOptions {
    protected $createleaves = true;
    protected $useheader = true;
    protected $usefooter = false;
    protected $pagelinktmpl = "* [[$1|$2]]";
    protected $chapterlinktmpl = "* [[$1|$2]]";
    protected $sectionheadtmpl = "== $1 ==";
    protected $booknamespace = null;

    function getOptions() {
        global $wgRequest, $wgUser, $wgOut;
        $this->createleaves = $wgRequest->getCheck("optCreateLeaves");
        $this->useheader = $wgRequest->getCheck("optHeaderTemplate");
        $this->usefooter = $wgRequest->getCheck("optFooterTemplate");

        $namespaceopt = $wgRequest->getVal("optNamespace");

        if ($namespaceopt == "default")
            $this->booknamespace = "";
        else if ($namespaceopt == "specify")
            $this->booknamespace = $wgRequest->getText("optNamespaceName") . ":";
        else if ($namespaceopt == "user")
            $this->booknamespace = $wgUser->getUserPage() . "/";

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

    function bookNamespace() {
        return $this->booknamespace;
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

    function createLeaves() {
        return $this->createleaves;
    }

    function useHeader() {
        return $this->useheader;
    }

    function useFooter() {
        return $this->usefooter;
    }

    function getMessage($msgname) {
        return wfMsg('bookdesigner-' . $msgname);
    }

    function getOptionsWidget() {
        $leaves = $this->checkbox($this->createleaves);
        $head = $this->checkbox($this->useheader);
        $foot = $this->checkbox($this->usefooter);
        $text = <<<EOD
<div id="VBDOptionsSpan">
        <h2>
            <span style="float: right; font-size: 67%;">
                [<a id="VBDOptionsToggle"
                    onclick="vbd.ToggleGUIWidget('VBDOptionsInternal', 'VBDOptionsToggle');"><!--
                    -->{$this->getMessage('show')}<!--
                --></a>]
            </span>
            {$this->getMessage('options')}
        </h2>
        <div id="VBDOptionsInternal" style="display: none;">
            <b>{$this->getMessage('optsbook')}</b><br>
            <input type="radio" name="optNamespace" value="default" checked>
                {$this->getMessage('optdefaultnamespace')}
            </input>
            <br />
            <input type="radio" name="optNamespace" value="specify">
                {$this->getMessage('optusenamespace')}:
            </input>
            <br />
            <input type="text" style="margin-left: 6em;" name="optNamespaceName"
                value="">
            <br />
            <input type="radio" name="optNamespace" value="user">
                {$this->getMessage('optuseuserspace')}
            </input>
            <br>
            <b>
                {$this->getMessage('optspage')}
            </b>
            <br>
            <input type="checkbox" name="optCreateLeaves" {$leaves}>
                {$this->getMessage('optcreateleaf')}
            </input>
            <br>
            <input type="checkbox" name="optNumberPages" disabled>
                {$this->getMessage('optnumberpages')}
            </input>
            <br>
            <b>
                {$this->getMessage('optstemplate')}
            </b>
            <br>
            <input type="checkbox" name="optHeaderTemplate" {$head}>
                {$this->getMessage('optheadertemplate')}
            </input>
            <br>
            <input type="checkbox" name="optFooterTemplate" {$foot}>
                {$this->getMessage('optfootertemplate')}
            </input>
            <br>
            <b>
                Formatting Options
            </b>
            <br>
            Chapter Links:
            <input type="text" name="optChapterLinks"
                value="{$this->chapterlinktmpl}" disabled/>
            <br>
            Page Links:
            <input type="text" name="optPageLinks"
                value="{$this->pagelinktmpl}"/>
            <br>
            Headers:
            <input type="text" name="optHeaderStyle"
                value="{$this->sectionheadtmpl}"/>
            <br>
            <!-- TODO: Add a <select> item here with a list of auto-generate
                       template styles -->
        </div>
    </div>
EOD;
        return $text;
    }
}
