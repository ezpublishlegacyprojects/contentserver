<?php

$Module = array( 'name' => 'Content Server', 'variable_params' => true );

$ViewList = array();

$ViewList['add'] = array(
	'script' => 'add.php',
	"default_navigation_part" => 'ezcontentnavigationpart',
	"params" => array( 'type' => 'type', "id" => "id" ),
	"unordered_params" => array(  ) );

$ViewList['edit'] = array(
	'script' => 'edit.php',
	'ui_context' => 'edit',
    'single_post_actions' => array( 'Store' => 'Store', 'Cancel' => 'Cancel' ),
    'post_action_parameters' => array( 'Cancel' => array( 'RedirectURI' => 'RedirectURI' ),
                                       'Store' => array( 'RedirectURI' => 'RedirectURI',
                                                         'Type' => 'Type',
                                                         'Day' => 'Day',  
                                                         'Month' => 'Month',
                                                         'Year' => 'Year',
                                                         'Update' => 'Update',
                                                         'Expiry' => 'Expiry' ) ),
	"default_navigation_part" => 'ezcontentnavigationpart',
	"params" => array( "id" => "id" ),
	"unordered_params" => array(  ) );

$ViewList['view'] = array(
	'script' => 'view.php',
    'single_post_actions' => array( 'Find' => 'Find' ),
    'post_action_parameters' => array( 'Find' => array( 'ID' => 'ID' ) ),
	"default_navigation_part" => 'ezcontentnavigationpart',
	"params" => array(  ),
	"unordered_params" => array(  ) );

$ViewList['viewexports'] = array(
	'script' => 'viewexports.php',
    'single_post_actions' => array( 'Find' => 'Find' ),
    'post_action_parameters' => array( 'Find' => array( 'ID' => 'ID' ) ),
	"default_navigation_part" => 'ezcontentnavigationpart',
	"params" => array(  ),
	"unordered_params" => array(  ) );

$ViewList['info'] = array(
	'script' => 'info.php',
    'single_post_actions' => array( 'Cancel' => 'Cancel' ),
    'post_action_parameters' => array( 'Cancel' => array( 'RedirectURI' => 'RedirectURI' ) ),
	"default_navigation_part" => 'ezcontentnavigationpart',
	"params" => array( "id" => "id" ),
	"unordered_params" => array(  ) );

$ViewList['changeremoteid'] = array(
	'script' => 'changeremoteid.php',
	'ui_context' => 'edit',
    'single_post_actions' => array( 'Store' => 'Store', 'Cancel' => 'Cancel' ),
    'post_action_parameters' => array( 'Cancel' => array( 'RedirectURI' => 'RedirectURI' ),
                                       'Store' => array( 'RedirectURI' => 'RedirectURI',
                                       'IDArray' => 'IDArray' ) ),
	"default_navigation_part" => 'ezcontentnavigationpart',
	"params" => array( "node_id" => "node_id" ),
	"unordered_params" => array(  ) );
	
/*Client used for testing */
$ViewList['client'] = array(
	'script' => 'client.php',
	"default_navigation_part" => 'ezcontentnavigationpart',
	'single_post_actions' => array( 'Cancel' => 'Cancel', 'ClearAll' => 'ClearAll', 'DownloadPackage' => 'DownloadPackage' ),
	'post_action_parameters' => array( 'Cancel' => array(  ), 'ClearAll' => array(), 'DownloadPackage' => array() ),
	"params" => array( ),
	"unordered_params" => array(  ) );

$ViewList['remove'] = array(
	'script' => 'remove.php',
	"default_navigation_part" => 'ezcontentnavigationpart',
	"params" => array( "id" => "id" ),
	"unordered_params" => array(  ) );

$ViewList['menu'] = array(
	'script' => 'menu.php',
	"default_navigation_part" => 'ezcontentservernavigationpart',
	"params" => array(  ),
	"unordered_params" => array(  ) );
?>