<?php
include_once( "kernel/common/template.php" );
include_once( 'lib/ezutils/classes/ezhttptool.php' );
include_once( 'extension/contentserver/classes/ezcontentserver.php' );
$Module =& $Params['Module'];
$sys  = eZSys::instance();
$tpl =& templateInit();
$ini =& eZINI::instance();
$contentini =& eZINI::instance( "content.ini", null, null, null, true );
$http =& eZHTTPTool::instance();

$output = "";
if ( $Module->isCurrentAction( 'Cancel' ) )
{
        $Module->redirectTo( 'contentserver/menu' );
}
if ( $Module->isCurrentAction( 'ClearAll' ) )
{
    eZContentServerImport::clearAll();
    $Module->redirectTo( 'contentserver/client' );
}
if ( $Module->isCurrentAction( 'DownloadPackage' ) and $http->hasPostVariable( 'RemoteID' ) )
{
	$params = array( 'login' => $http->postVariable( 'Username' ),
				 'password' => $http->postVariable( 'Password' ),
				 'server' => $http->postVariable( 'Server' ),
				 'port' => $http->postVariable( 'Port' )
				);
				
	$Contentserver = new eZContentServer( $params );
	$Contentserver->importPackage( $http->postVariable( 'RemoteID' ), true );
}
if ( $http->hasPostVariable( 'ForceUpdate' ) )
{
	eZContentServerImport::forceUpdateAll();
	$output="Set all objects for reinjection.";
}
if ( $http->hasPostVariable( 'Run' ) )
{

	$params = array( 'login' => $http->postVariable( 'Username' ),
				 'password' => $http->postVariable( 'Password' ),
				 'server' => $http->postVariable( 'Server' ),
				 'port' => $http->postVariable( 'Port' )
				);
				
	$Contentserver = new eZContentServer( $params );

	// send the request to the server and fetch the response
	$response =& $Contentserver->getAvialable( );
	
	// check if the server returned a fault, if not print out the result
	if ( $response->isFault() )
	{
	    $output =  "SOAP fault: " . $response->faultCode(). " - " . $response->faultString()."\n";
	}
	else
	{
		
		$data = $response->value();
		$output = '';
		
		if ( $http->hasPostVariable( 'RemoteID' ) and $http->postVariable( 'RemoteID' ) )
		{
            
                 $csi = eZContentServerImport::fetch( $http->postVariable( 'RemoteID' ) );
                 if ( !is_object( $csi ) )
                 {
                    $output .= $http->postVariable( 'RemoteID' )." not known as imported.\n";
                    $data = array( $http->postVariable( 'RemoteID' ) );
                 }
                 else
                 {
                    $output .= $http->postVariable( 'RemoteID' )." known as imported.\n";
                    $data = array( $http->postVariable( 'RemoteID' ) );
		 }
                }

		if ( $data == false or ( is_array( $data ) and count( $data ) == 0 ) )
			$output .= "Nothing to update.\n";
	
		foreach( $data as $id )
		{
			$return = $Contentserver->processPackage( $id ) ;

			switch ( $return )
			{
			    case EZ_CONTENTSERVER_STATUS_INITIAL_IMPORT:
			         $output .= "$id: INITIAL IMPORT\n";
			    break;
			    case EZ_CONTENTSERVER_STATUS_UPDATE:
			         $output .= "$id: UPDATE\n";
			    break;
                            case EZ_CONTENTSERVER_STATUS_UPDATE_DISCONTINUED:
                                 $output .= "$id: DISCONTINUED\n";
                            break; 
			    case EZ_CONTENTSERVER_STATUS_FAILURE:
			         $output .= "$id: FAILURE ". $Contentserver->lastError ."\n";
			    break;
			    default:
			         $output .= "$id: UNKOWN STATUS\n";
			    break;
			}
		}
	}
}

$tpl->setVariable( 'Output' , $output );

if ( $http->hasPostVariable( 'RemoteID' ) )
	$tpl->setVariable( 'RemoteID' , $http->postVariable( 'RemoteID' ) );
else
    $tpl->setVariable( 'RemoteID' , "" );

if (! $http->hasPostVariable( 'Username' ) )
	$tpl->setVariable( 'Username' , $contentini->variable( "ContentServerSettings", "User" ) );
else
	$tpl->setVariable( 'Username' , $http->postVariable( 'Username' ) );

if (! $http->hasPostVariable( 'Password' ) )
	$tpl->setVariable( 'Password' , $contentini->variable( "ContentServerSettings", "Password" ) );
else
	$tpl->setVariable( 'Password' , $http->postVariable( 'Password' ) );

if (! $http->hasPostVariable( 'Server' ) )
	$tpl->setVariable( 'Server' , $contentini->variable( "ContentServerSettings", "Server" ) );
else
	$tpl->setVariable( 'Server' , $http->postVariable( 'Server' ) );
if (! $http->hasPostVariable( 'Port' ) )
	$tpl->setVariable( 'Port' , 80 );
else
	$tpl->setVariable( 'Port' , $http->postVariable( 'Port' ) );

$Result = array();
$Result['content'] =& $tpl->fetch( "design:contentserver/client.tpl" );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'design/standard/extract', 'Content Server Web Client' ) ) );

?>
