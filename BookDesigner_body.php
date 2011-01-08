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
    protected $msgid = 0;

    # HELPER METHODS

    function validateUser() {
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

    function showMessage($msg, $back) {
        global $wgOut, $wgScriptPath;
        $id = $this->msgid++;
        $backlink = $back ?
            "{$wgScriptPath}/index.php?title=Special:BookDesigner" :
            "javascript: vbd.KillGUIWidget('bookdesigner-msg-{$id}');";
        $backtext = $back ? $this->GetMessage('backnav') : $this->GetMessage('hide');
        $text =<<<EOD
<div class="VBDMessageDiv" id="bookdesigner-msg-{$id}">
    {$this->GetMessage($msg)}
    <br />
    <a href="{$backlink}">
        {$backtext}
    </a>
</div>
EOD;
        $wgOut->addHTML($text);
    }

    function showErrorMessage($msg, $back) {
        global $wgOut, $wgScriptPath;
        $id = $this->msgid++;
        $backlink = $back ?
            "{$wgScriptPath}/index.php?title=Special:BookDesigner" :
            "javascript: vbd.KillGUIWidget('bookdesigner-msg-{$id}');";
        $backtext = $back ? $this->GetMessage('backnav') : $this->GetMessage('hide');
        $text =<<<EOD
<div class="VBDErrorMessageDiv">
    <span class="VBDErrorSpan">{$this->GetMessage("error")}</span>
    {$this->GetMessage($msg)}
    <br />
    <a href="{$backlink}">
        {$backtext}
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
        $this->msgid = 0;
        $this->setHeaders();
        $wgOut->setPageTitle("Book Designer");
        $this->loadJSAndCSS();
        $mode = "outline";
        $outlineid = 0;

        if (!$this->validateUser()) {
            $this->showErrorMessage('errauthenticate', true);
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
                $this->displayMainOutline("", 0, false);
                return;
            }
            else if ($mode == 'loadoutline' && isset($outlineid))
                $this->loadOutline($outlineid);
            else if ($mode == 'deleteoutline' && isset($outlineid))
                $this->deleteOutline($outlineid);
            else if ($mode == 'share' && isset($outlineid))
                $this->shareOutline($outlineid);
            else if ($mode == 'unshare' && isset($outlineid))
                $this->unshareOutline($outlineid);
            else
                $this->unknownModeError('show', $mode, $title);
        }
    }

    function unknownModeError($type, $mode) {
        # TODO: Covert showErrorMessage to take an array of parameters, and
        #       use that to replace this function.
        global $wgOut;
        $text = <<<EOD
<p>
    <span style='color: red; font-weight: bold;'>Error:</span>
    Could not {$type} with mode {$mode}
</p>

EOD;
        $wgOut->addHTML($text);
    }

    # SHARE/UNSHARE FUNCTIONS

    function shareOutline($outlineid) {
        global $wgUser, $wgRequest;
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select('bookdesigner_outlines',
            array("id", "user_id", "shared", "outline"),
            'id=' . $outlineid
        );
        if ($dbr->numRows($res) == 1) {
            $row = $dbr->fetchObject($res);
            if ($row->user_id == $wgUser->getId()) {
                $dbw = wfGetDB(DB_MASTER);
                $dbw->update('bookdesigner_outlines',
                    array('shared' => 1),
                    array('id' => $outlineid)
                );
            }
        }
        $this->showMessage('shared', false);
        $this->displayMainOutline($wgRequest->getText("VBDHiddenTextArea"), 0, false);
    }

    function unshareOutline($outlineid) {
        global $wgUser, $wgRequest;
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select('bookdesigner_outlines',
            array("id", "user_id", "shared", "outline"),
            'id=' . $outlineid
        );
        if ($dbr->numRows($res) == 1) {
            $row = $dbr->fetchObject($res);
            if ($row->user_id == $wgUser->getId()) {
                $dbw = wfGetDB(DB_MASTER);
                $dbw->update('bookdesigner_outlines',
                    array('shared' => 0),
                    array('id' => $outlineid)
                );
            }
        }
        $this->showMessage('unshared', false);
        $this->displayMainOutline($wgRequest->getText("VBDHiddenTextArea"), 0, false);
    }

    # LOAD/SAVE/DELETE FUNCTIONS

    function loadOutline($outlineid) {
        global $wgUser, $wgRequest;
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select('bookdesigner_outlines',
            array("id", "user_id", "shared", "outline"),
            'id=' . $outlineid
        );
        if ($dbr->numRows($res) == 1) {
            $row = $dbr->fetchObject($res);
            #if ($row->shared == 1 || $row->user_id == $wgUser->getId())
                $this->displayMainOutline($row->outline, $row->id, $row->shared);
            #else {
            #    $this->showErrorMessage("errload", false);
            #    $this->displayMainOutline($wgRequest->getText("VBDHiddenTextArea"), 0, false);
            #}
        }
        else
            $this->showErrorMessage("errload", true);
    }

    function deleteOutline($outlineid) {
        global $wgUser, $wgScriptPath;
        $dbw = wfGetDB(DB_MASTER);
        $res = $dbw->select('bookdesigner_outlines', 'user_id', 'id=' . $outlineid);
        if ($dbw->numRows($res) == 1 && $dbw->fetchObject($res)->user_id == $wgUser->getId()) {
            $dbw->delete('bookdesigner_outlines', array(
                'id' => $outlineid
            ));
            $this->showMessage("msgdeleted", false);
            $this->displayMainOutline("", 0, false);
        } else
            $this->showErrorMessage("errdeleted", true);
    }

    function saveOutline() {
        global $wgUser, $wgOut, $wgRequest, $wgScriptPath;
        $text = $wgRequest->getText('VBDHiddenTextArea');
        $parser = new BookDesignerParser($this, null);
        $parser->parseTitlePageOnly($text);
        $this->titlepage = $parser->titlePage();
        $dbw = wfGetDB(DB_MASTER);
        $dbw->insert('bookdesigner_outlines', array(
            'user_id'  => $wgUser->getId(),
            'shared'   => 0,
            'savedate' => gmdate('Y-m-d H:i:s'),
            'bookname' => $this->titlepage->name(),
            'outline'  => $text
        ));
        $this->showMessage('msgsaved', false);
        $this->displayMainOutline($text, 0, false);
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
    <input type="submit" value="{$this->GetMessage('publishbutton')}" />
    <a href="{$wgScriptPath}/index.php?title=Special:BookDesigner">
        {$this->GetMessage('backnav')}
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

    function sanitizeOutline($text) {
        $text = str_replace("<", "&lt;", $text);
        $text = str_replace(">", "&gt;", $text);
        $text = str_replace("'s", "ZOMGAPOSs", $text);
        return $text;
    }

    function displayMainOutline($inittext, $id, $shared) {
        global $wgOut, $wgScriptPath;
        $inittext = $this->sanitizeOutline($inittext);

        $text = <<<EOD

<form action="{$wgScriptPath}/index.php?title=Special:BookDesigner/verify" method="POST">
    <textarea name="VBDHiddenTextArea" id="VBDHiddenTextArea" style="display: none;">
        {$inittext}
    </textarea>
    <input type="hidden" name="VBDOutlineID" id="VBDOutlineID" value="{$id}"/>
    <input type="hidden" name="VBDShared" id="VBDShared" value="{$shared}"/>
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
        $res = $dbr->select('bookdesigner_outlines',
            array('id', 'savedate', 'bookname', 'shared'),
            'user_id=' . $wgUser->getId()
        );
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
                    <!--&mdash;-->
EOD;
            $wgOut->addHTML($text);
            #if ($row->shared == 0) {
            #    $text = <<<EOD
            #        <a href="{$wgScriptPath}/index.php?title=Special:BookDesigner/share/{$row->id}">
            #            Share
            #        </a>
#EOD;
            #} else {
            #    $text = <<<EOD
            #        <a href="{$wgScriptPath}/index.php?title=Special:BookDesigner/unshare/{$row->id}">
            #            Unshare
            #        </a>
#EOD;
            #}
            #$wgOut->addHTML($text);
            $text = <<<EOD
                </div>
            </div>
EOD;
            $wgOut->addHTML($text);
        }
    }
}

