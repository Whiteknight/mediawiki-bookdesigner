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
	'bookdesigner-desc' => "Design and create books",
);

