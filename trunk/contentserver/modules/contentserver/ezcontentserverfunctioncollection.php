<?php

/*! \file ezcontentserverfunctioncollection.php
*/

/*!
  \class eZContentFunctionCollection ezcontentfunctioncollection.php
  \brief The class eZContentFunctionCollection does

*/

include_once( 'kernel/error/errors.php' );
include_once( 'extension/contentserver/classes/ezcontentserver.php' );

class eZContentServerFunctionCollection
{
    /*!
     Constructor
    */
    function eZContentServerFunctionCollection()
    {
    }

    function &fetchObjectInformation( $id )
    {
        if ( is_numeric( $id ) )
            return array( 'result' => false );
        return array( 'result' => eZContentServer::fetchObjectInformation( $id ) );
    }

    function &fetchExportObject( $id )
    {
        include_once( 'extension/contentserver/classes/ezcontentserverexport.php' );

        $object =& eZContentServerExport::fetch( $id );

        if ( is_object( $object ) )
            return array( 'result' => $object );
        else
            return array( 'result' => false );
    }
    
    function &fetchImportObject( $id )
    {
        include_once( 'extension/contentserver/classes/ezcontentserverimport.php' );

        $object =& eZContentServerImport::fetch( $id );

       	if ( is_object( $object ) )
            return array( 'result' => $object );
        else
            return array( 'result' => false );        
    }
}

?>
