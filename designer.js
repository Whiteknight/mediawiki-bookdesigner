//Visual Book designer (vbd) class
var vbd = {
  // Version numbers
  version:  3.73,
  bookpageversion: 0.00,
  pageheadversion: 0.00,

  // default name of all new books:
  pageTree: null,
  defName: 'New Book',
  defpagename: 'New Page',
  defheadname: 'New Heading',
  newpagecnt: 1,
  newheadcnt: 1,

  //div IDs where the gadget is inserted into the page
  formspan: "WKVBDSpan",
  statspan: "WKVBDStatSpan",

  // Navigation templates and configuration parameters
  templates: new Array(
    ["Simple Header",    0, 0, "Simple header"],
    ["Page Nav Header",  1, 0, "Header with forward/back links"],
    ["Page Nav Header2", 1, 0, "Header with forward/back links (2)"],
    ["Page List Header", 0, 1, "Header with page list"],
    ["Page List Nav",    1, 1, "Header with page list and forward/back links"]
  ),
  template: 0,

  // Subjects for categorization
  subjects: new Array(),

  // other options
  defaultinherit:  false,
  defaultcollapse: false,
  commentsaspages: false,
  useexternaledit: false,
};

//Basic array management functions
vbd.CopyArray = function(array) { return array.slice(0); }
vbd.FindPageNameInArray = function (array, name) {
  for(var i = 0; i < array.length; i++) {
    if(array[i].pagename == name) return i;
  }
  return -1;
}
vbd.FindHeadNameInArray = function(array, name) {
  for(var i = 0; i < array.length; i++) {
    if(array[i].label == name) return i;
  }
  return -1;
}

//Make a checkbox that corresponds to a boolean flag in vbd
vbd.makeOptionsCheckbox = function (field) {
  var cbox = vbd.makeElement('input', {type: "checkbox"});
  cbox.checked = vbd[field];
  cbox.onclick = function () { vbd[field] = cbox.checked; }
  return cbox;
}

//Initialize
$(document).ready(function () {
  vbd.pageTree = new BookPage(vbd.defName);
  vbd.visual();
});

vbd.spanText = function(spanid, txt) {
  var item;
  if(typeof spanid == "string")
    item = document.getElementById(spanid);
  else
    item = spanid;
  if(txt != null)
    item.innerHTML = txt;
  return item;
}

//rebuild the outline
vbd.visual = function() {
  vbd.box = vbd.spanText(vbd.formspan, "");
  if(vbd.box == null) return;
  vbd.box.appendChild(vbd.pageTree.makeNode());
  vbd.updateSerializeData();
}

//Clear the outline completely and create a new one
vbd.clear = function() {
  vbd.pageTree = new BookPage(vbd.defName);
  vbd.subjects = new Array();
  vbd.readingLevel = 2;
  vbd.newpagecnt = 1;
  vbd.newheadcnt = 1;
  vbd.visual();
}

