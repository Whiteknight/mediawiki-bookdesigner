<?php
# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install BookDesigner, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/BookDesigner/BookDesigner.php" );
EOT;
        exit( 1 );
}
 
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'BookDesigner',
	'author' => 'Andrew Whitworth',
	'url' => 'http://www.wittiewiki.org',
	'description' => 'Design and create Books',
	'descriptionmsg' => 'BookDesigner-desc',
	'version' => '0.0.0',
);
 
$dir = dirname(__FILE__) . '/';
 
$wgAutoloadClasses['BookDesigner'] = $dir . 'BookDesigner_body.php'; # Tell MediaWiki to load the extension body.
$wgExtensionMessagesFiles['BookDesigner'] = $dir . 'BookDesigner.i18n.php';
$wgExtensionAliasesFiles['BookDesigner'] = $dir . 'BookDesigner.alias.php';
$wgSpecialPages['BookDesigner'] = 'BookDesigner'; # Let MediaWiki know about your new special page.

