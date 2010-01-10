<?php
class BookDesigner extends SpecialPage {
    function __construct() {
        parent::__construct( 'BookDesigner' );
        wfLoadExtensionMessages('BookDesigner');
    }

    // Change this if the prefix is different on your system.
    protected $pageprefix   = "/wiki/index.php?title=";

    // set this to true to enable debugging output.
    protected $debug        = false;

    // Internal values. Don't modify them, they get set at runtime
    protected $bookname     = "";
    protected $createleaves = false;
    protected $usetemplates = false;
    protected $numberpages  = false;
    protected $usenamespace = false;
    protected $autogentemp  = false;
    protected $namespace    = "";

    // Quick and dirty debugging utilities. The value of $this->debug determines whether
    // we print something. These functions can probably disappear soon since the
    // parseBookPage parser routine has been mostly tested.
    function _dbg($word)
    {
        global $wgOut;
        if($this->debug)
            $wgOut->addHTML($word);
    }
    function _dbgl($word)
    {
        $this->_dbg($word . "<br/>");
    }

    // Home-brewed recursive descent parser. Yes there are better ways of doing
    // this, and yes this is ugly and stupid and ugly. Whatever, this is what
    // we have.
    function parseBookPage($page, $path, $lines, $idx)
    {
        global $wgOut, $wgScriptPath;
        $pagetext = $this->usetemplates ? "{{" . $this->bookname . "}}\n\n" : "";
        $createleaf = $this->createleaves || ($idx == 1);
        $subpagenum = 0;
        $this->_dbgl("Creating leaf page: " . ($createleaf?"1":"0"));
        // First, read out the subpages
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
                // We have a page name
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
        // Second, read out the headings
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
                $this->_dbgl("Breaking subpage loop");
                $idx = $i;
                break;
            }
            $this->_dbgl("Heading");
            $createpage = TRUE;
            $pagetext .= "== " . $line . " ==\n\n";
            // a heading can have pages under it, so enter another loop here to
            // handle those pages.
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
        // We've parsed all direct subpages and all headings (and all subpages
        // of those). We have all the information we need now to actually create
        // this page. Page name is in $path. Page text is in $pagetext
        //$wgOut->addHTML("<h2>" . $page . "</h2>");
        //$wgOut->addHTML("<b>" . $path . "</b>");
        //$wgOut->addHTML("<pre>" . $pagetext . "</pre>");
        // We only create the page if (1) we opt to create all pages, (2) the page contains subpages,
        // (3) the page contains headings, or (4) it is the main page.
        if ($createleaf) {
            $title = Title::newFromText($path);
            $article = new Article($title);
            $article->doEdit($pagetext, "Creating new book automatically");
            $wgOut->addHTML("Created <a href=\"" . $this->pageprefix . $path . "\">" . $path . "</a><br/>");
        }
        return $idx;
    }

    // Build the header template
    function generateHeaderTemplate( $bookname ) {
        global $wgOut;
        $name = "Template:" . $bookname;
        $title = Title::newFromText($name);
        $article = new Article($title);
        $text = $this->getTemplateText($bookname);
        $article->doEdit($text, "Creating header template for " . $bookname);
        $wgOut->addHTML("Created <a href=\"" . $this->pageprefix . $name . "\">" . $name . "</a><br/>");
    }

    // Returns an EXTREMELY basic text string for creating a header template.
    // TODO: Make this less bare-bones
    function getTemplateText($bookname) {
        $text = <<<EOD

<div style="border: 1px solid #AAAAAA; background-color: #F8F8F8; padding: 5px; margin: auto; width: 95%">
    <center><big>
        '''[[$bookname]]'''
    </big></center>
</div>

EOD
        return $text;
    }

    // Main function, this is where execution starts
    function execute( $par ) {
        global $wgRequest, $wgOut, $wgScriptPath;
        $this->setHeaders();
        $wgOut->setPageTitle("Book Designer");
        $jspath  = "$wgScriptPath/extensions/BookDesigner";
        $csspath = "$wgScriptPath/extensions/BookDesigner";

        $wgOut->addScriptFile($jspath . "/bookpage.js");
        $wgOut->addScriptFile($jspath . "/pagehead.js");
        $wgOut->addScriptFile($jspath . "/designer.js");
        $wgOut->addStyle($csspath . "/designer.css");

        if(isset($par)) {
            // TODO: we've specified a book name, load that book into the outline
            $wgOut->addHTML($par);
        }
        else if($wgRequest->wasPosted()) {
            $text = $wgRequest->getText('VBDHiddenTextArea');

            $this->createleaves = $wgRequest->getCheck("optCreateLeaves");
            $this->_dbgl("Create leaves: " . ($this->createleaves ? "1" : "0"));

            $this->usetemplates = $wgRequest->getCheck("optHeaderTemplate");
            $this->_dbgl("Use Templates: " . ($this->usetemplates ? "1" : "0"));

            $this->numberpages = $wgRequest->getCheck("optNumberPages");
            $this->_dbgl("Number Pages: "  . ($this->numberpages  ? "1" : "0"));

            $this->usenamespace = $wgRequest->getCheck("optUseNamespace");
            $this->namespace = $this->usenamespace ? $wgRequest->getText("optNamespace") . ":" : "";
            $this->_dbgl("Use Namespace: " . ($this->usenamespace ? "1" : "0") . " " . $this->namespace);

            $this->autogentemp = $wgRequest->getCheck("optAutogenTemplate");
            $this->_dbgl("Autogenerate Template: " . ($this->autogenemp ? "1" : "0");

            $lines = explode("\n", $text);
            $this->bookname = $lines[0];
            $this->parseBookPage($lines[0], $this->namespace . $lines[0], $lines, 1);
            if ($this->autogentemp) {
                $this->generateHeaderTemplate($this->bookname);
            }
        }
        else {
            $text = <<<EOD


<form action="${this->pageprefix}Special:BookDesigner" method="POST">
    <textarea name="VBDHiddenTextArea" id="VBDHiddenTextArea" style="display: none;"></textarea>
    <div id="VBDWelcomeSpan">
        This is the <b>Visual Book Design</b> outlining tool. Use this page to create an outline for your new book.
    </div>
    <div id="VBDStatSpan"></div>
    <div style="margin: auto; clear: both; padding: 5px; border: 1px solid #AAAAAA; background-color: #F8F8F8; width: 95%;">
        <b>Quick Start Instructions</b>
        <ol>
            <li>Click the title of a book to rename it<br>Click "<b>New Book</b>" to give your book a name</li>
            <li>Click "Headings for this page" to add sections to the page<br>Click the <b>[ + ]</b> To add 1 new section</li>
            <li>Click "Subpages" to create new pages in the book here<br>Click the <b>[ + ]</b> to add 1 new subpage</li>
            <li>When you are finished, click <b>Publish Book!</b> to create the book
        </ol>
    </div>
    <div id="VBDSpan" style="width: 65%;">
        JavaScript is not working. Make sure to enable JavaScript in your browser.
    </div>
    <input type="submit" value="Publish Book!"/><br>
    <div id="VBDOptionsSpan">
        <h2>Options</h2>
        <input type="checkbox" name="optCreateLeaves" checked>Create Leaf Pages</input><br>
        <input type="checkbox" name="optNumberPages">Number Pages</input><br>
        <input type="checkbox" name="optHeaderTemplate" checked>Use Header Template</input><br>
        <input type="checkbox" name="optAutogenTemplate">Autogenerate Header Template</input><br>
        <!-- Add a <select> item here with a list of auto-generate template styles -->
        <input type="checkbox" name="optUseNamespace">Use Alternate Namespace:</input><input type="text" name="optNamespace"/>-
    </div>
    <!--
    TODO: This is a temporary addition to aid in debugging. It shows the intermediate code before it's transmitted to the
          server. This way if there is some kind of a server error, we can save a copy of that intermediate code to a safe place
          so when we are making a hugeo outline we don't lose all that work.
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

