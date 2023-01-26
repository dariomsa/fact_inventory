<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

return array(
    'documentos'=>  base_path().'/public/Documentos/',
    'path'=>'public/Documentos/',
    'public'=> 'http://192.168.5.87/test/facturacionelec/public/', 
    'resources'=> 'http://192.168.5.87/test/facturacionelec/public/',
    //'api_orden'=> 'http://200.7.211.20/ERP/current/public/ApiRest/ordenes/datos_facelec?numero=', 
    'api_orden'=> 'https://test.elcomercio.com/ERP/current/public/ApiRest/ordenes/datos_facelec?numero=', 
	'api_orden_combo'=> 'http://test.elcomercio.com/ERP/current/public/ApiRest/ordenes/datos_combo?numero=', 
    //'api_orden'=> 'http://172.20.11.20/erp/public/ApiRest/ordenes/datos_facelec?numero=', 	
    //'api_orden'=> 'http://192.168.2.31/ERP/current/public/ApiRest/ordenes/datos_facelec?numero=', 
    'front'=> 'http://192.168.5.87/test/facturacionelec/public/index.php', 
    'main'=>'http://192.168.5.87/test/facturacionelec/',
    'basePath'=>'/var/www/html/',
	'local'=> '/var/www/html/public/', 

	'api_orden'=> 'http://desa.comercialgec.news/ApiRest/ordenes/datos_facelec?numero=',
	/*
	'documentos'=>  base_path().'/public/Documentos/',
    'path'=>'public/Documentos/',
    'public'=> 'http://facturacion.grupoelcomercio.com/public/', 
    'front'=> 'http://facturacion.grupoelcomercio.com/', 
    'local'=> '/var/www/html/public/', 
    'main'=>'http://facturacion.grupoelcomercio.com/',
    'basePath'=>'/var/www/html/',
	'ip'=>'http://facturacion.grupoelcomercio.com'
	
    	*/
    
);