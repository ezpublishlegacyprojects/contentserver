<?php
include_once( 'lib/ezsoap/classes/ezsoapclient.php' );
include_once( 'lib/ezsoap/classes/ezsoaprequest.php' );
include_once( 'lib/ezfile/classes/ezfile.php' );

include_once( 'kernel/classes/ezpackage.php' );
include_once( 'kernel/classes/ezpackageinstallationhandler.php' );

include_once( 'kernel/classes/ezcontentobject.php' );

include_once( 'extension/contentserver/classes/ezcontentserverimport.php' );
include_once( 'extension/contentserver/classes/ezcontentserverexport.php' );


define( 'EZ_CONTENTSERVER_PACKAGENAME', 'ezcontentserver' );


define( 'EZ_CONTENTSERVER_STATUS_UPDATE', 3 );
define( 'EZ_CONTENTSERVER_STATUS_UPDATE_DISCONTINUED', 4 );
define( 'EZ_CONTENTSERVER_STATUS_INITIAL_IMPORT', 2 );
define( 'EZ_CONTENTSERVER_STATUS_SUCCESS', 1 );
define( 'EZ_CONTENTSERVER_STATUS_FAILURE', 0 );

define( 'EZ_CONTENTSERVER_MATCHTYPE_REMOTE_ID', 'remote_id' );
define( 'EZ_CONTENTSERVER_MATCHTYPE_PATH_NAME', 'path_name' );

define( 'EZ_CONTENTSERVER_OBJECT_STATUS_UNKOWN', 0 );
define( 'EZ_CONTENTSERVER_OBJECT_STATUS_EXPORTED', 1 );
define( 'EZ_CONTENTSERVER_OBJECT_STATUS_IMPORTED', 2 );
define( 'EZ_CONTENTSERVER_OBJECT_STATUS_EXPORTED_AND_IMPORTED', 3 );
define( 'EZ_CONTENTSERVER_OBJECT_STATUS_NOT_AVIALABLE', 4 );
define( 'EZ_CONTENTSERVER_OBJECT_STATUS_AVIALABLE', 5 );

define( 'EZ_CONTENTSERVER_TYPE_NOT_AVIALABLE', 2 );
define( "EZ_CONTENTSERVER_TYPE_SUBTREE", 1 );
define( "EZ_CONTENTSERVER_TYPE_NODE", 0 );

define( "EZ_CONTENTSERVER_UPDATEFLAG_ALWAYS", 0 );
define( "EZ_CONTENTSERVER_UPDATEFLAG_NEVER", 1 );
define( "EZ_CONTENTSERVER_UPDATEFLAG_DISCONTINUED", 2 );

