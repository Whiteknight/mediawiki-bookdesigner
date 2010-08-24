<?php
class BookDesigner extends SpecialPage {
    function __construct() {
        parent::__construct( 'BookDesigner' );
        wfLoadExtensionMessages('BookDesigner');
    }

    # set this to true to enable debugging output.
    protected $debug              = false;

    # Internal values. Don't modify them, they get set at runtime
    protected $bookname           = "";
    protected $createleaves       = false;
    protected $usetemplates       = false;
    protected $numberpages        = false;
    protected $usenamespace       = false;
    protected $autogentemp        = false;
    protected $createintroduction = false;
    protected $createresources    = false;
    protected $createlicensing    = false;
    protected $namespace          = "";

    # Quick and dirty debugging utilities. The value of $this->debug determines whether
    # we print something. These functions can probably disappear soon since the
    # parseBookPage parser routine has been mostly tested.
    function _dbg($word) {
        global $wgOut;
        if($this->debug)
            $wgOut->addHTML($word);
    }
    function _dbgl($word) {
        $this->_dbg($word . "<br/>");
    }

    function GetHeaderTemplateTag() {
        return $this->usetemplates ? "{{" . $this->bookname . "}}\n\n" : "";
    }

    function GetFooterTemplateTag() {
        return "";
    }

    function GetCreateFlag($isroot) {
        $create = $this->createleaves || $isroot;
        $this->_dbgl("Creating leaf page: " . ($createleaf ? "1" : "0"));
        return $create;
    }

    function CreateIntroduction($isroot) {
        if ($isroot && $this->createintroduction) {
            $text = $this->GetHeaderTemplateTag() . $this->GetFooterTemplateTag();
            $this->CreateOnePage($this->bookname . "/Introduction", $text, "Creating Introduction page");
            return "*[[" . $this->bookname . "/Introduction|Introduction]]\n";
        }
        return "";
    }
    function CreateResources($isroot) {
        if ($isroot && $this->createresources) {
            $text = $this->GetHeaderTemplateTag() . $this->GetFooterTemplateTag();
            $this->CreateOnePage($this->bookname . "/Resources", $text, "Creating Resources page");
            return "*[[" . $this->bookname . "/Resources|Resources]]\n";
        }
        return "";
    }
    function CreateLicensing($isroot) {
        if ($isroot && $this->createlicensing) {
            $text = $this->GetHeaderTemplateTag() . $this->GetFooterTemplateTag();
            $this->CreateOnePage($this->bookname . "/Licensing", $text, "Creating Licensing page");
            return "*[[" . $this->bookname . "/Licensing|Licensing]]\n";
        }
        return "";
    }

    # Home-brewed recursive descent parser. Yes there are better ways of doing
    # this, and yes this is ugly and stupid and ugly. Whatever, this is what
    # we have.
    # [] contain lists of pages. {} contain lists of headings. Each page has[]{}
    # and each heading has only []. Each bare line of text inside a set of brackets
    # is that type of thing. Empty lines are ignored.
    function parseBookPage($page, $path, $lines, $idx) {
        global $wgOut, $wgScriptPath;
        $isroot = ($idx == 1);
        $subpagenum = 0;
        $pagetext = $this->GetHeaderTemplateTag() . $this->CreateIntroduction($isroot);
        $createleaf = $this->GetCreateFlag($isroot);

        # Loop over all subpages inside [] brackets
        for($i = $idx; $i < sizeof($lines); $i++) {
            $line = rtrim($lines[$i]);
            $this->_dbg("Line " . $i . ": " . $line . "> ");
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
                $name = ($this->numberpages ? $subpagenum . ". " : "") . $line;
                $createpage = TRUE;
                $newpath = $path . "/" . $name;
                $pagetext .= "*[[" . $newpath . "|" . $name . "]]\n";
                $i = $this->parseBookPage($name, $newpath, $lines, $i + 1);
            }
        }
        $pagetext .= "\n";

