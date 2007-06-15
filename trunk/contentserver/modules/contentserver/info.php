<?PHP
include_once( 'extension/contentserver/classes/ezcontentserverexport.php' );
include_once( "kernel/common/template.php" );
include_once( 'lib/ezutils/classes/ezhttptool.php' );
include_once( 'lib/ezlocale/classes/ezdatetime.php' );


include_once( "lib/ezsoap/classes/ezsoapclient.php" );
include_once( "lib/ezsoap/classes/ezsoaprequest.php" );

$Module =& $Params['Module'];
$tpl =& templateInit();

$http =& eZHTTPTool::instance();

if ( $http->hasGetVariable( 'RedirectURI' ) )
	$RedirectURI = $http->getVariable( 'RedirectURI' );
else 
	$RedirectURI = '';


$obj =& eZContentObject::fetchByRemoteID( $Params['id'] );
if ( !is_object( $obj ) )
{
    eZDebug::writeError('Missing Object with RemoteID '. $Params['id'] ,"Contentserver");
    return $Module->handleError( EZ_ERROR_KERNEL_ACCESS_DENIED, 'kernel' );
}
$node =& eZContentObjectTreeNode::fetch( $obj->attribute( "main_node_id" ) );

$tpl->setVariable( 'node', $node );

$tpl->setVariable( 'ID', $Params['id'] );

$tpl->setVariable( 'RedirectURI', $RedirectURI );

if ( $Module->isCurrentAction( 'Cancel' ) )
{
    if ( $Module->hasActionParameter( 'RedirectURI' ) )
        $Module->redirectTo( $Module->actionParameter( 'RedirectURI' ) );
    else
        $Module->redirectTo( 'content/view/full/2' );
}
$Result = array();
$Result['content'] =& $tpl->fetch( "design:contentserver/info.tpl" );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'contentserver', 'Contentserver' ) ),
                         array( 'url' => false,
                                'text' => ezi18n( 'contentserver', 'Object Info' ) ) );


?>