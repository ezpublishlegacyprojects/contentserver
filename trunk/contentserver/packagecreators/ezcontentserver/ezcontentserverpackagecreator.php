<?php
//
// Definition of eZContentObjectPackageCreator class
//
// Created on: <09-Mar-2004 12:39:59 kk>
//
// Copyright (C) 1999-2005 eZ systems as. All rights reserved.
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

/*! \file ezcontentobjectpackagecreator.php
*/

/*!
  \ingroup package
  \class eZContentObjectPackageCreator ezcontentclasspackagecreator.php
  \brief A package creator for content objects
*/

include_once( 'kernel/classes/ezpackagecreationhandler.php' );
include_once( 'kernel/classes/packagecreators/ezcontentobject/ezcontentobjectpackagecreator.php' );

class eZContentServerPackageCreator extends eZContentObjectPackageCreator
{
    /*!
     \reimp
    */
    function eZContentServerPackageCreator( $id )
    {
        $steps = array();
        $steps[] = array( 'id' => 'object',
                          'name' => ezi18n( 'kernel/package', 'Content objects to include' ),
						  'methods' => array( 'initialize' => 'initializeObjectList',
                                              'load' => 'loadObjectList',
						                      'validate' => 'validateObjectList' ),
                          'template' => 'object_select.tpl' );
        $steps[] = array( 'id' => 'object_limits',
                          'name' => ezi18n( 'kernel/package', 'Content object limits' ),
						  'methods' => array( 'initialize' => 'initializeObjectLimits',
                                              'load' => 'loadObjectLimits',
						                      'validate' => 'validateObjectLimits' ),
                          'template' => 'object_limit.tpl' );
        $this->eZPackageCreationHandler( $id,
                                         ezi18n( 'kernel/package', 'Content Server object export' ),
                                         $steps );
    }

    /*!
     \reimp
     Creates the package and adds the selected content classes.
    */
    function finalize( &$package, &$http, &$persistentData )
    {
		$this->createPackage( $package, $http, $persistentData, $cleanupFiles );

        $objectHandler = eZPackage::packageHandler( 'ezcontentserver' );
        $nodeList = $persistentData['node_list'];
        $options = $persistentData['object_options'];

        foreach( $nodeList as $nodeInfo )
        {
            $objectHandler->addNode( $nodeInfo['id'], $nodeInfo['type'] == 'subtree' );
        }
        $objectHandler->generatePackage( $package, $options );

        $package->setAttribute( 'is_active', true );
        $package->store();
    }

    /*!
     \return \c 'contentclass'.
    */
	function packageType( &$package, &$persistentData )
	{
	    return 'contentserver';
	}
}
?>
