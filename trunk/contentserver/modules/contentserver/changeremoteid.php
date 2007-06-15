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

$node =& eZContentObjectTreeNode::fetch( $Params['node_id'] );

if ( !is_object( $node ) )
{
    eZDebug::writeError('Missing Object with NodeID '. $Params['node_id'] ,"Contentserver");
    return $Module->handleError( EZ_ERROR_KERNEL_ACCESS_DENIED, 'kernel' );
}
$nodes=&$node->fetchPath();
array_push( $nodes, $node );

$errors = array();

if ( $Module->hasActionParameter( 'IDArray' ) )
{
    foreach ( $Module->actionParameter( 'IDArray' ) as $oldid => $newid )
    {
        $db =& eZDB::instance();
        $remoteID =$db->escapeString( $newid );
        $resultArray = $db->arrayQuery( 'SELECT id FROM ezcontentobject WHERE remote_id=\'' . $remoteID . '\'' );
        if ( count( $resultArray ) > 1 )
        {
            $errors[] = ezi18n( 'contentserver', "Remote ID '%ID%' already exists for more then one object.", null, array( '%ID%' => $newid ) );
        }
        if ( count( $resultArray ) == 1 and $oldid != $newid )
            $errors[] = ezi18n( 'contentserver', "Remote ID '%ID%' already exists for another object.", null, array( '%ID%' => $newid ) );
    }
}

if ( $Module->isCurrentAction( 'Store' ) and count( $errors ) == 0 )
{
    
    foreach ( $Module->actionParameter( 'IDArray' ) as $oldid => $newid )
    {
        $obj=& eZContentObject::fetchByRemoteID( $oldid );

        if ( is_object( $obj ) and $newid )
        {
            $obj->setAttribute( 'remote_id', trim( $newid ) );
            $obj->store();
        }
    }
    if ( $Module->hasActionParameter( 'RedirectURI' ) )
        $Module->redirectTo( $Module->actionParameter( 'RedirectURI' ) );
    else
        $Module->redirectTo( 'content/view/full/2' );
}
if ( $Module->isCurrentAction( 'Cancel' ) )
{
    if ( $Module->hasActionParameter( 'RedirectURI' ) )
        $Module->redirectTo( $Module->actionParameter( 'RedirectURI' ) );
    else
        $Module->redirectTo( 'content/view/full/2' );
}
$Result = array();
$tpl->setVariable( 'errors', $errors );
$tpl->setVariable( 'node', $node );
$tpl->setVariable( 'node_path', $nodes );
$tpl->setVariable( 'RedirectURI', $RedirectURI );

$Result['content'] =& $tpl->fetch( "design:contentserver/changeremoteid.tpl" );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'contentserver', 'Contentserver' ) ),
                         array( 'url' => false,
                                'text' => ezi18n( 'contentserver', 'Change remote IDs' ) ) );

?>