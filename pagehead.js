function PageHeading(name) {
  if(name.match(/\s$/)) name = name.substring(0, name.length - 1);
  this.label = name;
  this.subpages = new Array();
  this.text = "";
  this.formspan = vbd.makeElement('div');
  this.parent = null;
  this.pagetext = "";
  this.comments = "";
  this.collapse = 0;
  this.box = null;
}

PageHeading.prototype.getTextNode = function () {
  var self = this;
  var div = vbd.makeElement('small', {style: "padding-left: 2em;"}, 
    ((this.pagetext.length == 0)?('[click here to edit heading text]'):(this.pagetext)));
  div.onclick = function() {
    var edit = vbd.makeElement('textarea', {rows:10});
    edit.value = self.pagetext;
    self.formspan.innerHTML = "";
    self.formspan.appendChild(document.createTextNode('Enter some text for the page:'));
    self.formspan.appendChild(edit);
    self.formspan.appendChild(vbd.makeButton('', 'Save', function() {
      self.pagetext = edit.value;
      if(self.pagetext.match(/^\s*$/)) self.pagetext = "";
      self.formspan.innerHTML = "";
      vbd.visual();
    }));
    self.closeButton();
    edit.focus(); 
  }
  div.style.cursor = "pointer";
  return div;
}

//make a link to modify the list of subpages
PageHeading.prototype.makeSubpagesLinks = function () {
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
    self.formspan.appendChild(document.createTextNode(
      'Enter the names of all subpages, one per line'));
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
  var add = vbd.makeElement('a', null, [' [ + ]']);
  add.onclick = function() {
    self.addSubpage(new BookPage(vbd.defpagename + " " + vbd.newpagecnt));
    vbd.newpagecnt++;
    vbd.visual();
  }
  return [link, add];
}

//the name of the heading
PageHeading.prototype.getTitleNode = function () {
  var container = vbd.makeElement('span');
  var collapse = vbd.makeElement('small', null, 
    ((this.collapse == 0)?('[-] '):('[+] ')));
  var span = vbd.makeElement('big', null, this.label);
  span.style.cursor = "pointer";
  var self = this;
  collapse.onclick = function () {
    self.collapse = (self.collapse == 1)?(0):(1);
    vbd.visual();
  }
  span.onclick = function() {
    var edit = vbd.makeElement('input', {type:'text', value:(self.label), size:50});
    self.formspan.innerHTML = "";
    self.formspan.appendChild(document.createTextNode('Enter the new name: '));
    self.formspan.appendChild(edit);
    self.formspan.appendChild(vbd.makeButton('', 'Rename', function() {
      self.formspan.innerHTML = "Renaming...";
      var pagename = vbd.forceFirstCaps(edit.value);
      self.label = pagename.replace("\n", "");
      self.formspan.innerHTML = "";
      vbd.visual();
    }));
    self.formspan.appendChild(vbd.makeButton('', 'Delete', function () {
      if(confirm("delete heading '" + self.label + "'?")) {
        self.parent.removeHeading(self); 
        vbd.visual();
      }
    }));
    self.closeButton();
    edit.focus();
  }
  if(this.subpages.length != 0) container.appendChild(collapse);
  container.appendChild(span);
  return(container);
}

//get the depth of the page the heading is on
PageHeading.prototype.getDepth = function () {
  return this.parent.getDepth();
}

//make wikitext of pages under a heading, but not of the heading itself
PageHeading.prototype.makeWikitextLinkStars = function (parent) {
  var text = "";
  for(var i = 0; i < this.subpages.length; i++) {
    text = text + this.subpages[i].makeWikitextLinkStars(parent);
  }
  return text;
}

//make the links for the pages under a heading, as formatted for a template
PageHeading.prototype.makeTemplateLinks = function () {
  var text = "";
  for(var i = 0; i < this.subpages.length; i++) {
    text = text + this.subpages[i].makeTemplateLinks();
  }
  return text;
}

