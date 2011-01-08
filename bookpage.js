// Class constructor for BookPage
function BookPage(name) {
    if(name.match(/\s$/))
        name = name.substring(0, name.length - 1);
    this.pagename = name;
    this.headings = new Array();
    this.subpages = new Array();
    this.formspan = vbd.makeElement('div');
    this.parent   = null;                   //subpage parent, null if root
    this.parent2  = null;                   //heading parent, null if not in a heading.
    this.pagetext = "";                     //initial text of a page
    this.comments = "";                     //comment text which is in the outline only
    this.collapse = 0;                      //flag to collapse part of the tree
    this.box = null;
}

// Add a new heading to the page
BookPage.prototype.addHeading = function(heading) {
    heading.parent = this;
    this.headings.push(heading);
}

// Add a new subpage to the page
BookPage.prototype.addSubpage = function(subpage) {
    subpage.parent = this;
    this.subpages.push(subpage);
}

// Create the part of the outline for the page text and comments
// TODO: See if jQuery has any UI magic that will make all this look less ugly
BookPage.prototype.getTextNode = function () {
    var self = this;
    var div = vbd.makeElement('small', {},
        ((this.pagetext.length == 0) ? '[click here to edit page text]' : this.pagetext));
    var cmts = vbd.makeElement('small', {style:'color: #666666'},
        ((this.comments.length == 0) ? '[click here to edit comments]' : this.comments));
    div.onclick = function() {
        var edit = vbd.makeElement('textarea', {rows:10});
        edit.value = self.pagetext;
        self.formspan.innerHTML = 'Enter some text for the page:';
        self.formspan.appendChild(edit);
        self.formspan.appendChild(vbd.makeButton('', 'Save', function() {
            self.pagetext = edit.value;
            if(self.pagetext.match(/^\s*$/))
                self.pagetext = "";
            self.formspan.innerHTML = "";
            vbd.visual();
        }));
        self.closeButton();
        edit.focus();
    }
    div.style.cursor = "pointer";
    cmts.onclick = function() {
        var edit = vbd.makeElement('textarea', {rows:10});
        edit.value = self.comments;
        self.formspan.innerHTML = 'Enter some comments:';
        self.formspan.appendChild(edit);
        self.formspan.appendChild(vbd.makeButton('', 'Save', function() {
            self.comments = edit.value;
            if(self.comments.match(/^\s*$/))
                self.comments = "";
            self.formspan.innerHTML = "";
            vbd.visual();
        }));
        self.closeButton();
        edit.focus();
    }
    cmts.style.cursor = "pointer";
    return vbd.makeElement('table', {width:"100%", style:"background-color: transparent"}, [
        vbd.makeElement('td', {width:"50%", style:"padding-left: 2em;"}, div),
        vbd.makeElement('td', {style:"padding-left: 2em;"}, cmts)
    ]);
}

// Remove a subpage from this page
BookPage.prototype.removeSubpage = function(subpage) {
    for(var i = 0; i < this.subpages.length; i++) {
        if(this.subpages[i] != subpage)
            continue;
        this.subpages.splice(i, 1);
        return;
    }
}

// Remove a heading from this page
BookPage.prototype.removeHeading = function(heading) {
    for(var i = 0; i < this.headings.length; i++) {
        if(this.headings[i] != heading)
            continue;
        this.headings.splice(i, 1);
        return;
    }
}

// Create a link to modify the list of headings.
BookPage.prototype.makeHeadingsLink = function () {
    var link = vbd.makeElement('a', null, ['Headings for this page']);
    var self = this;
    link.onclick = function() {
        var text = "";
        for(var i = 0; i < self.headings.length; i++)
            text = text + self.headings[i].label + "\n";
        var old = vbd.CopyArray(self.headings);
        var edit = vbd.makeElement('textarea', {rows:10, cols:50});
        edit.value = text;
        self.formspan.innerHTML = "";
        self.formspan.appendChild(document.createTextNode(
            'Enter the names of all headings, one per line'));
        self.formspan.appendChild(edit);
        self.formspan.appendChild(vbd.makeButton('', 'Save these headings', function() {
            self.headings.length = 0;
            var pages = edit.value.split("\n");
            for(var i = 0; i < pages.length; i++) {
                if(pages[i].match(/^\s*$/))
                    continue;
                var stat = vbd.FindHeadNameInArray(old, pages[i]);
                if(stat != -1)
                    self.addHeading(old[stat]);
                else
                    self.addHeading(new PageHeading(vbd.forceFirstCaps(pages[i])));
            }
            self.formspan.innerHTML = "";
            vbd.visual();
        }));
        self.closeButton();
        edit.focus();
    }
    return(link);
}

