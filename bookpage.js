//class constructor for BookPage
function BookPage(name) {
    if(name.match(/\s$/)) name = name.substring(0, name.length - 1);
    this.pagename = name;
    this.headings = new Array();
    this.subpages = new Array();
    this.formspan = vbd.makeElement('div');
    this.parent   = null;                   //subpage parent, null if root
    this.parent2  = null;                   //heading parent, null if not in a heading.
    this.pagetext = "";                     //initial text of a page
    this.comments = "";                     //comment text which is in the outline only
    this.collapse = vbd.defaultcollapse;    //flag to collapse part of the tree
    this.inherittext = vbd.defaultinherit;  //flag to inherit text from parent
    this.box = null;
}

//add a new heading to the page
BookPage.prototype.addHeading = function(heading) {
  heading.parent = this;
  this.headings.push(heading);
}

//add a new subpage to the page
BookPage.prototype.addSubpage = function(subpage) {
  subpage.parent = this;
  this.subpages.push(subpage);
}

//Show a little animation when things are happening behind the scenes
// TODO: Move this to a local image
BookPage.prototype.makeLoadingNotice = function(text) {
  this.formspan.innerHTML = "<img src=\"http://upload.wikimedia.org/wikipedia/commons/4/42/Loading.gif\"> <big>" + text + "...</big>"
}

//make an editor, according to the users preferences
// TODO: Use jQuery's AJAXy magic to load the edit window
BookPage.prototype.makeEditInterface = function(page, deftext, defsummary) {
    if(vbd.useexternaledit) vbd.showEditWindowExternal(page, deftext, defsummary);
    else {
        this.makeLoadingNotice("Loading");
        vbd.showEditWindowInline(this, this.formspan, page, deftext, defsummary);
    }
}

//create the part of the outline for the page text and comments
// TODO: See if jQuery has any UI magic that will make all this look less ugly
BookPage.prototype.getTextNode = function () {
  var self = this;
  var div = vbd.makeElement('small', {}, 
    ((this.pagetext.length == 0)?('[click here to edit page text]'):(this.pagetext)));
  div.innerHTML = div.innerHTML + ((this.inherittext && !this.isRoot())?(' (inherited)'):(""))
  var cmts = vbd.makeElement('small', {style:'color: #666666'}, 
    ((this.comments.length == 0)?('[click here to edit comments]'):(this.comments)));
  div.onclick = function() {
    var edit = vbd.makeElement('textarea', {rows:10});
    edit.value = self.pagetext;
    self.formspan.innerHTML = 'Enter some text for the page:';
    self.formspan.appendChild(edit);
    if(!self.isRoot()) {
      var inherit = vbd.makeElement('input', {type:"checkbox"});
      inherit.checked = self.inherittext;
      vbd.appendChildren(self.formspan, [inherit, 'inherit text from parent?']);
    }
    self.formspan.appendChild(vbd.makeButton('', 'Save', function() {
      if(!self.isRoot()) self.inherittext = inherit.checked;
      self.pagetext = edit.value;
      if(self.pagetext.match(/^\s*$/)) self.pagetext = "";
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
      if(self.comments.match(/^\s*$/)) self.comments = "";
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

//remove a subpage
BookPage.prototype.removeSubpage = function(subpage) {
  for(var i = 0; i < this.subpages.length; i++) {
    if(this.subpages[i] != subpage) continue;
    this.subpages.splice(i, 1);
    return;
  }
}

//remove a heading
BookPage.prototype.removeHeading = function(heading) {
  for(var i = 0; i < this.headings.length; i++) {
    if(this.headings[i] != heading) continue;
    this.headings.splice(i, 1);
    return;
  }
}

//create a link to modify the list of headings.
BookPage.prototype.makeHeadingsLink = function () {
  var link = vbd.makeElement('a', null, ['Headings for this page']);
  var self = this;
  link.onclick = function() {
    var text = "";
    for(var i = 0; i < self.headings.length; i++) {
      text = text + self.headings[i].label + "\n";
    }
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
        if(pages[i].match(/^\s*$/)) continue;
        var stat = vbd.FindHeadNameInArray(old, pages[i]);
        if(stat != -1) {
          self.addHeading(old[stat]);
        } else {
          self.addHeading(new PageHeading(vbd.forceFirstCaps(pages[i])));
        }
      }
      self.formspan.innerHTML = "";
      vbd.visual();
    }));
    self.closeButton();
    edit.focus();
  }
  return(link);
}

//create a link to add a new subpage
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

//create a link to add a new heading
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

//make a list to modify the list of subpages
BookPage.prototype.makeSubpagesLink = function () {
  var link = vbd.makeElement('a', null, ['Subpages']);
  var self = this;
  link.onclick = function() {
    var text = "";
    for(var i = 0; i < self.subpages.length; i++) {
      text = text + self.subpages[i].pagename + "\n";
    }
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
        if(pages[i].match(/^\s*$/)) continue;
        var stat = vbd.FindPageNameInArray(old, pages[i]);
        if(stat != -1) self.addSubpage(old[stat]);
        else self.addSubpage(new BookPage(vbd.forceFirstCaps(pages[i])));
      }
      self.formspan.innerHTML = "";
      vbd.visual();
    }));
    self.closeButton();
    edit.focus();
  }
  return(link);
}

