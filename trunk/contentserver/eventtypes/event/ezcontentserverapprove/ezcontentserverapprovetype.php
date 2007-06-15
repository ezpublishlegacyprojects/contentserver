<?php
//
// Definition of eZcontentserverapproveType class
//
// Created on: <16-Apr-2002 11:08:14 amos>
//
// Copyright (C) 1999-2004 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE included in
// the packaging of this file.
//
// Licencees holding a valid "eZ publish professional licence" version 2
// may use this file in accordance with the "eZ publish professional licence"
// version 2 Agreement provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" version 2 is available at
// http://ez.no/ez_publish/licences/professional/ and in the file
// PROFESSIONAL_LICENCE included in the packaging of this file.
// For pricing of this licence please contact us via e-mail to licence@ez.no.
// Further contact information is available at http://ez.no/company/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

/*!
\class eZContentServerApproveType ezcontentserverapprovetype.php
\brief Event type for user approvals

*/

include_once( "extension/contentserver/classes/ezcontentserverimport.php" );
include_once( "kernel/classes/ezworkflowtype.php" );
include_once( 'kernel/classes/collaborationhandlers/ezapprove/ezapprovecollaborationhandler.php' );

define( "EZ_WORKFLOW_TYPE_CONTENTSERVERAPPROVE_ID", "ezcontentserverapprove" );

define( "EZ_CONTENTSERVERAPPROVE_COLLABORATION_NOT_CREATED", 0 );
define( "EZ_CONTENTSERVERAPPROVE_COLLABORATION_CREATED", 1 );

