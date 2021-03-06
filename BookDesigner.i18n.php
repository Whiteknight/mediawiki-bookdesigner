<?php
$messages = array();

/* TODO BIG PROJECT:
Add proper i18n support for the UI. This is going to be a big project.
For the javascript, we will probably need to generate a string table
in the PHP backend and transmit that to the client. The JS will then
read string constants from this table to generate the UI. For the rest
of the UI, we can insert string constants directly.
*/
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
    'bookdesigner-optdefaultnamespace' => 'Main Namespace',
    'bookdesigner-optusenamespace'     => 'Specify Namespace',
    'bookdesigner-optuseuserspace'     => 'Create In User Namespace',
    'bookdesigner-optintroductionpage' => 'Create Introduction Page',
    'bookdesigner-optresourcespage'    => 'Create Resources Page',
    'bookdesigner-optlicensingpage'    => 'Create Licensing Page',
    'bookdesigner-optspage'            => 'Page Options',
    'bookdesigner-optcreateleaf'       => 'Create Leaf Pages',
    'bookdesigner-optnumberpages'      => 'Number Pages',
    'bookdesigner-optstemplate'        => 'Template Options',
    'bookdesigner-optsformatting'      => 'Formatting Options',
    'bookdesigner-optheadertemplate'   => 'Include Header Template',
    'bookdesigner-optfootertemplate'   => 'Include Footer Template',
    'bookdesigner-optpagelinks'        => 'Page Links:',
    'bookdesigner-optheaderstyle'      => 'Headers:',
    'bookdesigner-optautogenerate'     => 'Autogenerate',
    'bookdesigner-publishbutton'       => 'Publish Book!',
    'bookdesigner-savebutton'          => 'Save Outline',
    'bookdesigner-loadbutton'          => 'Load',
    'bookdesigner-backnav'             => 'Back',
    'bookdesigner-error'               => 'Error:',
    'bookdesigner-errload'             => 'Could not load outline',
    'bookdesigner-msgdeleted'          => 'Outline successfully deleted',
    'bookdesigner-errdeleted'          => 'Outline could not be deleted',
    'bookdesigner-msgsaved'            => 'Outline saved successfully',
    'bookdesigner-errauthenticate'     => 'You must be logged in and have <b>buildbook</b> permission to created books using this tool.',
    'bookdesigner-reallydelete'        => 'Really delete this outline?',
    'bookdesigner-shared'              => 'Outline has been shared',
    'bookdesigner-unshared'            => 'Outline has been unshared',
    'bookdesigner-defaultheader'       => <<<EOHEADER
<div style="border: 1px solid #AAAAAA; background-color: #F8F8F8; padding: 5px; margin: auto; width: 95%">
<center>
<big>'''[[$2|$1]]'''</big>
</center>
</div>
EOHEADER
    ,

    'bookdesigner-defaultfooter'       => <<<EOFOOTER
<div style="border: 1px solid #AAAAAA; background-color: #F8F8F8; padding: 5px; margin: auto; width: 95%">
<center>
<big>'''[[$2|$1]]'''</big>
</center>
</div>
EOFOOTER
    ,

    'bookdesigner-jserror' => <<<EOJSERROR
JavaScript is not working, or designer.js could not be found.
Make sure to enable JavaScript in your browser, and contact your wiki site administrator.
EOJSERROR

);

