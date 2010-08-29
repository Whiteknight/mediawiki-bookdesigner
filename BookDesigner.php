<?php
# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
    echo <<<EOT
To install BookDesigner, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/BookDesigner/BookDesigner.php" );
EOT;
    exit( 1 );
}

$wg_VBDExtensionVersion = '4.0 beta';

$wgExtensionCredits['specialpage'][] = array(
    'name'           => 'BookDesigner',
    'author'         => 'Andrew Whitworth',
    'url'            => 'http://github.com/Whiteknight/mediawiki-bookdesigner',
    'description'    => 'Design and create books using a graphical outlining tool',
    'descriptionmsg' => 'bookdesigner-desc',
    'version'        => $wg_VBDExtensionVersion
);

$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['BookDesigner']        = $dir . 'BookDesigner_body.php';
$wgAutoloadClasses['BookDesignerOptions'] = $dir . 'BookDesigner.options.php';
$wgAutoloadClasses['BookDesignerParser']  = $dir . 'BookDesigner.parser.php';
$wgAutoloadClasses['BookDesignerPage']    = $dir . 'BookDesigner.page.php';
$wgExtensionMessagesFiles['BookDesigner'] = $dir . 'BookDesigner.i18n.php';
$wgExtensionAliasesFiles['BookDesigner']  = $dir . 'BookDesigner.alias.php';
$wgSpecialPages['BookDesigner']           = 'BookDesigner';

$wgGroupPermissions['sysop']['buildbook'] = true;
