<?php
include_once( 'extension/contentserver/classes/ezcontentserver.php' );
class eZSOAPcontentserver
{
    function eZSOAPcontentserver()
    {
    }
    function getAvialable( $list = array() )
    {
        $ini = eZINI::instance( 'content.ini' );
        if ( $ini->variable( 'ContentServer', 'Server' ) != 'enabled' )
            return new eZSOAPFault( 99, 'Contentserver disabled' );
        $return = eZContentServerExport::remoteIDList();
        if ( !empty( $return ) )
        {
            foreach ( $return as $item )
            {
                $cse = eZContentServerExport::fetch( $item );
                if (  $cse->attribute( 'expires' ) and $cse->attribute( 'expires' ) <= time() )
                {
                    $key = array_search ( $item, $return );
                    unset ( $return[ $key ] );
                    continue;
                }
            }
        }
        
        if ( !empty( $list ) )
        {
            foreach ( $list as $item )
            {
                if ( !isset( $item['id'] ) or !isset( $item['contentobject_version'] ) or !isset( $item['remote_modified'] ) )
                    return new eZSOAPFault( 98, "Bad client request: " . print_r( $item, true ) ); 
                $cse = eZContentServerExport::fetch( $item['id'] );
                
                if ( is_object( $cse ) )
                {
                    $obj =& $cse->object();

                    if ( !is_object( $obj ) )
                    {
                        $cse->remove();
                        $key = array_search ( $item['id'], $return );
                        unset ( $return[ $key ] );
                        continue;
                    }
                    if ( $obj->attribute( 'status' ) != EZ_CONTENT_OBJECT_STATUS_PUBLISHED )
                    {
                        $key = array_search ( $item['id'], $return );
                        unset ( $return[ $key ] );
                        continue;
                    }
                    if ( $cse->Type = EZ_CONTENTSERVER_TYPE_NODE and $obj->attribute( 'current_version' ) <= $item['contentobject_version'] 
                         and $cse->attribute( 'modified' ) <= $item['remote_modified'] )
                    {
                        $key = array_search ( $item['id'], $return );
                        unset ( $return[ $key ] );
                        continue;
                    }
                    if ( $cse->Type = EZ_CONTENTSERVER_TYPE_SUBTREE and $obj->attribute( 'current_version' ) <= $item['contentobject_version'] 
                         and $cse->attribute( 'modified_subtree' ) <= $item['remote_modified']
                         )
                    {
                        $key = array_search ( $item['id'], $return );
                        unset ( $return[ $key ] );
                        continue;
                    }
                }
            }
        }
        return $return;
    }
    /*
    delivers one object at a time and if no further objects avialable it returns false.
    */
    function getPackage ( $remote_id, $includeclass = true )
    {
        include_once( 'kernel/classes/ezcontentobject.php' );
        include_once( 'kernel/classes/ezpackage.php' );
        include_once( 'kernel/classes/ezpackagecreationhandler.php' );

        $cse = eZContentServerExport::fetch( $remote_id );

        if ( !is_object( $cse ) )
            return new eZSOAPFault( 100, 'No such object' );
        $co =& $cse->object();
        if  ( !is_object( $co ) )
        {
            $cse->remove();
            return new eZSOAPFault( 101, 'No such remote id.' );
        }
        $node =& $co->mainNode();    
        if  ( !is_object( $node ) )
        {
            return new eZSOAPFault( 102, 'Object has no main node. It might be deleted.' );
        }  
        $creator =& eZPackageCreationHandler::instance( EZ_CONTENTSERVER_PACKAGENAME );
        $package = null;

        $persistentData =array( 
        'name' => 'object-'.$co->attribute('remote_id'),
        'object_options' => array(
            'include_classes' => $includeclass,
            'versions'        => 'current',
            'language_array'  => eZContentLanguage::prioritizedLanguageCodes(),
            'node_assignment' => 'selected',
            'related_objects' => 'selected'
        
                                )
        );
        $creator->createPackage( $package, $http, $persistentData, $cleanupFiles );

        $objectHandler = eZPackage::packageHandler( EZ_CONTENTSERVER_PACKAGENAME );
        $nodeList = $persistentData['node_list'];

        $objectHandler->addNode( $node->attribute('node_id') , 'subtree' );
        
        $objectHandler->generatePackage( $package, $persistentData['object_options'] );

        $package->setAttribute( 'is_active', true );
        $package->store();
        $exportDirectory = eZPackage::temporaryExportPath();
        $exportName = $package->exportName();
        $exportPath = $exportDirectory . '/' . $exportName;
        $exportPath = $package->exportToArchive( $exportPath );

        $fileName = $exportPath;
        $result['version'] = $co->attribute( 'current_version' );
        
        $nodes =& $co->attribute( 'parent_nodes' );
        $main_node =& $co->attribute( 'main_node' );

        foreach ( $nodes as $nodeID )
        {
            $node = eZContentObjectTreeNode::fetch( $nodeID );
            $node_array = $node->attribute( 'path_array' );
            $result['data'][] = $node->attribute( 'url_alias' );
            $result['placement']['path_name'][] = $node->attribute( 'url_alias' );
            $ParentObj =& $node->attribute( 'object' );
            $result['placement']['remote_id'][] = $ParentObj->attribute( 'remote_id' );
        }
        $ini =& eZINI::instance( 'site.ini' );
        $result['host'] = $ini->variable( 'SiteSettings', 'SiteURL' );
        if ( $cse->attribute( 'expires' ) )
          $result['expires'] = $cse->attribute( 'expires' );
        else
          $result['expires'] = null;
        if ( $cse->attribute( 'updateflag' ) )
          $result['updateflag'] = $cse->attribute( 'updateflag' );
        else
          $result['updateflag'] = null;
        if ( $cse->attribute( 'modified' ) and $cse->Type == EZ_CONTENTSERVER_TYPE_NODE )
          $result['modified'] = $cse->attribute( 'modified' );
        elseif ( $cse->attribute( 'modified_subtree' ) and $cse->Type == EZ_CONTENTSERVER_TYPE_SUBTREE )
        {
            if ( $cse->attribute( 'modified_subtree' ) > $cse->attribute( 'modified' ) )
                $result['modified'] = $cse->attribute( 'modified_subtree' );
            else 
                $result['modified'] = $cse->attribute( 'modified' );
        }
        else
          $result['modified'] = null;

        $result['type'] = $cse->attribute( 'type' );

        $result['section_id'] = $co->attribute( 'section_id' );
        $result['remote_id'] = $remote_id;
        $result['filename'] = $exportName;
        $result['packagename'] = $package->attribute('name');
        $result['package'] = base64_encode( eZFile::getContents( $fileName ) );
        $package->remove();
        return $result;
    }
}
?>
