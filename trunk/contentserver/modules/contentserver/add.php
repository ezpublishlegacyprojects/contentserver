<?PHP
include_once( 'extension/contentserver/classes/ezcontentserverexport.php' );
$Module =& $Params['Module'];

$Object_id = $Params['id'];
if ( !is_numeric( $id ) )
	$id = 0;

$viewParameters = array( 'offset' => $Offset );

$http =& eZHTTPTool::instance();
include_once( 'kernel/common/template.php' );
$tpl =& templateInit();
$tpl->setVariable( 'view_parameters', $viewParameters );
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
?>