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
     \note Transaction unsafe. If you call several transaction unsafe methods you must enclose
     the calls within a db transaction; thus within db->begin and db->commit.
    */
    function &unserialize( &$domNode, &$contentObject, $ownerID, $sectionID, $activeVersion, $firstVersion, &$nodeList, $options, &$package )
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

        $db =& eZDB::instance();
        $db->begin();
        foreach( array_keys( $languageNodeArray ) as $languageKey )
        {
            $languageNode =& $languageNodeArray[$languageKey];
            $language =& $languageNode->attributeValue( 'language' );

            // unserialize object name in current version-translation
            $objectName =& $languageNode->attributeValue( 'object_name' );
            if ( $objectName )
                $contentObject->setName( $objectName, $contentObjectVersion->attribute( 'version' ), $language );

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
        foreach( $nodeAssignmentNodeArray as $nodeAssignmentNode )
        {
            $result = eZContentObjectTreeNode::unserialize( $nodeAssignmentNode,
                                                  $contentObject,
                                                  $contentObjectVersion->attribute( 'version' ),
                                                  ( $oldVersion == $activeVersion ? 1 : 0 ),
                                                  $nodeList,
                                                  $options );
            if ( $result === false )
                return false;
        }
        $contentObjectVersion->store();
        $db->commit();

        return $contentObjectVersion;
    }
    /*!
     \static
     Unserialize xml structure. Create object from xml input.

     \param package
     \param XML DOM Node
     \param parent node object.
     \param Options
     \param owner ID, override owner ID, null to use XML owner id (optional)

     \returns created object, false if could not create object/xml invalid
     \note Transaction unsafe. If you call several transaction unsafe methods you must enclose
     the calls within a db transaction; thus within db->begin and db->commit.
    */
    function &unserialize( &$package, &$domNode, $options, $ownerID = false )
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
        if ( !$contentObject )
        {
            $contentObject =& $contentClass->instantiate( $ownerID, $sectionID );
        }

        $versionListNode =& $domNode->elementByName( 'version-list' );

        $db =& eZDB::instance();
        $db->begin();

        $contentObject->store();
        $activeVersion = 1;
        $firstVersion = true;
        $versionListActiveVersion = $versionListNode->attributeValue( 'active_version' );

        $versionList = array();
        foreach( $versionListNode->elementsByName( 'version' ) as $versionDOMNode )
        {
            unset( $nodeList );
            $nodeList = array();
            $contentObjectVersion = eZContentObjectVersion::unserialize( $versionDOMNode,
                                                                         $contentObject,
                                                                         $ownerID,
                                                                         $sectionID,
                                                                         $versionListActiveVersion,
                                                                         $firstVersion,
                                                                         $nodeList,
                                                                         $options,
                                                                         $package );

            if ( !$contentObjectVersion )
            {
                eZDebug::writeError( 'Unserialize error', 'eZContentObject::unserialize' );
                return false;
            }

            $versionStatus = $versionDOMNode->attributeValue( 'status' ); // we're really getting value of ezremote:status here
            $versionList[$versionDOMNode->attributeValue( 'version' )] = array( 'node_list' => $nodeList,
                                                                                'status' =>    $versionStatus );
            unset( $versionStatus );

            $firstVersion = false;
            if ( $versionDOMNode->attributeValue( 'version' ) == $versionListActiveVersion )
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

        $versions   =& $contentObject->versions();
        $objectName =& $contentObject->name();
        $objectID   =& $contentObject->attribute( 'id' );
        foreach ( $versions as $version )
        {
            $versionNum       = $version->attribute( 'version' );
            $oldVersionStatus = $version->attribute( 'status' );
            $newVersionStatus = isset( $versionList[$versionNum] ) ? $versionList[$versionNum]['status'] : null;

            // set the correct status for non-published versions
            if ( isset( $newVersionStatus ) && $oldVersionStatus != $newVersionStatus && $newVersionStatus != EZ_VERSION_STATUS_PUBLISHED )
            {
                $version->setAttribute( 'status', $newVersionStatus );
                $version->store( array( 'status' ) );
            }

            // when translation does not have object name set then we copy object name from the current object version
            $translations =& $version->translations( false );
            if ( !$translations )
                continue;
            foreach ( $translations as $translation )
            {
                if ( ! $contentObject->name( $versionNum, $translation ) )
                {
                    eZDebug::writeNotice( "Setting name '$objectName' for version ($versionNum) of the content object ($objectID) in language($translation)" );
                    $contentObject->setName( $objectName, $versionNum, $translation );
                }
            }
        }

        include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
        eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObject->attribute( 'id' ),
                                                                  'version' => $activeVersion ) );

        foreach ( $versionList[$versionListActiveVersion]['node_list'] as $nodeInfo )
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
        $db->commit();

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

     \note Transaction unsafe. If you call several transaction unsafe methods you must enclose
     the calls within a db transaction; thus within db->begin and db->commit.
    */
    function unserialize( $contentNodeDOMNode, $contentObject, $version, $isMain, &$nodeList, $options )
    {
        $parentNodeID = -1;

        $remoteID = $contentNodeDOMNode->attributeValue( 'remote-id' );
        if ( eZContentObjectTreeNode::fetchByRemoteID( $remoteID ) )
        {
            eZDebug::writeError( "Node with remote ID = $remoteID already exists, can't import", "eZContentObjectTreeNode::unserialize" );
            return false;
        }

        $parentNodeRemoteID = $contentNodeDOMNode->attributeValue( 'parent-node-remote-id' );
        if ( $parentNodeRemoteID !== false )
        {
            $parentNode = eZContentObjectTreeNode::fetchByRemoteID( $parentNodeRemoteID );
            $parentNodeID = $parentNode->attribute( 'node_id' );
        }
        else
        {
            if ( isset( $options['top_nodes_map'][$contentNodeDOMNode->attributeValue( 'node-id' )]['new_node_id'] ) )
            {
                $parentNodeID = $options['top_nodes_map'][$contentNodeDOMNode->attributeValue( 'node-id' )]['new_node_id'];
//                 eZDebug::writeNotice( 'Using user specified top node: ' . $parentNodeID,
//                                       'eZContentObjectTreeNode::unserialize()' );
            }
            else if ( isset( $options['top_nodes_map']['*'] ) )
            {
                $parentNodeID = $options['top_nodes_map']['*'];
//                 eZDebug::writeNotice( 'Using user specified top node: ' . $parentNodeID,
//                                       'eZContentObjectTreeNode::unserialize()' );

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
                           'sort_order' => $contentNodeDOMNode->attributeValue( 'sort-order' ) );
        $existNodeAssignment = eZPersistentObject::fetchObject( eZNodeAssignment::definition(),
                                                   null,
                                                   $nodeInfo );
        $nodeInfo['priority'] = $contentNodeDOMNode->attributeValue( 'priority' );
        if( !is_object( $existNodeAssignment ) )
        {
            $nodeAssignment =& eZNodeAssignment::create( $nodeInfo );
            $nodeList[] = $nodeInfo;
            $nodeAssignment->store();
        }

        return true;
    }
}
?>