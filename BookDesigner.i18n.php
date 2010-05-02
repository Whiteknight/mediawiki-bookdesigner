<?php
$messages = array();

/* TODO BIG PROJECT:
Add proper i18n support for the UI. This is going to be a big project.
For the javascript, we will probably need to generate a string table
in the PHP backend and transmit that to the client. The JS will then
read string constants from this table to generate the UI. For the rest
of the UI, we can insert string constants directly.
*/
// TODO: Can we get access to the raw $messages array from BookDesigner_body.php?
/* *** English *** */
$messages['en'] = array(
    'bookdesigner' => 'Book Designer',
    'bookdesigner-desc' => "Design and create books using a graphical outline interface",
    'bookdesigner-welcome' => "This is the <b>Visual Book Design</b> outlining tool. Use this page to create an outline for your new book.",
    'bookdesigner-qsistart' => "Quick Start Instructions",
    'bookdesigner-qsi' => <<< EOQSI
        <ol>
            <li>Click the title of a book to rename it<br>Click "<b>New Book</b>" to give your book a name</li>
            <li>Click "Headings for this page" to add sections to the page<br>Click the <b>[ + ]</b> To add 1 new section</li>
            <li>Click "Subpages" to create new pages in the book here<br>Click the <b>[ + ]</b> to add 1 new subpage</li>
            <li>When you are finished, click <b>Publish Book!</b> to create the book
        </ol>
EOQSI
    ,
    'bookdesigner-show'                => 'Show',
    'bookdesigner-hide'                => 'Hide',
    'bookdesigner-options'             => 'Options',
    'bookdesigner-optsbook'            => 'Book Options',
    'bookdesigner-optusenamespace'     => 'Specify Namespace',
    'bookdesigner-optuseuserspace'     => 'Create In User Namespace',
    'bookdesigner-optintroductionpage' => 'Create Introduction Page',
    'bookdesigner-optresourcespage'    => 'Create Resources Page',
    'bookdesigner-optlicensingpage'    => 'Create Licensing Page',
    'bookdesigner-optspage'            => 'Page Options',
    'bookdesigner-optcreateleaf'       => 'Create Leaf Pages',
    'bookdesigner-optnumberpages'      => 'Number Pages',
    'bookdesigner-optstemplate'        => 'Template Options',
    'bookdesigner-optheadertemplate'   => 'Include Header Template',
    'bookdesigner-optfootertemplate'   => 'Include Footer Template',
    'bookdesigner-optautogenerate'     => 'Autogenerate',
    'bookdesigner-publishbutton'       => 'Publish Book!',

    'bookdesigner-jserror' => <<<EOJSERROR
JavaScript is not working, or designer.js could not be found.
Make sure to enable JavaScript in your browser, and contact your wiki site administrator.
EOJSERROR
    ,
);

