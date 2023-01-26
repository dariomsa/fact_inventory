<?php
include "functions.php";

function create_xades_bes($comprobante, $certificate_p12, $PIN_code, $path_openssl, $path_certificate_CA, $digest_algorithm, $digest_algorithm_URI, $signature_algorithm_URI, $x_doctype, $certificate_subordinado, $certificate_root, $work_path,$ambiente,$emision,$claveAcceso) {

    include_once "functions_XAdES.php";
    
    /*
     *  //son la misma pieza
        $text = "hola mundo";
        print $text."<br>";
        print base64_encode(sha1($text, true))."<br>";
        print base64_encode(pack("H*", sha1($text)))."<br>";
        print base64_encode(openssl_digest ( $text, "sha1" , true))."<br>";
     * 
     */
    
    
    //input checking for missing parameters
    if ($certificate_p12 == '' || $PIN_code == '' || $path_openssl == '' || $path_certificate_CA == '' || $digest_algorithm == '' || $digest_algorithm_URI == '' || $signature_algorithm_URI == '') {
        throw new Exception('Missing input parameter!');
    }

    $validCertificate = getValidSigningCertificate($path_openssl, $path_certificate_CA, $certificate_p12, $work_path, $PIN_code, $certificate_subordinado, $certificate_root);
    if ($validCertificate[0] == false) {
        throw new Exception($validCertificate[1]);
    }    
    //////////////////
    //DOM XML object//
    //////////////////
    $xmlSignature = new DOMDocument();
    $xmlSignature->formatOutput = true;
    $xmlSignature->preserveWhiteSpace = false;
    $xmlSignature->xmlStandalone = true;
    $xmlSignature->validateOnParse = true;
    $xmlSignature->encoding = 'utf-8';
    $xmlSignature->version = '1.0';
    
    
    
    ///////////////
    //ambiente////
    /////////////
    $infoTributaria=$comprobante->getElementsByTagName('infoTributaria')->item(0);
    /*
    $ambienteNode= new \DOMElement('ambiente',$ambiente);
    $emisionNode= new \DOMElement('tipoEmision',$emision);
    $claveAccesoNode= new \DOMElement('claveAcceso',$claveAcceso);
    */
    $parentPath='//infoTributaria';
    $razonPath='//razonSocial';
    $rucPath='//codDoc';

    $xpath=new \DomXPath($comprobante);
    $parent=$xpath->query($parentPath);
    $next=$xpath->query($razonPath);
    $element=$comprobante->createElement('ambiente',$ambiente);
    $parent->item(0)->insertBefore($element,$next->item(0));

    $parent=$xpath->query($parentPath);
    $next=$xpath->query($razonPath);
    $element=$comprobante->createElement('tipoEmision',$emision);
    $parent->item(0)->insertBefore($element,$next->item(0));

    $parent=$xpath->query($parentPath);
    $next=$xpath->query($rucPath);
    $element=$comprobante->createElement('claveAcceso',$claveAcceso);
    $parent->item(0)->insertBefore($element,$next->item(0));





/*
    $infoTributaria->insertAfter($ambienteNode);
    $infoTributaria->insertAfter($emisionNode);
    $infoTributaria->appendChild($claveAccesoNode);
  */  
  
    
    ////////////////
    //ds:Signature//
    ////////////////

    $eSignature = $xmlSignature->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Signature', NULL);
    $eSignatureAttributeId = $xmlSignature->createAttribute('Id');
    $eSignatureAttributeId->value = 'Signature-' . $x_doctype;
    $eSignature->appendChild($eSignatureAttributeId);
    $eSignature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:etsi', 'http://uri.etsi.org/01903/v1.3.2#');
    $xmlSignature->appendChild($eSignature);


    //////////////
    //ds:KeyInfo//
    //////////////	
    $cert = openssl_x509_read(file_read($validCertificate[1]));
    $x509_parsed = openssl_x509_parse($cert, true);
    openssl_x509_export($cert, $X509CertificateValue);
    //print_r($x509_parsed);
    $pkey_rsc = openssl_pkey_get_private(file_read($validCertificate[2]));
    $privDetails = openssl_pkey_get_details($pkey_rsc);
    //print_r($privDetails);
    $ExponentValue = $privDetails['rsa']['e']; //exponent
    $ModulusValue = $privDetails['rsa']['n']; //modulus
    $ExponentValue = base64_encode($ExponentValue);    
    //creo que es mejor sin los chuck splits
    //$ModulusValue = "\n".chunk_split(base64_encode($ModulusValue), 76, "\n");
    //$ModulusValue = ltrim(rtrim(chunk_split(base64_encode($ModulusValue), 76)));
    $ModulusValue = base64_encode($ModulusValue);
    //$X509CertificateValue = "\n".chunk_split(filter_content_base64($X509CertificateValue), 76, "\n");
    //creo que es mejor sin los chuck splits
    //$X509CertificateValue = ltrim(rtrim(chunk_split(filter_content_base64($X509CertificateValue), 76)));
    $X509CertificateValue = filter_content_base64($X509CertificateValue);

    /*     * **KeyValue**** */
    //Signature -> KeyInfo -> KeyValue -> RSAKeyValue -> Exponent
    $eExponent = $xmlSignature->createElement('ds:Exponent', $ExponentValue);

    //Signature -> KeyInfo -> KeyValue -> RSAKeyValue -> Modulus
    $eModulus = $xmlSignature->createElement('ds:Modulus', $ModulusValue);

    //Signature -> KeyInfo -> KeyValue -> RSAKeyValue
    $eRSAKeyValue = $xmlSignature->createElement('ds:RSAKeyValue');
    $eRSAKeyValue->appendChild($eModulus);
    $eRSAKeyValue->appendChild($eExponent);

    //Signature -> KeyInfo -> KeyValue
    $eKeyValue = $xmlSignature->createElement('ds:KeyValue');
    $eKeyValue->appendChild($eRSAKeyValue);

    /*     * **X509Certificate**** */
    //Signature -> KeyInfo -> X509Data -> X509Certificate
    $eX509Certificate = $xmlSignature->createElement('ds:X509Certificate', $X509CertificateValue);

    //Signature -> KeyInfo -> X509Data
    $eX509Data = $xmlSignature->createElement('ds:X509Data');
    $eX509Data->appendChild($eX509Certificate);

    //Signature -> KeyInfo
    $eKeyInfo = $xmlSignature->createElement('ds:KeyInfo');
    $eKeyInfoAttributeId = $xmlSignature->createAttribute('Id');
    $eKeyInfoAttributeId->value = 'CertificateXAAX123';
    $eKeyInfo->appendChild($eKeyInfoAttributeId);
    $eKeyInfo->appendChild($eX509Data);
    $eKeyInfo->appendChild($eKeyValue);

    openssl_x509_free($cert);
    openssl_pkey_free($pkey_rsc);

    $eSignature->appendChild($eKeyInfo);
    //////////////////////
    //FIN -> ds:KeyInfo //
    //////////////////////
    /////////////
    //ds:Object//
    /////////////
    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedDataObjectProperties -> DataObjectFormat -> Description
    //$eEncoding = $xmlSignature->createElement('etsi:Encoding', 'http://www.w3.org/2000/09/xmldsig#base64');

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedDataObjectProperties -> DataObjectFormat -> Description
    $eDescription = $xmlSignature->createElement('etsi:Description', 'XAAX Comprobante ' . $x_doctype);

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedDataObjectProperties -> DataObjectFormat -> MimeType
    $eMimeType = $xmlSignature->createElement('etsi:MimeType', 'text/xml');

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedDataObjectProperties -> DataObjectFormat
    $eDataObjectFormat = $xmlSignature->createElement('etsi:DataObjectFormat');
    $eDataObjectFormatAttributeObjectReference = $xmlSignature->createAttribute('ObjectReference');
    $eDataObjectFormatAttributeObjectReference->value = '#Comprobante-Reference-'.$x_doctype;
    $eDataObjectFormat->appendChild($eDataObjectFormatAttributeObjectReference);
    $eDataObjectFormat->appendChild($eDescription);
    $eDataObjectFormat->appendChild($eMimeType);
    //$eDataObjectFormat->appendChild($eEncoding);
    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedDataObjectProperties
    $eSignedDataObjectProperties = $xmlSignature->createElement('etsi:SignedDataObjectProperties');
    $eSignedDataObjectProperties->appendChild($eDataObjectFormat);

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedSignatureProperties -> SigningCertificate -> Cert -> IssuerSerial -> X509IssuerName
    $X509IssuerName_array = array();
    foreach ($x509_parsed['issuer'] as $keyId => $keyValue) {
        $X509IssuerName_array[] = $keyId . "=" . $keyValue;
    }
    
    $X509IssuerName_value = implode(",", array_reverse($X509IssuerName_array));
    //$X509IssuerName_value="CN=AC BANCO CENTRAL DEL ECUADOR TEST,L=QUITO,OU=ENTIDAD DE CERTIFICACION DE INFORMACION-ECIBCE TEST,O=BANCO CENTRAL DEL ECUADOR TEST,C=EC";
    //result from abobe code = CN=AC BANCO CENTRAL DEL ECUADOR TEST,L=QUITO,OU=ENTIDAD DE CERTIFICACION DE INFORMACION-ECIBCE TEST,O=BANCO CENTRAL DEL ECUADOR TEST,C=EC
    //:)
    
    $eX509IssuerName = $xmlSignature->createElement('ds:X509IssuerName', $X509IssuerName_value);

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedSignatureProperties -> SigningCertificate -> Cert -> IssuerSerial -> X509SerialNumber
    $eX509SerialNumber = $xmlSignature->createElement('ds:X509SerialNumber', $x509_parsed['serialNumber']);

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedSignatureProperties -> SigningCertificate -> Cert -> IssuerSerial
    $eIssuerSerial = $xmlSignature->createElement('etsi:IssuerSerial');
    $eIssuerSerial->appendChild($eX509IssuerName);
    $eIssuerSerial->appendChild($eX509SerialNumber);

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedSignatureProperties -> SigningCertificate -> Cert -> CertDigest -> DigestMethod
    $eCertDigestDigestMethod = $xmlSignature->createElement('ds:DigestMethod');
    $eCertDigestDigestMethodAttributeAlgorithm = $xmlSignature->createAttribute('Algorithm');
    $eCertDigestDigestMethodAttributeAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#sha1';
    $eCertDigestDigestMethod->appendChild($eCertDigestDigestMethodAttributeAlgorithm);
    
    /*deacurdo al ultimo bajado 1) decode64, luego digest, luego encode64*/
    //base64_decode($X509Certificate_value . "\n"));
    $X509CertificateValueDecoded = base64_decode($X509CertificateValue);
    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedSignatureProperties -> SigningCertificate -> Cert -> CertDigest -> DigestValue
    $eCertDigestValue = $xmlSignature->createElement('ds:DigestValue', base64_encode(sha1($X509CertificateValueDecoded, true)));
    //$eCertDigestValue = $xmlSignature->createElement('ds:DigestValue', base64_encode(openssl_digest ( $X509CertificateValue, "sha256" , true)));
    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedSignatureProperties -> SigningCertificate -> Cert -> CertDigest
    $eCertDigest = $xmlSignature->createElement('etsi:CertDigest');
    $eCertDigest->appendChild($eCertDigestDigestMethod);
    $eCertDigest->appendChild($eCertDigestValue);

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedSignatureProperties -> SigningCertificate -> Cert
    $eCert = $xmlSignature->createElement('etsi:Cert');
    $eCert->appendChild($eCertDigest);
    $eCert->appendChild($eIssuerSerial);

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedSignatureProperties -> SigningCertificate
    $eSigningCertificate = $xmlSignature->createElement('etsi:SigningCertificate');
    $eSigningCertificate->appendChild($eCert);

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedSignatureProperties -> SigningTime
    $eSigningTime = $xmlSignature->createElement('etsi:SigningTime', date("c"));

    //Signature -> Object -> QualifyingProperties -> SignedProperties -> SignedSignatureProperties
    $eSignedSignatureProperties = $xmlSignature->createElement('etsi:SignedSignatureProperties');
    $eSignedSignatureProperties->appendChild($eSigningTime);
    $eSignedSignatureProperties->appendChild($eSigningCertificate);

    //Signature -> Object -> QualifyingProperties -> SignedProperties
    $eSignedProperties = $xmlSignature->createElement('etsi:SignedProperties');
    $eSignedPropertiesAttributeId = $xmlSignature->createAttribute('Id');
    $eSignedPropertiesAttributeId->value = 'Signature-' . $x_doctype . 'SignedProperties';
    $eSignedProperties->appendChild($eSignedPropertiesAttributeId);
    $eSignedProperties->appendChild($eSignedSignatureProperties);
    $eSignedProperties->appendChild($eSignedDataObjectProperties);

    //Signature -> Object -> QualifyingProperties
    $eQualifyingProperties = $xmlSignature->createElement('etsi:QualifyingProperties');
    $eQualifyingPropertiestAttributeTarget = $xmlSignature->createAttribute('Target');
    $eQualifyingPropertiestAttributeTarget->value = '#Signature-' . $x_doctype;
    $eQualifyingProperties->appendChild($eQualifyingPropertiestAttributeTarget);
    $eQualifyingProperties->appendChild($eSignedProperties);


    //Signature -> Object
    $eObject = $xmlSignature->createElement('ds:Object');
    $eObjectAttributeId = $xmlSignature->createAttribute('Id');
    $eObjectAttributeId->value = 'Signature-' . $x_doctype . '-Object';
    $eObject->appendChild($eObjectAttributeId);
    $eObject->appendChild($eQualifyingProperties);

    $eSignature->appendChild($eObject);
    ////////////////////
    //FIN -> ds:Object//
    ////////////////////
    /////////////////
    //ds:SignedInfo//
    /////////////////

    /*     * ***REFERENCE SIGNED PROPERTIES******** */
    //Signature -> SignedInfo -> Reference[Reference-SignedProperties] -> DigestMethod
    $eReferenceSignedPropertiesDigestMethod = $xmlSignature->createElement('ds:DigestMethod');
    $eReferenceSignedPropertiesDigestMethodAttributeAlgorithm = $xmlSignature->createAttribute('Algorithm');
    $eReferenceSignedPropertiesDigestMethodAttributeAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#sha1';
    $eReferenceSignedPropertiesDigestMethod->appendChild($eReferenceSignedPropertiesDigestMethodAttributeAlgorithm);

    //Signature -> SignedInfo -> Reference[Reference-SignedProperties] -> DigestValue
    //$canonical = C14NGeneral($eSignedProperties, TRUE);
    //print $canonical;
    //print $eSignedProperties->C14N(false,false);
    $eReferenceSignedPropertiesDigestValue = $xmlSignature->createElement('ds:DigestValue', base64_encode(sha1($eSignedProperties->C14N(false, false), true)));

    //Signature -> SignedInfo -> Reference[Reference-SignedProperties]
    $eReferenceSignedProperties = $xmlSignature->createElement('ds:Reference');
    $eReferenceSignedPropertiesAttributeId = $xmlSignature->createAttribute('Id');
    $eReferenceSignedPropertiesAttributeId->value = 'SignedProperties-Reference';
    $eReferenceSignedProperties->appendChild($eReferenceSignedPropertiesAttributeId);

    $eReferenceSignedPropertiesAttributeType = $xmlSignature->createAttribute('Type');
    $eReferenceSignedPropertiesAttributeType->value = 'http://uri.etsi.org/01903#SignedProperties';
    $eReferenceSignedProperties->appendChild($eReferenceSignedPropertiesAttributeType);

    $eReferenceSignedPropertiesAttributeURI = $xmlSignature->createAttribute('URI');
    $eReferenceSignedPropertiesAttributeURI->value = '#Signature-' . $x_doctype . 'SignedProperties';
    $eReferenceSignedProperties->appendChild($eReferenceSignedPropertiesAttributeURI);

    $eReferenceSignedProperties->appendChild($eReferenceSignedPropertiesDigestMethod);
    $eReferenceSignedProperties->appendChild($eReferenceSignedPropertiesDigestValue);
    /*     * ***FIN REFERENCE SIGNED PROPERTIES******** */

    /*     * ***REFERENCE SIGNED KEYINFO [certificado]******** */
    //Signature -> SignedInfo -> Reference[Reference-KeyInfo] -> DigestMethod
    $eReferenceKeyInfoDigestMethod = $xmlSignature->createElement('ds:DigestMethod');
    $eReferenceKeyInfoDigestMethodAttributeAlgorithm = $xmlSignature->createAttribute('Algorithm');
    $eReferenceKeyInfoDigestMethodAttributeAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#sha1';
    $eReferenceKeyInfoDigestMethod->appendChild($eReferenceKeyInfoDigestMethodAttributeAlgorithm);

    //Signature -> SignedInfo -> Reference[Reference-KeyInfo] -> DigestValue
    //SEGUN SRI = <ds:DigestValue><!-- HASH O DIGEST DEL CERTIFICADO X509 --></ds:DigestValue>
    //MAS NO DEL KEYINFO como YO HICE
    //$eReferenceKeyInfoDigestValue = $xmlSignature->createElement('ds:DigestValue', base64_encode(sha1($eKeyInfo->C14N(false, false), true)));
    $eReferenceKeyInfoDigestValue = $xmlSignature->createElement('ds:DigestValue', base64_encode(sha1($eKeyInfo->C14N(false, false), true)));

    //Signature -> SignedInfo -> Reference[Reference-KeyInfo]
    $eReferenceKeyInfo = $xmlSignature->createElement('ds:Reference');
    //$eReferenceKeyInfoAttributeId = $xmlSignature->createAttribute('Id');
    //$eReferenceKeyInfoAttributeId->value = 'KeyInfo-Reference';
    //$eReferenceKeyInfo->appendChild($eReferenceKeyInfoAttributeId);

    $eReferenceKeyInfoAttributeURI = $xmlSignature->createAttribute('URI');
    $eReferenceKeyInfoAttributeURI->value = '#CertificateXAAX123';
    $eReferenceKeyInfo->appendChild($eReferenceKeyInfoAttributeURI);

    $eReferenceKeyInfo->appendChild($eReferenceKeyInfoDigestMethod);
    $eReferenceKeyInfo->appendChild($eReferenceKeyInfoDigestValue);
    /*     * ***FIN REFERENCE SIGNED KEYINFO [certificado]******** */

    /*     * ***REFERENCE COMPROBANTE******** */
    //Signature -> SignedInfo -> Reference[Reference-Comprobante] -> DigestMethod
    $eReferenceComprobanteDigestMethod = $xmlSignature->createElement('ds:DigestMethod');
    $eReferenceComprobanteDigestMethodAttributeAlgorithm = $xmlSignature->createAttribute('Algorithm');
    $eReferenceComprobanteDigestMethodAttributeAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#sha1';
    $eReferenceComprobanteDigestMethod->appendChild($eReferenceComprobanteDigestMethodAttributeAlgorithm);

    //Signature -> SignedInfo -> Reference[Reference-Comprobante] -> Transforms -> Transform
    $eReferenceComprobanteTransform = $xmlSignature->createElement('ds:Transform');
    $eReferenceComprobanteTransformAttributeAlgorithm = $xmlSignature->createAttribute('Algorithm');
    $eReferenceComprobanteTransformAttributeAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    $eReferenceComprobanteTransform->appendChild($eReferenceComprobanteTransformAttributeAlgorithm);

    //Signature -> SignedInfo -> Reference[Reference-Comprobante] -> Transforms
    $eReferenceComprobanteTransforms = $xmlSignature->createElement('ds:Transforms');
    $eReferenceComprobanteTransforms->appendChild($eReferenceComprobanteTransform);

    //Signature -> SignedInfo -> Reference[Reference-Comprobante] -> DigestValue
    //DE LEY TODO DEBE SER CON C14N(false, false) CANONIZACION NOSE COMITO :) :)
    $eReferenceComprobanteDigestValue = $xmlSignature->createElement('ds:DigestValue', base64_encode(sha1($comprobante->C14N(false, false), true)));
    //print $comprobante->C14N(false, false);
    //print base64_encode(sha1($comprobante->C14N(false, false), true));

    //Signature -> SignedInfo -> Reference[Reference-Comprobante]
    $eReferenceComprobante = $xmlSignature->createElement('ds:Reference');
    $eReferenceComprobanteAttributeId = $xmlSignature->createAttribute('Id');
    $eReferenceComprobanteAttributeId->value = 'Comprobante-Reference-'.$x_doctype;
    $eReferenceComprobante->appendChild($eReferenceComprobanteAttributeId);

    $eReferenceComprobanteAttributeURI = $xmlSignature->createAttribute('URI');
    $eReferenceComprobanteAttributeURI->value = '#comprobante';
    $eReferenceComprobante->appendChild($eReferenceComprobanteAttributeURI);

    $eReferenceComprobante->appendChild($eReferenceComprobanteTransforms);
    $eReferenceComprobante->appendChild($eReferenceComprobanteDigestMethod);
    $eReferenceComprobante->appendChild($eReferenceComprobanteDigestValue);
    /*     * ***FIN REFERENCE COMPROBANTE******** */

    //Signature -> SignedInfo -> SignatureMethod
    $eSignatureMethod = $xmlSignature->createElement('ds:SignatureMethod');
    $eSignatureMethodAttributeAlgorithm = $xmlSignature->createAttribute('Algorithm');
    $eSignatureMethodAttributeAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    $eSignatureMethod->appendChild($eSignatureMethodAttributeAlgorithm);

    //Signature -> SignedInfo -> CanonicalizationMethod
    $eCanonicalizationMethod = $xmlSignature->createElement('ds:CanonicalizationMethod');
    $eCanonicalizationMethodAttributeAlgorithm = $xmlSignature->createAttribute('Algorithm');
    $eCanonicalizationMethodAttributeAlgorithm->value = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    $eCanonicalizationMethod->appendChild($eCanonicalizationMethodAttributeAlgorithm);

    //Signature -> SignedInfo
    $eSignedInfo = $xmlSignature->createElement('ds:SignedInfo');
    $eSignedInfoAttributeId = $xmlSignature->createAttribute('Id');
    $eSignedInfoAttributeId->value = 'Signature-SignedInfo';
    $eSignedInfo->appendChild($eSignedInfoAttributeId);
    $eSignedInfo->appendChild($eCanonicalizationMethod);
    $eSignedInfo->appendChild($eSignatureMethod);
    $eSignedInfo->appendChild($eReferenceSignedProperties);
    $eSignedInfo->appendChild($eReferenceKeyInfo);
    $eSignedInfo->appendChild($eReferenceComprobante);

    $eSignature->insertBefore($eSignedInfo, $eKeyInfo);

    ////////////////////////
    //FIN -> ds:SignedInfo//
    ////////////////////////
    /////////////////////
    //ds:SignatureValue//
    /////////////////////
    //creating encoded hash of to-be-signed structures
    $SignatureValue_value = NULL;
    $pkey_rsc = openssl_pkey_get_private(file_read($validCertificate[2]));
    $publickey_rsc = openssl_pkey_get_public(file_read($validCertificate[1]));

    /* FIRMA CON OPENSSL SIGN */
    /* segun = https://locallost.net/?p=332 */
    /* Despite the fact that openssl_sign() seems to do the same operations (compute digest, then encrypt the digest with the priv key), the resulting signature is not the same. */
    /* Afer grepping into the openssl source code for a while, I finally found that openssl does not encrypt the Â« raw Â» digest, but encrypt the digest in its ASN1 form. */

    openssl_sign($eSignedInfo->C14N(false, false), $SignatureValue_value, $pkey_rsc, OPENSSL_ALGO_SHA1);
    // Check signature
    $ok = openssl_verify($eSignedInfo->C14N(false, false), $SignatureValue_value, $publickey_rsc, OPENSSL_ALGO_SHA1);
    if ($ok <> 1) {
        echo "ERROR: Mal Firmado<br>";
    }
    //mejor sin los splits
    //$SignatureValue_value = "\n".chunk_split(base64_encode($SignatureValue_value), 76, "\n");
    $SignatureValue_value = base64_encode($SignatureValue_value);
    //print "PHPOS=".$SignatureValue_value."<br><br>";


    /* FIRMA CON private_encrypt */
    /*
      $digestSignedInfo = sha1($eSignedInfo->C14N(false,false), true);
      openssl_private_encrypt($digestSignedInfo, $SignatureValue_value, $pkey_rsc, OPENSSL_PKCS1_PADDING);
      // Check signature
      openssl_public_decrypt($SignatureValue_value, $decryptedSignedInfoDigest, $publickey_rsc, OPENSSL_PKCS1_PADDING);
      if (!($digestSignedInfo == $decryptedSignedInfoDigest)) {
      echo "ERROR: Mal Firmado<br>";
      }
      $SignatureValue_value = base64_encode($SignatureValue_value);
      //print "PHPDC=".$SignatureValue_value."<br><br>";
     */

    openssl_pkey_free($pkey_rsc);
    openssl_pkey_free($publickey_rsc);
    $eSignatureValue = $xmlSignature->createElement('ds:SignatureValue', $SignatureValue_value);
    $eSignatureValueAttributeId = $xmlSignature->createAttribute('Id');
    $eSignatureValueAttributeId->value = 'SignatureValue';
    $eSignatureValue->appendChild($eSignatureValueAttributeId);

    $eSignature->insertBefore($eSignatureValue, $eKeyInfo);
    ////////////////////////////
    //FIN -> ds:SignatureValue//
    ////////////////////////////
    
    //print "CHA=".$xmlSignature->saveXml($xmlSignature, LIBXML_NOEMPTYTAG);
    //print "CHA2=".$xmlSignature->C14N(false,false);
    //borrar archivo firmador y key
    file_delete($validCertificate[1]);
    file_delete($validCertificate[2]);

    return $xmlSignature;
}

?>