// Create a link to add a new subpage
BookPage.prototype.makeSubpagesAddLink = function () {
    var link = vbd.makeElement('a', null, [" [ + ]"]);
    var self = this;
    link.onclick = function () {
        self.addSubpage(new BookPage(vbd.defpagename + " " + vbd.newpagecnt));
        vbd.newpagecnt++;
        vbd.visual();
    }
    return link;
}

BookPage.prototype.makeDeleteLink = function() {
    var link = vbd.makeElement('a', null, ["delete"]);
    var self = this;
    link.onclick = function() {
        if(confirm("delete page '" + self.pagename + "'? " +
            "You will lose all contents. This cannot be undone.")) {
            if(self.parent2 == null)
                self.parent.removeSubpage(self);
            else
                self.parent2.removeSubpage(self);
            vbd.visual();
        }
    }
    return link;
}

// Create a link to add a new heading
BookPage.prototype.makeHeadingsAddLink = function () {
    var link = vbd.makeElement('a', null, [" [ + ]"]);
    var self = this;
    link.onclick = function () {
        self.addHeading(new PageHeading(vbd.defheadname + " " + vbd.newheadcnt));
        vbd.newheadcnt++;
        vbd.visual();
    }
    return link;
}

// Make a list to modify the list of subpages
BookPage.prototype.makeSubpagesLink = function () {
    var link = vbd.makeElement('a', null, ['Subpages']);
    var self = this;
    link.onclick = function() {
        var text = "";
        for(var i = 0; i < self.subpages.length; i++)
            text = text + self.subpages[i].pagename + "\n";
        var old = vbd.CopyArray(self.subpages);
        var edit = vbd.makeElement('textarea', {rows:10, cols:50});
        edit.value = text;
        self.formspan.innerHTML = "";
        self.formspan.appendChild(document.createTextNode('Enter subpages, one per line'));
        self.formspan.appendChild(edit);
        self.formspan.appendChild(vbd.makeButton('', 'Save', function() {
            self.subpages.length = 0;
            var pages = edit.value.split("\n");
            for(var i = 0; i < pages.length; i++) {
                if(pages[i].match(/^\s*$/))
                    continue;
                var stat = vbd.FindPageNameInArray(old, pages[i]);
                if(stat != -1)
                    self.addSubpage(old[stat]);
                else
                    self.addSubpage(new BookPage(vbd.forceFirstCaps(pages[i])));
            }
            self.formspan.innerHTML = "";
            vbd.visual();
        }));
        self.closeButton();
        edit.focus();
    }
    return(link);
}

// Get an element containing the title of the page, and related decorations
BookPage.prototype.getTitleNode = function () {
    var self = this;
    var container = vbd.makeElement('span');
    var collapse = vbd.makeElement('small', null,
        ((this.collapse == 0) ? '[-] ' : '[+] '));
    collapse.style.cursor = "pointer";
    collapse.onclick = function () {
        self.collapse = (self.collapse == 1) ? 0 : 1;
        vbd.visual();
    }
    var span = vbd.makeElement('big', {style:"font-weight: bold;"}, this.pagename);
    span.style.cursor = "pointer";
    span.onclick = function() {
        var edit = vbd.makeElement('input', {type:'text', value:(self.pagename), size:50});
        self.formspan.innerHTML = "";
        vbd.appendChildren(self.formspan, ['Enter the new name: ', edit, vbd.makeButton('', 'Accept', function() {
            self.formspan.innerHTML = "Renaming...";
            var pagename = vbd.forceCaps(edit.value, self.isRoot());
            if(self.isRoot()) {
                vbd.loadWikiText(pagename, function (text) {
                    if(text.length != 0)
                        self.formspan.innerHTML = "<b>Warning:</b> " + pagename + " already exists. Check it before saving over it.";
                    else
                        self.formspan.innerHTML = "";
                });
            } else
                self.formspan.innerHTML = "";
            self.pagename = pagename.replace("\n", "");
            vbd.visual();
        })]);
        self.closeButton();
    }
    if(this.subpages.length != 0 || this.headings.length != 0)
        container.appendChild(collapse);
    container.appendChild(span);
    return(container);
}

// Determine if this page is the root page or not.
BookPage.prototype.isRoot = function () {
    return (this.parent == null) ? 1 : 0;
}

BookPage.prototype.getPageText = function() {
    return this.pagetext;
}

// Make a link to view the wikitext of the current page
// TODO: This isn't currently used but it might have utility later.
//BookPage.prototype.makeDisplayLink = function () {
//    var link = vbd.makeElement('a', null, ['Wikitext']);
//    var self = this;
//    link.onclick = function() {
//        self.formspan.innerHTML = "<pre>" + self.makeWikitextAll() + "</pre>";
//        self.closeButton();
//    }
//    return(link);
//}

