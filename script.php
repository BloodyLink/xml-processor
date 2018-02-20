<?php

//URL DEL XML QUE SE RECIBE
$xmlFile = "example-xml-input.xml";

$xml_data = file_get_contents($xmlFile);//OBTENEMOS DATOS DEL ARCHIVO

$xml = simplexml_load_string($xml_data);
$json = json_encode($xml);//CONVERTIMOS A JSON
$array = json_decode($json,TRUE);//DE JSON PASAMOS A UN ARRAY

// echo "<pre>";
// print_r($array);
// echo "</pre>";



/*
    ESTOS DATOS DEBEN SER PROCESADOS EN UN SP, EL CUAL DEBE DAR UNA RESPUESTA
    PARECIDA A LA SIGUIENTE, LA CUAL DEJARE EN UN ARRAY EN BRUTO:
*/

$responseArray = array(
    "respuesta" => array(
        "folio" => 201801041724,
        "cc_carga" => null,
        "estado" => 100,
        "descripcion" => "El procedimiento se ha realizado satisfactoriamente"
    )
);


//AHORA A XML


function array_to_xml( $data, &$xml_data ) {
    foreach( $data as $key => $value ) {
        if( is_numeric($key) ){
            $key = 'item'.$key; 
        }
        if( is_array($value) ) {
            $subnode = $xml_data->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml_data->addChild("$key",htmlspecialchars("$value"));
        }
     }
}

$xml_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><data></data>');

array_to_xml($responseArray,$xml_data);

$result = $xml_data->asXML('output-test.xml'); //guardamos respuesta en un archivo


