<?php
/**
 * Clase MiAPI
 *
 * Esta clase proporciona funciones básicas para interactuar con una API que utiliza una base de datos MySQL.
 */
class MiAPI {
    /**
     * @var mysqli $conexion La conexión a la base de datos.
     */
    private $conexion;

    /**
     * Constructor de la clase. Inicializa la conexión a la base de datos.
     */
    public function __construct($host, $usuario, $clave, $baseDeDatos) {
        // Establecer la conexión a la base de datos
        $this->conexion = new mysqli($host, $usuario, $clave, $baseDeDatos);

        // Verificar la conexión
        if ($this->conexion->connect_error) {
            die("Error de conexión a la base de datos: " . $this->conexion->connect_error);
        }
    }

    /**
     * Obtener todas las filas de la tabla 'datos'.
     *
     * @return array Datos obtenidos de la base de datos.
     */
    public function obtenerInformacion() {
        $resultados = array();

        $query = "SELECT id, informacion FROM datos";
        $resultado = $this->conexion->query($query);

        if ($resultado) {
            while ($fila = $resultado->fetch_assoc()) {
                $resultados[] = $fila;
            }
            $resultado->free();
        }

        return $resultados;
    }

    /**
     * Agregar información a la base de datos.
     *
     * @param string $info La información a agregar.
     */
    public function agregarInformacion($info) {
        $info = $this->conexion->real_escape_string($info);
        $query = "INSERT INTO datos (informacion) VALUES ('$info')";
        $this->conexion->query($query);
    }

    /**
     * Cerrar la conexión a la base de datos al destruir la instancia de la clase.
     */
    public function __destruct() {
        $this->conexion->close();
    }
}

// Uso de la clase MiAPI
$miApi = new MiAPI("localhost", "usuario", "clave", "nombre_base_de_datos");

// Agregar información a la base de datos
$miApi->agregarInformacion("Dato 1");
$miApi->agregarInformacion("Dato 2");

// Obtener información de la base de datos
$informacion = $miApi->obtenerInformacion();

// Mostrar la información obtenida
echo "Información de la API: ";
print_r($informacion);
?>