class eZContentServerApproveType extends eZWorkflowEventType
{
	function eZContentServerApproveType()
	{
		$this->eZWorkflowEventType( EZ_WORKFLOW_TYPE_CONTENTSERVERAPPROVE_ID, ezi18n( 'kernel/workflow/event', "Content Server Approve" ) );
		$this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'before' ) ) ) );
	}

	function &attributeDecoder( &$event, $attr )
	{
		switch ( $attr )
		{
			case 'selected_sections':
			{
				$sections = explode( ',', $event->attribute( 'data_text1' ) );
				return $sections;
			}break;
			case 'selected_users':
			{
				$users =  array( $event->attribute( 'data_int1' ) );
				return $users;
			}break;
			case 'selected_usergroups':
			{
				$groups = explode( ',', $event->attribute( 'data_text2' ) );
				return $groups;
			}
		}
		return null;
	}

	function typeFunctionalAttributes( )
	{
		return array( 'selected_sections',
		'selected_users',
		'selected_usergroups' );
	}

	function &attribute( $attr )
	{
		switch( $attr )
		{
			case 'sections':
			{
				include_once( 'kernel/classes/ezsection.php' );
				$sections =& eZSection::fetchList( false );
				foreach ( array_keys( $sections ) as $key )
				{
					$section =& $sections[$key];
					$section['Name'] = $section['name'];
					$section['value'] = $section['id'];
				}
				return $sections;
			}break;
			case 'users':
			{
				include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
				$users =& eZUser::fetchContentList();
				$userList = array();
				foreach ( array_keys( $users ) as $key )
				{
					$user =& $users[$key];
					$user['Name'] = $user['name'];
					$user['value'] = $user['id'];
					$userList[] = $user;
				}
				return $userList;
			}break;
			case 'usergroups':
			{
				$ini =& eZINI::instance();
				$classID = $ini->variable( 'UserSettings', 'UserGroupClassID' );
				$groups =& eZPersistentObject::fetchObjectList( eZContentObject::definition(), array( 'id', 'name' ), array( 'contentclass_id' => $classID ), null, null, false );
				foreach ( array_keys( $groups ) as $key )
				{
					$group =& $groups[$key];
					$group['Name'] = $group['name'];
					$group['value'] = $group['id'];
				}
				return $groups;
			}
		}
		return eZWorkflowEventType::attribute( $attr );
	}
	function hasAttribute( $attr )
	{
		return in_array( $attr, array( 'sections',
		'users',
		'usergroups' ) ) || eZWorkflowEventType::hasAttribute( $attr );
	}

	function execute( &$process, &$event )
	{
		eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $process, 'eZContentServerApproveType::execute' );
		eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $event, 'eZContentServerApproveType::execute' );
		$parameters = $process->attribute( 'parameter_list' );
		$versionID =& $parameters['version'];
		$object =& eZContentObject::fetch( $parameters['object_id'] );

		if ( is_object( $object ) )
			$csi = eZContentServerImport::fetch( $object->attribute( 'remote_id' ) );
		else
			return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
		if ( is_object( $csi ) and $csi->attribute( 'status' ) == EZ_CONTENTSERVERIMPORT_STATUS_CONFIRMED )
		{
			eZDebugSetting::writeNotice( 'kernel-workflow-contentserverapprove', 'Object has been imported before.', 'eZContentServerApproveType::execute::ContentServerImport' );
			return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
		}
		if ( !is_object( $csi ) )
		{
			return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
		}
		/*
		If we run event first time ( when we click publish in admin ) we do not have user_id set in workflow process,
		so we take current user and store it in workflow process, so next time when we run event from cronjob we fetch
		'         user_id from there.
		*/
		if ( $process->attribute( 'user_id' ) == 0 )
		{
			$user =& eZUser::currentUser();
			$process->setAttribute( 'user_id', $user->id() );
		}
		else
		{
			$user =& eZUser::instance( $process->attribute( 'user_id' ) );
		}

		$userGroups = $user->attribute( 'groups' );
		$workflowSections = explode( ',', $event->attribute( 'data_text1' ) );
		$workflowGroups = explode( ',', $event->attribute( 'data_text2' ) );
		$editor = $event->attribute( 'data_int1' );

		eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $user, 'eZContentServerApproveType::execute::user' );
		eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $userGroups, 'eZContentServerApproveType::execute::userGroups' );
		eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $editor, 'eZContentServerApproveType::execute::editor' );
		eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $workflowSections, 'eZContentServerApproveType::execute::workflowSections' );
		eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $workflowGroups, 'eZContentServerApproveType::execute::workflowGroups' );
		eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $object->attribute( 'section_id'), 'eZContentServerApproveType::execute::section_id' );

		$correctSection = true;
		if ( !in_array( -1, $workflowSections ) )
		$correctSection = in_array( $object->attribute( 'section_id'), $workflowSections );

		$inExcludeGroups = count( array_intersect( $userGroups, $workflowGroups ) ) != 0;

		$userIsEditor = $user->id() == $editor;

		if ( !$userIsEditor and
		!$inExcludeGroups and
		$correctSection )
		{

			$collaborationID = false;
			$db = & eZDb::instance();
			$taskResult = $db->arrayQuery( 'select workflow_process_id, collaboration_id from ezapprove_items where workflow_process_id = ' . $process->attribute( 'id' )  );
			if ( count( $taskResult ) > 0 )
			$collaborationID = $taskResult[0]['collaboration_id'];
			//             if( $process->attribute( 'event_state') == EZ_APPROVE_COLLABORATION_NOT_CREATED )
			eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $collaborationID, 'approve collaborationID' );
			eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $process->attribute( 'event_state'), 'approve $process->attribute( \'event_state\')' );
			if ( $collaborationID === false )
			{
				$this->createApproveCollaboration( $process, $event, $user->id(), $object->attribute( 'id' ), $versionID, $editor );
				$this->setInformation( "We are going to create approval" );
				$process->setAttribute( 'event_state', EZ_APPROVE_COLLABORATION_CREATED );
				$process->store();
				eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $this, 'approve execute' );
				return EZ_WORKFLOW_TYPE_STATUS_DEFERRED_TO_CRON_REPEAT;
			}
			else if ( $process->attribute( 'event_state') == EZ_APPROVE_COLLABORATION_NOT_CREATED )
			{
				eZApproveCollaborationHandler::activateApproval( $collaborationID );
				$process->setAttribute( 'event_state', EZ_APPROVE_COLLABORATION_CREATED );
				$process->store();
				eZDebugSetting::writeDebug( 'kernel-workflow-approve', $this, 'approve re-execute' );
				return EZ_WORKFLOW_TYPE_STATUS_DEFERRED_TO_CRON_REPEAT;
			}
			//             else if ( $process->attribute( 'event_state') == EZ_APPROVE_COLLABORATION_CREATED )
			else
			{
				$this->setInformation( "we are checking approval now" );
				eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $event, 'check approval' );
				return $this->checkApproveCollaboration(  $process, $event );
			}
		}
		else
		{
			eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $workflowSections , "we are not going to create approval " . $object->attribute( 'section_id') );
			eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $userGroups, "we are not going to create approval" );
			eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $workflowGroups,  "we are not going to create approval" );
			eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $user->id(), "we are not going to create approval $editor "  );
			return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
		}
	}

	function initializeEvent( &$event )
	{
	}

	function fetchHTTPInput( &$http, $base, &$event )
	{
		$editorVar = $base . "_event_ezcontentserverapprove_editor_" . $event->attribute( "id" );
		if ( $http->hasPostVariable( $editorVar ) )
		{
			$editorID = $http->postVariable( $editorVar );
			$editorID = $editorID[0];
			$event->setAttribute( "data_int1", $editorID );
		}
		$userGroupsVar = $base . "_event_ezcontentserverapprove_groups_" . $event->attribute( "id" );
		if ( $http->hasPostVariable( $userGroupsVar ) )
		{
			$userGroupsArray = $http->postVariable( $userGroupsVar );
			if ( in_array( '-1', $userGroupsArray ) )
			{
				$userGroupsArray = array( -1 );
			}


			$userGroupsString = implode( ',', $userGroupsArray );
			$event->setAttribute( "data_text2", $userGroupsString );
		}

		$sectionsVar = $base . "_event_ezcontentserverapprove_section_" . $event->attribute( "id" );
		if ( $http->hasPostVariable( $sectionsVar ) )
		{
			$sectionsArray = $http->postVariable( $sectionsVar );
			if ( in_array( '-1', $sectionsArray ) )
			{
				$sectionsArray = array( -1 );
			}
			$sectionsString = implode( ',', $sectionsArray );
			//            $userGroupList = explode( ',', $userGroupsString );
			$event->setAttribute( "data_text1", $sectionsString );
		}

	}

	function createApproveCollaboration( &$process, &$event, $userID, $contentobjectID, $contentobjectVersion, $editor )
	{
		if ( $editor === null )
		return false;
		$authorID = $userID;
		$collaborationItem =& eZApproveCollaborationHandler::createApproval( $contentobjectID, $contentobjectVersion,
		$authorID, $editor );

		$db = & eZDb::instance();
		$db->query( 'insert into ezapprove_items( workflow_process_id,
                                                      collaboration_id )
                                              values(' . $process->attribute( 'id' ) .','. $collaborationItem->attribute( 'id' ) .' ) '
		);
	}

	function checkApproveCollaboration( &$process, &$event )
	{
		$db = & eZDb::instance();
		$taskResult = $db->arrayQuery( 'select workflow_process_id, collaboration_id from ezapprove_items where workflow_process_id = ' . $process->attribute( 'id' )  );
		$collaborationID = $taskResult[0]['collaboration_id'];
		$collaborationItem =& eZCollaborationItem::fetch( $collaborationID );
		$contentObjectVersion =& eZApproveCollaborationHandler::contentObjectVersion( $collaborationItem );
		$approvalStatus = eZApproveCollaborationHandler::checkApproval( $collaborationID );
		if ( $approvalStatus == EZ_COLLABORATION_APPROVE_STATUS_WAITING )
		{
			eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $event, 'approval still waiting' );
			return EZ_WORKFLOW_TYPE_STATUS_DEFERRED_TO_CRON_REPEAT;
		}
		else if ( $approvalStatus == EZ_COLLABORATION_APPROVE_STATUS_ACCEPTED )
		{
			$object =& eZContentObject::fetch( $contentObjectVersion->attribute( 'contentobject_id' ) );
			$csi = eZContentServerImport::fetch( $object->attribute( 'remote_id' ) );
			$csi->setAttribute( 'status', EZ_CONTENTSERVERIMPORT_STATUS_CONFIRMED );
			$csi->store();
			eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $event, 'approval was accepted' );
			$status = EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
		}
		else if ( $approvalStatus == EZ_COLLABORATION_APPROVE_STATUS_DENIED or
		$approvalStatus == EZ_COLLABORATION_APPROVE_STATUS_DEFERRED )
		{
			eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $event, 'approval was denied' );

			$object =& eZContentObject::fetch( $contentObjectVersion->attribute( 'contentobject_id' ) );

			$csi = eZContentServerImport::fetch( $object->attribute( 'remote_id' ) );
			$csi->setAttribute( 'status', EZ_CONTENTSERVERIMPORT_STATUS_UNCONFIRMED );
			$csi->store();

			$list =& $contentObjectVersion->nodeAssignments();
			foreach( $list as $ass )
			{
				if ( !$ass->attribute( 'is_main' ) )
				$ass->remove();
			}

			$status = EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
		}
		//         else if ( $approvalStatus == EZ_COLLABORATION_APPROVE_STATUS_DEFERRED )
		//         {
		//             eZDebugSetting::writeDebug( 'kernel-workflow-approve', $event, 'approval was deferred' );
		//             $contentObjectVersion->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
		//             $status = EZ_WORKFLOW_TYPE_STATUS_WORKFLOW_RESET;
		//         }
		else
		{
			eZDebugSetting::writeDebug( 'kernel-workflow-contentserverapprove', $event, "approval unknown status '$approvalStatus'" );
			$contentObjectVersion->setAttribute( 'status', EZ_VERSION_STATUS_REJECTED );
			$status = EZ_WORKFLOW_TYPE_STATUS_WORKFLOW_CANCELLED;
		}
		$contentObjectVersion->sync();
		if ( $approvalStatus != EZ_COLLABORATION_APPROVE_STATUS_DEFERRED )
		$db->query( 'DELETE FROM ezapprove_items WHERE workflow_process_id = ' . $process->attribute( 'id' )  );
		return $status;
	}
}

eZWorkflowEventType::registerType( EZ_WORKFLOW_TYPE_CONTENTSERVERAPPROVE_ID, "ezcontentserverapprovetype" );

?>
