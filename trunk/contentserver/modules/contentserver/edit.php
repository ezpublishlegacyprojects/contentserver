<?PHP
include_once( 'extension/contentserver/classes/ezcontentserver.php' );
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

$tpl->setVariable( 'RedirectURI', $RedirectURI );

$ini =& eZINI::instance( "content.ini" );

if ( in_array( $obj->attribute( 'class_identifier' ), $ini->variable( "ContentServer" , "NodeExportClassList" ) ) )
{
    $Type = EZ_CONTENTSERVER_TYPE_NODE;
}
elseif( in_array( $obj->attribute( 'class_identifier' ), $ini->variable( "ContentServer" , "SubtreeExportClassList" ) ) )
{
    $Type = EZ_CONTENTSERVER_TYPE_SUBTREE;
}
else
{
    $Type = EZ_CONTENTSERVER_TYPE_NODE;
}
$cse = eZContentServerExport::fetch( $Params['id'] );
$tpl->setVariable( 'cse', $cse );
    
$errors = array();

if ( $Module->actionParameter( 'Expiry' ) )
{
    
    $date = new eZDateTime();
    $datenow = new eZDateTime();
    $date->setMDY( $Module->actionParameter( 'Month' ), $Module->actionParameter( 'Day' ), $Module->actionParameter( 'Year' ) );
    if ( !$date->isValid() or !$date->isGreaterThan( $datenow ) )
    {
        $errors['time'] = true;
        $date = null;
    }
}



if ( $Module->hasActionParameter( 'Type' ) )
    $tpl->setVariable( 'Type', $Module->actionParameter( 'Type' ) );
else
    $tpl->setVariable( 'Type', $Type );

if ( $Module->hasActionParameter( 'Expiry' ) )
{
    $tpl->setVariable( 'Expiry', $Module->actionParameter( 'Expiry' ) );
}
else
{
    if ( is_object( $cse ) and $cse->attribute( 'expires' ) > 0 )
        $tpl->setVariable( 'Expiry', 1 );
    else
        $tpl->setVariable( 'Expiry', 0 );
}

if ( is_object( $cse ) and $cse->attribute( 'expires' ) > 0 and !$Module->isCurrentAction( 'Store' ) )
{
    $date = new eZDateTime( $cse->attribute( 'expires' ) );
    $tpl->setVariable( 'Year', $date->year() );
    $tpl->setVariable( 'Month', $date->month() );
    $tpl->setVariable( 'Day', $date->day() );
}
else
{
    $tpl->setVariable( 'Year', $Module->actionParameter( 'Year' ) );
    $tpl->setVariable( 'Month', $Module->actionParameter( 'Month' ) );
    $tpl->setVariable( 'Day', $Module->actionParameter( 'Day' ) );
}
if ( is_object( $cse ) and !$Module->isCurrentAction( 'Store' ) )
{
    $tpl->setVariable( 'Update', $cse->attribute( 'updateflag' ) );
}
else
{
    $tpl->setVariable( 'Update', $Module->actionParameter( 'Update' ) );
}




$tpl->setVariable( 'errors', $errors );
if ( $Module->isCurrentAction( 'Store' ) and count( $errors ) == 0 )
{
    $cse =& eZContentServerExport::fetch( $Params['id'] );

    if ( !is_object( $cse ) )
    {
    
        if ( is_object( $date ) )
            $cse = eZContentServerExport::create( $Params['id'], $Module->actionParameter( 'Type' ), $Module->actionParameter( 'Update' ), $date->timeStamp() );
        else
            $cse = eZContentServerExport::create( $Params['id'], $Module->actionParameter( 'Type' ), $Module->actionParameter( 'Update' ) );
    }
    else
    {
        if ( is_object( $date ) )
            $cse->setAttribute( 'expires', $date->timeStamp() );
        else
        {
            $cse->setAttribute( 'expires', 0 );
        }
        if ( $Module->hasActionParameter( 'Update' ) )
            $cse->setAttribute( 'updateflag', $Module->actionParameter( 'Update' ) );
        #type can't get changed
    }    
    $cse->store();
    
    include_once( 'kernel/classes/ezcontentcachemanager.php' );
    #eZContentCacheManager::clearObjectViewCache( $obj->attribute( 'id' ) );
    eZContentCacheManager::clearAllContentCache();
    
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
$Result['content'] =& $tpl->fetch( "design:contentserver/edit.tpl" );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'contentserver', 'Contentserver' ) ),
                         array( 'url' => false,
                                'text' => ezi18n( 'contentserver', 'Export' ) ) );

                                

/*
if ( $Params['type'] == 'subtree' )
{
	$NodeIDArray = addNode( $Params['id'], true );
}
else
{
	$NodeIDArray = addNode( $Params['id'], false );	
}
foreach ( $NodeIDArray as $node_id)
{
	$node =& eZContentObjectTreeNode::fetch( $node_id );
	$co =& eZContentObject::fetch( $node->attribute( 'contentobject_id' ) );
	if ( !eZContentServerExport::fetch( $co->attribute('remote_id') ) )
	{
		$export =  new eZContentServerExport( array( 'id' => $co->attribute('remote_id') ) );#, 'path' => $node->pathWithNames()
		$export->store();
		include_once( 'kernel/classes/ezcontentcache.php' );
		eZContentCache::cleanup( array( $node_id, $node->attribute( 'parent_node_id' ) ) );
	}
}

$Module->redirectTo( '/'.trim( $http->getVariable( 'RedirectURI' ) ) );

                        
function addNode( $nodeID, $isSubtree = true )
{
	include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
	$NodeIDArray[] = $nodeID;

	if ( $isSubtree )
	{
		$nodeArray =& eZContentObjectTreeNode::subtree( array( 'AsObject' => false ), $nodeID );
		foreach( $nodeArray as $node )
		{
			$NodeIDArray[] = $node['node_id'];
		}
	}
	return $NodeIDArray;
}
*/
?>
