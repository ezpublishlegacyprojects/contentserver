<?php
class eZContentServerToolKit
{
    function eZContentServerToolKit ()
    {
        
    }
    /*!
     \static
     Unserialize xml structure. Create object from xml input.

     \param XML DOM Node
     \param contentobject.
     \param owner ID
     \param section ID
     \param new object, true if first version of new object
     \param options
     \param package

     \returns created object, false if could not create object/xml invalid
    */
    function &unserialize_eZContentObjectVersion( &$domNode, &$contentObject, $ownerID, $sectionID, $activeVersion, $firstVersion, &$nodeList, $options, &$package )
    {
        $oldVersion =& $domNode->attributeValue( 'version' );
        $status =& $domNode->attributeValue( 'status' );

        if ( $firstVersion )
        {
            $contentObjectVersion = $contentObject->version( 1 );
        }
        else
        {
            $contentObjectVersion =& $contentObject->createNewVersion();
        }
        if ( !$contentObject )
        {
            eZDebug::writeError( 'Could not fetch object version : ' . $oldVersion,
                                 'eZContentObjectVersion::unserialize()' );
            return false;
        }

        if ( !isset( $options['restore_dates'] ) or $options['restore_dates'] )
        {
            include_once( 'lib/ezlocale/classes/ezdateutils.php' );
            $created = eZDateUtils::textToDate( $domNode->attributeValue( 'created' ) );
            $modified = eZDateUtils::textToDate( $domNode->attributeValue( 'modified' ) );
            $contentObjectVersion->setAttribute( 'created', $created );
            $contentObjectVersion->setAttribute( 'modified', $modified );
        }
        $contentObjectVersion->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
        $contentObjectVersion->store();

        $languageNodeArray =& $domNode->elementsByName( 'object-translation' );
        foreach( array_keys( $languageNodeArray ) as $languageKey )
        {
            $languageNode =& $languageNodeArray[$languageKey];
            $language =& $languageNode->attributeValue( 'language' );

            $attributeArray =& $contentObjectVersion->contentObjectAttributes( $language );
            $attributeNodeArray =& $languageNode->elementsByName( 'attribute' );
            foreach( array_keys( $attributeArray ) as $attributeKey )
            {
                $attribute =& $attributeArray[$attributeKey];

                $attributeIdentifier =& $attribute->attribute( 'contentclass_attribute_identifier' );

                $attributeDomNode =& $languageNode->elementByAttributeValue( 'identifier', $attributeIdentifier );
                if ( !$attributeDomNode )
                {
                    continue;
                }

                $attribute->unserialize( $package, $attributeDomNode );
                $attribute->store();
            }
        }

        $nodeAssignmentNodeList = $domNode->elementByName( 'node-assignment-list' );
        $nodeAssignmentNodeArray = $nodeAssignmentNodeList->elementsByName( 'node-assignment' );
        if ( ! array_key_exists( 'assigned_nodes', $options ) )
        {	
        	$mainNodeSet=false;
        	foreach ( $options['auto_assign_array']['path_name'] as $path )
        	{
        		$nodeid=null;
        		$url =& eZURLAlias::fetchBySourceURL( $path );
        		if ( is_object( $url ) )
        			$nodeid =  str_replace( 'content/view/full/', '', $url->attribute( 'destination_url' ) );
        		$node =& eZContentObjectTreeNode::fetch( $nodeid );
				if ( $node )
				{
					if ( !$mainNodeSet )
					{
						$contentObjectVersion->assignToNode( $node->attribute( 'node_id' ), 1, 0, $node->attribute( 'sort_field' ), $node->attribute( 'sort_order' ) );
						$mainNodeSet=true;
					}
					else
					{
						$contentObjectVersion->assignToNode( $node->attribute( 'node_id' ), 0, 0, $node->attribute( 'sort_field' ), $node->attribute( 'sort_order' ) );
					} 
				}
        	}
        	foreach ( $options['auto_assign_array']['remote_id'] as $remote_id )
        	{
                $obj =& eZContentObject::fetchByRemoteID( $remote_id );
                if ( is_object( $obj ) )
        		  $node =& $obj->attribute( 'main_node' );
				if ( $node )
				{
					if ( !$mainNodeSet )
					{
						$contentObjectVersion->assignToNode( $node->attribute( 'node_id' ), 1, 0, $node->attribute( 'sort_field' ), $node->attribute( 'sort_order' ) );
						$mainNodeSet=true;
					}
					else
					{
						$contentObjectVersion->assignToNode( $node->attribute( 'node_id' ), 0, 0, $node->attribute( 'sort_field' ), $node->attribute( 'sort_order' ) );
					} 
				}
        	}
        	foreach( $nodeAssignmentNodeArray as $nodeAssignmentNode )
        	{
            	eZContentServerToolKit::unserialize_eZContentObjectTreeNode( $nodeAssignmentNode,
                                                  $contentObject,
                                                  $contentObjectVersion->attribute( 'version' ),
                                                  ( ( $oldVersion == $activeVersion && !$mainNodeSet ) ? 1 : 0 ),
                                                  $nodeList,
                                                  $options );
        	}
        }
        else
        {
        	if ( is_object( $options['assigned_nodes'][0] ) and $options['assigned_nodes'][0]->attribute( 'contentobject_version' ) != $oldVersion )
        	{
				$list =& $contentObjectVersion->nodeAssignments();
       			foreach( $list as $ass )
       			{
       				$ass->remove();
       			}
				foreach ( $options['assigned_nodes'] as $node )
        		{
        			if ( $node->attribute( 'main_node_id' ) == $node->attribute( 'node_id' ) )
        				$ismain = true;
        			else
        				$ismain = false;
        			$contentObjectVersion->assignToNode( $node->attribute( 'parent_node_id' ), $ismain, 0, $node->attribute( 'sort_field' ), $node->attribute( 'sort_order' ) );
        		}
        	}
        }
        $contentObjectVersion->store();

        return $contentObjectVersion;
    }

