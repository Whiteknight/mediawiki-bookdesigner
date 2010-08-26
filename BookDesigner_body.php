<?php
class BookDesigner extends SpecialPage {
    function __construct() {
        parent::__construct( 'BookDesigner' );
        wfLoadExtensionMessages('BookDesigner');
    }

    # set this to true to enable debugging output.
    protected $debug = false;

    # Internal values. Don't modify them, they get set at runtime
    protected $options = array(
        "CreateLeaves"    => true,
        "UseHeader"       => true,
        "UseFooter"       => false,
        "NumberPages"     => false,
        "UseNamespace"    => false,
        "GenerateHeader"  => false,
        "GenerateFooter"  => false,
        "UseIntroduction" => true,
        "UseResources"    => false,
        "UseLicensing"    => true
    );
    protected $namespace = "";
    protected $bookname  = "";

    function getOption($name) {
        return $this->options[$name];
    }

    function getOptionCheckbox($name) {
        return $this->options[$name] ? "checked" : "";
    }

    function setOption($name, $value) {
        $this->options[$name] = $value;
    }

    function getHeaderTemplateTag() {
        return $this->getOption('UseHeader') ? "{{" . $this->bookname . "}}\n\n" : "";
    }

    function getPageHeadText($isroot) {
        $text = $this->getHeaderTemplateTag();
        if ($isroot && $this->getOption('UseIntroduction'))
            $text .= "\n\n*[[" . $this->bookname . "/Introduction|Introduction]]\n";
        return $text;
    }

    function getPageFootText($isroot) {
        $text = "\n\n";
        if ($isroot && $this->getOption('UseResources'))
            $text .= "*[[" . $this->bookname . "/Resources|Resources]]\n";
        if ($isroot && $this->getOption('UseLicensing'))
            $text .= "*[[" . $this->bookname . "/Licensing|Licensing]]\n";
        return $text . $this->getFooterTemplateTag();
    }

    # Quick and dirty debugging utilities. The value of $this->debug determines
    # whether we print something. These functions can probably disappear soon
    # since the parseBookPage parser routine has been mostly tested.
    function _dbg($word) {
        global $wgOut;
        if($this->debug)
            $wgOut->addHTML($word);
    }
    function _dbgl($word) {
        $this->_dbg($word . "<br/>");
    }

    function getFooterTemplateTag() {
        # TODO: Add an option to specify a footer tag
        return "";
    }

    function GetCreateFlag($isroot) {
        $create = $this->getOption('CreateLeaves') || $isroot;
        return $create;
    }

    function addPageToList(&$pagelist, $pagename, $fullname, $pagetext, $create) {
        $pagelist[] = array(
            "name"     => $pagename,
            "fullname" => $fullname,
            "text"     => $pagetext,
            "create"   => $create
        );
        $this->_dbgl("Adding page $fullname");
    }

    function getIntroductionPageText() {
        return $this->GetHeaderTemplateTag() .
            "\n\n" .
             $this->GetFooterTemplateTag();
    }

    function getResourcesPageText() {
        return $this->GetHeaderTemplateTag() .
            "\n\n" .
             $this->GetFooterTemplateTag();
    }

    function getLicensingPageText() {
        return $this->GetHeaderTemplateTag() .
            "\n\n" .
             $this->GetFooterTemplateTag();
    }

