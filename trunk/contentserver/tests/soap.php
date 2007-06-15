#!/usr/bin/env php
<?php

include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );

$cli =& eZCLI::instance();
$script =& eZScript::instance( array( 'description' => ( "Test cases for the contentserver\n" .
                                                         "./bin/ezcontentcache.php --id=e3a46283bf9b21b46d06fe984f96a32e" ),
                                      'use-session' => false,
                                      'use-modules' => false,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[id:]",
                                "",
                                array( 'id' => ( "Remote content object id." ) ) );
$sys =& eZSys::instance();

$script->initialize();

include_once( 'extension/contentserver/classes/ezsoapcontentserver.php' );
eZSOAPcontentserver::getPackage( $options['id'] );
$script->shutdown( 1 );

?>
