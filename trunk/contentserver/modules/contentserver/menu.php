<?php
include_once( "kernel/common/template.php" );
include_once( 'lib/ezutils/classes/ezhttptool.php' );
include_once( 'extension/contentserver/classes/ezcontentserver.php' );
$tpl =& templateInit();
$Module =& $Params['Module'];
$ini = eZINI::instance("content.ini");

$Result = array();
if ( $ini->variable('ContentServer','Client') == 'enabled' )
    $Module->redirectTo( '/content/view/full/' . eZContentServer::incomingNodeID() );
if ( $ini->variable('ContentServer','Server') == 'enabled' )
    $Module->redirectTo( '/contentserver/viewexports' );
else
    $Result['content'] =& $tpl->fetch( "design:contentserver/server.tpl" );

$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'design/contentserver', 'Content Server' ) ) );
?>