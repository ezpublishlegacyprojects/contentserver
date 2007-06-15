<?php

/*! \file function_definition.php
*/

$FunctionList = array();

$FunctionList['objectinformation'] = array( 'name' => 'object',
                                #'operation_types' => array( 'read' ),
                                'call_method' => array( 'include_file' => 'extension/contentserver/modules/contentserver/ezcontentserverfunctioncollection.php',
                                                        'class' => 'eZContentServerFunctionCollection',
                                                        'method' => 'fetchObjectInformation' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'id',
                                                              'type' => 'string',
                                                              'required' => true ) ) );
$FunctionList['exportobject'] = array( 'name' => 'object',
                                #'operation_types' => array( 'read' ),
                                'call_method' => array( 'include_file' => 'extension/contentserver/modules/contentserver/ezcontentserverfunctioncollection.php',
                                                        'class' => 'eZContentServerFunctionCollection',
                                                        'method' => 'fetchExportObject' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'id',
                                                              'type' => 'string',
                                                              'required' => true ) ) );
$FunctionList['importobject'] = array( 'name' => 'importobject',
                                #'operation_types' => array( 'read' ),
                                'call_method' => array( 'include_file' => 'extension/contentserver/modules/contentserver/ezcontentserverfunctioncollection.php',
                                                        'class' => 'eZContentServerFunctionCollection',
                                                        'method' => 'fetchImportObject' ),
                                'parameter_type' => 'standard',
                                'parameters' => array( array( 'name' => 'id',
                                                              'type' => 'string',
                                                              'required' => true ) ) );

?>