//display the title of the page
BookPage.prototype.getTitleNode = function () {
  var self = this;
  var container = vbd.makeElement('span');
  var collapse = vbd.makeElement('small', null, 
    ((this.collapse == 0)?('[-] '):('[+] ')));
  collapse.style.cursor = "pointer";
  collapse.onclick = function () {
    self.collapse = (self.collapse == 1)?(0):(1);
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
      if(self.isRoot()) vbd.loadWikiText(pagename, function (text) { 
        if(text.length != 0) self.formspan.innerHTML = "<b>Warning:</b> " + pagename + " already exists. Check it before saving over it.";
        else self.formspan.innerHTML = "";
      });
      else self.formspan.innerHTML = "";
      self.pagename = pagename.replace("\n", "");
      vbd.visual();
    })]);
    if(!self.isRoot()) self.formspan.appendChild(vbd.makeButton('', 'Delete', function () {
      if(confirm("delete page '" + self.pagename + "'?")) {
        if(self.parent2 == null) self.parent.removeSubpage(self); 
        else self.parent2.removeSubpage(self);
        vbd.visual();
      }
    }));
    self.closeButton();
  }
  if(this.subpages.length != 0 || this.headings.length != 0) container.appendChild(collapse);
  container.appendChild(span);
  return(container);
}

//functions for dealing with recursion
BookPage.prototype.isRoot = function () { 
  return (this.parent == null)?(1):(0);
}
BookPage.prototype.getFullName = function() {
  if(this.isRoot()) return this.pagename;
  return this.parent.getFullName() + "/" + this.pagename;
} 
BookPage.prototype.getDepth = function() {
  if(this.isRoot()) return 0;
  return this.parent.getDepth() + 1;
}
BookPage.prototype.getBookName = function () {
  if(this.isRoot()) return this.pagename;
  return this.parent.getBookName();
}

//make wikitext of a page, using nested asterisks, as required
BookPage.prototype.makeWikitextLinkStars = function (parent) {
  var depth = this.getDepth() - ((parent != null)?(parent.getDepth()):(0));
  var stars = "";
  for(var i = 0; i < depth; i++) {
    stars = stars + "*";
  }
  var text = stars + "[[" + this.getFullName() + "|" + this.pagename + "]]\n";
  text = text.replace(/\n/g, "") + "\n";
  for(var i = 0; i < this.subpages.length; i++) {
    text += this.subpages[i].makeWikitextLinkStars(parent);
  }
  for(var i = 0; i < this.headings.length; i++) {
    text += this.headings[i].makeWikitextLinkStars(parent);
  }
  return text;
}

