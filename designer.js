// Visual Book designer (vbd) object, global singleton
var vbd = {
    version: "unknown",
    // Default name of all new books:
    pageTree: null,
    defName: 'New Book',
    defpagename: 'New Page',
    defheadname: 'New Heading',
    newpagecnt: 1,
    newheadcnt: 1,

    // Div IDs where the gadget is inserted into the page
    formspan: "VBDOutlineSpan",
    statspan: "VBDStatSpan",
    hiddenBoxName: 'VBDHiddenTextArea',

    _URLBase: wgServer + wgScript + "?title=",
    _httpmethod: null,
    _httpmethods: [
        function() { return new XMLHttpRequest(); },
        function() { return new ActiveXObject("msxml2.XMLHTTP"); },
        function() { return new ActiveXObject("Microsoft.XMLHTTP"); }
    ]
};

// Set VBD to load on document load
addOnloadHook(function() {
    vbd.version = document.getElementById("VBDVersion").value;
    if (!vbd.parseExistingOutline())
        vbd.pageTree = new BookPage(vbd.defName);
    vbd.visual();
});



// Basic array management functions
vbd.CopyArray = function(array) {
    return array.slice(0);
}
vbd.FindPageNameInArray = function (array, name) {
    for(var i = 0; i < array.length; i++)
        if(array[i].pagename == name)
            return i;
    return -1;
}
vbd.FindHeadNameInArray = function(array, name) {
    for(var i = 0; i < array.length; i++)
        if(array[i].label == name)
            return i;
    return -1;
}

// Make a checkbox that corresponds to a boolean flag in vbd
vbd.makeOptionsCheckbox = function (field) {
    var cbox = vbd.makeElement('input', {type: "checkbox"});
    cbox.checked = vbd[field];
    cbox.onclick = function () { vbd[field] = cbox.checked; }
    return cbox;
}

// Set the text of an element. If the given element is a Node, set the innerHTML
// directly. If it is a string, treat it as an ID and look up the element in
// the document.
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

// Rebuild the outline and display the updated version in the browser
vbd.visual = function() {
    vbd.box = vbd.spanText(vbd.formspan, "");
    if(vbd.box == null)
        return;
    vbd.box.appendChild(vbd.pageTree.makeNode());
    vbd.updateSerializeData();
}

// Clear the outline completely and create a new one
vbd.clear = function() {
    vbd.pageTree = new BookPage(vbd.defName);
    vbd.subjects = new Array();
    vbd.readingLevel = 2;
    vbd.newpagecnt = 1;
    vbd.newheadcnt = 1;
    vbd.visual();
}

// Force a page name to use appropriate capitalization
vbd.forceCaps = function(title, isRoot) {
    if (isRoot)
        return vbd.forceTitleCaps(title);
    else
        return vbd.forceFirstCaps(title);
}

// Determines if the word needs to be capitalized. Returns 1 if it should be, 0
// otherwise.
vbd.isRealWord = function (word) {
    var preps = new Array('the', 'in', 'of', 'for', 'to', 'is', 'a', 'an');
    for(var i = 0; i < preps.length; i++)
        if(word == preps[i])
            return 0;
    return 1;
}

// Book names are forced to title caps
vbd.forceTitleCaps = function(title) {
    title.replace("_", " ");
    var words = title.split(" ");
    for(var i = 0; i < words.length; i++)
        if(words[i].length > 3 || i == 0 || vbd.isRealWord(words[i]))
            words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1);
    title = words.join(" ");
    return title;
}

// Page names are forced to have the first letter capitalized, but are not
// forced to have title-caps
vbd.forceFirstCaps = function(title) {
  title.replace("_", " ");
  return title.charAt(0).toUpperCase() + title.slice(1);
}

// Update the hidden intermediate code for the outline.
vbd.updateSerializeData = function() {
    var box = document.getElementById(vbd.hiddenBoxName);
    box.value = vbd.pageTree.makeSaveText();
}

// Create a new DOM element of the given type. Element has the given attributes
// and children. Children can be other DOM elements or plain-text strings
vbd.makeElement = function(type, attr, children) {
    var elem = document.createElement(type);
    if(attr) for(var a in attr)
        elem.setAttribute(a, attr[a]);
    if(children == null)
        return elem;
    if(children instanceof Array) {
        for(var i = 0; i < children.length; i++) {
            var child = children[i];
            if(typeof child == "string")
                child = document.createTextNode(child);
            elem.appendChild(child);
        }
    } else if(typeof children == "string")
        elem.appendChild(document.createTextNode(children));
    else
        elem.appendChild(children);
    return elem;
}

// Create a button with the given onclick handler
vbd.makeButton = function (name, value, onclick) {
    var elem = vbd.makeElement("input", {type:"button", name:name, value:value}, null);
    elem.onclick = onclick;
    return elem;
}

