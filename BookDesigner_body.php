<?php
class BookDesigner extends SpecialPage {
    function __construct() {
        parent::__construct('BookDesigner', 'autoconfirmed');
        wfLoadExtensionMessages('BookDesigner');
        $this->options = new BookDesignerOptions();
    }

    # Internal values. Don't modify them, they get set at runtime
    protected $options;
    protected $validuser = false;
    protected $namespace = "";
    protected $bookname  = "";

    function bookName($set = null) {
        if ($set != null)
            $this->bookname = $set;
        return $this->bookname;
    }

    function bookNamespace($set = null) {
        if ($set != null)
            $this->namespace = $set . ":";
        return $this->namespace;
    }

    function validateUser()
    {
        global $wgUser;
        if (!$this->userCanExecute($wgUser)) {
            $this->displayRestrictionError();
            return false;
        }
        $this->validuser = $wgUser->isAllowed('buildbook');
        return $this->validuser;
    }



    function getCreateFlag($isroot) {
        $create = $this->options->CreateLeaves() || $isroot;
        return $create;
    }

    function createOnePage($path, $text) {
        global $wgOut, $wgScriptPath;
        $title = Title::newFromText($path);
        $article = new Article($title);
        $article->doEdit($text, "Creating page for book '{$this->bookname}'. " .
            "Automated page creation by BookDesigner");
        return $title;
    }

    # Returns an EXTREMELY basic text string for creating a header template.
    # TODO: Make this less bare-bones
    # TODO: Add Forward/Back links
    # TODO: Use a message to set the default, instead of hard-coding it here.
    function getDefaultHeaderTemplateText($bookname) {
        $text = <<<EOD

<div style="border: 1px solid #AAAAAA; background-color: #F8F8F8; padding: 5px; margin: auto; width: 95%">
<center>
<big>'''[[$bookname]]'''</big>
</center>
</div>

EOD;
        return $text;
    }

    # TODO: Make this less bare-bones
    # TODO: Add Forward/Back links
    # TODO: Use a message to set the default, instead of hard-coding it here.
    function getDefaultFooterTemplateText($bookname) {
        $text = <<<EOD

<div style="border: 1px solid #AAAAAA; background-color: #F8F8F8; padding: 5px; margin: auto; width: 95%">
<center>
<big>'''[[$bookname]]'''</big>
</center>
</div>

EOD;
        return $text;
    }

    function GetMessage($msgname) {
        return wfMsg('bookdesigner-' . $msgname);
    }

    # Main function, this is where execution starts
    function execute($par) {
        # TODO: Validate that we are logged in. Also, create an option to
        #       require certain permissions (either admin, or a custom
        #       permission or something)
        global $wgRequest, $wgOut;
        $this->setHeaders();
        $wgOut->setPageTitle("Book Designer");

        if (!$this->validateUser()) {
            $this->showAuthenticationError();
            return;
        }

        $this->loadJSAndCSS();
        $this->options->getOptions();

        $mode = "outline";
        $title = null;

        if(isset($par)) {
            $parts = explode('/', $par, 2);
            $mode = $parts[0];
            #$title = $parts[1];
        }
        if($wgRequest->wasPosted()) {
            if ($mode == 'verify') {
                $this->verifyPublishOutline();
            }
            else if ($mode == 'publish') {
                $this->reallyPublishOutline();
            }
            else {
                $this->unknownModeError('post', $mode, $title);
            }
        }
        else {
            # TODO: we've specified a book name, load that book into the outline
            #       $mode == 'outline' creates an empty outline with "title"
            #       $mode == 'preload' attempts to load an existing outline
            if (!isset($mode) || $mode == "" || $mode == "outline" || $mode == "preload") {
                $this->displayMainOutline($mode, $title);
                return;
            }
            else {
                $this->unknownModeError('show', $mode, $title);
            }

}
    }

    function showauthenticationError() {
        global $wgOut;
        $text = <<<EOT
<div>
    <p>
        <span style='color: darkred; font-weight: bold'>Error:</span>
        You must be logged in and have 'buildbook' permission to created
        books using this tool.
    </p>
</div>
EOT;
        $wgOut->addHTML($text);
    }

    function unknownModeError($type, $mode, $title) {
        global $wgOut;
        $title_extra = "";
        if (isset($title)) {
            $title_extra = "with arguments (" . $title . ")";
        }
        $text = <<<EOD
<p>
    <span style='color: red; font-weight: bold;'>Error:</span>
    Could not {$type} with mode {$mode} $title_extra
</p>

EOD;
        $wgOut->addHTML($text);
    }

    function verifyPublishOutline() {
        global $wgRequest;
        $text = $wgRequest->getText('VBDHiddenTextArea');
        $parser = new BookDesignerParser($this, $this->options);
        $parser->parse($text);
        # TODO: Instead of hard-coding in a list of pages that can be added,
        #       Allow the site to specify a list of standard pages, and supply
        #       a text template to be used on those pages.
        #if ($this->options->useHeader()) {
        #    $this->addPageToList("Template:" . $this->bookname,
        #        "Template:" . $this->bookname,
        #        $this->getDefaultHeaderTemplateText($this->bookname),
        #        true
        #    );
        #}
        #if ($this->options->useFooter()) {
        #    $this->addPageToList("Template:" . $this->bookname . "/Footer",
        #        "Template:" . $this->bookname . "/Footer",
        #        $this->getDefaultFooterTemplateText($this->bookname),
        #        true
        #    );
        #}

        $pagelist = $parser->getPages();
        $this->showConfirmationPage($this->bookname, $pagelist);
    }

