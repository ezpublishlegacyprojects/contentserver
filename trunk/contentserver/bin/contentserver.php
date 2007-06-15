<?php
if ( !isset( $cli ) )
{
	include_once( 'lib/ezutils/classes/ezcli.php' );
	include_once( 'kernel/classes/ezscript.php' );
	$cli =& eZCLI::instance();
	$script =& eZScript::instance( array( 'description' => ( "Content Server Client\n" .
                                                         "Allows for easy updating content from remote servers\n" .
                                                         "\n" .
                                                         "./extension/contentserver/bin/contentserver.php --user=admin --password=publish --remote-server=somehost.example.com --remote-port=8080" ),
                                      'use-session' => true,
                                      'use-modules' => true,
                                      'use-extensions' => true ) );

	$script->startup();

	$options = $script->getOptions( "[remote-server:][remote-port:][user:][password:][force]",
                                "",
                                array( 'remote-server' => 'Remote server',
				       				   'remote-port' => 'Remote server port',
                                       'user' => 'Username on remote server',
                                       'password' => 'Password on remote server',
									   'force' => 'Force update on all contentobjects' ) );

	$script->initialize();
	$isCRON=false;
}
else
{
	$isCRON=true;
}

$sys =& eZSys::instance();

$cli->output( 'Using Siteaccess '.$GLOBALS['eZCurrentAccess']['name'] );

// login as admin
include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
$contentini = eZINI::instance( 'content.ini' );

$user = eZUser::fetchByName( $contentini->variable( 'ContentServerSettings', 'LocalSystemUser' ) );

if ( is_object( $user ) )
{
	$user->loginCurrent();
	$cli->output( "Logged in as '".$contentini->variable( 'ContentServerSettings', 'LocalSystemUser' )."'" );
}
else
{
	$user = eZUser::fetch( 14 );
	if ( is_object ( $user ) )
	{
		$user->loginCurrent();
	}
	else
	{
		$cli->output( "Not logged in as 'admin'" );
	}
}

if ( !isset( $options['user'] ) )
	$options['user'] = $contentini->variable( 'ContentServerSettings', 'User' );
if ( !isset( $options['password'] ) )
	$options['password'] = $contentini->variable( 'ContentServerSettings', 'Password' );
if ( !isset( $options['remote-server'] ) )
	$options['remote-server'] = $contentini->variable( 'ContentServerSettings', 'Server' );
if ( !isset( $options['port'] ) )
	$options['port'] = $contentini->variable( 'ContentServerSettings', 'Port' );

if ( $contentini->variable( 'ContentServer', 'Client' ) != 'enabled' )
{
    $options['remote-server'] = null;
    $cli->output( 'Contentserver client disabled.' );
}

if ( $options['remote-server'] )
{
	if ( isset( $options['force'] ) and $options['force'] )
	{
		include_once( 'extension/contentserver/classes/ezcontentserverimport.php' );
		$cli->output( "Update forced for all objects. Deleting old Objects." );
		eZContentServerImport::forceUpdateAll();
	}
	$cli->output( 'Getting Content from "'.$options['remote-server'].'"' );
	include_once( 'extension/contentserver/classes/ezcontentserver.php' );
	$params = array( 'login' => $options['user'] ,
				 'password' => $options['password'],
				 'server' => $options['remote-server'],
				 'port' => $options['remote-port']
				);

	$Contentserver = new eZContentServer( $params );

	// send the request to the server and fetch the response
	$response =& $Contentserver->getAvialable( );
	// check if the server returned a fault, if not print out the result
	if ( $response->isFault() )
	{
	    $cli->output( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
	    eZDebug::writeError( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
	    if ( !$isCRON )
	    	return $script->shutdown( 1 );
	}
	else
	{
		$data = $response->value();
		if ( is_array( $data ) and count( $data ) == 0 )
			$cli->output( 'Nothing to update.' );
		foreach( $data as $id )
		{
			$return = $Contentserver->processPackage( $id ) ;
			if ( $return == EZ_CONTENTSERVER_STATUS_INITIAL_IMPORT )
				$cli->output( $id.': INITIAL IMPORT' );
			if ( $return == EZ_CONTENTSERVER_STATUS_UPDATE )
				$cli->output( $id.': UPDATE' );
			if ( $return == EZ_CONTENTSERVER_STATUS_FAILURE )
				$cli->output( $id.': FAILURE' );
		}
		eZSessionDestroy( eZSessionUserID() );
		if ( !$isCRON )
			return $script->shutdown();
	}
}
if ( !$isCRON )
{
	$script->showHelp();
	return $script->shutdown();
}
?>