//make links for a subpage, as formattined for a template
BookPage.prototype.makeTemplateLinks = function () {
  var text = "[[" + this.getFullName() + "|" + this.pagename + "]] - ";
  for(var i = 0; i < this.subpages.length; i++) {
     text += this.subpages[i].makeTemplateLinks() + " - ";
  }
  for(var i = 0; i < this.headings.length; i++) {
     text += this.headings[i].makeTemplateLinks();
  }
  return text;
}

//make a link for a page without nested asterisks
BookPage.prototype.makeWikitextLink = function () {
  return "*[[" + this.getFullName() + "|" + this.pagename + "]]";
}

BookPage.prototype.getPageText = function() {
  if(!this.inherittext || this.isRoot()) return this.pagetext;
  return this.parent.getPageText() + "\n" + this.pagetext;
}

//make the wikitext of an entire page
BookPage.prototype.makeWikitextPage = function () {
  var text = "{{" + this.getBookName() + "/Page}}\n\n";
  for(var i = 0; i < this.subpages.length; i++) {
    text = text + this.subpages[i].makeWikitextLinkStars(this);
  }
  text += this.getPageText() + "\n";
  for(var i = 0; i < this.headings.length; i++) {
    text = text + this.headings[i].makeWikitext(this) + "\n";
  }
  text = text + "[[Category:" + this.getBookName() + "]]\n";
  return text;
}

//make the wikitext for the special root page
BookPage.prototype.makeWikitextRoot = function () {
  var text = "{{New book}}\n{{" + this.pagename + "/Page}}\n" +
    "{{Reading level|" + vbd.readingLevels[vbd.readingLevel] + "}}\n\n";
  text += "== Preface ==\n" + this.pagetext + "\n";
  text += "== Table of Contents ==\n\n";
  if(vbd.useintroduction) text += "*[[" + this.pagename + "/Introduction|Introduction]]\n";
  for(var i = 0; i < this.subpages.length; i++) {
    text += this.subpages[i].makeWikitextLinkStars();
  }
  text += "\n";
  for(var i = 0; i < this.headings.length; i++) {
    text += this.headings[i].makeWikitext() + "\n";
  }
  text += "\n== Resources and Licensing ==\n\n";
  if(vbd.useresources) text += "*[[" + this.pagename + "/Resources|Resources]]\n";
  if(vbd.uselicensing) text += "*[[" + this.pagename + "/Licensing|Licensing]]\n";
  text += "\n[[Category:" + this.getBookName() + "]]\n";
  text += "{{Subject";
  for(var i = 0; i < vbd.subjects.length; i++) {
    text += "|" + vbd.subjects[i];
  }
  text += "}}\n{{Alphabetical|" + this.pagename.substr(0, 1) + "}}\n";
  return text;
}

//make the print version text of the root page
BookPage.prototype.makePrintVersionRoot = function() {
  var text = "{{Print version notice}}\n" +
    "__NOTOC__ __NOEDITSECTION__\n" +
    "<br style=\"page-break-after: always\">\n" +
    "{{:" + this.getBookName() + "/Cover}}\n" +
    "<br style=\"page-break-after: always\">\n\n";
  if(vbd.useintroduction) text += "{{Print chapter heading|Introduction}}\n";
  for(var x = 0; x < this.subpages.length; x++) {
    text += this.subpages[x].makePrintVersionText();
  }
  for(var x = 0; x < this.headings.length; x++) {
    text += this.headings[x].makePrintVersionText();
  }
  text = text + "{{Print unit page|Resources and Licensing}}\n\n";
  if(vbd.useresources) text += "{{Print chapter heading|Resources}}\n"
  if(vbd.uselicensing) text += "{{Print chapter heading|Licensing}}\n";
  text += "\n== License: GFDL ==\n\n{{:GFDL}}\n\n";
  return text;
}

//make the print version text of a subpage
BookPage.prototype.makePrintVersionText = function () {
  return "{{Print chapter heading|" + this.pagename + "}}\n" + 
    "{{:" + this.getFullName() + "}}\n\n";
}

//make the wikitext for any page
BookPage.prototype.makeWikitextAll = function()  {
  if(this.isRoot()) return this.makeWikitextRoot();
  return this.makeWikitextPage();
}