    function showConfirmationPage($bookname, $pagelist) {
        # TODO: detect if any pages already exist. Link to them if they do, and
        #       bring the issue to the attention of the user
        global $wgOut, $wgScriptPath;
        $jspath  = "$wgScriptPath/extensions/BookDesigner";
        $wgOut->addScriptFile($jspath . "/designer.js");
        $this->addCSSFile("designer.css");
        $i = 0;
        $text = <<<EOT
<form action="{$wgScriptPath}/index.php?title=Special:BookDesigner/publish" method="POST">
    <div>
        <h2>
            Confirm Pages for {$bookname}
        </h2>
        <p>
            Below is a list of pages that will be created in your book. You can
            select which pages to create, and you can modify the page text. You
            cannot alter the structure of the book otherwise. Click
            <b>Publish</b> if everything is ready to publish.
        </p>
    </div>
EOT;
        $wgOut->addHTML($text);
        $numpages = count($pagelist);
        foreach ($pagelist as &$page) {
            $this->showPageSinglePageConfirmation($i, $page);
            $i++;
        }
        $text = <<<EOT
    <input type="submit" value="Publish" />
    <a href="$wgScriptPath/index.php?title=Special:BookDesigner">
        Cancel
    </a>
    <input type="hidden" name="VBDTotalPageCount" value="{$numpages}" />
    <input type="hidden" name="VBDBookName" value="{$this->bookname}" />
</form>
EOT;
        $wgOut->addHTML($text);
    }

    function showPageSinglePageConfirmation($idx, $page) {
        global $wgOut;
        $path = $page->fullname();
        $create = $this->options->createLeaves() ? true : $page->children() > 0;
        $text = $page->text();

        $checked = $create ? "checked" : "";
        $text = <<<EOT
        <div class="VBDConfirmPageDiv">
            <input type="hidden" name="path_{$idx}" value="{$path}"/>
            <input type="checkbox" name="confirm_{$idx}" {$checked}>
                <span style='font-size: larger; font-weight: bold;'>
                    {$path}
                </span>
            </input>
            [<a id="text_toggle_{$idx}"
                onclick="vbd.ToggleGUIWidget('text_div_{$idx}', 'text_toggle_{$idx}');"><!--
                -->{$this->GetMessage('show')}<!--
            --></a>]

            <div class="VBDPageTextDiv" id="text_div_{$idx}" style="display: none;">
                <textarea name="text_{$idx}" rows="10">{$text}</textarea>
            </div>
        </div>
EOT;
        $wgOut->addHTML($text);
    }

    function reallyPublishOutline() {
        global $wgRequest, $wgOut;;
        $numpages = $wgRequest->getInt('VBDTotalPageCount');
        $this->bookname = $wgRequest->getText('VBDBookName');
        $wgOut->addHTML("<ul>");
        for ($i = 0; $i < $numpages; $i++) {
            $path = $wgRequest->getText("path_{$i}");
            $create = $wgRequest->getBool("confirm_{$i}");
            $text = $wgRequest->getText("text_{$i}");
            if (!$create) {
                $this->showPageNotCreatedMessage($path);
            } else {
                $title = $this->createOnePage($path, $text);
                $this->showPageCreatedMessage($path, $title);
            }
        }
        $wgOut->addHTML("</ul>");
        # TODO: Show statistics (number of pages created, total time, etc)
        #       here
        # TODO: Show an "Oops!" delete/undo link here that goes back over
        #       the list of pages and deletes them all again (if the user
        #       is an admin)
    }

    function showPageNotCreatedMessage($path) {
        global $wgOut;
        $text = <<<EOT
    <div class="VBDCreateIgnored">
        <p>
            Did not create $path
        </p>
    </div>
EOT;
        $wgOut->addHTML($text);
    }

    function showPageCreatedMessage($path, $title) {
        global $wgOut;
        $url = $title->getFullURL();
        $text = <<<EOT
    <div class="VBDCreateSuccess">
        <p>
            Created <a href="{$url}">$path</a>
        </p>
    </div>
EOT;
        $wgOut->addHTML($text);
    }

    function loadJSAndCSS() {
        global $wgScriptPath, $wgOut;
        $jspath  = "$wgScriptPath/extensions/BookDesigner";
        $wgOut->addScriptFile($jspath . "/bookpage.js");
        $wgOut->addScriptFile($jspath . "/pagehead.js");
        $wgOut->addScriptFile($jspath . "/designer.js");
        $this->addCSSFile("designer.css");
    }

    function getVersion() {
        global $wg_VBDExtensionVersion;
        return $wg_VBDExtensionVersion;
    }