//Try to load in a saved collection
vbd.loadNodeTreeCollection = function(text) {
  vbd.clear();
  var last = vbd.pageTree;
  var lines = text.split("\n");
  for(var i = 0; i < lines.length; i++) {
    if(lines[i].match(/^===/) || lines[i].match(/\[\[category:/i)) {
      continue;
    } else if(lines[i].match(/^==.+==/)) {
      vbd.pageTree.pagename = vbd.extractHeadingName(lines[i]);
    } else if(lines[i].match(/^;/)) {
      var head = new PageHeading(lines[i].substring(1));
      last.addHeading(head);
      last = head;
    } else if(lines[i].match(/^:\[\[/)) {
      if(lines[i].match(/Resources/)) { vbd.useresources = true; continue; }
      if(lines[i].match(/Licensing/)) { vbd.uselicensing = true; continue; }
      if(lines[i].match(/Wikibooks:/)) { vbd.usecollectionpreface = true; continue; }
      var page = new BookPage(vbd.extractLinkPageName(lines[i], vbd.pageTree.pagename));
      last.addSubpage(page);
    }
  }
  vbd.visual();
  vbd.pageTree.formspan.innerHTML = 'Successfully loaded collection!';
}

//take the wikitext of an arbitrary page and try to load a reasonable outline from it.
vbd.loadNodeTreeTOC = function (text, title) {
  vbd.clear();
  var lastn = 0;
  var last = new Array();
  last[0] = vbd.pageTree;
  vbd.pageTree.pagename = title;
  var lines = text.split("\n");
  for(i = 0; i < lines.length; i++) {
    lines[i] = lines[i].replace(/^[ \t]+/, "");
    lines[i] = lines[i].replace(/[ \t]+$/, "");
    lines[i] = lines[i].replace(/\r\n/g, "");
    if(lines[i] == "") continue;
    if(lines[i].match(/^===.+===/)) { //heading
      var head = new PageHeading(vbd.extractHeadingName(lines[i]));
      last[0].addHeading(head);
      last[0] = head;
    } else if(lines[i].match(/^[\*\#]*\s*\[\[.+\]\]/)) { //subpage
      if(lines[i].match(/Resources/)) { vbd.useresources = true; continue; }
      if(lines[i].match(/Licensing/)) { vbd.uselicensing = true; continue; }
      if(lines[i].match(/Category:/i)) continue;
      var stars = lines[i].match(/^[\*\#]*/);
      var n = stars[0].length;
      if(n == 0) n = 1;
      var k = n - 1;
      if(last[k] == null) { k = 0; }
      last[n] = new BookPage(vbd.extractLinkPageName(lines[i], title));
      last[k].addSubpage(last[n]);
      lastn = n;
    }
  }
  vbd.pageTree.formspan.innerHTML = "";
  vbd.visual();
  vbd.pageTree.formspan.appendChild(document.createTextNode(
    'Successfully loaded outline from ' + title + ' TOC'
  ));
}

//try to get the name of a level-3 heading
vbd.extractHeadingName = function(line) {
  line = line.substring(3);
  line = line.substring(0, line.indexOf("==="));
  return line;
}

//Try to get the page name from a link. Very limited ability to deal with relative links
vbd.extractLinkPageName = function(line, title) {
  line = line.substring(line.indexOf("[[") + 2);
  var end = line.indexOf("|");
  if(end == -1) {
    end = line.indexOf("]]");
  }
  line = line.substring(0, end)
  if(line.charAt(line.length - 1) == '/') line = line.substring(0, line.length - 1);
  if(line.charAt(0) == '/') line = title + line;
  for(end = line.indexOf("/") ; end != -1; end = line.indexOf("/")) {
    line = line.substring(end + 1);
  }
  return line;
}

//force a page name to use appropriate capitalization
vbd.forceCaps = function(title, isRoot) {
  if(isRoot) return vbd.forceTitleCaps(title);
  else return vbd.forceFirstCaps(title);
}

//determines if the word needs to be capitalized. Returns 1 if it should be, 0 otherwise.
vbd.isRealWord = function (word) {
  var preps = new Array('the', 'in', 'of', 'for', 'to', 'is', 'a', 'an');
  for(var i = 0; i < preps.length; i++) {
    if(word == preps[i]) return 0;
  }
  return 1;
}

//book names are forced to title caps
vbd.forceTitleCaps = function(title) {
  title.replace("_", " ");
  var words = title.split(" ");
  for(var i = 0; i < words.length; i++) {
    if(words[i].length > 3 || i == 0 || vbd.isRealWord(words[i]))
      words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1);
  }
  title = words.join(" ");
  return title;
}

//page names are forced to have the first letter capitalized, but are not forced to have title-caps
vbd.forceFirstCaps = function(title) {
  title.replace("_", " ");
  return title.charAt(0).toUpperCase() + title.slice(1);
}

//Create the wikitext of the selected navigation template
vbd.makeTemplateText = function () {
  var template = vbd.templates[vbd.template];
  var book = vbd.pageTree.pagename;
  var text = "{"+"{subst:User:Whiteknight/" + template[0] + "|" + 
    ((template[1])?("book="):("")) + book;
  if(template[2] == 1) {
    text += "|" + ((template[1] == 1)?("list="):("")) + vbd.pageTree.makeTemplateLinks();
    text += "[[" + book + "/Resources|Resources]] - " + 
            "[[" + book + "/Licensing|Licensing]] - " +
            "[[Talk:" + book + "|Discuss]]";
  }
  return text + "}}";
}

vbd.updateSerializeData = function() {
    var box = document.getElementById('WKVBDHiddenTextArea');
    box.value = vbd.pageTree.makeSaveText();
}

vbd.makeElement = function(type, attr, children) {
  var elem = document.createElement(type);
  if(attr) for(var a in attr) elem.setAttribute(a, attr[a]);
  if(children == null) return elem;
  if(children instanceof Array) for(var i = 0; i < children.length; i++) {
    var child = children[i];
    if(typeof child == "string") child = document.createTextNode(child);
    elem.appendChild(child);
  }
  else if(typeof children == "string") elem.appendChild(document.createTextNode(children));
  else elem.appendChild(children);
  return elem;
}

vbd.makeButton = function (name, value, onclick) {
    var elem = vbd.makeElement("input", {type:"button", name:name, value:value}, null);
    elem.onclick = onclick;
    return elem;
}

vbd._getNode = function(elem) {
    if(elem == null) return null;
    if(typeof elem == "string") return document.getElementById(elem);
    return elem;
}

vbd.appendChildren = function(parent, children) {
    var res = vbd._getNode(parent);
    if(res == null) return;
    if(children instanceof Array) {
        for(var i = 0; i < children.length; i++) {
            if(typeof children[i] == 'string') children[i] = document.createTextNode(children[i]);
            res.appendChild(children[i]);
        }
    } else {
        if(typeof children == 'string') children[i] = document.createTextNode(children);
        res.appendChild(children);
    }
}

vbd._URLBase = wgServer + wgScript + "?title=";

vbd._editURL = function(page, section) {
    if(section == null)
        return vbd._URLBase + page.replace(/ /g, "_") + "&action=edit&printable=yes";
    else
        return vbd._URLBase + page.replace(/ /g, "_") +
             "&section=" + section +
             "&action=edit&printable=yes";
}

vbd.showEditWindowExternal = function(page, text, summary) {
    var loadwin = function(win, newtext, newsummary) {
        var eb = win.document.getElementById('wpTextbox1');
        if(typeof newtext == "string") eb.value = newtext;
        else if(typeof newtext == "function") eb.value = newtext(eb.value);
        if(newsummary != null) win.document.getElementById('wpSummary').value = newsummary;
    }
    var w = window.open(vbd._editURL(page));
    if (w.attachEvent)
        w.attachEvent("onload", function() { loadwin(w, text, summary) });
    else if (window.addEventListener)
        w.addEventListener("load", function() { loadwin(w, text, summary) }, false);
    else
        w.document.addEventListener("load", function() { loadwin(w, out) }, false);
}

vbd.showEditWindowInline = function(bookpage, parentspan, page, deftext, defsummary) {
    if(typeof parentspan == "string")
        $("#" + parentspan).load(vbd._editURL + " #editform");
    else
        $(parentspan).load(vbd._editURL + " #editform");
    $("#Textbox1").value(deftext);
    $("#wpSummary").value(defsummary);
    bookpage.closeButton();
}

vbd._rawURL = function(page) {
    return vbd._URLBase + page.replace(/ /g, "_") + "&action=raw&ctype=text/x-wiki";
}

vbd._httpmethods = [
    function() { return new XMLHttpRequest(); },
    function() { return new ActiveXObject("msxml2.XMLHTTP"); },
    function() { return new ActiveXObject("Microsoft.XMLHTTP"); }
];

vbd._httpmethod = null;

vbd._initclient = function() {
    for(var i = 0; i < vbd._httpmethods.length; i++) {
        try {
           var method = vbd._httpmethods[i];
            var client = method();
            if(client != null) vbd._httpmethod = method;
            return client;
        } catch(e) { continue; }
    }
    return null;
}

vbd.httpClient = function() {
    if(vbd._httpmethod != null) return vbd._httpmethod();
    if(vbd._initclient() == null) return null;
    return vbd._httpmethod();
}

vbd.loadPage = function(url, callback, type) {
    if(callback == null || url == null) return false;
    var client = vbd.httpClient();
    client.onreadystatechange = function() {
        if(client.readyState != 4) return;
        if(type == "text/xml" && client.overrideMimeType) callback(client.responseXML);
        else if(client.status == 200) callback(client.responseText);
        else callback('');
    }
    client.open("GET", url, true);
    if(type == "text/xml" && client.overrideMimeType) client.overrideMimeType(type);
    client.send(null);
    return true;
}

vbd.loadWikiText = function(page, callback) {
    return vbd.loadPage(vbd._rawURL(page), callback, "text/x-wiki");
}



