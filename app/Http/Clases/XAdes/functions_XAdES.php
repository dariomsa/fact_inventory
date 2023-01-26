<?php

///////////////////
//input validations
///////////////////

function filter_content($content) {
    $filtered_content = $content;
    $filtered_content = preg_replace('/&/', '&amp;', $filtered_content);
    $filtered_content = preg_replace('/</', '&lt;', $filtered_content);
    $filtered_content = preg_replace('/>/', '&gt;', $filtered_content);
    $filtered_content = preg_replace('/"/', '&quot;', $filtered_content);
    $filtered_content = preg_replace("/'/", "&#39;", $filtered_content);
    return $filtered_content;
}

function filter_content_white_spaces($content) {
    $filtered_content = $content;
    $filtered_content = preg_replace('/\n/', '', $filtered_content);
    $filtered_content = preg_replace('/\s/', '', $filtered_content);
    return $filtered_content;
}

function filter_content_base64($content) {
    //$filtered_content = filter_content($content);
    $filtered_content = ($content);
    $filtered_content = preg_replace('/-----BEGIN CERTIFICATE-----/', '', $filtered_content);
    $filtered_content = preg_replace('/-----END CERTIFICATE-----/', '', $filtered_content);
    $filtered_content = preg_replace('/-----BEGIN X509 CERTIFICATE-----/', '', $filtered_content);
    $filtered_content = preg_replace('/-----END X509 CERTIFICATE-----/', '', $filtered_content);
    $filtered_content = preg_replace('/-----BEGIN CRL-----/', '', $filtered_content);
    $filtered_content = preg_replace('/-----END CRL-----/', '', $filtered_content);
    $filtered_content = preg_replace('/-----BEGIN X509 CRL-----/', '', $filtered_content);
    $filtered_content = preg_replace('/-----END X509 CRL-----/', '', $filtered_content);
    $filtered_content = filter_content_white_spaces($filtered_content);
    return $filtered_content;
}

function getValidOCSPUris($ocspUri){
    $arrayURI = array();
    $arrayURI = explode(PHP_EOL, $ocspUri);
    $arrayURI = array_filter($arrayURI);
    foreach($arrayURI as $clave => $valor){
        $arrayURI[$clave] = substr($valor, strpos($valor, "http://"));
    }
    //print_r($arrayURI);
    return $arrayURI;
}

function checkOCSPValidation($arrayURI, $rootCA, $certCA, $signing){
    foreach($arrayURI as $clave => $valor){
        $shellText = 'openssl ocsp -CAfile ' . $rootCA . ' -issuer ' . $certCA . ' -cert ' . $signing . ' -url ' . $valor;
        //print $shellText."<br>";
        $output = shell_exec($shellText);
        //print $output."<br>";
        if(!empty($output)){
            $output2 = preg_split('/[\r\n]/', $output);
            //print_r($output2)."<br><br>";
            $output3 = preg_split('/: /', $output2[0]);
            //print_r($output3)."<br><br>";
            $ocsp = $output3[1];
            //echo "OCSP status: ".$ocsp; // will be "good", "revoked", or "unknown"
            if($ocsp === "good"){
                return $ocsp;
            }
        }
        //print "<br><br>";
    }
    return $ocsp;
}