    function addCSSFile($file) {
        global $wgScriptPath, $wgOut;
        $csspath = "$wgScriptPath/extensions/BookDesigner";
        if(method_exists($wgOut, "addExtensionStyle")) {
            $wgOut->addExtensionStyle($csspath . "/" . $file);
        } else {
            # This is a hack for older MediaWiki (1.14 and below?).
            # addStyle prepends "$wgScriptPath/skins/" to the front,
            # so we need to navigate to the correct place
            $wgOut->addStyle("../extensions/BookDesigner/" . $file);
        }
    }

    function displayMainOutline() {
        global $wgOut, $wgScriptPath;

        # TODO: Have a hidden field somewhere that we can hold a list of
        #       pages for pre-populating the outline.
        $text = <<<EOD

<form action="{$wgScriptPath}/index.php?title=Special:BookDesigner/verify" method="POST">
    <textarea name="VBDHiddenTextArea" id="VBDHiddenTextArea" style="display: none;">
    </textarea>
    <div id="VBDWelcomeSpan">
        {$this->GetMessage('welcome')}
    </div>
    <div id="VBDStatSpan"></div>
    <div id="VBDInstructionSpan">
        <h2>
            <span style="float: right; font-size: 67%;">
                [<a id="VBDQuickStartToggle"
                    onclick="vbd.ToggleGUIWidget('VBDQuickStartInternal', 'VBDQuickStartToggle');"><!--
                    -->{$this->GetMessage('hide')}<!--
                --></a>]
            </span>
            {$this->GetMessage('qsistart')}
        </h2>
        <div id="VBDQuickStartInternal">
            {$this->GetMessage('qsi')}
        </div>
    </div>
    {$this->getOptionsWidget()}
    <input type="hidden" id="VBDVersion" value="{$this->getVersion()}"/>
    <div id="VBDOutlineSpan">
        {$this->GetMessage('jserror')}
    </div>
    <!-- TODO: Add another button here to "save" an incomplete outline to a page
         somewhere in userspace, and maybe a button somewhere to "load" an
         existing outline. -->
    <input type="submit" value="{$this->GetMessage('publishbutton')}" /><br>
    <!-- TODO: This is a temporary addition to aid in debugging. It shows the
         intermediate code before it's transmitted to the server. This way if
         there is some kind of a server error, we can save a copy of that
         intermediate code to a safe place so when we are making a huge outline
         we don't lose all that work. No i18n for debug stuff.
    -->
    <small>
        <a href="#" onclick="document.getElementById('VBDHiddenTextArea').style.display = 'block';">
            Show Intermediate Code
        </a>
    </small>
</form>

EOD;
        $wgOut->addHTML($text);
    }

    function getOptionsWidget() {
        $text = <<<EOD
<div id="VBDOptionsSpan">
        <h2>
            <span style="float: right; font-size: 67%;">
                [<a id="VBDOptionsToggle"
                    onclick="vbd.ToggleGUIWidget('VBDOptionsInternal', 'VBDOptionsToggle');"><!--
                    -->{$this->GetMessage('show')}<!--
                --></a>]
            </span>
            {$this->GetMessage('options')}
        </h2>
        <div id="VBDOptionsInternal" style="display: none;">
            <b>{$this->GetMessage('optsbook')}</b><br>
            <input type="checkbox" name="optUseNamespace" disabled>
                {$this->GetMessage('optusenamespace')}:
            </input>
            <br>
            <input type="text" style="margin-left: 6em;" name="optNamespace"
                value="{$this->namespace}" disabled>
            <br>
            <input type="checkbox" name="optUseUserSpace" disabled>
                {$this->GetMessage('optuseuserspace')}
            </input>
            <br>
            <b>
                {$this->GetMessage('optspage')}
            </b>
            <br>
            <input type="checkbox" name="optCreateLeaves"
                {$this->options->createLeaves(true)}>
                {$this->GetMessage('optcreateleaf')}
            </input>
            <br>
            <input type="checkbox" name="optNumberPages" disabled>
                {$this->GetMessage('optnumberpages')}
            </input>
            <br>
            <b>
                {$this->GetMessage('optstemplate')}
            </b>
            <br>
            <input type="checkbox" name="optHeaderTemplate"
                {$this->options->useHeader(true)}>
                {$this->GetMessage('optheadertemplate')}
            </input>
            <br>
            <input type="checkbox" name="optFooterTemplate"
                {$this->options->useFooter(true)}>
                {$this->GetMessage('optfootertemplate')}
            </input>
            <br>
            <b>
                Formatting Options
            </b>
            <br>
            Chapter Links:
            <input type="text" name="optChapterLinks"
                value="{$this->options->chapterLinkTemplate()}" disabled/>
            <br>
            Page Links:
            <input type="text" name="optPageLinks"
                value="{$this->options->pageLinkTemplate()}"/>
            <br>
            Headers:
            <input type="text" name="optHeaderStyle"
                value="{$this->options->sectionHeaderTemplate()}"/>
            <br>
            <!-- TODO: Add a <select> item here with a list of auto-generate
                       template styles -->
        </div>
    </div>
EOD;
        return $text;
    }
}