    # Home-brewed recursive descent parser. Yes there are better ways of doing
    # this, and yes this is ugly and stupid and ugly. Whatever, this is what
    # we have.
    # [] contain lists of pages. {} contain lists of headings. Each page has[]{}
    # and each heading has only []. Each bare line of text inside a set of
    # brackets is that type of thing. Empty lines are ignored.
    function parseBookPage(&$pagelist, $page, $path, $lines, $idx) {
        global $wgOut, $wgScriptPath;
        $isroot = ($idx == 1);
        $subpagenum = 0;
        $pagetext = $this->getPageHeadText($isroot);
        $createpage = $this->GetCreateFlag($isroot);
        # Loop over all subpages inside [] brackets
        for($i = $idx; $i < sizeof($lines); $i++) {
            $line = rtrim($lines[$i]);
            $this->_dbg("($path) Line $i: '$line'> ");
            if($line == '[') {
                $this->_dbgl("Ignored");
                continue;
            }
            if(strlen($line) == 0) {
                $this->_dbgl("Ignored");
                continue;
            }
            if($line == "]") {
                $this->_dbgl("Breaking subpage loop");
                $i++;
                $idx = $i;
                break;
            }
            else {
                # We have a page name
                $this->_dbgl("Recurse");
                $subpagenum++;
                $name = ($this->getOption('NumberPages') ? $subpagenum . ". " : "") . $line;
                $createpage = TRUE;
                $newpath = $path . "/" . $name;
                $pagetext .= "*[[" . $newpath . "|" . $name . "]]\n";
                $i = $this->parseBookPage($pagelist, $name, $newpath, $lines, $i + 1);
            }
        }
        $pagetext .= "\n";

        # Loop over all headings inside {} brackets
        for($i = $idx; $i < sizeof($lines); $i++) {
            $line = rtrim($lines[$i]);
            $this->_dbg("($path) Line $i: '$line'> ");
            if($line == '{') {
                $this->_dbgl("Ignored");
                continue;
            }
            if(strlen($line) == 0) {
                $this->_dbgl("Ignored");
                continue;
            }
            if($line == '}') {
                $this->_dbgl("Breaking heading loop");
                $idx = $i;
                break;
            }
            $this->_dbgl("Heading");
            $createpage = TRUE;
            $pagetext .= "== " . $line . " ==\n\n";
            # a heading can have pages under it, so enter another loop here to
            # handle those pages.
            for($i++; $i < sizeof($lines); $i++) {
                $line2 = rtrim($lines[$i]);
                $this->_dbg("Line " . $i . ": " . $line2 . "> ");
                if($line2 == '[') {
                    $this->_dbgl("Ignored");
                    continue;
                }
                if(strlen($line2) == 0) {
                    $this->_dbgl("Ignored");
                    continue;
                }
                if($line2 == ']') {
                    $this->_dbgl("Breaking heading-subpage loop");
                    break;
                }
                $this->_dbgl("Heading-Subpage");
                $newpath = $path . "/" . $line2;
                $pagetext .= "*[[" . $newpath . "|" . $line2 . "]]\n";
                $j = $i + 1;
                $i = $this->parseBookPage($pagelist, $line2, $newpath, $lines, $i + 1);
            }
        }

        # Get the rest of the text, most of which is optional
        $pagetext = $pagetext . $this->getPageFootText($isroot);

        # We've parsed all direct subpages and all headings (and all subpages
        # of those). We have all the information we need now to actually create
        # this page. Page name is in $path. Page text is in $pagetext
        # We only create the page if (1) we opt to create all pages, (2) the
        # page contains subpages, (3) the page contains headings, or (4) it is
        # the main page.
        $this->addPageToList($pagelist, $page, $path, $pagetext, $createpage);
        return $idx;
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
    # TODO: Make this user-configurable
    function getTemplateText($bookname) {
        $text = <<<EOD

<div style="border: 1px solid #AAAAAA; background-color: #F8F8F8; padding: 5px; margin: auto; width: 95%">
<center>
<big>'''[[$bookname]]'''</big>
</center>
</div>

EOD;
        return $text;
    }

    function getOptions() {
        global $wgRequest;
        $this->setOption("CreateLeaves", $wgRequest->getCheck("optCreateLeaves"));
        $this->setOption("UseHeader", $wgRequest->getCheck("optHeaderTemplate"));
        $this->setOption("NumberPages", $wgRequest->getCheck("optNumberPages"));
        $this->setOption("UseNamespace", $wgRequest->getCheck("optUseNamespace"));

        $this->namespace = $this->getOption('UseNamespace')
            ? $wgRequest->getText("optNamespace") . ":" : "";
        $this->setOption('GenerateHeader', $wgRequest->getCheck("optAutogenHeaderTemplate"));
        $this->setOption('UseIntroduction', $wgRequest->getCheck("optIntroductionPage"));
        $this->setOption('UseResources', $wgRequest->getCheck("optResourcesPage"));
        $this->setOption('UseLicensing', $wgRequest->getCheck("optLicensingPage"));
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
        $this->loadJSAndCSS();

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
                $this->_dbgl("publish");
                $this->reallyPublishOutline();
            }
            else {
                $this->_dbgl("post error");
                $this->unknownModeError('post', $mode, $title);
            }
        }
        else {
            # TODO: we've specified a book name, load that book into the outline
            #       $mode == 'outline' creates an empty outline with "title"
            #       $mode == 'preload' attempts to load an existing outline
            if (!isset($mode) || $mode == "" || $mode == "outline" || $mode == "preload") {
                $this->displayMainOutline($mode, $title);
            }
            else {
                $this->unknownModeError('show', $mode, $title);
            }
        }
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
        $lines = explode("\n", $text);
        $this->bookname = $lines[0];
        $this->getOptions();
        $pagelist = array();
        $this->parseBookPage($pagelist, $lines[0], $this->namespace . $lines[0], $lines, 1);
        if ($this->getOption('UseHeader')) {
            $this->addPageToList($pagelist, "Template:" . $this->bookname,
                "Template:" . $this->bookname,
                $this->getTemplateText($this->bookname),
                true
            );
        }
        if ($this->getOption('UseIntroduction')) {
            $this->addPageToList($pagelist, "Introduction",
                $this->bookname . "/Introduction",
                $this->getIntroductionPageText(),
                true
            );
        }
        if ($this->getOption('UseResources')) {
            $this->addPageToList($pagelist, "Resources",
                $this->bookname . "/Resources",
                $this->getResourcesPageText(),
                true
            );
        }
        if ($this->getOption('UseLicensing')) {
            $this->addPageToList($pagelist, "Licensing",
                $this->bookname . "/Licensing",
                $this->getLicensingPageText(),
                true
            );
        }
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
            $this->showPageSinglePageConfirmation($i, $page["name"],
                $page["fullname"], $page["text"], $page["create"]);
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

