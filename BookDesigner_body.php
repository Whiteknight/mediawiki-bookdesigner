<?php
class BookDesigner extends SpecialPage {
    function __construct() {
        parent::__construct( 'BookDesigner' );
        wfLoadExtensionMessages('BookDesigner');
    }

    protected $bookname = "";
    protected $debug = false;

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
        global $wgOut;
        $pagetext = "{{" . $this->bookname . "}}\n\n";
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
                $newpath = $path . "/" . $line;
                $pagetext .= "*[[" . $newpath . "|" . $line . "]]\n";
                $i = $this->parseBookPage($line, $newpath, $lines, $i + 1);
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
        $title = Title::newFromText($path);
        $article = new Article($title);
        $article->doEdit($pagetext, "Creating new book automatically");
	$wgOut->addHTML("Created <a href=\"/wiki/" . $path . "\">" . $path . "</a><br/>");
        return $idx;
    }

    function execute( $par ) {
        global $wgRequest, $wgOut;
        $this->setHeaders();
        $wgOut->setPageTitle( "Book Designer" );
        
        // TODO: Don't hardcode the path. Pick a better way to access this file
        $bdpath = "/wiki/extensions/BookDesigner/";
        
        $wgOut->addScriptFile($bdpath . "bookpage.js");
        $wgOut->addScriptFile($bdpath . "pagehead.js");
        $wgOut->addScriptFile($bdpath . "designer.js");
        $wgOut->addStyle($bdpath . "designer.css");

        if(isset($par)) {
            // TODO: we've specified a book name, load that book into the outline
            $wgOut->addHTML($par);
        }
        else if($wgRequest->wasPosted()) {
            $text = $wgRequest->getText('VBDHiddenTextArea');
            $lines = explode("\n", $text);
            $this->bookname = $lines[0];
            $this->parseBookPage($lines[0], $lines[0], $lines, 1);
            // TODO: Create a template for this
        }
        else {
            $text = <<<EOD


<form action="/wiki/Special:BookDesigner" method="POST">
  <textarea name="VBDHiddenTextArea" id="VBDHiddenTextArea" style="display: none;"></textarea>
  <div id="VBDWelcomeSpan">This is the <b>Visual Book Design</b> outlining tool. Use this page to create an outline for your new book.</div>
  <div id="VBDStatSpan"></div>
  <div style="float: right; margin: 5px; padding: 5px; border: 1px solid #AAAAAA; background-color: #F8F8F8; width: 25%;">
    <b>Quick Start Instructions</b>
    <ol>
      <li>Click the title of a book to rename it<br>Click "<b>New Book</b>" to give your book a name
      <li>Click "Headings for this page" to add sections to the page<br>Click the <b>[ + ]</b> To add 1 new section
      <li>Click "Subpages" to create new pages in the book here<br>Click the <b>[ + ]</b> to add 1 new subpage
      <li>When you are finished, click <b>Publish Book!</b> to create the book
    </ol>
  </div>
  <div id="VBDSpan" style="width: 65%;">JavaScript is not working. Make sure to enable JavaScript in your browser.</div>
  <input type="submit" value="Publish Book!"/> 
</form>

EOD;
            $wgOut->addHTML($par);
            $wgOut->addHTML($text);
        }
    }
}

