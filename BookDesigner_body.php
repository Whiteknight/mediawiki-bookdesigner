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
        "UseIntroduction" => true,
        "UseResources"    => false,
        "UseLicensing"    => true,
    );
    protected $namespace = "";
    protected $bookname  = "";
    protected $pagelinktmpl = "* [[$1|$2]]";
    protected $chapterlinktmpl = "* [[$1|$2]]";
    protected $sectionheadtmpl = "== $1 ==";

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
        return $this->getOption('UseHeader') ? "{{" . $this->bookname . "}}" : "";
    }

    function getPageHeadText($isroot) {
        $text = $this->getHeaderTemplateTag() . "\n\n";
        if ($isroot && $this->getOption('UseIntroduction'))
            $text .=
                $this->GetPageLinkWikiText($this->bookname . "/Introduction", "Introduction")
                . "\n";
        return $text;
    }

    function getPageFootText($isroot) {
        $text = "\n\n";
        if ($isroot && $this->getOption('UseResources'))
            $text .= $this->getPageLinkWikiText($this->bookname . "/Resources", "Resources") . "\n";
        if ($isroot && $this->getOption('UseLicensing'))
            $text .= $this->getPageLinkWikiText($this->bookname . "/Licensing", "Licensing") . "\n";
        return $text . $this->getFooterTemplateTag();
    }

    function getPageLinkWikiText($path, $name) {
        return str_replace(array('$1', '$2'), array($path, $name), $this->pagelinktmpl);
    }

    function getChapterLinkWikiText($path, $name) {
        # TODO: Once we support it, change this to use chapterlinktmpl
        return str_replace(array('$1', '$2'), array($path, $name), $this->pagelinktmpl);
    }

    function getSectionHeadWikiText($name) {
        return str_replace('$1', $name, $this->sectionheadtmpl);
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
        return "{{" . $this->bookname . "/Footer}}";
    }

    function GetCreateFlag($isroot) {
        $create = $this->getOption('CreateLeaves') || $isroot;
        return $create;
    }

    function addPageToList($pagename, $fullname, $pagetext, $numchildren) {
        $create = $this->getOption('CreateLeaves');
        if (!$create)
            $create = ($numchildren > 0);
        $this->pagelist[] = array(
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

    protected $pagestack = array();
    protected $currentpage = null;
    protected $pagelist = array();

    # push a new page onto the stack, and set it as the current. Return the
    # object with information about the page
    function startPage($name, $children) {
        $num = sizeof($this->pagestack);
        $fullname = $name;
        $isroot = true;
        $this->_dbgl("Starting page $name, $isroot");
        if ($num > 0) {
            $isroot = false;
            $lastpage = $this->pagestack[sizeof($this->pagestack) - 1];
            $fullname = $lastpage["fullname"] . "/" . $name;
        }
        $page = array(
            "name" => $name,
            "fullname" => $isroot ? $this->namespace . $fullname : $fullname,
            "children" => $children,
            "text" => $this->getPageHeadText($isroot)
        );
        array_push($this->pagestack, $page);
        $this->currentpage = $page;
        return $page;
    }

    # pop the last page off the stack, update the current page to be the previous
    # one, and return the page that was popped
    function endPage() {

        $old = array_pop($this->pagestack);
        $num = sizeof($this->pagestack);
        $isroot = false;
        if ($num > 0) {
            $lastpage = $this->pagestack[sizeof($this->pagestack) - 1];
            $this->currentpage = $lastpage;
        }
        else {
            $isroot = true;
            $this->currentpage = null;
        }
        $old["text"] .= $this->getPageFootText($isroot);
        return $old;
    }


    # Home-brewed XML parser. I'm not familiar with any PHP XML libraries or
    # utilities, and I don't know what features the MediaWiki server is going
    # to have available anyway, so I'm just writing my own quickly.
    # TODO: Reimplement 'NumberPages' option, to prepend a page number to each page.
    function parseBookPage($text) {
        global $wgOut, $wgScriptPath;
        $matches = array();
        $lines = explode("\n", $text);

        $bookline = $lines[0];
                $dbgtext = str_replace("<", '&lt;', $bookline);
        $wgOut->addHTML("<pre>$dbgtext</pre>");
        if (!preg_match("/<page name='([^']+)' children='(\d+)'>/", $bookline, $matches)) {

            $wgOut->addHTML("XML ERROR");
            return;
        }
        $this->bookname = $matches[1];
        $this->_dbgl("Bookname: {$this->bookname}");

        # dummy, just to get the loop started
        $currentpage = array(
            "name" => "",
            "fullname" => "",
            "children" => 0,
            "text" => ""
        );

        for ($i = 0; $i < sizeof($lines); $i++) {
            $line = $lines[$i];
            $this->_dbgl("Line $i");
            if (preg_match("/<page name='([^']+)' children='(\d+)'>/", $line, $matches)) {
                $name = $matches[1];
                $fullname = $currentpage["fullname"] . "/" . $name;
                $this->_dbgl("Page: $name, $fullname");
                $currentpage["text"] .= $this->getPageLinkWikiText($fullname, $name) . "\n";
                $currentpage = $this->startPage($name, $matches[2]);
            }
            else if (preg_match("/<heading name='([^']+)' children='(\d+)'>/", $line, $matches)) {
                $name = $matches[1];
                $currentpage["text"] .= $this->getSectionHeadWikiText($name) . "\n\n";
            }
            else if($line == "</page>") {
                $page = $this->endPage();
                $this->addPageToList($page["name"], $page["fullname"], $page["text"], $page["children"]);
            }
            else if($line == "</heading>") {
            }
        }
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

    function getOptions() {
        global $wgRequest;
        $this->setOption("CreateLeaves", $wgRequest->getCheck("optCreateLeaves"));
        $this->setOption("UseHeader", $wgRequest->getCheck("optHeaderTemplate"));
        $this->setOption("UseFooter", $wgRequest->getCheck("optFooterTemplate"));
        $this->setOption("NumberPages", $wgRequest->getCheck("optNumberPages"));
        $this->setOption("UseNamespace", $wgRequest->getCheck("optUseNamespace"));
        $tmpl = $wgRequest->getText('optPageLinks');
        if (isset($tmpl) && strlen($tmpl) > 0)
            $this->pagelinktmpl = $tmpl;

        $tmpl = $wgRequest->getText('optChapterLinks');
        if (isset($tmpl) && strlen($tmpl) > 0)
            $this->chapterlinktmpl = $tmpl;

        $tmpl = $wgRequest->getText('optHeaderStyle');
        if (isset($tmpl) && strlen($tmpl) > 0)
            $this->sectionheadtmpl = $tmpl;

        $this->namespace = $this->getOption('UseNamespace')
            ? $wgRequest->getText("optNamespace") . ":" : "";
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
        $this->getOptions();
        $this->parseBookPage($text);

        # TODO: Instead of hard-coding in a list of pages that can be added,
        #       Allow the site to specify a list of standard pages, and supply
        #       a text template to be used on those pages.
        if ($this->getOption('UseHeader')) {
            $this->addPageToList("Template:" . $this->bookname,
                "Template:" . $this->bookname,
                $this->getDefaultHeaderTemplateText($this->bookname),
                true
            );
        }
        if ($this->getOption('UseFooter')) {
            $this->addPageToList("Template:" . $this->bookname . "/Footer",
                "Template:" . $this->bookname . "/Footer",
                $this->getDefaultFooterTemplateText($this->bookname),
                true
            );
        }
        if ($this->getOption('UseIntroduction')) {
            $this->addPageToList("Introduction",
                $this->bookname . "/Introduction",
                $this->getIntroductionPageText(),
                true
            );
        }
        if ($this->getOption('UseResources')) {
            $this->addPageToList("Resources",
                $this->bookname . "/Resources",
                $this->getResourcesPageText(),
                true
            );
        }
        if ($this->getOption('UseLicensing')) {
            $this->addPageToList("Licensing",
                $this->bookname . "/Licensing",
                $this->getLicensingPageText(),
                true
            );
        }
        $this->showConfirmationPage($this->bookname, $this->pagelist);
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
                {$this->getOptionCheckbox('NumberPages')} disabled>
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
            <br>
            <input type="checkbox" name="optFooterTemplate"
                {$this->getOption('UseFooter')}>
                {$this->GetMessage('optfootertemplate')}
            </input>
            <br>
            <b>
                Formatting Options
            </b>
            <br>
            Chapter Links:
            <input type="text" name="optChapterLinks" value="{$this->chapterlinktmpl}" disabled/>
            <br>
            Page Links:
            <input type="text" name="optPageLinks" value="{$this->pagelinktmpl}"/>
            <br>
            Headers:
            <input type="text" name="optHeaderStyle" value="{$this->sectionheadtmpl}"/>
            <br>
            <!-- TODO: Add a <select> item here with a list of auto-generate
                       template styles -->
        </div>
    </div>
EOD;
        return $text;
    }
}