    function showPageSinglePageConfirmation($idx, $name, $path, $text, $create) {
        global $wgOut;
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
            <input type="checkbox" name="optUseNamespace"
                {$this->getOptionCheckbox('UseNamespace')}>
                {$this->GetMessage('optusenamespace')}:
            </input>
            <br>
            <input type="text" style="margin-left: 6em;" name="optNamespace"
                value="{$this->namespace}">
            <br>
            <input type="checkbox" name="optUseUserSpace" disabled>
                {$this->GetMessage('optuseuserspace')}
            </input>
            <br>
            <input type="checkbox" name="optIntroductionPage"
                {$this->getOptionCheckbox('UseIntroduction')}>
                {$this->GetMessage('optintroductionpage')}
            </input>
            <br>
            <input type="checkbox" name="optResourcesPage"
                {$this->getOptionCheckbox('UseResources')}>
                {$this->GetMessage('optresourcespage')}
            </input>
            <br>
            <input type="checkbox" name="optLicensingPage"
                {$this->getOptionCheckbox('UseLicensing')}>
                {$this->GetMessage('optlicensingpage')}
            </input>
            <br>
            <b>
                {$this->GetMessage('optspage')}
            </b>
            <br>
            <input type="checkbox" name="optCreateLeaves"
                {$this->getOptionCheckbox('CreateLeaves')}>
                {$this->GetMessage('optcreateleaf')}
            </input>
            <br>
            <input type="checkbox" name="optNumberPages"
                {$this->getOptionCheckbox('NumberPages')}>
                {$this->GetMessage('optnumberpages')}
            </input>
            <br>
            <b>
                {$this->GetMessage('optstemplate')}
            </b>
            <br>
            <input type="checkbox" name="optHeaderTemplate"
                {$this->getOptionCheckbox('UseHeader')}>
                {$this->GetMessage('optheadertemplate')}
            </input>
            <input type="checkbox" name="optAutogenHeaderTemplate"
                {$this->getOptionCheckbox('GenerateHeader')}>
                {$this->GetMessage('optautogenerate')}
            </input>
            <br>
            <input type="checkbox" name="optFooterTemplate" disabled>
                {$this->GetMessage('optfootertemplate')}
            </input>
            <input type="checkbox" name="optAutogenFooterTemplate" disabled>
                {$this->GetMessage('optautogenerate')}
            </input>
            <!-- TODO: Add a <select> item here with a list of auto-generate
                       template styles -->
        </div>
    </div>
EOD;
        return $text;
    }
}

