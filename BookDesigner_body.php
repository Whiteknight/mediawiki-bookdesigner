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
    protected $titlepage = null;

    # HELPER METHODS

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

    function GetMessage($msgname) {
        return wfMsg('bookdesigner-' . $msgname);
    }

    function showMessage($msg) {
        global $wgOut, $wgScriptPath;
        $text =<<<EOD
<div class="VBDMessageDiv">
    {$this->GetMessage($msg)}
    <br />
    <a href="{$wgScriptPath}/index.php?title=Special:BookDesigner">
        {$this->GetMessage('backnav')}
    </a>
</div>
EOD;
        $wgOut->addHTML($text);
    }

    function showErrorMessage($msg) {
        global $wgOut, $wgScriptPath;
        $text =<<<EOD
<div class="VBDErrorMessageDiv">
    <span class="VBDErrorSpan">{$this->GetMessage("error")}</span>
    {$this->GetMessage($msg)}
    <br />
    <a href="{$wgScriptPath}/index.php?title=Special:BookDesigner">
        {$this->GetMessage('backnav')}
    </a>
</div>
EOD;
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

    # Main function, this is where execution starts
    function execute($par) {
        # TODO: Validate that we are logged in. Also, create an option to
        #       require certain permissions (either admin, or a custom
        #       permission or something)
        global $wgRequest, $wgOut;
        $this->setHeaders();
        $wgOut->setPageTitle("Book Designer");
        $this->loadJSAndCSS();
        $mode = "outline";
        $outlineid = 0;

        if (!$this->validateUser()) {
            $this->showErrorMessage('errauthenticate');
            return;
        }

        if(isset($par)) {
            $parts = explode('/', $par, 2);
            $mode = $parts[0];
            if (count($parts) > 1)
                $outlineid = $parts[1];
        }
        if($wgRequest->wasPosted()) {
            if ($mode == 'verify') {
                $submit = $wgRequest->getVal("btnSubmit");
                if ($submit == $this->GetMessage("publishbutton")) {
                    $this->options->getOptions();
                    $this->verifyPublishOutline();
                } else if ($submit == $this->GetMessage("savebutton"))
                    $this->saveOutline();
            }
            else if ($mode == 'publish')
                $this->reallyPublishOutline();
            else
                $this->unknownModeError('post', $mode, $title);
        }
        else {
            # TODO: we've specified a book name, load that book into the outline
            #       $mode == 'outline' creates an empty outline with "title"
            #       $mode == 'preload' attempts to load an existing outline
            if (!isset($mode) || $mode == "" || $mode == "outline" || $mode == "preload") {
                $this->displayMainOutline("");
                return;
            }
            else if ($mode == 'loadoutline' && isset($outlineid))
                $this->loadOutline($outlineid);
            else if ($mode == 'deleteoutline' && isset($outlineid))
                $this->deleteOutline($outlineid);
            else
                $this->unknownModeError('show', $mode, $title);
        }
    }

    function unknownModeError($type, $mode) {
        global $wgOut;
        $text = <<<EOD
<p>
    <span style='color: red; font-weight: bold;'>Error:</span>
    Could not {$type} with mode {$mode}
</p>

EOD;
        $wgOut->addHTML($text);
    }

    # LOAD/SAVE/DELETE FUNCTIONS

    function loadOutline($outlineid) {
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select('bookdesigner_outlines', array("user_id", "outline"), 'id=' . $outlineid);
        if ($dbr->numRows($res) == 1) {
            $row = $dbr->fetchObject($res);
            $this->displayMainOutline($row->outline);
        }
        else $this->showErrorMessage("errload");
    }

    function deleteOutline($outlineid) {
        global $wgUser, $wgScriptPath;
        $dbw = wfGetDB(DB_MASTER);
        $res = $dbw->select('bookdesigner_outlines', 'user_id', 'id=' . $outlineid);
        if ($dbw->numRows($res) == 1 && $dbw->fetchObject($res)->user_id == $wgUser->getId()) {
            $dbw->delete('bookdesigner_outlines', array(
                'id' => $outlineid
            ));
            $this->showMessage("msgdeleted");
        } else
            $this->showMessage("errdeleted");
    }

    function saveOutline() {
        global $wgUser, $wgOut, $wgRequest, $wgScriptPath;
        $text = $wgRequest->getText('VBDHiddenTextArea');
        $parser = new BookDesignerParser($this, null);
        $parser->parseTitlePageOnly($text);
        $this->titlepage = $parser->titlePage();
        $dbw = wfGetDB(DB_MASTER);
        $dbw->insert('bookdesigner_outlines', array(
            'user_id' => $wgUser->getId(),
            'savedate' => gmdate('Y-m-d H:i:s'),
            'bookname' => $this->titlepage->name(),
            'outline' => $text
        ));
        $this->showMessage('msgsaved');
    }

    # VERIFY OUTLINE BEFORE PUBLISH FUNCTIONS

    function verifyPublishOutline() {
        global $wgRequest;
        $text = $wgRequest->getText('VBDHiddenTextArea');
        $parser = new BookDesignerParser($this, $this->options);
        $parser->parse($text);
        $this->titlepage = $parser->titlePage();
        $pagelist = $parser->getPages();
        $this->showConfirmationPage($this->titlepage->name(), $pagelist);
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
    <input type="hidden" name="VBDBookName" value="{$this->titlepage->name()}" />
</form>
EOT;
        $wgOut->addHTML($text);
    }

    function showPageSinglePageConfirmation($idx, $page) {
        global $wgOut;
        $path = $page->fullname();
        $create = $page->shouldCreate($this->options->createLeaves());
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

    # PUBLISH OUTLINE TO BOOK

    function reallyPublishOutline() {
        global $wgRequest, $wgOut;;
        $numpages = $wgRequest->getInt('VBDTotalPageCount');
        $bookname = $wgRequest->getText('VBDBookName');
        $wgOut->addHTML("<ul>");
        for ($i = 0; $i < $numpages; $i++) {
            $path = $wgRequest->getText("path_{$i}");
            $create = $wgRequest->getBool("confirm_{$i}");
            $text = $wgRequest->getText("text_{$i}");
            if (!$create) {
                $this->showPageNotCreatedMessage($path);
            } else {
                $title = $this->createOnePage($bookname, $path, $text);
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

    function createOnePage($bookname, $path, $text) {
        global $wgOut, $wgScriptPath;
        $title = Title::newFromText($path);
        $article = new Article($title);
        $article->doEdit($text, "Creating page for book '{$bookname}'. " .
            "Automated page creation by BookDesigner");
        return $title;
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

    # MAIN OUTLINE FUNCTIONS

    function displayMainOutline($inittext) {
        global $wgOut, $wgScriptPath;

        # TODO: Have a hidden field somewhere that we can hold a list of
        #       pages for pre-populating the outline.
        $text = <<<EOD

<form action="{$wgScriptPath}/index.php?title=Special:BookDesigner/verify" method="POST">
    <textarea name="VBDHiddenTextArea" id="VBDHiddenTextArea" style="display: none;">
        {$inittext}
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
    {$this->options->getOptionsWidget()}
    <input type="hidden" id="VBDVersion" value="{$this->getVersion()}"/>
    <div id="VBDOutlineSpan">
        {$this->GetMessage('jserror')}
    </div>
    <!-- TODO: Add another button here to "save" an incomplete outline to a page
         somewhere in userspace, and maybe a button somewhere to "load" an
         existing outline. -->
    <input type="submit" name="btnSubmit" value="{$this->GetMessage('publishbutton')}" />
EOD;
        $wgOut->addHTML($text);
        if ($this->hasOutlineManager())
            $this->showOutlineManager();
        $text = <<<EOD
</form>
EOD;
        $wgOut->addHTML($text);
    }

    function hasOutlineManager() {
        try {
            $dbr = wfGetDB(DB_SLAVE);
            $res = $dbr->select('bookdesigner_outlines', array('id'));
            return true;
        }
        catch (Exception $e) {
            return false;
        }
    }

    function showOutlineManager() {
        global $wgOut, $wgScriptPath;
        $text = <<<EOD
        <div id="VBDOutlineManager">
        <script type="text/javascript">
            function really_delete(id) {
                var url = "{$wgScriptPath}/index.php?title=Special:BookDesigner/deleteoutline/" + id;
                if(confirm("{$this->GetMessage('reallydelete')}"))
                    document.location = url;
            }
        </script>
        <input type="submit" name="btnSubmit" value="{$this->GetMessage('savebutton')}" />
EOD;
        $wgOut->addHTML($text);
        $this->getSavedOutlines();
        $text = <<<EOD
    </div>
EOD;
        $wgOut->addHTML($text);
}

    function getSavedOutlines() {
        global $wgOut, $wgUser, $wgScriptPath;
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select('bookdesigner_outlines', array('id', 'savedate', 'bookname'), 'user_id=' . $wgUser->getId());
        while($row = $dbr->fetchObject($res)) {
            $text = <<<EOD
            <div class="VBDSavedOutlineEntry">
                <b>{$row->bookname}</b>: {$row->savedate}
                <br />
                <div class="VBDSavedOutlineCommands">
                    <a href="{$wgScriptPath}/index.php?title=Special:BookDesigner/loadoutline/{$row->id}">
                        Load
                    </a>
                    &mdash;
                    <a href="javascript: really_delete({$row->id})">
                        Delete
                    </a>
                </div>
            </div>
EOD;
            $wgOut->addHTML($text);
        }
    }
}

