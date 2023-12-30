<?php


class MainClass

{
    public function __construct()
    {
        $host = $_SERVER['HTTP_HOST'];

        // Configuración para la conexión local
        if ($host === 'localhost') {
            $dbHost = 'localhost';
            $dbUser = 'root';
            $dbPassword = '';
            $dbName = 'smartpun_chrysalis';
        } else {
            // Configuración para la conexión remota
            $dbHost = 'smartpuntogob.mx';  // Reemplaza con el host remoto
            $dbUser = 'smartpunt_chrysalis';  // Reemplaza con el usuario remoto
            $dbPassword = 'remoto';  // Reemplaza con la contraseña remota
            $dbName = 'remoto';  // Reemplaza con el nombre de la base de datos remota
        }

        // Usar try-catch para manejar posibles errores en la conexión
        try {
            $this->conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }

            echo "Conexión exitosa";
        } catch (Exception $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function createTicket($module, $description)
    {
        $module = $this->conn->real_escape_string($module);
        $description = $this->conn->real_escape_string($description);

        $sql = "INSERT INTO support_tickets (module, description) VALUES ('$module', '$description')";

        if ($this->conn->query($sql) === TRUE) {
            return "Ticket creado con éxito";
        } else {
            return "Error al crear el ticket: " . $this->conn->error;
        }
    }

    public function createVenta($id_comisionista, $id_cliente, $monto_total)
    {
        $sql = "INSERT INTO ventas (id_comisionista, id_cliente, monto_total, fecha_venta) 

        VALUES ('$id_comisionista', '$id_cliente', '$monto_total', NOW())";

        if ($this->conn->query($sql) === TRUE) {

            echo "Venta registrada con éxito";
        } else {

            echo "Error al registrar la venta: " . $this->conn->error;
        }
    }

    public function get_customers()
    {
        $result = $this->conn->query("SELECT agenda.*, comisionistas.* 
                                FROM agenda 
                                INNER JOIN comisionistas ON agenda.comisionista_id = comisionistas.id;");

        $agendas = array();

        while ($row = $result->fetch_assoc()) {
            $agendas[] = $row;
        }

        echo json_encode($agendas);
    }

    public function editar_prospecto($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            // Obtiene el ID del prospecto y el nuevo estado del cuerpo de la solicitud
            $id = $_GET['id']; /// ***
            $nuevoEstado = json_decode(file_get_contents('php://input'), true)['status'];

            $sql = "UPDATE prospectos SET status='$nuevoEstado' WHERE id=$id";

            if ($this->conn->query($sql) === TRUE) {
                echo "Estado actualizado correctamente";
            } else {
                echo "Error al actualizar el estado: " . $conn->error;
            }
        }
    }

    public function employee_report()
    {
        $comisionista_id = $_GET['comisionista_id'];

        // Consulta para obtener el total de clientes registrados por mes
        $sql_mes = "SELECT MONTH(fecha_creacion) AS mes, COUNT(*) AS total_mes
                    FROM clientes
                    WHERE comisionista_id = $comisionista_id
                    GROUP BY mes";

        $result_mes = $this->conn->query($sql_mes);

        // Consulta para obtener el total de clientes registrados por día
        $sql_dia = "SELECT DATE(fecha_creacion) AS dia, COUNT(*) AS total_dia
                    FROM clientes
                    WHERE comisionista_id = $comisionista_id
                    AND DATE(fecha_creacion) = CURDATE()
                    GROUP BY dia;";

        $result_dia = $this->conn->query($sql_dia);

        // Consulta para obtener el total de clientes registrados por semana
        $sql_semana = "SELECT WEEK(fecha_creacion) AS semana, COUNT(*) AS total_semana
                    FROM clientes
                    WHERE comisionista_id = $comisionista_id
                    GROUP BY semana";

        $result_semana = $this->conn->query($sql_semana);

        // Consulta para obtener el total de clientes registrados
        $sql_total = "SELECT COUNT(*) AS total
                    FROM clientes
                    WHERE comisionista_id = $comisionista_id";

        $result_total = $this->conn->query($sql_total);

        // Crear un array asociativo con los resultados
        $response = array(
            "total_mes" => $result_mes->fetch_all(MYSQLI_ASSOC),
            "total_dia" => $result_dia->fetch_all(MYSQLI_ASSOC),
            "total_semana" => $result_semana->fetch_all(MYSQLI_ASSOC),
            "total" => $result_total->fetch_assoc()['total']
        );

        // Devolver la respuesta como JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    }

    public function get_all_dates()
    {
        $result = $this->conn->query("SELECT agenda.*, comisionistas.* 
                                FROM agenda 
                                INNER JOIN comisionistas ON agenda.comisionista_id = comisionistas.id;");

        $agendas = array();

        while ($row = $result->fetch_assoc()) {
            $agendas[] = $row;
        }
        echo json_encode($agendas);
    }

    public function get_all_empleados()
    {
        $sql = "SELECT * FROM comisionistas";

        $resultado = $this->conn->query($sql);

        if ($resultado->num_rows > 0) {
            $comisionistas = array();
            while ($row = $resultado->fetch_assoc()) {
                // Convertir el id a entero
                $row['id'] = (int)$row['id'];
                $comisionistas[] = $row;
            }
            echo json_encode($comisionistas);
        } else {
            echo "No se encontraron comisionistas";
        }
    }

    public function get_clientes()
    {
        // Consulta para obtener todos los clientes con la información de su comisionista
        $result = $this->conn->query("SELECT clientes.*, comisionistas.nombre AS nombre_comisionista 
                                    FROM clientes 
                                    INNER JOIN comisionistas ON clientes.comisionista_id = comisionistas.id");

        $clientes = array();

        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }

        echo json_encode($clientes);
    }

    public function get_clientes_all($page, $pageSize)
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
        $offset = ($page - 1) * $pageSize;

        $sql = "SELECT clientes.*, comisionistas.nombre AS nombre_comisionista 
                FROM clientes 
                INNER JOIN comisionistas ON clientes.comisionista_id = comisionistas.id
                ORDER BY clientes.fecha_creacion DESC
                LIMIT $pageSize OFFSET $offset";

        $resultado = $this->conn->query($sql);

        if ($resultado->num_rows > 0) {

            $clientes = array();

            while ($row = $resultado->fetch_assoc()) {
                // Convertir el id a entero
                $row['id'] = (int)$row['id'];
                $clientes[] = $row;
            }

            echo json_encode($clientes);
        } else {

            echo "No se encontraron clientes";
        }
    }

    public function get_clientes_percomisionista($comisionista_id)
    {
        // Verificamos si se recibió el ID del comisionista
        if (isset($_GET['comisionista_id'])) {
            $comisionista_id = $_GET['comisionista_id'];

            // Consulta para obtener los clientes con estado 'En proceso' asociados al comisionista
            $result = $this->conn->query("SELECT * FROM clientes
            WHERE comisionista_id = $comisionista_id AND status = 'En proceso'");

            $clientes = array();

            while ($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }

            echo json_encode($clientes);
        } else {
            echo "No se proporcionó el ID del comisionista.";
        }
    }

    public function get_current_location()
    {
        $comisionista_id = $_GET['comisionista_id'];

        // Consulta para obtener las últimas localizaciones
        $sql = "SELECT latitud, longitud, fecha_de_creacion 
                FROM localizacion 
                WHERE comisionista_id = $comisionista_id 
                ORDER BY fecha_de_creacion DESC LIMIT 5";

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            // Crear un array para almacenar los resultados

            $localizaciones = array();
            while ($row = $result->fetch_assoc()) {
                // Limpiar los datos antes de agregarlos al array
                $latitud = isset($row['latitud']) ? floatval($row['latitud']) : null;
                $longitud = isset($row['longitud']) ? floatval($row['longitud']) : null;
                $fecha_creacion = isset($row['fecha_de_creacion']) ? $row['fecha_de_creacion'] : null;

                // Agregar al array
                $localizaciones[] = array(
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'fecha_de_creacion' => $fecha_creacion
                );
            }

            // Devolver el resultado como JSON
            echo json_encode($localizaciones);
        } else {
            echo "0 resultados";
        }
    }

    public function get_dates($comisionista_id)
    {
        // Verificamos si se recibió el ID del comisionista
        if (isset($_GET['comisionista_id'])) {
            $comisionista_id = $_GET['comisionista_id'];

            // Consulta para obtener las citas del comisionista
            $result = $this->conn->query("SELECT * FROM agenda WHERE comisionista_id = $comisionista_id");

            if ($result) {
                // Convertimos el resultado a un array asociativo y lo codificamos como JSON
                $citas = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode($citas);
            } else {
                echo "Error al obtener las citas: " . $conn->error;
            }
        } else {
            echo "No se proporcionó el ID del comisionista.";
        }
    }

    public function get_geolocalization($comisionista_id, $latitud, $longitud)
    {
        // Preparar y ejecutar la consulta para insertar la ubicación
        $sql = "INSERT INTO localizacion (comisionista_id, latitud, longitud) 
                VALUES ('$comisionista_id', '$latitud', '$longitud')";

        if ($this->conn->query($sql) === TRUE) {
            echo "Ubicación guardada exitosamente";
        } else {
            echo "Error al guardar la ubicación: " . $conn->error;
        }
    }

    public function get_manager_chats($manager_id_1, $manager_id_2)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener datos del mensaje
            $manager_id_1 = intval($_POST['manager_id_1']);
            $manager_id_2 = intval($_POST['manager_id_2']);

            // Obtener mensajes de la base de datos
            $sql = "SELECT * FROM chats_between_managers 
                    WHERE (manager_id_1 = $manager_id_1 AND manager_id_2 = $manager_id_2) OR (manager_id_1 = $manager_id_2 AND manager_id_2 = $manager_id_1)";
            $result = $this->conn->query($sql);

            if ($result->num_rows > 0) {
                $mensajes = array();
                while ($row = $result->fetch_assoc()) {
                    $mensajes[] = $row;
                }
                echo json_encode($mensajes);
            } else {
                echo json_encode(array());
            }
        } else {
            echo json_encode(array("message" => "Método no permitido"));
        }
    }