//make a link to view the wikitext of the current page
BookPage.prototype.makeDisplayLink = function () {
  var link = vbd.makeElement('a', null, ['Wikitext']);
  var self = this;
  link.onclick = function() {
    self.formspan.innerHTML = "<pre>" + self.makeWikitextAll() + "</pre>";
    self.closeButton();
  }
  return(link);
}

//make the collection text of a page
BookPage.prototype.makeCollectionText = function (subt) {
  var text = "";
  if(this.isRoot()) {
    if(subt == null || subt == "") subt = "A Book from English Wikibooks";
    text = "== " + this.pagename + " ==\n=== " + subt + " ===\n";
    if(vbd.usecollectionpreface) text +=  ":[[Wikibooks:Collections Preface]]\n";
    if(vbd.useintroduction) text += ":[[" + this.pagename + "/Introduction|Introduction]]\n";
  }
  else text = ":[[" + this.getFullName() + "|" + this.pagename + "]]\n";
  for(var i = 0; i < this.subpages.length; i++) {
    text = text + this.subpages[i].makeCollectionText();
  }
  for(var i = 0; i < this.headings.length; i++) {
    text = text + this.headings[i].makeCollectionText();
  }
  if(this.isRoot()) {
    text += "\n;Resources and Licensing\n";
    if(vbd.useresources) text += ":[[" + this.pagename + "/Resources|Resources]]\n";
    if(vbd.uselicensing) text += ":[[" + this.pagename + "/Licensing|Licensing]]\n";
    text += "\n[[Category:Collections]]\n";
  }
  return text;
}

//make a button to close an open formspan
BookPage.prototype.closeButton = function () {
  var self = this;
  this.formspan.appendChild(vbd.makeButton('', 'Close', function() {
    self.formspan.innerHTML = "";
  }));  
}

//add all subpages of the current page to list
BookPage.prototype.listAllSubpages = function(list) {
  if(this.subpages.length) {
    for(var i = this.subpages.length - 1; i >= 0; i--) {
      list = this.subpages[i].listAllSubpages(list);
      list.push(this.subpages[i]);
    }
  }
  if(this.headings.length) {
    for(var i = this.headings.length - 1; i >= 0; i--) {
      list = this.headings[i].listAllSubpages(list);
    }
  }
  return list;
}

// TODO: Had to rip out all the automation stuff. Hopefully we can post the text to the wiki and create the pages using PHP

//make a link to edit the text of the page
BookPage.prototype.makeEditLink = function () {
  var link = vbd.makeElement('a', null, ['Edit']);
  var self = this;
  link.onclick = function() {
    self.makeEditInterface(self.getFullName(), self.makeWikitextAll(), self.pagename + ": Created by Whiteknight's Visual Book Designer");
  }
  return(link);
}

//make a link to load the TOC from an existing book
BookPage.prototype.makeLoadTOCLink = function () {
  var link = vbd.makeElement('a', null, ['Load TOC']);
  var self = this;
  link.onclick = function() {
    var page = self.pagename;
    var edit = vbd.makeElement('input', {type:'text', value:page, size:50});
    self.formspan.innerHTML = 'Enter the name of the book to load: ';
    self.formspan.appendChild(edit);
    self.formspan.appendChild(vbd.makeButton('', 'Load', function() {
      page = edit.value;
      vbd.loadWikiText(page, function (text) { 
        vbd.loadNodeTreeTOC(text, page);
      });
    }));
    self.closeButton();
    edit.focus();
  }
  return(link);
}