// Safe function to get a node from the document. If elem is a string, look it
// up as an ID. Otherwise, return the element itself.
vbd._getNode = function(elem) {
    if(elem == null)
        return null;
    if(typeof elem == "string")
        return document.getElementById(elem);
    return elem;
}

// Append an array of children to the given node
vbd.appendChildren = function(parent, children) {
    var res = vbd._getNode(parent);
    if(res == null)
        return;
    if(children instanceof Array) {
        for(var i = 0; i < children.length; i++) {
            if(typeof children[i] == 'string')
                children[i] = document.createTextNode(children[i]);
            res.appendChild(children[i]);
        }
    } else {
        if(typeof children == 'string')
            children[i] = document.createTextNode(children);
        res.appendChild(children);
    }
}

// Get a URL representing an edit page on the wiki
// TODO: I don't think this is used. Triage.
vbd._editURL = function(page, section) {
    if(section == null)
        return vbd._URLBase + page.replace(/ /g, "_") + "&action=edit&printable=yes";
    else
        return vbd._URLBase + page.replace(/ /g, "_") +
             "&section=" + section +
             "&action=edit&printable=yes";
}

// Get a URL for a raw display
vbd._rawURL = function(page) {
    return vbd._URLBase + page.replace(/ /g, "_") + "&action=raw&ctype=text/x-wiki";
}

// Initialize the AJAX client object
vbd._initclient = function() {
    for(var i = 0; i < vbd._httpmethods.length; i++) {
        try {
            var method = vbd._httpmethods[i];
            var client = method();
            if(client != null)
                vbd._httpmethod = method;
            return client;
        } catch(e) {
            continue;
        }
    }
    return null;
}

// Get the AJAX client object, initializing it if not already done.
vbd.httpClient = function() {
    if(vbd._httpmethod != null)
        return vbd._httpmethod();
    if(vbd._initclient() == null)
        return null;
    return vbd._httpmethod();
}

// Load the text of a page from the wiki using the AJAX object
vbd.loadPage = function(url, callback, type) {
    if(callback == null || url == null)
        return false;
    var client = vbd.httpClient();
    client.onreadystatechange = function() {
        if(client.readyState != 4)
            return;
        if(type == "text/xml" && client.overrideMimeType)
            callback(client.responseXML);
        else if(client.status == 200)
            callback(client.responseText);
        else
            callback('');
    }
    client.open("GET", url, true);
    if(type == "text/xml" && client.overrideMimeType)
        client.overrideMimeType(type);
    client.send(null);
    return true;
}

// Load the raw wikitext of a page from the wiki, passing it to the given
// callback function.
vbd.loadWikiText = function(page, callback) {
    return vbd.loadPage(vbd._rawURL(page), callback, "text/x-wiki");
}

// Toggle display of the options section on the UI
vbd.ToggleGUIWidget = function(pane, link) {
    var span = document.getElementById(pane);
    var state = span.style.display;
    span.style.display = (state == "none") ? "block" : "none";
    var linkelem = document.getElementById(link);
    linkelem.innerHTML = (state == "none") ? "Hide" : "Show";
}

vbd.KillGUIWidget = function(id) {
    var elem = document.getElementById(id);
    if (elem)
        elem.style.display = "none";
}

vbd.parseExistingOutline = function() {
    var text = document.getElementById('VBDHiddenTextArea').value;
    text = text.replace("&lt;", "<");
    text = text.replace("&gt;", ">");
    if (text.indexOf('<') == -1)
        return false;
    text = "<?xml version='1.0' encoding='UTF-8' ?>\n" + text;
    var xmlDoc = null;
    if (window.DOMParser) {
        var parser = new DOMParser();
        xmlDoc = parser.parseFromString(text, "application/xml");
    } else {
        xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
        xmlDoc.async = "false";
        xmlDoc.loadXML(text);
    }
    if (xmlDoc == null)
        return false;
    var node = vbd.readNodeFromOutline(xmlDoc.documentElement);
    if (node == null)
        return false;
    this.pageTree = node;
    return true;
}

vbd.readNodeFromOutline = function (node) {
    var n = node.getAttribute('name');
    var page = new BookPage(n);
    for (var i = 0; i < node.children.length; i++) {
        if (node.children[i].tagName == "page") {
            var kid = vbd.readNodeFromOutline(node.children[i]);
            page.addSubpage(kid);
            vbd.newpagecnt++;
        } else if (node.children[i].tagName == "heading") {
            var headname = node.children[i].getAttribute('name');
            var head = new PageHeading(headname);
            page.addHeading(head);
            vbd.newheadcnt++;
            vbd.getHeadingChildren(head, node.children[i]);
        }
    }
    return page;
}

vbd.getHeadingChildren = function(heading, node) {
    var children = node.children;
    for (var i = 0; i < children.length; i++) {
        var page = vbd.readNodeFromOutline(children[i]);
        heading.addSubpage(page);
    }
}


