# API en PHP con MySQL

Este proyecto proporciona una API simple en PHP que interactúa con una base de datos MySQL. La API incluye operaciones básicas como obtener información y agregar información a través de consultas SQL.

## Requisitos

- PHP 7.x o superior
- Servidor web (por ejemplo, Apache) con soporte para PHP
- Base de datos MySQL

## Configuración

1. Clona este repositorio en tu servidor web.

    ```bash
    git clone https://github.com/tu-usuario/tu-repositorio.git
    ```

2. Configura la base de datos.

    - Crea una base de datos MySQL.
    - Importa el archivo `database.sql` en tu base de datos para crear la tabla necesaria.

3. Modifica la configuración de la base de datos.

    Abre el archivo `MiAPI.php` y actualiza las siguientes líneas con la información de tu base de datos:

    ```php
    $miApi = new MiAPI("localhost", "usuario", "clave", "nombre_base_de_datos");
    ```

## Uso

La clase `MiAPI` proporciona las siguientes funciones:

### 1. Obtener Información

```php
$miApi = new MiAPI("localhost", "usuario", "clave", "nombre_base_de_datos");
$informacion = $miApi->obtenerInformacion();

// $informacion ahora contiene un array con los datos de la base de datos.

Para implementar en Postman:

1. Abre Postman.
2. Selecciona el tipo de solicitud (GET o POST) según la operación que deseas realizar.
3. Ingresa la URL del servidor local donde se encuentra tu archivo PHP (por ejemplo, `http://localhost/api.php`).
4. En el caso de POST, ve a la sección "Body" y proporciona los datos en formato JSON (por ejemplo, `{"info": "Nueva información"}`).
5. Haz clic en "Send" para realizar la solicitud y ver la respuesta.

Recuerda tener tu servidor local en funcionamiento y asegurarte de que el archivo PHP esté accesible desde la URL proporcionada en Postman.