function getValidSigningCertificate($path_openssl, $path_certificate_CA, $certificate_p12, $work_path, $PIN_code, $certificate_subordinado, $certificate_root) {
    $signingCertificate = array();
    $certs = array();
    $extraCerts = array();

    //print $path_certificate_CA . $certificate_p12;
    $pkcs12 = file_get_contents($path_certificate_CA . $certificate_p12);

    if (openssl_pkcs12_read($pkcs12, $certs, $PIN_code)) {

        //$extraCerts = $certs['extracerts'];
        $extraCerts[] = $certs['cert'];
        $pkey = $certs['pkey'];

        foreach ($extraCerts as $certificate => $certValue) {
            $cert = openssl_x509_read($certValue);
            $x509_res = openssl_x509_parse($cert);
            //print_r($x509_res);

            $output = NULL;
            $dateValid = false;
            $mimeValid = false;
            $ocspValid = false;

            //$validFrom = date('Y-m-d H:i:s', $x509_res['validFrom_time_t']);
            //$validTo   = date('Y-m-d H:i:s', $x509_res['validTo_time_t']);
            $now = time();
            if (isset($x509_res['validFrom_time_t']) && isset($x509_res['validTo_time_t'])) {
                if (($x509_res['validFrom_time_t'] <= $now) && ($x509_res['validTo_time_t'] >= $now)) {
                    //echo 'Certificate '.$certificate.' is valid for now'."<br>";
                    $dateValid = true;
                }
            }

            if (isset($x509_res['purposes'][4][0])) {
                $certPurpose = $x509_res['purposes'][4][0]; //check for mimesign:1 [yes] (para firmar mails o SRI)						
                if ($certPurpose == 1) {
                    //echo 'Certificate '.$certificate.' is valid for use as MIME Signing'."<br>";
                    $mimeValid = true;
                }
            }

            //hacer este chekeo solo si ya se sabe que si esta valido (tiempo) y es para firmar (signing)
            if (($dateValid == true) && ($mimeValid == true)) {
                if (isset($x509_res['extensions']['authorityInfoAccess'])) {
                    $ocspUri = $x509_res['extensions']['authorityInfoAccess'];
                    //print_r($ocspUri);
                    $arrayURI = array();
                    $arrayURI = getValidOCSPUris($ocspUri);
                    //$ocspUri = substr($ocspUri, strpos($ocspUri, "http://"));
                    //$ocspUri = substr($ocspUri, 0, strpos($ocspUri, PHP_EOL));

                    $valueOCSP = array();

                    $a = rand(1000, 99999); // Needed if you expect more page clicks in one second! (no pero ... por sia)
                    //obetener archivo de ROOTca en PEM
                    //openssl x509 -inform DER/PEM -in certificate.cer -outform PEM -out certificate.pem
                    //pasar archivo banco central ROOT .cer to .pem
                    exec($path_openssl . ' x509 -inform DER -in "' . $path_certificate_CA . $certificate_root . '" -outform PEM -out "' . $work_path . $a . 'root.pem" ');
                    if (!(file_exists($work_path . $a . 'root.pem'))) { //echo "El fichero no existe";
                        exec($path_openssl . ' x509 -inform PEM -in "' . $path_certificate_CA . $certificate_root . '" -outform PEM -out "' . $work_path . $a . 'root.pem" ');
                        if (!(file_exists($work_path . $a . 'root.pem'))) { //echo "El fichero no existe";
                            $signingCertificate[0] = false;
                            $signingCertificate[1] = "ERROR: No se ha podido crear el certificado ROOT en formato PEM [" . $certificate_root . "]";
                            return $signingCertificate;
                        }
                    }

                    //obetener archivo de subornidado en PEM
                    //openssl x509 -inform DER/PEM -in certificate.cer -outform PEM -out certificate.pem
                    //pasar archivo banco central subornidado (intermediario) .cer to .pem					
                    exec($path_openssl . ' x509 -inform DER -in "' . $path_certificate_CA . $certificate_subordinado . '" -outform PEM -out "' . $work_path . $a . 'certificado.pem" ');
                    if (!(file_exists($work_path . $a . 'certificado.pem'))) { //echo "El fichero no existe";
                        exec($path_openssl . ' x509 -inform PEM -in "' . $path_certificate_CA . $certificate_subordinado . '" -outform PEM -out "' . $work_path . $a . 'certificado.pem" ');
                        if (!(file_exists($work_path . $a . 'certificado.pem'))) { //echo "El fichero no existe";
                            file_delete($work_path . $a . 'root.pem');
                            $signingCertificate[0] = false;
                            $signingCertificate[1] = "ERROR: No se ha podido crear el certificado SUBORDINADO en formato PEM [" . $certificate_subordinado . "]";
                            return $signingCertificate;
                        }
                    }
                    //obtener archivo de signing en formato PEM
                    if (!(openssl_x509_export_to_file($cert, $work_path . $a . 'firmador.pem'))) { //echo "El fichero no existe";
                        file_delete($work_path . $a . 'root.pem');
                        file_delete($work_path . $a . 'certificado.pem');
                        $signingCertificate[0] = false;
                        $signingCertificate[1] = "ERROR: No se ha podido crear el certificado FIRMADOR en formato PEM [" . $certificate_p12 . "]";
                        return $signingCertificate;
                    }

                    $rootCA = $work_path . $a . 'root.pem';        // Points to the Root CA in PEM format.
                    $certCA = $work_path . $a . 'certificado.pem'; // Points to the SUB/INTER CA in PEM format.
                    $signing = $work_path . $a . 'firmador.pem';    // Points to the Signing Cert in PEM format.

                    //$ocsp = checkOCSPValidation($arrayURI, $rootCA, $certCA, $signing);
                    $ocsp = "good";

                    if ($ocsp === "good") { //MARAVILLA				
                        $ocspValid = true;
                        $keys = getTheRightKey($path_openssl, $path_certificate_CA, $certificate_p12, $work_path, $PIN_code, $cert, $a);
                        if ($keys[0] == false) { //algo anda mal con el p12
                            file_delete($work_path . $a . 'certificado.pem');
                            file_delete($work_path . $a . 'firmador.pem');
                            file_delete($work_path . $a . 'root.pem');
                            $signingCertificate[0] = false;
                            $signingCertificate[1] = $keys[1];
                            return $signingCertificate;
                        }

                        file_delete($work_path . $a . 'certificado.pem');
                        file_delete($work_path . $a . 'root.pem');
                        $signingCertificate[0] = true;
                        $signingCertificate[1] = $work_path . $a . 'firmador.pem';
                        $signingCertificate[2] = $keys[1];
                        return $signingCertificate; //retorna el SigningCertificate en formato PEM, listo para su uso ... bacansss
                    } else { //Duuuuogh!
                        file_delete($work_path . $a . 'certificado.pem');
                        file_delete($work_path . $a . 'firmador.pem');
                        file_delete($work_path . $a . 'root.pem');
                        $valueOCSP[] = $ocsp.chr(13).$output;
                    }
                }else{
                    $signingCertificate[0] = false;
                    $signingCertificate[1] = "ERROR: NO SE HA PODIDO ENCONTRAR EL URL DE VALIDACION DE CERTIFICADOS OSCP";
                    return $signingCertificate;
                }
            }
            //print_r($x509_res);
            openssl_x509_free($cert);
        }
        //print_r( $extraCerts );
    } else {
        $signingCertificate[0] = false;
        $signingCertificate[1] = "ERROR: No se ha podido leer el Archivo PKCS12 [" . $certificate_p12 . "]";
        return $signingCertificate;
    }

//    if ($dateValid == false) { //si llego hasta aca sin ningun GOOD algo anda mal con el archivo pk12
//        $validFrom = date('Y-m-d H:i:s', $x509_res['validFrom_time_t']);
//        $validTo   = date('Y-m-d H:i:s', $x509_res['validTo_time_t']);
//        $signingCertificate[0] = false;
//        $signingCertificate[1] = "ERROR: TIEMPO EXPIRADO [desde $validFrom hasta $validTo]".chr(13).print_r($x509_res);
//        return $signingCertificate; 
//    }
    
    if ($ocspValid == false) { //si llego hasta aca sin ningun GOOD algo anda mal con el archivo pk12
        $signingCertificate[0] = false;
        $signingCertificate[1] = "ERROR: Revision OCSP [NO se pudo localizar host " .chr(13). print_r($arrayURI, true) .chr(13). "]: " .chr(13). print_r($valueOCSP, true)."POR FAVOR INTENTE DE NUEVO";
        return $signingCertificate;
    }

    $signingCertificate[0] = false;
    $signingCertificate[1] = "ERROR: Algo anda muy mal y no se que pasa :( ";
    return $signingCertificate;
}