    public function get_managers()
    {
        // Consulta para obtener los datos de los managers
        $result = $this->conn->query("SELECT * FROM managers");

        if ($result) {
            $managers = array();

            while ($row = $result->fetch_assoc()) {
                $managers[] = $row;
            }
            echo json_encode($managers);
        } else {
            echo "Error al obtener los datos de los managers: " . $conn->error;
        }
    }

    public function get_message_from_manager()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $manager_id = $data['manager_id'];
        $comisionista_id = $data['comisionista_id'];

        $sql = "SELECT * FROM chat 
                WHERE (manager_id = '$manager_id' AND comisionista_id = '$comisionista_id') OR (manager_id = '$comisionista_id' AND comisionista_id = '$manager_id') ORDER BY created_at";

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            $mensajes = array();

            while ($row = $result->fetch_assoc()) {
                $mensajes[] = $row;
            }

            echo json_encode($mensajes);
        } else {
            echo json_encode(array("message" => "No hay mensajes"));
        }
    }

    public function get_tickets()
    {
        $sql = "SELECT * FROM support_tickets";
        $result = $this->conn->query($sql);

        $tickets = array();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ticket = array(
                    "module" => $row["module"],
                    "description" => $row["description"],
                    "isCompleted" => $row["is_completed"] == 1
                );
                array_push($tickets, $ticket);
            }
        }

        $this->conn->close();

        echo json_encode($tickets);
    }

    public function get_total_clientes()
    {
        // Obtener la fecha de hoy en el formato de MySQL (YYYY-MM-DD)
        $hoy = date("Y-m-d");

        // Consulta SQL para obtener el total de clientes registrados hoy
        $sql = "SELECT COUNT(*) as total_clientes 
                FROM clientes 
                WHERE fecha_creacion >= CURDATE(); ";

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalClientes = $row["total_clientes"];
            echo json_encode(array("total_clientes" => $totalClientes));
        } else {
            echo json_encode(array("total_clientes" => 0));
        }
    }

    public function ventas()
    {
        $sql = "SELECT ventas.*, comisionistas.nombre as nombre_comisionista, clientes.nombre as nombre_cliente
                FROM ventas
                JOIN comisionistas ON ventas.id_comisionista = comisionistas.id
                JOIN clientes ON ventas.id_cliente = clientes.id";

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            $ventas = array();

            while ($row = $result->fetch_assoc()) {
                $ventas[] = $row;
            }
            echo json_encode($ventas);
        } else {
            echo json_encode(array("message" => "No hay ventas"));
        }
    }

    public function get_week_clients()
    {
        // Query para obtener el total de clientes de la semana actual
        $sql = "SELECT COUNT(*) AS total_clientes_semana 
                FROM clientes 
                WHERE YEARWEEK(fecha_creacion, 1) = YEARWEEK(CURDATE(), 1)";

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            // Devuelve el resultado como JSON
            $row = $result->fetch_assoc();
            echo json_encode($row);
        } else {
            echo "0 resultados";
        }
    }

    public function get_weekly_report()
    {
        $consulta = "
        SELECT clientes.*, comisionistas.nombre as nombre_comisionista
        FROM clientes
        JOIN comisionistas ON clientes.comisionista_id = comisionistas.id
        WHERE WEEKOFYEAR(fecha_registro) = WEEKOFYEAR(CURRENT_DATE())
        ";

        $resultado = mysqli_query($conexion, $consulta);

        if (!$resultado) {
            die('Error en la consulta: ' . mysqli_error($conexion));
        }

        $clientes = array();
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $clientes[] = $fila;
        }

        echo json_encode($clientes);
    }

    public function localizador()
    {
        // Endpoint para recibir datos de ubicación
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Obtener datos del cuerpo de la solicitud
            $data = json_decode(file_get_contents("php://input"));

            // Validar y procesar los datos (ajusta según tus necesidades)
            $latitude = $data->latitude;
            $longitude = $data->longitude;
            $comisionista_id = $data->comisionista_id;

            // Insertar datos en la base de datos
            $sql = "INSERT INTO localizacion (latitud, longitud, comisionista_id) 
                    VALUES ('$latitude', '$longitude', '$comisionista_id')";

            if ($this->conn->query($sql) === TRUE) {
                echo json_encode(array("mensaje" => "Datos de ubicación recibidos con éxito."));
            } else {
                echo json_encode(array("mensaje" => "Error al procesar la solicitud: " . $conn->error));
            }
        } else {
            echo json_encode(array("mensaje" => "Método no permitido"));
        }
    }

    public function login($email, $password)
    {
        // Obtener datos del usuario desde la solicitud POST
        $email = $this->conn->real_escape_string($email);
        $password = $this->conn->real_escape_string($password);

        // Consultar la base de datos para verificar las credenciales
        $sql = "SELECT * FROM comisionistas 
                WHERE correo = '$email' AND pwd = '$password'";

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            // Si se encontró una coincidencia, las credenciales son válidas
            $row = $result->fetch_assoc();
            $response = array(
                'success' => true,
                'comisionista_id' => $row['id'],
                'nombre' => $row['nombre']
            );
            echo json_encode($response);
        } else {
            // No se encontró ninguna coincidencia, las credenciales son inválidas
            $response = array('success' => false);
            echo json_encode($response);
        }
    }

    public function login_manager($correo, $contrasena)
    {
        $correo = $this->conn->real_escape_string($correo);
        $contrasena = $this->conn->real_escape_string($contrasena);

        // Consulta para verificar las credenciales
        $sql = "SELECT * FROM managers 
                WHERE correo = '$correo' AND pwd = '$contrasena'";

        $resultado = $this->conn->query($sql);

        if ($resultado->num_rows == 1) {
            // Login exitoso
            $row = $resultado->fetch_assoc();
            $datos_manager = array(
                "id" => $row['id'],
                "nombre" => $row['nombre'],
                "correo" => $row['correo'],
                "cargo" => $row['cargo']
            );
            echo json_encode($datos_manager);
        } else {
            // Credenciales incorrectas
            echo "Error: Usuario o contraseña incorrectos";
        }
    }

    public function register_client($nombre, $apellidos, $correo, $telefono, $comisionista_id, $nombre_negocio, $notas, $sitio_web, $direccion)
    {
        // Verificamos si se recibieron los datos del prospecto

        if (isset($_POST['nombre']) && isset($_POST['apellidos']) && isset($_POST['correo']) && isset($_POST['telefono']) && isset($_POST['comisionista_id'])) {

            // Obtenemos los datos del POST
            $nombre = $this->conn->real_escape_string($nombre);
            $apellidos = $this->conn->real_escape_string($apellidos);
            $correo = $this->conn->real_escape_string($correo);
            $telefono = $this->conn->real_escape_string($telefono);

            $nombre_negocio = $this->conn->real_escape_string($nombre_negocio);
            $notas = $this->conn->real_escape_string($notas);
            $sitio_web = $this->conn->real_escape_string($sitio_web);
            $direccion = $this->conn->real_escape_string($direccion);

            // Consulta para insertar el prospecto en la base de datos
            $result = $this->conn->query("INSERT INTO clientes (nombre, apellidos, correo, telefono, status, comisionista_id, nombre_negocio, notas, sitio_web, direccion) 
            VALUES ('$nombre', '$apellidos', '$correo', '$telefono', 'En Proceso', $comisionista_id, '$nombre_negocio', '$notas', '$sitio_web', '$direccion')");

            if ($result) {

                echo "Cliente registrado con exito.";
            } else {

                echo "Error al registrar el cliente: " . $conn->error;
            }
        } else {

            echo "No se proporcionaron todos los datos del cliente.";
        }
    }

    public function register_date($fecha, $hora, $con_quien, $donde, $comisionista_id)
    {
        // Verificamos si se recibieron los datos necesarios

        if (isset($_POST['fecha']) && isset($_POST['hora']) && isset($_POST['con_quien']) && isset($_POST['donde']) && isset($_POST['comisionista_id'])) {

            // Obtener los datos desde la solicitud POST

            $fecha = $this->conn->real_escape_string($fecha);
            $hora = $this->conn->real_escape_string($hora);
            $con_quien = $this->conn->real_escape_string($con_quien);
            $donde = $this->conn->real_escape_string($donde);

            var_dump($_POST);

            // Preparamos la consulta para insertar los datos

            $stmt = $this->conn->prepare("INSERT INTO agenda (fecha, hora, nombre_cliente, lugar, comisionista_id) VALUES (?, ?, ?, ?, ?)");

            $stmt->bind_param("ssssi", $fecha, $hora, $con_quien, $donde, $comisionista_id);

            // Ejecutamos la consulta

            if ($stmt->execute()) {

                $response = array('success' => true);

                echo json_encode($response);
            } else {

                $response = array('success' => false, 'message' => 'Error al insertar la cita.');

                echo json_encode($response);
            }

            // Cerramos la consulta y la conexion a la base de datos
            $stmt->close();
            $this->conn->close();
        } else {

            $response = array('success' => false, 'message' => 'No se proporcionaron todos los datos necesarios.');

            echo json_encode($response);
        }
    }

    public function report_by_day()
    {
        $fecha_actual = date('Y-m-d');

        $sql = "SELECT clientes.*, comisionistas.nombre as nombre_comisionista
                FROM clientes
                LEFT JOIN comisionistas ON clientes.comisionista_id = comisionistas.id
                WHERE fecha_creacion = '$fecha_actual'";

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            $clientes = array();

            while ($row = $result->fetch_assoc()) {
                // Reemplaza los campos null por "N/A"
                foreach ($row as $key => $value) {
                    if ($value === null) {
                        $row[$key] = "N/A";
                    }
                }
                $clientes[] = $row;
            }

            // Establece la codificación UTF-8
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($clientes, JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE asegura que los caracteres especiales se mantengan en UTF-8
        } else {
            echo "0 resultados";
        }
    }

    public function report_by_month()
    {
        $fecha_actual = date('Y-m-d');
        $primer_dia_mes = date('Y-m-01', strtotime($fecha_actual));
        $ultimo_dia_mes = date('Y-m-t', strtotime($fecha_actual));

        $sql = "SELECT clientes.*, comisionistas.nombre as nombre_comisionista
                FROM clientes
                LEFT JOIN comisionistas ON clientes.comisionista_id = comisionistas.id
                WHERE fecha_creacion BETWEEN '$primer_dia_mes' AND '$ultimo_dia_mes'";

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            $clientes = array();

            while ($row = $result->fetch_assoc()) {
                // Reemplaza los campos null por "N/A"
                foreach ($row as $key => $value) {
                    if ($value === null) {
                        $row[$key] = "N/A";
                    }
                }
                $clientes[] = $row;
            }

            // Establece la codificación UTF-8
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($clientes, JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE asegura que los caracteres especiales se mantengan en UTF-8
        } else {
            echo "0 resultados";
        }
    }

    public function report_by_week()
    {
        $fecha_actual = date('Y-m-d');
        $fecha_inicio_semana = date('Y-m-d', strtotime('monday this week', strtotime($fecha_actual)));
        $fecha_fin_semana = date('Y-m-d', strtotime('sunday this week', strtotime($fecha_actual)));

        $sql = "SELECT clientes.*, comisionistas.nombre as nombre_comisionista
                FROM clientes
                LEFT JOIN comisionistas ON clientes.comisionista_id = comisionistas.id
                WHERE fecha_creacion BETWEEN '$fecha_inicio_semana' AND '$fecha_fin_semana'";

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            $clientes = array();

            while ($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }

            header('Content-Type: application/json');
            echo json_encode($clientes);
        } else {
            echo "0 resultados";
        }
    }

    public function report_total()
    {
        $sql = "SELECT clientes.*, comisionistas.nombre as nombre_comisionista
                FROM clientes
                LEFT JOIN comisionistas ON clientes.comisionista_id = comisionistas.id";

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            $clientes = array();

            while ($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }

            header('Content-Type: application/json');
            echo json_encode($clientes);
        } else {
            echo "0 resultados";
        }
    }

    public function send_message($manager_id_1, $manager_id_2, $mensaje)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Obtener datos del mensaje
            $manager_id_1 = intval($_POST['manager_id_1']);
            $manager_id_2 = intval($_POST['manager_id_2']);
            $mensaje = $this->conn->real_escape_string($mensaje);


            // Insertar mensaje en la base de datos
            $sql = "INSERT INTO chats_between_managers (manager_id_1, manager_id_2, message_text, created_at) 
                VALUES ('$manager_id_1', '$manager_id_2', '$mensaje', NOW())";

            if ($this->conn->query($sql) === TRUE) {

                echo "Mensaje enviado correctamente";
            } else {

                echo "Error al enviar el mensaje: " . $conn->error;
            }
        } else {

            echo "Método no permitido";
        }
    }

    public function send_message_to_manager($comisionista_id, $manager_id, $message_text)
    {
        // Obtener los datos del mensaje
        $message_text = $this->conn->real_escape_string($message_text);

        // Insertar el mensaje en la base de datos
        $sql = "INSERT INTO chat (comisionista_id, manager_id, message_text) VALUES ('$comisionista_id', '$manager_id', '$message_text')";

        if ($this->conn->query($sql) === TRUE) {

            echo "Mensaje enviado con éxito";
        } else {

            echo "Error al enviar el mensaje: " . $conn->error;
            var_dump($sql);
        }
    }


    public function sayHello()
    {
        return "Hola desde MainClass";
    }

    public function __destruct()
    {
        $this->conn->close();
    }
}

