<?php
/*
DROP TABLE IF EXISTS ezcontentserver_import;
CREATE TABLE `ezcontentserver_import` (
`id` VARCHAR( 50 ) NOT NULL ,
`created` INT( 11 ) NOT NULL ,
`modified` INT( 11 ) NOT NULL ,
`last_processed` INT( 11 ) NOT NULL ,
`contentobject_version` INT( 11 ) NOT NULL ,
`data` TEXT NOT NULL ,
PRIMARY KEY ( `id` )
);
ALTER TABLE `ezcontentserver_import` ADD `status` INT( 1 ) DEFAULT '1' NOT NULL;
*/

define( "EZ_CONTENTSERVERIMPORT_STATUS_UNCONFIRMED", 0 );
define( "EZ_CONTENTSERVERIMPORT_STATUS_CONFIRMED", 1 );

class eZContentServerImport extends eZPersistentObject
{
    function eZContentServerImport( $row )
    {
       $this->eZPersistentObject( $row );
    }

    function &definition()
    {
        return array( "fields" => array( "id" => array( 'name' => 'ID',
                                                        'datatype' => 'string',
                                                        'default' => 0,
                                                        'required' => true ),
                                         "created" => array( 'name' => "Created",
                                                             'datatype' => 'integer',
                                                             'default' => time(),
                                                             'required' => true ),
                                         "modified" => array( 'name' => "Modified",
                                                             'datatype' => 'integer',
                                                             'default' => time(),
                                                             'required' => true ),
                                         "last_processed" => array( 'name' => "LastProcessed",
                                                             'datatype' => 'integer',
                                                             'default' => time(),
                                                             'required' => true ),
                                         "contentobject_version" => array( 'name' => "ContentobjectVersion",
                                                              'datatype' => 'integer',
                                                              'default' => 1,
                                                              'required' => true ),
                                         "data" => array( 'name' => 'Data',
                                                        'datatype' => 'string',
                                                        'default' => '',
                                                        'required' => false ),
                                         "status" => array( 'name' => "Status",
                                                              'datatype' => 'integer',
                                                              'default' => EZ_CONTENTSERVERIMPORT_STATUS_UNCONFIRMED,
                                                              'required' => true ),
                                         "remote_host" => array( 'name' => "Remotehost",
                                                              'datatype' => 'string',
                                                              'default' => '',
                                                              'required' => true ),
                                         "type" => array( 'name' => "Type",
                                                              'datatype' => 'integer',
                                                              'default' => 0,
                                                              'required' => true ),
                                         "expires" => array( 'name' => "expires",
                                                              'datatype' => 'integer',
                                                              'default' => null,
                                                              'required' => true ),
                                         "updateflag" => array( 'name' => "Updateflag",
                                                              'datatype' => 'integer',
                                                              'default' => EZ_CONTENTSERVER_UPDATEFLAG_ALWAYS,
                                                              'required' => true ),
                                         "remote_modified" => array( 'name' => "Remotemodified",
                                                              'datatype' => 'integer',
                                                              'default' => null,
                                                              'required' => true )                                                              
                                                              
                                                              
                                                              
                                                              ),
                      "keys" => array( "id" ),
                      "function_attributes" => array( 'data_array' => 'dataArray',
                                                      "object" => "object"  ),
                      /*"increment_key" => "id",*/
                      "class_name" => "eZContentServerImport",
                      "sort" => array( "id" => "asc" ),
                      "name" => "ezcontentserver_import" );
    }
    function canUpdate()
    {
        if ( $this->attribute( 'updateflag' ) == EZ_CONTENTSERVER_UPDATEFLAG_ALWAYS )
            return true;
        else
            return false;
    }
    function expire()
    {
        #$this->setAttribute( 'updateflag', EZ_CONTENTSERVER_UDPATEFLAG_DISCONTINUED );
        #$this->store();
        $this->deleteObject();
    }
    function &create( $remote_id, $version )
    {
        $dateTime = time();
        $dataArray;
        $dataArrayText='';
        $row = array(
            "id" => $remote_id,
            "created" => $dateTime,
            'last_processed' => $dateTime,
            "modified" => $dateTime,
            "contentobject_version" => $version );
            
        $csi = new eZContentServerImport( $row );
        $csi->store();
        return $csi;
    }
/*
    function hasAttribute( $attr )
    {
      #  die($attr);
        return eZPersistentObject::hasAttribute( $attr );
    }
    function &attribute( $attr )
    {
      #  die($attr);
        switch ( $attr )
        {
            default:
                return eZPersistentObject::attribute( $attr );
        }
        return $return;
    }
*/
    function &remove( $remote_id = false )
    {
        if ( get_class( $this ) == 'ezcontentserverimport' and !$remote_id )
            eZPersistentObject::removeObject( eZContentServerImport::definition(),
                                          array( "id" => $this->attribute( 'id' ) ) );
        else
            eZPersistentObject::removeObject( eZContentServerImport::definition(),
                                          array( "id" => $remote_id ) );
    }
    function &node()
    {
        include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
        return eZContentObjectTreeNode::fetch( $this->attribute( 'id' ) );
    }
    function &object()
    {
        return eZContentObject::fetchByRemoteID( $this->attribute( 'id' ) );
    }
    function remoteIDList()
    {
        return eZPersistentObject::fetchObjectList( eZContentServerImport::definition(), array( 'id' ), null, null, null, false );
    }
    function remoteIDWithVersionList()
    {
        return eZPersistentObject::fetchObjectList( eZContentServerImport::definition(), array( 'id', 'contentobject_version', 'remote_modified' ), null, null, null, false );
    }
    function &fetch( $id, $asObject = true )
    {
        $conds = array( "id" => $id );
        return eZPersistentObject::fetchObject( eZContentServerImport::definition(),
                                                false,
                                                $conds,
                                                $asObject );
    }
    function &setAttributeArrayToXML($name,$val)
    {
        $node = eZDOMDocument::createElementNodeFromArray($name,$val);
        $doc = new eZDOMDocument();
        $doc->setRoot( $node );
        return $this->setAttribute($name, $doc->toString() );    
    }
    function xmlToArray($name)
    {
        $xml = new eZXML();
        $doc = $xml->domTree( $this->attribute($name) );
        if (is_object($doc))
            return eZDOMDocument::createArrayFromDOMNode( $doc->root() );
    }
    function forceUpdateAll()
    {
        $db=& eZDB::instance();
        return $db->query( "UPDATE ezcontentserver_import SET contentobject_version = 0" );
    }
    function clearAll()
    {
        $list = eZContentServerImport::fetchList();
        foreach( $list as $csi )
        {
            $csi->deleteObject();
            $csi->remove();
        }
        
    }
    function store()
    {
        $this->setAttribute( 'modified', time() );
        return parent::store();
    }
    function deleteObject( )
    {
        $obj =& $this->object();
        $deleteIDArray =array();
        if ( is_object( $obj ) )
        {
            $allAssignedNodes =& $obj->attribute( 'assigned_nodes' ); 
            foreach ( $allAssignedNodes as $node )
            {
                $deleteIDArray[] = $node->attribute( "node_id" );
            }
            return eZContentObjectTreeNode::removeSubtrees( $deleteIDArray, false );
        }
        return false;
        
    }
    function dataArray()
    {
        $xml = new eZXML();
        $doc = $xml->domTree( $this->attribute('data') );
        if (is_object($doc))
            return eZDOMDocument::createArrayFromDOMNode( $doc->root() );
    }
    function fetchByContentObjectID( $id )
    {
        $object =& eZContentObject::fetch( $id );
        if ( !is_object( $object ) )
            return null;
        return eZContentServerImport::fetch( $object->attribute( 'remote_id' ) ); 
    }

    function &fetchList( $asObject = true )
    {
        $conds = array();
        return eZPersistentObject::fetchObjectList( eZContentServerImport::definition(),
                                                    null, $conds, null, null,
                                                    $asObject );
    }
}
?>