class eZContentServer
{
    function eZContentServer( $parameters )
    {
        // create a new client
        
        if ( !array_key_exists( 'port' ,$parameters) or !is_numeric( $parameters['port'] ) )
            $parameters['port'] = 80;
        $this->client = new eZSOAPClient( $parameters['server'], "/soap/server/contentserver", $parameters['port'] );
        $this->client->setLogin( $parameters['login'] );
        $this->client->setPassword( $parameters['password'] ); 
        $this->serverurl = 'http://'.$parameters['server']."/soap/server/contentserver";
    }
    function incomingNodeID()
    {
    	$node = eZContentObjectTreeNode::fetchByURLPath( 'incoming' );
    	if ( !$node->MainNodeID )
            eZDebug::writeError( 'No incoming node found', 'content server' );
    	return $node->MainNodeID;
    }
    function &fetchObjectInformation ( $id )
    {   
        $obj =& eZContentObject::fetchByRemoteID( $id );

        $cse =& eZContentServerExport::fetch( $id );
        
        $csi =& eZContentServerImport::fetch( $id );

        $ini =& eZINI::instance( 'content.ini' );

        if ( is_object( $obj ) )
            $node =& $obj->attribute( 'main_node' );
        else
            return array( );

        if ( $ini->hasVariable( 'ContentServer', 'NodeExportClassList' ) and in_array( $obj->attribute( 'class_identifier' ), $ini->variable( 'ContentServer', 'NodeExportClassList' ) ) )
            $exporttype = EZ_CONTENTSERVER_TYPE_NODE;
        # We don't want to export empty subtrees, it is most likely that the root
        # of the subtree is not wanted in the export
        elseif ( $ini->hasVariable( 'ContentServer', 'SubtreeExportClassList' ) and is_object( $node ) and in_array( $obj->attribute( 'class_identifier' ), $ini->variable( 'ContentServer', 'SubtreeExportClassList' ) ) and $node->attribute( 'children_count' ) > 0 )
            $exporttype = EZ_CONTENTSERVER_TYPE_SUBTREE;
        else
            $exporttype = EZ_CONTENTSERVER_TYPE_NOT_AVIALABLE;

        if ( is_object( $csi ) )
        {
            if ( $csi->attribute( 'type' ) == EZ_CONTENTSERVER_TYPE_SUBTREE )
                $importtype = EZ_CONTENTSERVER_TYPE_SUBTREE;
            else
                $importtype = EZ_CONTENTSERVER_TYPE_NODE;
        }
        else
        {
            $importtype = EZ_CONTENTSERVER_TYPE_NOT_AVIALABLE;
        }

        if ( is_object( $cse ) and is_object( $csi ) )
            $status = EZ_CONTENTSERVER_OBJECT_STATUS_EXPORTED_AND_IMPORTED;
        elseif ( !is_object( $cse ) and is_object( $csi ) )
            $status = EZ_CONTENTSERVER_OBJECT_STATUS_IMPORTED;
        elseif ( is_object( $cse ) and !is_object( $csi ) )
            $status = EZ_CONTENTSERVER_OBJECT_STATUS_EXPORTED;
        elseif ( !is_object( $cse ) and !is_object( $csi ) and $exporttype === EZ_CONTENTSERVER_TYPE_NOT_AVIALABLE )
            $status = EZ_CONTENTSERVER_OBJECT_STATUS_NOT_AVIALABLE;
        elseif ( !is_object( $cse ) and !is_object( $csi ) and ( $exporttype == EZ_CONTENTSERVER_TYPE_NODE or $exporttype == EZ_CONTENTSERVER_TYPE_SUBTREE ) )
            $status = EZ_CONTENTSERVER_OBJECT_STATUS_AVIALABLE;
        else
            $status = EZ_CONTENTSERVER_OBJECT_STATUS_UNKOWN; 

        return  array( 'cse' => $cse, 
                                         'csi' => $csi,
                                         'import_type' => $importtype,
                                         'export_type' => $exporttype,
                                         'status' => $status 
                                         );
    }
    function &request ( $functionName, $params = array() )
    {
        // create the SOAP request object
        $request = new eZSOAPRequest( $functionName, $this->serverurl );

        foreach ( $params as $key => $value )
        {
            $request->addParameter( $key, $value );
        }
        
        // send the request to the server and fetch the response
        $response = $this->client->send( $request );
        // check if the server returned a fault, if not print out the result
        if ( $response === 0 )
        {
            eZDebug::writeError( "SOAP server not avialable." );
            return false;
        }
        if ( $response->isFault() )
        {
            $this->lastError=$response->faultString() . ' (' . $response->faultCode() . ') ';
            eZDebug::writeNotice( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
        }

        return $response;
        
    }
    function &findNode( $data, $type = EZ_CONTENTSERVER_MATCHTYPE_PATH_NAME )
    {
        $node = null;
        if ( $type == EZ_CONTENTSERVER_MATCHTYPE_PATH_NAME )
        {
            $url =& eZURLAlias::fetchBySourceURL( $data );
            if ( is_object( $url ) )
            {
                $node_id =  str_replace( 'content/view/full/', '', $url->attribute( 'destination_url' ) );
                $node =& eZContentObjectTreeNode::fetch( $node_id );
            }        
        }
        if ( $type == EZ_CONTENTSERVER_MATCHTYPE_REMOTE_ID )
        {
            $obj =& eZContentObject::fetchByRemoteID( $data );
            if ( is_object( $obj ) )
        	   $node =& $obj->attribute( 'main_node' );
        }

        return $node;
    }
    function initializeTopNodes( &$packageNode, &$options )
    {
        $ini =& eZINI::instance( 'content.ini' );
        if ( !isset( $options['top_nodes_map']['*'] ) )
        {
            $options['top_nodes_map']['*'] = eZContentServer::incomingNodeID();
        }
        $topNodeListNode = $packageNode->elementByName( 'top-node-list' );

        foreach ( $topNodeListNode->elementsByName( 'top-node' ) as $topNodeDOMNode )
        {

            foreach( $ini->variable( 'ContentServerSettings', 'MatchRuleOrder' ) as $type )
            {
                
                if ( $type == EZ_CONTENTSERVER_MATCHTYPE_PATH_NAME )
                {
                    $node =& eZContentServer::findNode( $topNodeDOMNode->attributeValue( 'remote-path' ), $type  );
                }
                elseif ( $type == EZ_CONTENTSERVER_MATCHTYPE_REMOTE_ID )
                {
                    $node =& eZContentServer::findNode( $topNodeDOMNode->attributeValue( 'parent-object-remote-id' ), $type  );
                }
                if ( is_object( $node ) )
                    break;
            }
                                                                                       
            if ( is_object( $node ) )
                $options['top_nodes_map'][(string)$topNodeDOMNode->attributeValue( 'node-id' )] = array( 'old_node_id' => $topNodeDOMNode->attributeValue( 'node-id' ),
                                                                                                                'name' => $topNodeDOMNode->textContent(),
                                                                                                                'new_node_id' => $node->attribute( 'node_id' ),
                                                                                                                'new_parent_name' => $node->attribute( 'name' ) );
        }
    }
    //static
    function doPackage( $data )
    {
        eZDebug::writeNotice( ezi18n( 'kernel/package', 'Processing Package %packagename.', false, array( '%packagename' => $data['packageName'] ) ), 'eZContentServer::doPackage' );

        $package =& eZPackage::import( $data['packageFilename'], $data['packageName'], false );

        $ini =& eZINI::instance( 'content.ini' );
        $defaultPlacementNodeID = eZContentServer::incomingNodeID();
        $options['top_nodes_map']['*'] = $defaultPlacementNodeID;
        $options['section_id'] = $data['section_id'];
        $options['remote_id'] = $data['remote_id'];
        $options['auto_assign_array']=$data['placement'];
        $options['non-interactive']=true;

        if ( is_object( $package ) )
        {
            eZDebug::writeNotice( ezi18n( 'extension/contentserver', 'Package %packagename imported.', false, array( '%packagename' => $data['packageName'] ) ) );
            if ( $package->attribute( 'install_type' ) != 'install' )
            {
                eZDebug::writeError( ezi18n( 'extension/contentserver', "Package %packagename exists already can't be installed", false, array( '%packagename' => $packageName ) ) );
                return false;
            }
            else if ( !$package->attribute( 'can_install' ) )
            {
                eZDebug::writeError( ezi18n( 'kernel/package', "User can't install Package %packagename. Insufficent permissions.", false, array( '%packagename' => $packageName ) ) );
                return false;
            }
            else if ( $package->attribute( 'install_type' ) == 'install' )
            {
                

                $package->install( $options );
                
                $package->remove();
                
                eZDebug::writeNotice( ezi18n( 'kernel/package', 'Package %packagename installed.', false, array( '%packagename' => $data['packageName'] ) ) );
                
                return true;
            }
        }
        else if ( $package == EZ_PACKAGE_STATUS_ALREADY_EXISTS )
        {
            eZDebug::writeNotice( ezi18n( 'kernel/package', 'Package %packagename already exists, cannot import the package. Deleteing...', false, array( '%packagename' => $packageName ) ) );
            $package = eZPackage::fetch( $data['packageName'] );
            $package->remove();
            eZContentServer::doPackage( $data );
            return false;
        }
        else
        {
            eZDebug::writeError( "Uploaded file is not an eZ publish package", 'eZContentServer::doPackage()' );
            return false;
        }
    }
    function getAvialable()
    {
        $list = eZContentServerImport::remoteIDWithVersionList();
        if ( $list )
        {
            for ( $i=0; $i < count($list); $i++ )    
            {
                $csi =& eZContentServerImport::fetch( $list[$i]['id'] );
                $obj =& $csi->attribute( 'object' );

                if ( $csi->attribute( 'updateflag' ) == EZ_CONTENTSERVER_UPDATEFLAG_DISCONTINUED
                     or $csi->attribute( 'updateflag' ) == EZ_CONTENTSERVER_UPDATEFLAG_NEVER )
                {
                    array_splice( $list, $i, 1);
                    $i = $i-1;
                    continue;
                }

                if ( !is_object( $obj ) and $csi->attribute( 'updateflag' ) != EZ_CONTENTSERVER_UPDATEFLAG_DISCONTINUED )
                {
                    $csi = eZContentServerImport::fetch( $list[$i]['id'] );
                    $csi->remove();
                    array_splice( $list, $i, 1);
                    $i = $i-1;
                    continue;
                }

                if ( $csi->attribute( 'expires' ) < time() and $csi->attribute( 'expires' ) > 0 and $csi->attribute( 'updateflag' ) != EZ_CONTENTSERVER_UPDATEFLAG_DISCONTINUED )
                {
                    $csi->expire();
                    array_splice( $list, $i, 1);
                    $i = $i-1;
                    continue;
                }
            }
        }
        // send the request to the server and fetch the response
        return $this->request( "eZSOAPcontentserver::getAvialable", array( 'list' => $list ) );

    }
    //static
    function processPackage( $remote_id )
    {

        $csi =& eZContentServerImport::fetch( $remote_id );
        
        if ( is_object( $csi ) )
        {
            if ( !$csi->canUpdate() )
            {
   		return EZ_CONTENTSERVER_STATUS_UPDATE_DISCONTINUED;
	    }
	    $obj =& $csi->attribute( "object" );
            if ( is_object( $obj ) and $obj->attribute( 'status' ) == EZ_CONTENT_OBJECT_STATUS_ARCHIVED )
                return EZ_CONTENTSERVER_STATUS_UPDATE_DISCONTINUED;
        }
	
        $result = $this->importPackage( $remote_id );

        if ( $result['status'] == EZ_CONTENTSERVER_STATUS_SUCCESS )
        {

            if( !is_object( $csi ) )
            {
                $csi =& eZContentServerImport::create( $remote_id, $result['content']['version'] );
                $csi->setAttribute( 'remote_host', $result['content']['host'] );
                $csi->setAttribute( 'remote_modified', $result['content']['modified'] );
                $csi->setAttribute( 'updateflag', $result['content']['updateflag'] );
                $csi->setAttribute( 'type', $result['content']['type'] );
                $csi->setAttribute( 'expires', $result['content']['expires'] );                
                $csi->setAttributeArrayToXML( 'data', $result['content']['data'] );
                $csi->store();
                return EZ_CONTENTSERVER_STATUS_INITIAL_IMPORT;
            }
            else
            {
                $csi->setAttribute( 'last_processed', time() );
                
                $csi->setAttribute( 'contentobject_version', $result['content']['version'] );
                $csi->setAttribute( 'remote_host', $result['content']['host'] );
                $csi->setAttribute( 'remote_modified', $result['content']['modified'] );
                $csi->setAttribute( 'updateflag', $result['content']['updateflag'] );
                $csi->setAttribute( 'type', $result['content']['type'] );                
                $csi->setAttribute( 'expires', $result['content']['expires'] );
                $csi->setAttributeArrayToXML( 'data', $result['content']['data'] );
                $csi->store();
                return EZ_CONTENTSERVER_STATUS_UPDATE;
            }
        }
        else if( $result['status'] == EZ_CONTENTSERVER_STATUS_FAILURE )
        {
            if ( is_object( $csi ) )
            {
                $csi->setAttribute( 'last_processed', time() );
                $csi->store();
            }
        }
        return EZ_CONTENTSERVER_STATUS_FAILURE;
            
    }
    function importPackage( $remote_id, $download = false  )
    {    
        $response =& $this->request("eZSOAPcontentserver::getPackage", array( 'remote_id' => $remote_id ) );

        if ( $response->isFault() )
        {
            if ( $response->faultCode() == 100 )
            {
                $csi = eZContentServerImport::fetch( $remote_id );
                if ( $csi )
                {
                    $csi->remove();
                }
            }
            return array ( 'status' => EZ_CONTENTSERVER_STATUS_FAILURE, 'content' => $response->faultString() );
        }

        $data = $response->value();
        if ( !$data )
        {
            eZDebug::writeError( 'Package ' . $remote_id . ' returned no data.', 'ezcontentserver::importPackage()');
            return array ( 'status' => EZ_CONTENTSERVER_STATUS_FAILURE, 'content' => 'Package ' . $remote_id . ' returned no data.' );
        }
        $data['packageDir'] = eZSys::cacheDirectory().'/packages/incomming';
        include_once( 'kernel/common/template.php' );
        $tpl =& templateInit();
        $http =& eZHTTPTool::instance();
        
        eZDir::mkdir( 'packages/incomming', 0777 );
        
        
        $data['packageFilename'] = $data['packageDir'].'/'.$data['filename'];
        $data['packageName'] = $data['filename'];
        $data['packageName'] = $data['packagename'];

        //clean leftovers
        if( file_exists( $data['packageFilename'] ) )
            @unlink( $data['packageFilename'] );

        if( file_exists( $storeagepath ) )
            eZDir::recursiveDelete( $storeagepath );

        eZFile::create( $data['filename'], $data['packageDir'], base64_decode( $data['package'] ) );

        if ( $download )
            eZFile::download( $data['packageFilename'] );
        eZContentServer::doPackage( $data );
        if( file_exists( $data['packageFilename'] ) )
            unlink( $data['packageFilename'] );
        
        return array( 'status' => EZ_CONTENTSERVER_STATUS_SUCCESS, 'content' => $data );
    }
}
?>