function getTheRightKey($path_openssl, $path_certificate_CA, $certificate_p12, $work_path, $PIN_code, $validCert, $randNumber) {
    //openssl pkcs12 -in file.p12 -clcerts -out file.pem
    $functionResults = array();
    $keyArray = array();
    exec($path_openssl . ' pkcs12 -in "' . $path_certificate_CA . $certificate_p12 . '" -out "' . $work_path . $randNumber . 'keys.pem" -nocerts -passin pass:' . $PIN_code . ' -passout pass:' . $PIN_code);
    if (file_exists($work_path . $randNumber . 'keys.pem')) { //echo "El fichero SI existe";
        $keys = file_read($work_path . $randNumber . 'keys.pem');
        $keyArray = explode("-----END ENCRYPTED PRIVATE KEY-----", $keys, -1);
        file_delete($work_path . $randNumber . 'keys.pem');
        //print_r($keyArray);

        foreach ($keyArray as $keyId => $keyValue) {
            $keyValue .= "-----END ENCRYPTED PRIVATE KEY-----";
            file_put_contents($work_path . $randNumber . 'flagkey.pem', $keyValue);
            exec($path_openssl . ' pkey -in "' . $work_path . $randNumber . 'flagkey.pem" -out "' . $work_path . $randNumber . 'keyout.pem" -passin pass:' . $PIN_code);
            file_delete($work_path . $randNumber . 'flagkey.pem');
            $pkey_rsc = openssl_pkey_get_private(file_read($work_path . $randNumber . 'keyout.pem'));
            $validacion = openssl_x509_check_private_key($validCert, $pkey_rsc);
            //print "<br><br>VAL[".$keyId."]=".$validacion."<br><br>";
            openssl_pkey_free($pkey_rsc);

            if ($validacion) { // BINGO !!!
                $functionResults[0] = true;
                $functionResults[1] = $work_path . $randNumber . 'keyout.pem';
                return $functionResults;
            } else {
                file_delete($work_path . $randNumber . 'keyout.pem');
            }
        }
    } else {
        $functionResults[0] = false;
        $functionResults[1] = "ERROR: No se ha podido obtener las llaves del certificado P12 [" . $certificate_p12 . "]";
        return $functionResults;
    }

    //si llego hasta aca, tonce ninguna llave valio !!! ouch
    $functionResults[0] = false;
    $functionResults[1] = "ERROR: No se han podido comprobar las llaves del certificado P12 [" . $certificate_p12 . "]";
    return $functionResults;
}

?>