// Make the collection text of a page
// TODO: Not currently used, but maybe useful later
//BookPage.prototype.makeCollectionText = function (subt) {
//  var text = "";
//  if(this.isRoot()) {
//    if(subt == null || subt == "") subt = "A Book from English Wikibooks";
//    text = "== " + this.pagename + " ==\n=== " + subt + " ===\n";
//    if(vbd.usecollectionpreface) text +=  ":[[Wikibooks:Collections Preface]]\n";
//    if(vbd.useintroduction) text += ":[[" + this.pagename + "/Introduction|Introduction]]\n";
//  }
//  else text = ":[[" + this.getFullName() + "|" + this.pagename + "]]\n";
//  for(var i = 0; i < this.subpages.length; i++) {
//    text = text + this.subpages[i].makeCollectionText();
//  }
//  for(var i = 0; i < this.headings.length; i++) {
//    text = text + this.headings[i].makeCollectionText();
//  }
//  if(this.isRoot()) {
//    text += "\n;Resources and Licensing\n";
//    if(vbd.useresources) text += ":[[" + this.pagename + "/Resources|Resources]]\n";
//    if(vbd.uselicensing) text += ":[[" + this.pagename + "/Licensing|Licensing]]\n";
//    text += "\n[[Category:Collections]]\n";
//  }
//  return text;
//}

//make a button to close an open formspan
BookPage.prototype.closeButton = function () {
    var self = this;
    this.formspan.appendChild(vbd.makeButton('', 'Close', function() {
        self.formspan.innerHTML = "";
    }));
}

// Prepare the text to save a subpage from the outline. Create the intermediate
// code form that is sent to the server for processing.
BookPage.prototype.makeSaveText = function () {
    var children = this.subpages.length + this.headings.length;
    var saveTitle = this.pagename.replace("\"", "\\\"");
    var text = "<page name=\"" + saveTitle + "\" children='" + children + "'>\n";
  //if(this.pagetext.length != 0) {
  //  var pagetext = this.pagetext;
  //  pagetext.replace(/\n/g, "\n&");
  //  text += "&" + this.pagetext + "\n";
  //}
  //if(this.comments.length != 0) {
  //  var comments = this.comments;
  //  comments.replace(/\n/g, "\n%");
  //  text += "%" + this.comments + "\n";
  //}
    for(var i = 0; i < this.subpages.length; i++)
        text += this.subpages[i].makeSaveText();
    for(var i = 0; i < this.headings.length; i++)
        text += this.headings[i].makeSaveText();
    return text + "</page>\n";
}

//make additional links for the main page
BookPage.prototype.makeRootMenu = function() {
    if(!this.isRoot())
        return document.createTextNode(' - ');
    var box = vbd.makeElement('span', null, [" - "]);
    var clear = vbd.makeElement('a', null, ['Clear']);
    clear.onclick = function () {
        if(!confirm('Clear this outline? The book you began will not be created.'))
            return;
        vbd.clear();
        vbd.visual();
    }
    vbd.appendChildren(box, [
        clear, ' - ',
        vbd.makeElement('small', null, [" (Version " + vbd.version + ")"]),
        vbd.makeElement('br')
    ]);
    return box;
}

// Make a complete subpage node in the outline
BookPage.prototype.makeNode = function() {
    this.box = vbd.makeElement('div', null, [
        this.getTitleNode(),
        this.makeRootMenu()
    ]);
    var smalllinks = vbd.makeElement('small', null, [
        this.makeHeadingsLink(),
        this.makeHeadingsAddLink(),
        " - ",
        this.makeSubpagesLink(),
        this.makeSubpagesAddLink()
    ])
    if (!this.isRoot())
        vbd.appendChildren(smalllinks, [" - ", this.makeDeleteLink()])
    this.box.appendChild(smalllinks);
    this.box.style.position = "relative";
    this.box.appendChild(vbd.makeElement('br'));
    //this.box.appendChild(this.getTextNode());
    this.box.appendChild(this.formspan);
    this.box.style.padding = "5px";
    this.box.style.marginBottom = "1em";
    var container = vbd.makeElement('div');
    container.style.marginLeft = "2em";
    if(this.collapse == 0) {
        for(var i = 0; i < this.subpages.length; i++) {
            if(this.subpages[i] == null)
                continue;
            var node = this.subpages[i].makeNode();
            container.appendChild(node);
        }
        for(var i = 0; i < this.headings.length; i++) {
            container.appendChild(this.headings[i].makeNode());
            container.appendChild(vbd.makeElement('br'));
        }
    }
    container.style.borderLeft = "1px dashed #000000";
    container.style.borderBottom = "1px dashed #000000";
    this.box.appendChild(container);
    var self = this;
    return this.box;
}

