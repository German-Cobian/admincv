<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php'; // Asegúrate de especificar la ruta correcta

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Comprueba si se ha enviado un archivo
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $comisionista_id = $_POST['comisionista_id']; // Obtén el ID del comisionista desde la solicitud

        // Procesa el archivo CSV
        $file_name = $_FILES['csv_file']['name'];
        $file_tmp = $_FILES['csv_file']['tmp_name'];

        $csv_data = array_map('str_getcsv', file($file_tmp));

        // Empezar desde la segunda fila
        for ($i = 1; $i < count($csv_data); $i++) {
            $row = $csv_data[$i];

            // Aquí puedes realizar las inserciones en la base de datos
            $nombre = $row[0];
            $apellidos = $row[1];
            $correo = $row[2];
            $telefono = $row[3];
            $nombre_negocio = $row[4];
            $notas = $row[5];
            $sitio_web = $row[6];
            $direccion = $row[7];

            // Utiliza $comisionista_id y los valores de las variables para insertar los datos
            $query = "INSERT INTO clientes (comisionista_id, nombre, apellidos, correo, telefono, nombre_negocio, notas, sitio_web, direccion) 
                      VALUES ('$comisionista_id', '$nombre', '$apellidos', '$correo', '$telefono', '$nombre_negocio', '$notas', '$sitio_web', '$direccion')";

            // Ejecutar la consulta en la base de datos
            $result = mysqli_query($conn, $query);

            if (!$result) {
                $error_message = mysqli_error($conn);
                $response = array('error' => 'Error al ejecutar la consulta: ' . $error_message);
                echo json_encode($response);
                exit();
            }
            else{
                echo 'Todo fine haciendo pruebas en produccion';
            }
        }

        // Devuelve una respuesta JSON indicando el éxito de la operación
        $response = array('success' => true, 'message' => 'Datos importados con éxito');
        echo json_encode($response);
    } else {
        $response = array('error' => 'No se ha proporcionado un archivo válido');
        echo json_encode($response);
    }
} else {
    $response = array('error' => 'Método no permitido');
    echo json_encode($response);
}
