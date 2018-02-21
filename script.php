<?php

//require_once('ConnectionDAO.php');

//$connectionDao = new ConnectionDAO();

//XML QUE SE RECIBE
$xmlFile = file_get_contents('php://input'); 

//$xml_data = file_get_contents($xmlFile);//OBTENEMOS DATOS DEL ARCHIVO

$xml = simplexml_load_string($xmlFile);
$json = json_encode($xml);//CONVERTIMOS A JSON
$clienteData = json_decode($json,TRUE);//DE JSON PASAMOS A UN ARRAY

// echo "<pre>";
// print_r($clienteData);
// echo "</pre>";



/*

1.- Seleccionar el apodo del cliente:
“SELECT app_cliente FROM a_cli WHERE codu_cliente = “ + Valor que viene bajo etiqueta <cliente> en el XML.

2.- Una vez que tengas el apodo, lo concatenas al texto “tmp_ws_” + Apodo… Con eso ya tienes la tabla temporal…

3.- Para el caso de Colombia, la tabla tiene exactamente las mismas columnas que el XML… Mi idea es a futuro 
utilizar ese WS para el resto de nuestros clientes, por ende, la estructura de la tabla temporal debe ser la 
misma que el XML. Al menos en ASP hay fórmulas para saber los nombres de los campos, espero que en PHP también 
los haya. Para finalizar respecto a la tabla temporal, la primera columna se llama COD_REGISTRO, el cual es el 
índice de esa tabla, que se llena con la secuencia que te comenté. Para obtener el valor de esa secuencia, al 
menos en ASP ejecuto el SQL "SELECT NEXT VALUE FOR dbo.seq_persona AS codigo”, y luego asigno a una variable 
local el campo “código” que arroja la consulta.

4.- Como te comenté ideal que el INSERT lo construya a partir del nombre de los camps. Para el caso de Colombia 
es un seguro de salud en el cual pueden incluir a toda la familia e incluso varios otras relaciones (excepto 
amantes o concubinas, jajajaja), pero en Chile solo hacemos entrevistas a seguros individuales, por lo cual, 
para acá necesitamos solo el RUT del asegurable. Por eso el XML tiene 2 Cédulas de Ciudadanía: Asegurable y Carga.
*/


//TODO: CONFIGURAR CONEXION A SQL SERVER

$cliente = $clienteData["acceso"]["cliente"];

// $pdo = new PDO();

/*obtenemos el COD_REGISTRO*/
// try{
//     $pdo->beginTransaction();
//     $sql = "SELECT NEXT VALUE FOR dbo.seq_webservice AS codigo";
//     $q = $pdo->query($sql);
//     $res = $q->fetchAll();    

// }catch(exception $e){
//     throw new Exception("Hubo un problema al obtener codigo." . $e->getMessage());
// }

// $codigoRegistro = $res["codigo"];



//OBTENEMOS app_cliente de la BD
$sql = "SELECT app_cliente FROM a_cli WHERE codu_cliente = '" . $cliente . "';";

$apodo = "lucho";

//definimos el nombre de la tabla temporal
$tableName = "tmp_ws_" . $apodo;


//recorreremos el array con el input para definir columnas en la tabla temporal

$tags = null;
$values = null;
foreach($clienteData as $secciones){
    foreach($secciones as $keys => $data){
        $tags .= ", '" . $keys . "'";

        if(is_array($data)){
            $values .=  ", NULL";
        }else{
            $values .=  ", '" . $data . "'";
        }
    }
}

$columns = substr($tags, 2);
$values = substr($values, 2);
/*
cliente, usuario, password, cc_asegurable, cc_carga, tipo_doc_carga, tipo_carga, 
nombres, apellido1, apellido2, genero, nacimiento, fono_hog, fono_cel, email, folio, 
fechaalta, servicio, producto, capital, estado, observaciones, ejecutivo
*/ 

//obtenemos el codigo

// $sqlCod = "SELECT NEXT VALUE FOR dbo.seq_persona AS codigo";
//blah blah

//CREAMOS UN INSERT SEGUN DATOS DEL XML

// try{
//     $pdo->beginTransaction();
//     $sql = "INSERT INTO $tableName ('COD_REGISTRO', $columns) VALUES ($codigoRegistro, $values);";
//     $q = $pdo->query($sql);  

// }catch(exception $e){
//     throw new Exception("Hubo un problema al insertar en tabla temporal." . $e->getMessage());
// }



// try{
//     $sql = "CALL SP_carga_webservice(?,?);";
//     $q = $pdo->prepare($sql);
//     $res = $pdo->execute();

//     $resultSP = $res->fetchAll();

// }catch(exception $e){
//     throw new Exception("Hubo un problema al llamar procedimiento." . $e->getMessage());
// }




//TEST
$resultSP = "513-Formato de la Fecha de Nacimiento del Asegurable es incorrecto";

$arrayResultSP = explode("-", $resultSP);
$estado = $arrayResultSP[0];
$descripcion = $arrayResultSP[1];




$responseArray = array(
    "respuesta" => array(
        "folio" => $clienteData["solicitud"]["folio"],
        "cc_carga" => $clienteData["asegurable"]["cc_carga"],
        "estado" => $estado,
        "descripcion" => $descripcion
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

// $result = $xml_data->asXML('output-test.xml'); //guardamos respuesta en un archivo


header("Content-type: text/xml; charset=utf-8");
echo $xml_data->asXML();


