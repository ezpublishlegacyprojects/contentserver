<?PHP
include_once( 'extension/contentserver/classes/ezcontentserver.php' );
include_once( "kernel/common/template.php" );
include_once( 'lib/ezutils/classes/ezhttptool.php' );

$Module =& $Params['Module'];
$tpl =& templateInit();

$http =& eZHTTPTool::instance();

if ( $Module->isCurrentAction( 'Find' ) )
{
    if ( $Module->actionParameter( 'ID' ) )
        $cond = array( 'id' => trim ( $Module->actionParameter( 'ID' ) ) );
    else
        $cond = null;
}
else
{    
    $cond = null;
}
$list =& eZContentServerExport::fetchObjectList(eZContentServerExport::definition(), null, $cond, array(  'created'=> 'desc' ) );

$tpl->setVariable( 'id', $Module->actionParameter( 'ID' ) );
$tpl->setVariable( 'list', $list );
$tpl->setVariable( 'list_count', count( $list ) );
$tpl->setVariable( 'view_parameters', $Params['UserParameters'] );
$Result = array();
$Result['content'] =& $tpl->fetch( "design:contentserver/viewexports.tpl" );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'contentserver', 'Contentserver' ) ),
                         array( 'url' => false,
                                'text' => ezi18n( 'contentserver', 'Exported Objects' ) ) );


?>