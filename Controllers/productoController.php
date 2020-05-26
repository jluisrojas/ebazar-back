<?php
// Controlador del producto
// el cual se encarga de las siguientes rutas


require_once('../Models/Producto.php');
require_once('../Models/Pregunta.php');
require_once('../Models/DB.php');
require_once('../Models/Response.php');


try {
  $connection = DB::init();
}
catch(PDOException $e){
  error_log('Error de conexión: '. $e);
  $response = new Response();
  $response->setHttpCode(500);
  $response->setSuccess(false);
  $response->addMessage("Error en la conexión a Base de datos");
  $response->send();
  exit();
}

// GET server/producto?id=#
if($_SERVER['REQUEST_METHOD'] === 'GET') {
    if(array_key_exists("producto_id", $_GET)) {
        $producto_id = $_GET["producto_id"];
        if($producto_id == '' || !is_numeric($producto_id)){
            $response = new Response();
            $response->setHttpCode(400);
            $response->setSuccess(false);
            $response->addMessage("El campo de producto id no puede estar vacio o ser diferente de un número");
            $response->send();
            exit();
        
        }

        // Consulta el producto
        $sql = "SELECT * FROM productos WHERE id = $producto_id";
        $query = $connection->prepare($sql);
        $query->execute();

        // Si no existe producto resulta en un error
        $rowCount = $query->rowCount();
        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpCode(404);
            $response->setSuccess(false);
            $response->addMessage("No existe el producto con id: $producto_id");
            $response->send();
            exit();
        }

        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $producto = Producto::fromArray($row);
        }

        // Obtener preguntas
        $sqlPreguntas = "SELECT * FROM Preguntas WHERE id_producto = $producto_id";
        $queryPreguntas = $connection->prepare($sqlPreguntas);
        $queryPreguntas->execute();

        $preguntas = array();
        while($row = $queryPreguntas->fetch(PDO::FETCH_ASSOC)){
            $pregunta = Pregunta::fromArray($row);
            $preguntas[] = $pregunta->getArray();
        }

        // Formato de los datos del producto
        $productoData = $producto->getArray();
        $productoData = [
            'titulo' => $productoData['titulo'],
            'precio' => $productoData['precio'],
            'disponibles' => $productoData['disponibles'],
            'ubicacion' => $productoData['ubicacion'],
            'descripcion_corta' => $productoData['descripcion_corta'],
            'descripcion_larga' => $productoData['descripcion_corta'],
            'caracteristicas' => $productoData['caracteristicas'],
            'preguntas' => array_map(function($pregunta) {
                return [
                    'pregunta' => $pregunta['pregunta'],
                    'fecha' => $pregunta['fecha_pregunta'],
                    'respuesta' => $pregunta['respuesta']
                ];
            }, $preguntas)
        ];

        // Response todo bien
        $returnData['producto'] = $productoData;
        $response = new Response();
        $response->setHttpCode(200);
        $response->setSuccess(true);
        $response->setData($returnData);
        $response->send();
        exit();
    } else {
        $response = new Response();
        $response->setHttpCode(400);
        $response->setSuccess(false);
        $response->addMessage("El metodo no tiene campo de id");
        $response->send();
        exit();
    }
} elseif($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST server/producto
    echo "Metodo post...";

} else {
    $response = new Response();
    $response->setHttpCode(405);
    $response->setSuccess(false);
    $response->addMessage("Método no permitido");
    $response->send();
    exit();
}

?>