        # Loop over all headings inside {} brackets
        for($i = $idx; $i < sizeof($lines); $i++) {
            $line = rtrim($lines[$i]);
            $this->_dbg("Line " . $i . ": " . $line . "> ");
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
                $i = $this->parseBookPage($line2, $newpath, $lines, $i + 1);
            }
        }

        # Get the rest of the text, most of which is optional
        $pagetext = $pagetext .
            $this->CreateResources($isroot) .
            $this->CreateLicensing($isroot) .
            $this->GetFooterTemplateTag();

        # We've parsed all direct subpages and all headings (and all subpages
        # of those). We have all the information we need now to actually create
        # this page. Page name is in $path. Page text is in $pagetext
        # We only create the page if (1) we opt to create all pages, (2) the
        # page contains subpages, (3) the page contains headings, or (4) it is
        # the main page.
        if ($createleaf)
            $this->CreateOnePage($path, $pagetext, "Creating new book automatically");
        return $idx;
    }

    function CreateOnePage($path, $text, $comment)
    {
        global $wgOut, $wgScriptPath;
        $title = Title::newFromText($path);
        $article = new Article($title);
        $article->doEdit($text, $comment);
        $wgOut->addHTML("Created <a href=\"$wgScriptPath/index.php?title=$path\">$path</a><br/>");
    }

    # Build the header template
    function generateHeaderTemplate( $bookname ) {
        global $wgOut, $wgScriptPath;
        $name = "Template:" . $bookname;
        $title = Title::newFromText($name);
        $article = new Article($title);
        $text = $this->getTemplateText($bookname);
        $article->doEdit($text, "Creating header template for " . $bookname);
        $wgOut->addHTML("Created <a href=\"$wgScriptPath/index.php?title=$name\">$name</a><br/>");
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
        $this->createleaves = $wgRequest->getCheck("optCreateLeaves");
        $this->_dbgl("Create leaves: " . ($this->createleaves ? "1" : "0"));

        $this->usetemplates = $wgRequest->getCheck("optHeaderTemplate");
        $this->_dbgl("Use Templates: " . ($this->usetemplates ? "1" : "0"));

        $this->numberpages = $wgRequest->getCheck("optNumberPages");
        $this->_dbgl("Number Pages: "  . ($this->numberpages  ? "1" : "0"));

        $this->usenamespace = $wgRequest->getCheck("optUseNamespace");
        $this->namespace = $this->usenamespace ? $wgRequest->getText("optNamespace") . ":" : "";
        $this->_dbgl("Use Namespace: " . ($this->usenamespace ? "1" : "0") . " " . $this->namespace);

        $this->autogentemp = $wgRequest->getCheck("optAutogenHeaderTemplate");
        $this->_dbgl("Autogenerate Template: " . ($this->autogenemp ? "1" : "0"));

        $this->createintroduction = $wgRequest->getCheck("optIntroductionPage");
        $this->_dbgl("Create Introduction Page: " . ($this->createintroduction ? "1" : "0"));

        $this->createresources = $wgRequest->getCheck("optResourcesPage");
        $this->_dbgl("Create Resources Page: " . ($this->createresources ? "1" : "0"));

        $this->createlicensing = $wgRequest->getCheck("optLicensingPage");
        $this->_dbgl("Create Licensing Page: " . ($this->createlicensing ? "1" : "0"));
    }

    function GetMessage($msgname) {
        return wfMsg('bookdesigner-' . $msgname);
    }

    # Main function, this is where execution starts
    function execute( $par ) {
        # TODO: Refactor all this.
        global $wgRequest, $wgOut, $wgScriptPath;
        $this->setHeaders();
        $wgOut->setPageTitle("Book Designer");
        $jspath  = "$wgScriptPath/extensions/BookDesigner";
        $csspath = "$wgScriptPath/extensions/BookDesigner";

        $wgOut->addScriptFile($jspath . "/bookpage.js");
        $wgOut->addScriptFile($jspath . "/pagehead.js");
        $wgOut->addScriptFile($jspath . "/designer.js");
        if(method_exists($wgOut, "addExtensionStyle")) {
            $wgOut->addExtensionStyle($csspath . "/designer.css");
        } else {
            # This is a hack for older MediaWiki (1.14 and below?).
            # addStyle prepends "$wgScriptPath/skins/" to the front,
            # so we need to navigate to the correct place
            $wgOut->addStyle("../extensions/BookDesigner/designer.css");
        }

        if(isset($par)) {
            # TODO: we've specified a book name, load that book into the outline
            $wgOut->addHTML($par);
        }
        else if($wgRequest->wasPosted()) {
            # TODO: Validate that we are logged in. Also, create an option to require
            #       certain permissions (either admin, or a custom permission or something)
            $text = $wgRequest->getText('VBDHiddenTextArea');
            $this->getOptions();

            $lines = explode("\n", $text);
            $this->bookname = $lines[0];
            # TODO: Parse the list of pages/headings into some kind of PHP-friendly
            #       array or structure first, instead of parsing it and creating it
            #       all in one swoop
            # TODO: Show the user a list of pages to create, with checkboxes to confirm
            #       creation of all these pages.
            $this->parseBookPage($lines[0], $this->namespace . $lines[0], $lines, 1);
            # TODO: Show statistics (number of pages created, total time, etc) Here
            # TODO: Show an "Oops!" delete/undo link here that goes back over the list of pages
            #       and deletes them all again (if the user is an admin)
            if ($this->autogentemp) {
                $this->generateHeaderTemplate($this->bookname);
            }
        }
        else {
            # TODO: Have a hidden field somewhere that we can hold a list of
            #       pages for pre-populating the outline.
            $text = <<<EOD

<form action="{$wgScriptPath}/index.php?title=Special:BookDesigner" method="POST">
    <textarea name="VBDHiddenTextArea" id="VBDHiddenTextArea" style="display: none;"></textarea>
    <div id="VBDWelcomeSpan">
        {$this->GetMessage('welcome')}
    </div>
    <div id="VBDStatSpan"></div>
    <div id="VBDInstructionSpan">
        <h2>
            <span style="float: right; font-size: 67%;">
                [<a id="VBDQuickStartToggle" onclick="vbd.ToggleGUIWidget('VBDQuickStartInternal', 'VBDQuickStartToggle');"><!--
                    -->{$this->GetMessage('hide')}<!--
                --></a>]
            </span>
            {$this->GetMessage('qsistart')}
        </h2>
        <div id="VBDQuickStartInternal">
            {$this->GetMessage('qsi')}
        </div>
    </div>
    <div id="VBDOptionsSpan">
        <h2>
            <span style="float: right; font-size: 67%;">
                [<a id="VBDOptionsToggle" onclick="vbd.ToggleGUIWidget('VBDOptionsInternal', 'VBDOptionsToggle');"><!--
                    -->{$this->GetMessage('show')}<!--
                --></a>]
            </span>
            {$this->GetMessage('options')}
        </h2>
        <div id="VBDOptionsInternal" style="display: none;">
            <b>{$this->GetMessage('optsbook')}</b><br>
            <input type="checkbox" name="optUseNamespace">{$this->GetMessage('optusenamespace')}:</input><br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="optNamespace"/><br>
            <input type="checkbox" name="optUseUserSpace" disabled>{$this->GetMessage('optuseuserspace')}</input><br>
            <input type="checkbox" name="optIntroductionPage">{$this->GetMessage('optintroductionpage')}</input><br>
            <input type="checkbox" name="optResourcesPage">{$this->GetMessage('optresourcespage')}</input><br>
            <input type="checkbox" name="optLicensingPage">{$this->GetMessage('optlicensingpage')}</input><br>

            <b>{$this->GetMessage('optspage')}</b><br>
            <input type="checkbox" name="optCreateLeaves" checked>{$this->GetMessage('optcreateleaf')}</input><br>
            <input type="checkbox" name="optNumberPages">{$this->GetMessage('optnumberpages')}</input><br>

            <b>{$this->GetMessage('optstemplate')}</b><br>
            <input type="checkbox" name="optHeaderTemplate" checked>{$this->GetMessage('optheadertemplate')}</input>
            <input type="checkbox" name="optAutogenHeaderTemplate">{$this->GetMessage('optautogenerate')}</input><br>
            <input type="checkbox" name="optFooterTemplate" disabled>{$this->GetMessage('optfootertemplate')}</input>
            <input type="checkbox" name="optAutogenFooterTemplate" disabled>{$this->GetMessage('optautogenerate')}</input><br>
            <!-- TODO: Add a <select> item here with a list of auto-generate template styles -->
        </div>
    </div>
    <div id="VBDOutlineSpan">
        {$this->GetMessage('jserror')}
    </div>
    <input type="submit" value="{$this->GetMessage('publishbutton')}"/><br>
    <!--
    TODO: This is a temporary addition to aid in debugging. It shows the
          intermediate code before it's transmitted to the server. This way if
          there is some kind of a server error, we can save a copy of that
          intermediate code to a safe place so when we are making a huge outline
          we don't lose all that work. No i18n for debug stuff.
    -->
    <small>
        <a href="#" onclick="document.getElementById('VBDHiddenTextArea').style.display = 'block';">Show Intermediate Code</a>
    </small>
</form>

EOD;
            $wgOut->addHTML($par);
            $wgOut->addHTML($text);
        }
    }
}