BookPage.prototype.makeSaveCollectionLink = function (key) {
  var link = vbd.makeElement('a', null, ['Save']);
  var self = this;
  link.onclick = function() {
    var page = "";
    if(key == "personal") page = "User:" + wgUserName + "/Collections/" + self.pagename;
    else page = "Wikibooks:Collections/" + self.pagename;
    var subt = vbd.makeElement('input', {type:'text', size:50});
    var edit = vbd.makeElement('input', {type:'text', value:page, size:50});
    self.formspan.innerHTML = "";
    vbd.appendChildren(self.formspan, ["Enter a subtitle for the book: ", subt,
      vbd.makeElement('br'), 
      'Enter the ' + key + ' collection name to save at: ', edit]);
    self.formspan.appendChild(vbd.makeButton('', 'Save', function() {
      self.makeEditInterface(edit.value, self.makeCollectionText(subt.value), ": Saving " + key + " collection");
    }));
    self.closeButton();
    edit.focus();
  }
  return(link);
}

BookPage.prototype.makeLoadCollectionLink = function (type) {
  var link = vbd.makeElement('a', null, ['Load']);
  var self = this;
  link.onclick = function() {
    var page = "";
    if(type == 'personal') page = "User:" + wgUserName + "/Collections/";
    else page = "Wikibooks:Collections/";
    var edit = vbd.makeElement('input', {type:'text', size:50});
    self.formspan.innerHTML = "";
    vbd.appendChildren(self.formspan, ["Enter the name of the " + type + " collection to load: ", edit,
      vbd.makeButton('', 'Load', function () {
        vbd.loadWikiText(page + edit.value, function (text) { 
          vbd.loadNodeTreeCollection(text, page);
        });
      })
    ]);
  }
  return link;
}

//make the text for a heading template
BookPage.prototype.makeTemplate = function () {
  var self = this;
  self.makeEditInterface("Template:" + this.pagename + "/Page", vbd.makeTemplateText(), self.pagename + ": Creating page head template");
}

//prepare the text to save a subpage from the outline.
BookPage.prototype.makeSaveText = function () {
  var text = this.pagename + "\n";
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
  text += "[\n";
  for(var i = 0; i < this.subpages.length; i++) {
    text += this.subpages[i].makeSaveText();
  }
  text += "\n]\n{\n";
  for(var i = 0; i < this.headings.length; i++) {
    text += this.headings[i].makeSaveText();
  }
  return text + "\n}\n";
}


BookPage.prototype.makeFileLink = function() {
  var self = this;
  var link = vbd.makeElement('a', null, ['File']);
  var self = this;
  link.onclick = function () {
    self.formspan.innerHTML = "";
    vbd.appendChildren(self.formspan, [
      "Select whether you want to load or save this outline:", vbd.makeElement('br'),
      'Personal Collection: ', self.makeSaveCollectionLink('personal'), ' - ', self.makeLoadCollectionLink('personal'), vbd.makeElement('br'),
      'Public Collection: ', self.makeSaveCollectionLink('community'), ' - ', self.makeLoadCollectionLink('community'), vbd.makeElement('br'),
      'Existing Books: ', self.makeLoadTOCLink(), ' - ', self.makeAutomateLink(), vbd.makeElement('br')
    ]);
    self.closeButton();
  }
  return link;
}

//make additional links for the main page
BookPage.prototype.makeRootMenu = function() {
  if(!this.isRoot()) { return document.createTextNode(' - '); }
  var box = vbd.makeElement('span', null, [" - "]);
  var clear = vbd.makeElement('a', null, ['Clear']);
  clear.onclick = function () {
    if(!confirm('Clear this outline? The book you began will not be created.')) return;
    vbd.clear();
    self.formspan.innerHTML = "outline cleared";
    vbd.visual();
  }
  vbd.appendChildren(box, [
    clear, ' - ',
    vbd.makeElement('small', null, [" (Version " + vbd.version + ")"]),
    vbd.makeElement('br')
  ]);
  return box;
}

//make a subpage node in the outline
BookPage.prototype.makeNode = function() {
  this.box = vbd.makeElement('div', null, [
    this.getTitleNode(),
    this.makeRootMenu(),
    vbd.makeElement('small', null, [
      this.makeHeadingsLink(), this.makeHeadingsAddLink(), " - ",
      this.makeSubpagesLink(), this.makeSubpagesAddLink()
    ])
  ]);
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
      if(this.subpages[i] == null) continue;
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
  return(this.box);
}



