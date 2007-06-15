<?php
//
// Definition of eZContentServerPackageHandler class
//
// Created on: <09-Mar-2004 16:11:42 kk>
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

/*! \file ezcontentserverobjectpackagehandler.php
*/

/*!
  \class eZContentServerPackageHandler ezcontentserverobjectpackagehandler.php
  \brief Handles content objects in the package system

*/

include_once( 'lib/ezxml/classes/ezxml.php' );
include_once( 'kernel/classes/ezcontentobject.php' );
include_once( 'kernel/classes/ezpackagehandler.php' );

include_once( 'kernel/classes/packagehandlers/ezcontentobject/ezcontentobjectpackagehandler.php' );

class eZContentServerPackageHandler extends eZContentObjectPackageHandler
{
    /*!
     Constructor
    */
    function eZContentServerPackageHandler()
    {
        $this->eZPackageHandler( 'ezcontenserver',
                                 array( 'extract-install-content' => true ) );
    }
    /*!
     Generate package based on NodeArray and input options

     \param package
     \param options
    */
    function generatePackage( &$package, $options )
    {
        $this->Package =& $package;
        $remoteIDArray = array();
        $this->NodeIDArray = array_unique( $this->NodeIDArray );
        foreach( $this->NodeIDArray as $nodeID )
        {
            $this->NodeObjectArray[(string)$nodeID] = eZContentObjectTreeNode::fetch( $nodeID );
        }

        foreach( $this->RootNodeIDArray as $nodeID )
        {
            $this->RootNodeObjectArray[(string)$nodeID] = eZContentObjectTreeNode::fetch( $nodeID );
        }

        $this->generateObjectArray( $options['node_assignment'] );

        $classIDArray = false;
        if ( $options['include_classes'] )
        {
            $remoteIDArray['class'] = array();
            $classIDArray =& $this->generateClassIDArray();

            include_once( 'kernel/classes/packagehandlers/ezcontentclass/ezcontentclasspackagehandler.php' );
            foreach ( $classIDArray as $classID )
            {
                eZContentClassPackageHandler::addClass( $package, $classID );
            }
        }

        $packageRoot = eZDOMDocument::createElementNode( 'content-object' );

        $objectListDOMNode = $this->createObjectListNode( $options );
        $packageRoot->appendChild( $objectListDOMNode );

        $overrideSettingsArray = false;
        $templateFilenameArray = false;
        if ( $options['include_templates'] )
        {
            $overrideSettingsListNode =& $this->generateOverrideSettingsArray( $options['site_access_array'] );
            $packageRoot->appendChild( $overrideSettingsListNode );

            $designTemplateListNode =& $this->generateTemplateFilenameArray();
            $packageRoot->appendChild( $designTemplateListNode );

            $fetchAliasListNode =& $this->generateFetchAliasArray();
            $packageRoot->appendChild( $fetchAliasListNode );
        }

        $siteAccessListDOMNode = $this->createSiteAccessListNode( $options );
        $packageRoot->appendChild( $siteAccessListDOMNode );

        $topNodeListDOMNode = $this->createTopNodeListDOMNode( $options );
        $packageRoot->appendChild( $topNodeListDOMNode );

        $filename = substr( md5( mt_rand() ), 0, 8 );
        $this->Package->appendInstall( 'ezcontentserver', false, false, true,
                                       $filename, 'ezcontentserver',
                                       array( 'content' => $packageRoot ) );
        $this->Package->appendInstall( 'ezcontentserver', false, false, false,
                                       $filename, 'ezcontentserver',
                                       array( 'content' => false ) );
    }

    /*!
     \private
     Create DOMNode for list of top nodes.

     \param options
    */
    function createTopNodeListDOMNode( $options )
    {
        $topNodeListDOMNode = eZDOMDocument::createElementNode( 'top-node-list' );

        foreach( $this->RootNodeObjectArray as $topNode )
        {
            $parent =& $topNode->attribute( 'parent' );
            $obj =& $parent->object();

            $topNodeListDOMNode->appendChild( eZDOMDocument::createElementTextNode( 'top-node', $topNode->attribute( 'name' ),
                                                                                    array( 'node-id' => $topNode->attribute( 'node_id' ),
                                                                                           'remote-id' => $topNode->attribute( 'remote_id' ),
                                                                                           'parent-object-remote-id' => $obj->attribute( 'remote_id' ),
                                                                                           'remote-path' => $parent->attribute( 'url_alias' )
                                                                                           
                                                                                            ) ) );
        }

        return $topNodeListDOMNode;
    }
    /*!
     \private
     Add file to repository and return DONNode description of file

     \param filename
     \param siteaccess
     \param filetype (optional)
    */
    function createDOMNodeFromFile( $filename, $siteAccess, $filetype = false )
    {
        $fileAttributes = array( 'site-access' => $siteAccess );
        if ( $filetype !== false )
        {
            $fileAttributes['file-type'] = $filetype;
        }

        $path = substr( $filename, strpos( $filename, '/', 7 ) );

        $fileDOMNode = eZDOMDocument::createElementNode( 'file', $fileAttributes );
        $fileDOMNode->appendChild( eZDOMDocument::createElementTextNode( 'original-path', $filename ) );
        $fileDOMNode->appendChild( eZDOMDocument::createElementTextNode( 'path', $path ) );


        $destinationPath = $this->Package->path() . '/' .  eZContentServerPackageHandler::contentObjectDirectory() . '/' . $path;
        eZDir::mkdir( eZDir::dirpath( $destinationPath ),  eZDir::directoryPermission(),  true );
        eZFileHandler::copy( $filename, $destinationPath );

        return $fileDOMNode;
    }


    /*!
     \reimp
     Creates a new contentclass as defined in the xml structure.
    */
    function install( &$package, $installType, $parameters,
                      $name, $os, $filename, $subdirectory,
                      &$content, $installParameters,
                      &$installData )
    {
        $this->Package =& $package;
        eZContentServer::initializeTopNodes( $content, $installParameters );

        if ( !$this->installContentObjects( $content->elementByName( 'object-list' ),
                                      $content->elementByName( 'top-node-list' ),
                                      $installParameters ) )
                                      return false;
        return true;
    }
    function installContentObjects( $objectListNode, $topNodeListNode, $installParameters )
    {
        include_once( 'kernel/classes/ezcontentobject.php' );
        $userID = eZUser::currentUserID();
        if ( isset( $installParameters['user_id'] ) )
            $userID = $installParameters['user_id'];
        foreach( $objectListNode->elementsByName( 'object' ) as $objectNode )
        {
            $result = eZContentobject::unserialize( $this->Package, $objectNode, $installParameters, $userID );
        }
        return true;
    }

    function contentObjectDirectory()
    {
        return 'ezcontentserver';
    }

    var $NodeIDArray = array();
    var $RootNodeIDArray = array();
    var $NodeObjectArray = array();
    var $ObjectArray = array();
    var $RootNodeObjectArray = array();
    var $OverrideSettingsArray = array();
    var $TemplateFileArray = array();
    var $Package = null;

    // Static class variables - replacing match values in override.ini
    var $OverrideObjectRemoteID = 'content_object_remote_id';
    var $OverrideNodeRemoteID = 'content_node_remote_id';
    var $OverrideParentNodeRemoteID = 'parent_content_node_remote_id';
    var $OverrideClassRemoteID = 'content_class_remote_id';
}

?>