//create a list of all subpages of the given heading and append them to list
PageHeading.prototype.listAllSubpages = function(list) {
  if(this.subpages.length) {
    for(var i = this.subpages.length - 1; i >= 0; i--) {
      list = this.subpages[i].listAllSubpages(list);
      list.push(this.subpages[i]);
    }
  }
  return list;
} 

//make the wikitext for a heading
PageHeading.prototype.makeWikitext = function () {
  var text = "";
  if(this.parent.isRoot()) text = "=== " + this.label + " ===\n\n";
  else text = "== " + this.label + " ==\n\n";
  text += this.pagetext + "\n";
  for(var i = 0; i < this.subpages.length; i++) {
    text = text + this.subpages[i].makeWikitextLinkStars(this);
  }
  return text;
}

//create the print version text for this heading
PageHeading.prototype.makePrintVersionText = function () {
  var text = "{{Print unit page|" + this.label + "|" + this.pagetext + "}}\n\n";
  for(var x = 0; x < this.subpages.length; x++) {
    text = text + this.subpages[x].makePrintVersionText();
  }
  return text;
}

//make the collection text for this heading
PageHeading.prototype.makeCollectionText = function () {
  var text = ";" + this.label + "\n";
  for(var i = 0; i < this.subpages.length; i++) {
    text = text + this.subpages[i].makeCollectionText();
  }
  return text;
}

//create a close button 
PageHeading.prototype.closeButton = function () {
  var self = this;
  this.formspan.appendChild(vbd.makeButton('', 'Close', function() {
    self.formspan.innerHTML = "";
  }));
}

//prepare the text to save a heading from the outline
PageHeading.prototype.makeSaveText = function () {
  var text = "";
  text += this.label + "\n[\n";
  //if(this.pagetext.length != 0) {
  //  var pagetext = this.pagetext;
  //  pagetext.replace(/\n/g, "\n&");
  //  text += "&" + pagetext + "\n";
  //}
  //if(this.comments.length != 0) {
  //  var comments = this.comments;
  //  comments.replace(/\n/g, "\n%");
  //  text += "%" + comments + "\n";
  //}
  for(var i = 0; i < this.subpages.length; i++) {
    text += this.subpages[i].makeSaveText();
  }
  return text + "\n]\n";
}

//make a heading node in the outline
PageHeading.prototype.makeNode = function () {
  this.box = vbd.makeElement('div', null, [
    this.getTitleNode(), " - ",
    vbd.makeElement('small', null, this.makeSubpagesLinks()),
    vbd.makeElement('br'),
    this.getTextNode(),
    this.formspan
  ]);
  this.box.style.position = "relative";
  this.box.style.padding = "5px";
  var container = vbd.makeElement('div');
  container.style.marginLeft = "2em";
  if(this.collapse == 0) {
    for(var i = 0; i < this.subpages.length; i++) {
      if(this.subpages[i] == null) continue;
      var node = this.subpages[i].makeNode();
      container.appendChild(node);
    }
  }
  container.style.borderBottom = "1px dashed #AAAAAA";
  container.style.borderLeft = "1px dashed #AAAAAA";
  this.box.appendChild(container);
  var self = this;
  return this.box;
}

//add a subpage to this heading
PageHeading.prototype.addSubpage = function (subpage) {
  subpage.parent = this.parent;
  subpage.parent2 = this;
  this.subpages.push(subpage);
}

//add a new heading to the parent of this (headings cannot contain headings)
PageHeading.prototype.addHeading = function (heading) {
  heading.parent = this.parent;
  this.parent.headings.push(heading);
}

//remove a subpage from this heading
PageHeading.prototype.removeSubpage = function(subpage) {
  for(var i = 0; i < this.subpages.length; i++) {
    if(this.subpages[i] != subpage) continue;
    this.subpages.splice(i, 1);
    return;
  }
}