    function &unserialize_eZContentObject( &$package, &$domNode, $options, $ownerID = false )
    {
        if ( $domNode->name() != 'object' )
        {
            return false;
        }

        $sectionID =& $domNode->attributeValue( 'section_id' );
        if ( $ownerID === false )
        {
            $ownerID =& $domNode->attributeValue( 'owner_id' );
        }
        $remoteID =& $domNode->attributeValue( 'remote_id' );
        $name =& $domNode->attributeValue( 'name' );
        $classRemoteID =& $domNode->attributeValue( 'class_remote_id' );
        $classIdentifier =& $domNode->attributeValue( 'class_identifier' );

        $contentClass =& eZContentClass::fetchByRemoteID( $classRemoteID );
        if ( !$contentClass )
        {
            $contentClass =& eZContentClass::fetchByIdentifier( $classIdentifier );
        }

        if ( !$contentClass )
        {
            eZDebug::writeError( 'Could not fetch class ' . $classIdentifier . ', remote_id: ' . $classRemoteID, 'eZContentObject::unserialize()' );
            return false;
        }

        $contentObject =& eZContentObject::fetchByRemoteID( $remoteID );
        
        if ( $contentObject )
        {
            $options['assigned_nodes'] =& $contentObject->assignedNodes();
        }
        
       
        if ( !$contentObject )
        {
            $contentObject =& $contentClass->instantiate( $ownerID, $sectionID );
        }

        $versionListNode =& $domNode->elementByName( 'version-list' );
        $contentObject->store();
        $activeVersion = 1;
        $firstVersion = true;

        $versionList = array();
        foreach( $versionListNode->elementsByName( 'version' ) as $versionDOMNode )
        {
            unset( $nodeList );
            $nodeList = array();
            $contentObjectVersion = eZContentServerToolKit::unserialize_eZContentObjectVersion( $versionDOMNode,
                                                                         $contentObject,
                                                                         $ownerID,
                                                                         $sectionID,
                                                                         $versionListNode->attributeValue( 'active_version' ),
                                                                         $firstVersion,
                                                                         $nodeList,
                                                                         $options,
                                                                         $package );
            $versionList[$versionDOMNode->attributeValue( 'version' )] = array( 'node_list' => $nodeList );

            $firstVersion = false;
            if ( $versionDOMNode->attributeValue( 'version' ) == $versionListNode->attributeValue( 'active_version' ) )
            {
                $activeVersion = $contentObjectVersion->attribute( 'version' );
            }
        }

        if ( !isset( $options['restore_dates'] ) or $options['restore_dates'] )
        {
            include_once( 'lib/ezlocale/classes/ezdateutils.php' );
            $modified = eZDateUtils::textToDate( $domNode->attributeValue( 'modified' ) );
            $contentObject->setAttribute( 'modified', $modified );
        }
        $contentObject->setAttribute( 'remote_id', $remoteID );
        $contentObject->setAttribute( 'current_version', $activeVersion );
        $contentObject->setAttribute( 'contentclass_id', $contentClass->attribute( 'id' ) );
        $contentObject->setAttribute( 'name', $name );
        $contentObject->store();

        include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
        eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObject->attribute( 'id' ),
                                                                  'version' => $activeVersion ) );

        foreach ( $versionList[$activeVersion]['node_list'] as $nodeInfo )
        {
            unset( $parentNode );
            $parentNode =& eZContentObjectTreeNode::fetchNode( $contentObject->attribute( 'id' ),
                                                               $nodeInfo['parent_node'] );
            if ( is_object( $parentNode ) )
            {
                $parentNode->setAttribute( 'priority', $nodeInfo['priority'] );
                $parentNode->store( array( 'priority' ) );
            }
        }

        if ( !isset( $options['restore_dates'] ) or $options['restore_dates'] )
        {
            include_once( 'lib/ezlocale/classes/ezdateutils.php' );
            $published = eZDateUtils::textToDate( $domNode->attributeValue( 'published' ) );
            $contentObject =& eZContentObject::fetch( $contentObject->attribute( 'id' ) );
            $contentObject->setAttribute( 'published', $published );
            $contentObject->store( array( 'published' ) );
        }
        return $contentObject;
    }

    /*!
     \static
     Creates propper nodeassigment from contentNodeDOMNode specification

     \param contentobjecttreenode DOMNode
     \param contentobject.
     \param version
     \param isMain
     \param options
    */
    function unserialize_eZContentObjectTreeNode( $contentNodeDOMNode, $contentObject, $version, $isMain, &$nodeList, $options )
    {
        $parentNodeID = -1;
        if ( $contentNodeDOMNode->attributeValue( 'parent-node-remote-id' ) !== false )
        {
            $parentNode = eZContentObjectTreeNode::fetchByRemoteID( $contentNodeDOMNode->attributeValue( 'parent-node-remote-id' ) );
            $parentNodeID = $parentNode->attribute( 'node_id' );
        }
        else
        {
            if ( isset( $options['top_nodes_map'][$contentNodeDOMNode->attributeValue( 'node-id' )]['new_node_id'] ) )
            {
                $parentNodeID = $options['top_nodes_map'][$contentNodeDOMNode->attributeValue( 'node-id' )]['new_node_id'];
                eZDebug::writeNotice( 'Using user specified top node: ' . $parentNodeID,
                                      'eZContentObjectTreeNode::unserialize()' );
            }
            else if ( isset( $options['top_nodes_map']['*'] ) )
            {
                $parentNodeID = $options['top_nodes_map']['*'];
                eZDebug::writeNotice( 'Using user specified top node: ' . $parentNodeID,
                                      'eZContentObjectTreeNode::unserialize()' );

            }
            else
            {
                eZDebug::writeError( 'New parent node not set ' . $contentNodeDOMNode->attributeValue( 'name' ),
                                     'eZContentObjectTreeNode::unserialize()' );
            }
        }

        $nodeInfo = array( 'contentobject_id' => $contentObject->attribute( 'id' ),
                           'contentobject_version' => $version,
                           'is_main' => $isMain,
                           'parent_node' => $parentNodeID,
                           'parent_remote_id' => $contentNodeDOMNode->attributeValue( 'remote-id' ),
                           'sort_field' => eZContentObjectTreeNode::sortFieldID( $contentNodeDOMNode->attributeValue( 'sort-field' ) ),
                           'sort_order' => $contentNodeDOMNode->attributeValue( 'sort-order' )
                            );
       
        $object = eZPersistentObject::fetchObject( eZNodeAssignment::definition(), false, $nodeInfo ); 
        if( !is_object( $object ) )
        {
        	$nodeAssignment =& eZNodeAssignment::create( $nodeInfo );
        	$nodeList[] = $nodeInfo;
        	$nodeAssignment->store();
        }
    }
}
?>