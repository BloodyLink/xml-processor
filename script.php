<?php

require_once('ConnectionDAO.php');

$connectionDao = new ConnectionDAO();

$xml_data = file_get_contents('php://input');

$xml         = simplexml_load_string($xml_data);
$json        = json_encode($xml); 
$clienteData = json_decode($json, TRUE); 

$cliente = $clienteData["acceso"]["cliente"];

$pdo = $connectionDao->getPDO();

try {
    $pdo->begintransaction();
    $sql = "select next value for dbo.seq_webservice as codigo";
    $q   = $pdo->query($sql);
    $res = $q->fetch();
    $pdo->commit();
}
catch (exception $e) {
    throw new exception("hubo un problema al obtener codigo." . $e->getmessage());
}

$codigoRegistro = $res["codigo"];

try{
	$pdo->beginTransaction();
	$sql = "SELECT apo_cliente FROM a_cli WHERE codu_cliente = '$cliente'";
	$q   = $pdo->query($sql);
    $res = $q->fetch();
	$pdo->commit();
}catch(exception $e){
	throw new Exception("no se pudo obtener apodo." . $e->getMessage());
}

$apodo = $res["apo_cliente"];

$tableName = "tmp_ws_" . $apodo;

$tags   = null;
$values = null;
foreach ($clienteData as $secciones) {
    foreach ($secciones as $keys => $data) {
        
        if ($keys != "cliente") {
            $tags .= ", " . $keys;
            if (is_array($data)) {
                $values .= ", NULL";
            } else {
                $values .= ", '" . $data . "'";
            }
        }
        
    }
}

$columns = substr($tags, 2);
$values  = substr($values, 2);

 try {
    $pdo->beginTransaction();
    $sql = "INSERT INTO $tableName (COD_REGISTRO, $columns) VALUES ($codigoRegistro, $values);";
    
    $q = $pdo->query($sql);
    $pdo->commit();
 }
 catch (exception $e) {
     throw new Exception("Hubo un problema al insertar en tabla temporal." . $e->getMessage());
 }

$respuesta = 0;
try {
    
    $insert = "	SET NOCOUNT ON
				DECLARE    @return_value int,
                        @respuesta varchar(max)

                EXEC    @return_value = SP_carga_webservice
                        @cliente = N'$cliente',
                        @registro = $codigoRegistro,
                        @respuesta = @respuesta OUTPUT
				
                SELECT    @respuesta as N'@respuesta'";
    $bdd    = $connectionDao->getPDO();
    $stmt   = $bdd->prepare($insert);
    $stmt->execute();
    $tabResultat = $stmt->fetch();
	//var_dump($tabResultat);
    $resultSP    = $tabResultat["@respuesta"];
    
}
catch (exception $e) {
    throw new Exception("Hubo un problema al llamar procedimiento." . $e->getMessage());
}

// var_dump($resultSP);
// die();

$arrayResultSP = explode("-", $resultSP);
$estado        = $arrayResultSP[0];
$descripcion   = $arrayResultSP[1];
$responseArray = array(
    "respuesta" => array(
        "folio" => $clienteData["solicitud"]["folio"],
        "cc_carga" => $clienteData["asegurable"]["cc_carga"],
        "estado" => $estado,
        "descripcion" => $descripcion
    )
);

function array_to_xml($data, &$xml_data)
{
    foreach ($data as $key => $value) {
        if (is_numeric($key)) {
            $key = 'item' . $key;
        }
        if (is_array($value)) {
            $subnode = $xml_data->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml_data->addChild("$key", htmlspecialchars("$value"));
        }
    }
}

$xml_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><data></data>');

array_to_xml($responseArray, $xml_data);

header("content-type: text/xml; charset=utf-8");
echo $xml_data->asxml();