// Ejemplo de uso:
$api = new MainClass();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';  // Obtener el valor de 'accion' desde la solicitud POST
    switch ($accion) {
        case 'createTicket':
            // Manejar la solicitud POST para crear un ticket
            $response = $api->createTicket($_POST['module'], $_POST['description']);
            echo $response;
            break;
        case 'createVenta':
            // Manejar la solicitud POST para crear una venta
            $response = $api->createVenta($_POST['id_comisionista'], $_POST['id_cliente'], $_POST['monto_total']);
            echo $response;
            break;
        case 'get_geolocalization':
            // Manejar la solicitud POST para determinar la geolocalizacion
            $response = $api->get_geolocalization($_POST['comisionista_id'], $_POST['latitud'], $_POST['longitud']);
            echo $response;
            break;
        case 'login':
            // Manejar la solicitud POST para crear una sesion para usuarios comunes
            $response = $api->login($_POST['email'], $_POST['password']);
            echo $response;
            break;
        case 'login_manager':
            // Manejar la solicitud POST para crear una sesion para managers
            $response = $api->login_manager($_POST['correo'], $_POST['contrasena']);
            echo $response;
            break;
        case 'register_client':
            // Manejar la solicitud POST para crear un cliente
            $response = $api->register_client(
                $_POST['nombre'],
                $_POST['apellidos'],
                $_POST['correo'],
                $_POST['telefono'],
                $_POST['comisionista_id'],
                $_POST['nombre_negocio'],
                $_POST['notas'],
                $_POST['sitio-web'],
                $_POST['direccion']
            );
            echo $response;
            break;
        case 'register_date':
            // Manejar la solicitud POST para crear una cita
            $response = $api->register_date($_POST['fecha'], $_POST['hora'], $_POST['con_quien'], $_POST['donde'], $_POST['comisionista_id']);
            echo $response;
            break;
        case 'send_message':
            // Manejar la solicitud POST para 
            $response = $api->send_message($_POST['manager_id_1'], $_POST['manager_id_2'], $_POST['mensaje']);
            break;
        case 'send_message_to_manager':
            // Manejar la solicitud POST para 
            $response = $api->send_message_to_manager($_POST['comisionista_id'], $_POST['manager_id'], $_POST['message_text']);
            break;

        default:
            echo "Acción no válida";
            break;
    }
} if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = $api->get_clientes_percomisionista($comisionista_id›);
    echo $response;
}  



//EN POSTMAN ENVIA LA VARIABLE ACCION EN FORM DATA, LLAMA LA VARIABLE ACCION Y ENVIAS EL VALOR
