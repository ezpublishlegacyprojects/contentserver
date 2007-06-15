<?php

include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );
$cli =& eZCLI::instance();
$script =& eZScript::instance( array( 'description' => ( "Autoprove script\n" .
                                                         "Allows automatic approval of all collaboration items\n" .
                                                         "\n" .
                                                         "./extension/contentserver/bin/autoapprove.php --user=admin" ),
                                      'use-session' => true,
                                      'use-modules' => true,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[user:][clean][clean-all]",
                                "",
                                array( 'user' => 'Username on remote server' ,
                                	   'clean' => 'Clean approved items.',
                                	   'clean-all' => 'Clean all items from all users. Use with great care.' ) );

$script->initialize();

$sys =& eZSys::instance();

include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );

$user = eZUser::fetchByName( $options['user'] );

if ( is_object( $user ) )
{
	$user->loginCurrent();
	$cli->output( "Logged in as '".$options['user']."'" );
}
else
{
	$cli->output( "Not logged in. Please set option user.\n" );
	$script->showHelp();
	$script->shutdown();
}

include_once( 'kernel/classes/ezcollaborationitem.php' );
include_once( 'kernel/classes/ezcollaborationitemhandler.php' );

$list = eZCollaborationItem::fetchList( array( 'as_object' => true,
                                          'offset' => false,
                                          'parent_group_id' => false,
                                          'limit' => false,
                                          'is_active' => true,
                                          'is_read' => null,
                                          'status' => false,
                                          'sort_by' => false ) );
                                          
foreach ( $list as $collaborationItem )
{
	$typeIdentifier = $collaborationItem->attribute( 'type_identifier' );
    $handler =& $collaborationItem->attribute( 'handler' );
    $content = $collaborationItem->content();

	$cli->output( "Content Object ID: ".$content['content_object_id']." Version: ".$content['content_object_version']." Status: ". $content['approval_status'] );
    if ( $content['approval_status'] != EZ_COLLABORATION_APPROVE_STATUS_ACCEPTED )
    {
		$status = EZ_COLLABORATION_APPROVE_STATUS_ACCEPTED;
    	$collaborationItem->setAttribute( 'data_int3', $status );
    	$collaborationItem->setAttribute( 'status', EZ_COLLABORATION_STATUS_INACTIVE );
    	$timestamp = time();
    	$collaborationItem->setAttribute( 'modified', $timestamp );
    	$collaborationItem->setIsActive( false );
    	$collaborationItem->sync();
    }
    $co = eZContentObject::fetch( $content['content_object_id'] );
    if ( !is_object( $co ) )
    {
    	eZCollaborationItem_remove( $collaborationItem->attribute( 'id' ) );
    	$cli->output( "Removing bogus Collaboration item with ID ". $collaborationItem->attribute( 'id' ) );
    }
}
include_once( "kernel/classes/ezworkflowprocess.php" );
$cli->output( "Cleaning up bogus Workflow processes." );
$list = eZWorkflowProcess::fetchList();
foreach( $list as $workflowprocess )
{
	$parameters = $workflowprocess->attribute( 'parameter_list' );
	$object =& eZContentObject::fetch( $parameters['object_id'] );
	if ( !is_object( $object ) )
	{	
		$cli->output( "Removing ID " . $parameters['object_id'] );
		$workflowprocess->remove();
	}
}
require_once( 'cronjobs/workflow.php' );
$list = array();
if ( $options['clean'] )
$list = eZCollaborationItem::fetchList( array( 'as_object' => true,
                                          'offset' => false,
                                          'parent_group_id' => false,
                                          'limit' => false,
                                          'is_active' => null,
                                          'is_read' => null,
                                          'status' => false,
                                          'sort_by' => false ) );
foreach ( $list as $collaborationItem )
{
	$handler =& $collaborationItem->attribute( 'handler' );
	$content = $collaborationItem->content();
	
	if ( $content['approval_status'] == EZ_COLLABORATION_APPROVE_STATUS_ACCEPTED )
	{
		eZCollaborationItem_remove( $collaborationItem->attribute( 'id' ) );
		$cli->output( "Removing Collobaration Item for Content Object ID: ".$content['content_object_id']." Version: ".$content['content_object_version']." Status: ". $content['approval_status'] );
	}
}
if ( $options['clean-all'] )
	eZCollaborationItem::cleanup();

function eZCollaborationItem_remove( $id = false )
{
	if ( is_object( $this ) )
		$id  = $this->attribute( 'id' );
	
	if ( !is_numeric( $id) )
		return false;
		
	$db =& eZDB::instance();
	$db->begin();
	$db->query( "DELETE FROM ezcollab_item WHERE id = " . $id );
	$db->query( "DELETE FROM ezcollab_item_group_link WHERE collaboration_id = " . $id );
	$db->query( "DELETE FROM ezcollab_item_message_link WHERE collaboration_id = " . $id );
	$db->query( "DELETE FROM ezcollab_item_participant_link WHERE collaboration_id = " . $id );
    $db->query( "DELETE FROM ezcollab_item_status WHERE collaboration_id = " . $id );
    #$db->query( "DELETE FROM ezcollab_notification_rule " . $id );
    #$db->query( "DELETE FROM ezcollab_profile" . $id );
    #$db->query( "DELETE FROM ezcollab_simple_message" . $id );
    $db->commit();
}
$script->shutdown();

?>