<?php
class BookDesignerParser {
    function __construct($designer, $options) {
        $this->designer = $designer;
        $this->options = $options;
    }

    protected $designer;
    protected $options;
    protected $pagestack = array();
    protected $currentpage = null;
    protected $pagelist = array();
    protected $titlepage = null;

    function getPages() {
        return $this->pagelist;
    }

    function titlePage() {
        return $this->titlepage;
    }

    function getHeaderTemplateTag() {
        $bookname = $this->titlepage->name();
        return $this->options->UseHeader() ? "{{" . $bookname . "}}" : "";
    }

    function getFooterTemplateTag() {
        $bookname = $this->titlepage->name();
        return $this->options->UseFooter() ? "{{" . $bookname . "/Footer}}" : "";
    }

    function getPageLinkWikiText($path, $name) {
        $template = $this->options->PageLinkTemplate();
        return str_replace(array('$1', '$2'), array($path, $name), $template);
    }

    function getChapterLinkWikiText($path, $name) {
        $template = $this->options->ChapterLinkTemplate();
        return str_replace(array('$1', '$2'), array($path, $name), $template);
    }

    function getSectionHeadWikiText($name) {
        $template = $this->options->SectionHeaderTemplate();
        return str_replace('$1', $name, $template);
    }

    function getPageHeadText($isroot) {
        $text = $this->getHeaderTemplateTag() . "\n\n";
        return $text;
    }

    function getPageFootText($isroot) {
        $text = "\n\n";
        return $text . $this->getFooterTemplateTag();
    }

    function addPageToList($page) {
        $this->pagelist[] = $page;
    }

    function getHeaderTemplateText() {
        $header = wfMsg('bookdesigner-defaultheader', $this->titlepage->name(),
            $this->titlepage->fullname());
        return $header;
    }

    function getFooterTemplateText() {
        $header = wfMsg('bookdesigner-defaultfooter', $this->titlepage->name(),
            $this->titlepage->fullname());
        return $header;
    }

    function maybeAddTemplates() {
        if ($this->options->useHeader()) {
            $head = new BookDesignerPage("Template:" . $this->titlepage->name(),
                "Template:" . $this->titlepage->name());
            $head->text($this->getHeaderTemplateText());
            $head->forceCreate(true);
            $this->addPageToList($head);
        }
        if ($this->options->useFooter()) {
            $name = "Template:" . $this->designer->bookName() . "/Footer";
            $foot = new BookDesignerPage($name, $name);
            $foot->text($this->getFooterTemplateText());
            $foot->forceCreate(true);
            $this->addPageToList($foot);
        }
    }

    # push a new page onto the stack, and set it as the current. Return the
    # object with information about the page
    function startPage($name, $fullname, $children) {
        $num = sizeof($this->pagestack);
        $isroot = true;
        if ($num > 0)
            $isroot = false;
        $page = new BookDesignerPage($name, $fullname);
        if ($this->titlepage == null)
            $this->titlepage = $page;
        $page->children($children);
        $page->text($this->getPageHeadText($isroot));
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
        $old->addText($this->getPageFootText($isroot));

        return $old;
    }


    # Home-brewed XML parser. I'm not familiar with any PHP XML libraries or
    # utilities, and I don't know what features the MediaWiki server is going
    # to have available anyway, so I'm just writing my own quickly.
    # TODO: Reimplement 'NumberPages' option, to prepend a page number to each page.
    function parse($text) {
        global $wgOut, $wgScriptPath;
        $matches = array();
        $lines = explode("\n", $text);

        $bookline = $lines[0];
        if (!preg_match("/<page name='([^']+)' children='(\d+)'>/", $bookline, $matches)) {
            $wgOut->addHTML("XML ERROR");
            return;
        }

        # dummy, just to get the loop started
        $this->currentpage = new BookDesignerPage("", "");

        for ($i = 0; $i < sizeof($lines); $i++) {
            $line = $lines[$i];
            if (preg_match("/<page name='([^']+)' children='(\d+)'>/", $line, $matches)) {
                $name = $matches[1];
                if ($i == 0)
                    $fullname = $this->options->bookNamespace() . $name;
                else
                    $fullname = $this->currentpage->fullname() . "/" . $name;
                $this->currentpage->addText($this->getPageLinkWikiText($fullname, $name) . "\n");
                $this->startPage($name, $fullname, $matches[2]);
            }
            else if (preg_match("/<heading name='([^']+)' children='(\d+)'>/", $line, $matches)) {
                $name = $matches[1];
                $this->currentpage->addText($this->getSectionHeadWikiText($name) . "\n\n");
            }
            else if($line == "</page>") {
                $page = $this->endPage();
                $this->addPageToList($page);
            }
            else if($line == "</heading>") {
            }
        }
        $this->maybeAddTemplates();
    }
}
