<?php

/*! \file ezcontentserverpackageinstaller.php
*/

/*!
  \ingroup package
  \class eZContentServerPackageInstaller ezcontentclasspackageinstaller.php
  \brief A package creator for content objects
*/

include_once( 'kernel/classes/ezpackageinstallationhandler.php' );
include_once( 'kernel/classes/packageinstallers/ezcontentobjectpackageinstaller.php' );
include_once( 'extension/contentserver/classes/ezcontentservertoolkit.php' );

class eZContentServerPackageInstaller extends eZContentObjectPackageInstaller
{

        /*!
     \reimp
    */
    function eZContentServerPackageInstaller( &$package, $id, $installItem )
    {
        $steps = array();
        $steps[] = array( 'id' => 'top_nodes',
                          'name' => ezi18n( 'kernel/package', 'Top node placements' ),
						  'methods' => array( 'initialize' => 'initializeTopNodes',
						                      'validate' => 'validateTopNodes' ),
                          'template' => 'top_nodes.tpl' );
        $this->eZPackageInstallationHandler( $package,
                                             $id,
                                             ezi18n( 'kernel/package', 'Content object import' ),
                                             $steps,
                                             $installItem );
    }
    /*!
     \return \c 'contentclass'.
    */
	function packageType( &$package, &$persistentData )
	{
	    return 'contentserver';
	}

    /*!
     \reimp
    */
    function initializeTopNodes( &$package, &$http, $step, &$persistentData, &$tpl, &$module )
    {
        
        if ( !isset( $persistentData['top_nodes_map'] ) )
        {
            $persistentData['top_nodes_map'] = array();
            $rootDOMNode = $this->rootDOMNode();
            $topNodeListNode = $rootDOMNode->elementByName( 'top-node-list' );
			
            $ini =& eZINI::instance( 'content.ini' );
            $defaultPlacementNodeID = eZContentServer::incomingNodeID();
            $defaultPlacementNode = eZContentObjectTreeNode::fetch( $defaultPlacementNodeID );
            $defaultPlacementName = $defaultPlacementNode->attribute( 'name' );
            foreach ( $topNodeListNode->elementsByName( 'top-node' ) as $topNodeDOMNode )
            {
                $persistentData['top_nodes_map'][(string)$topNodeDOMNode->attributeValue( 'node-id' )] = array( 'old_node_id' => $topNodeDOMNode->attributeValue( 'node-id' ),
                                                                                                                'name' => $topNodeDOMNode->textContent(),
                                                                                                                'new_node_id' => $defaultPlacementNodeID,
                                                                                                                'new_parent_name' => $defaultPlacementName );
            }
        }

        foreach( array_keys( $persistentData['top_nodes_map'] ) as $topNodeArrayKey )
        {
            if ( $http->hasPostVariable( 'BrowseNode_' . $topNodeArrayKey ) )
            {
                include_once( 'kernel/classes/ezcontentbrowse.php' );
                eZContentBrowse::browse( array( 'action_name' => 'SelectObjectRelationNode',
                                                'description_template' => 'design:package/installers/ezcontentobject/browse_topnode.tpl',
                                                'from_page' => '/package/install',
                                                'persistent_data' => array( 'PackageStep' => $http->postVariable( 'PackageStep' ),
                                                                            'InstallItemID' => $http->postVariable( 'InstallItemID' ),
                                                                            'InstallStepID' => $http->postVariable( 'InstallStepID' ),
                                                                            'ReturnBrowse_' . $topNodeArrayKey => 1 ) ),
                                         $module );
            }
            else if ( $http->hasPostVariable( 'ReturnBrowse_' . $topNodeArrayKey ) )
            {
                $nodeIDArray = $http->postVariable( 'SelectedNodeIDArray' );
                $persistentData['top_nodes_map'][$topNodeArrayKey]['new_node_id'] = $nodeIDArray[0];
                $contentNode = eZContentObjectTreeNode::fetch( $nodeIDArray[0] );
                $persistentData['top_nodes_map'][$topNodeArrayKey]['new_parent_name'] = $contentNode->attribute( 'name' );
            }
        }

        $tpl->setVariable( 'top_nodes_map', $persistentData['top_nodes_map'] );
    }

}
?>
