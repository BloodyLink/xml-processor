<?php

require_once('ConnectionDAO.php');
require_once('lib/nusoap.php');

 class Script extends ConnectionDAO {

    public function ingresoData ($xml_data) {

        $cliente = $xml_data["generacion"]["acceso"]["cliente"];

        $pdo = $this->getPDO();

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
        foreach ($xml_data["generacion"] as $secciones) {
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
            $bdd    = $this->getPDO();
            $stmt   = $bdd->prepare($insert);
            $stmt->execute();
            $tabResultat = $stmt->fetch();
            $resultSP    = $tabResultat["@respuesta"];
            
        }
        catch (exception $e) {
            throw new Exception("Hubo un problema al llamar procedimiento." . $e->getMessage());
        }

        $arrayResultSP = explode("-", $resultSP);
        $estado        = $arrayResultSP[0];
        $descripcion   = $arrayResultSP[1];
        $responseArray = array(
            "respuesta" => array(
                "folio" => $xml_data["generacion"]["solicitud"]["folio"],
                "cc_carga" => $xml_data["generacion"]["asegurable"]["cc_carga"],
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

        $xml_data_new = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><data></data>');

        array_to_xml($responseArray, $xml_data_new);

        header("content-type: text/xml; charset=utf-8");

        return $xml_data_new->asxml();
    }
    
}

$server = new soap_server();
$server->configureWSDL("servicioClientes", "192.168.10.3:5020/xml-processor/script.php");
 
$server->register("Script.ingresoData",
    array("type" => "xsd:string"),
    array("return" => "xsd:string"),
    "192.168.10.3:5020/xml-processor/script.php",
    "192.168.10.3:5020/xml-processor/script.php#ingresoData",
    "rpc",
    "literal",
    "Ingresa Clientes");
 
@$server->service($HTTP_RAW_POST_DATA);
