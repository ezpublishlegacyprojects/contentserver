<?php
/*
DROP TABLE IF EXISTS ezcontentserver_export;
CREATE TABLE ezcontentserver_export (
  id char(64) NOT NULL default '',
  created int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
);
    
  path char(255) NOT NULL default '',
*/
//!! eZKernel
//! The class eZContentServerExport
/*!

*/

class eZContentServerExport extends eZPersistentObject
{
    function eZContentServerExport( $row )
    {
       $this->eZPersistentObject( $row );
    }

    function definition()
    {
        return array( "fields" => array( "id" => array( 'name' => 'ID',
                                                        'datatype' => 'string',
                                                        'default' => 0,
                                                        'required' => true ),
                                         "created" => array( 'name' => "Created",
                                                             'datatype' => 'integer',
                                                             'default' => time(),
                                                             'required' => true ),
                                         "type" => array(     'name' => "Type",
                                                              'datatype' => 'string',
                                                              'default' => EZ_CONTENTSERVER_TYPE_NODE,
                                                              'required' => true ),
                                         "updateflag" => array(  'name' => "Updateflag",
                                                              'datatype' => 'integer',
                                                              'default' => EZ_CONTENTSERVER_UPDATEFLAG_ALWAYS,
                                                              'required' => true ),
                                         "expires" => array(  'name' => "Expires",
                                                              'datatype' => 'integer',
                                                              'default' => null,
                                                              'required' => false ),
                                         "modified" => array( 'name' => "Modified",
                                                             'datatype' => 'integer',
                                                             'default' => time(),
                                                             'required' => true )
                                                               ),
                      "keys" => array( "id" ),
                      "class_name" => "eZContentServerExport",
                      "function_attributes" => array( "object" => "object", 'modified_subtree' => 'modifiedSubtree' ),
                      "sort" => array( "id" => "asc" ),
                      "name" => "ezcontentserver_export" );
    }

    function create( $id, $type = null, $updateflag = null, $expires = null )
    {
        $row = array(
            "id" => $id,
            "created" => time(),
            "type" => $type,
            "updateflag" => $updateflag,
            "expires" => $expires );
        return new eZContentServerExport( $row );
    }

    function hasAttribute( $attr )
    {
        return ( $attr == "modifier" or
                 $attr == 'creator' or
                 eZPersistentObject::hasAttribute( $attr ) );
    }
    function &attribute( $attr )
    {
        switch ( $attr )
        {
            case "modifier":
            {
                $user_id = $this->ModifierID;
            } break;
            case "creator":
            {
                $user_id = $this->CreatorID;
            } break;
            default:
                return eZPersistentObject::attribute( $attr );
        }
        include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );
        $user =& eZUser::fetch( $user_id );
        return $user;
    }
    function modifiedSubtree()
    {
    	$obj = $this->object();
    	$node = $obj->mainNode();
    	$return = $node->attribute( 'modified_subnode' );
    	if ( $return > 0 )
    	   return $return;
    	else
    	   return false;
    }
    function removeSelected( $id )
    {
        eZPersistentObject::removeObject( eZContentClassGroup::definition(),
                                          array( "id" => $id ) );
    }
    function &node()
    {
        return eZContentObjectTreeNode::fetch( $this->attribute( 'id' ) );
    }
    function &object()
    {
        return eZContentObject::fetchByRemoteID( $this->attribute( 'id' ) );
    }
    function remoteIDList()
    {
        $return = array();
        $list = eZPersistentObject::fetchObjectList( eZContentServerExport::definition(), array( 'id', 'expires' ), null, null, null, false );

        foreach ( $list as $item )
        {
            $return[]=$item['id'];
        }
        return $return;
    }
    function store()
    {
        $this->setAttribute( 'modified', time() );
        return parent::store();
    }
    function fetch( $id, $asObject = true )
    {
        $conds = array( "id" => $id );
        return eZPersistentObject::fetchObject( eZContentServerExport::definition(),
                                                null,
                                                $conds,
                                                $asObject );
    }

    function fetchByID( $id )
    {
    $object = eZContentObject::fetch( $id );
    if ( !is_object( $object ) )
        return null;
    return eZContentServerExport::fetch( $object->attribute( 'remote_id' ) ); 
    }

    function fetchList( $user_id = false, $asObject = true )
    {
        $conds = array();
        if ( $user_id !== false and is_numeric( $user_id ) )
            $conds["creator_id"] = $user_id;
        return eZPersistentObject::fetchObjectList( eZContentClassGroup::definition(),
                                                    null, $conds, null, null,
                                                    $asObject );
    }

    var $ID;
    var $Name;
    var $CreatorID;
    var $ModifierID;
    var $Created;
    var $Modified;
}

?>
