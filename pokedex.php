<?php
// ==========================================
// 1. CONFIGURACIÓN Y CONEXIÓN BASE DE DATOS
// ==========================================
$host = 'localhost';
$db   = 'pokedex_clase';
$user = 'root'; 
$pass = '';     

try {
    // Nos conectamos
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Creamos la tabla automáticamente si no existe (para que no tengas que usar SQL manual)
    $sql_tabla = "CREATE TABLE IF NOT EXISTS pokemones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero_pokedex INT NOT NULL,
        nombre VARCHAR(50) NOT NULL,
        imagen VARCHAR(255) NOT NULL
    )";
    $pdo->exec($sql_tabla);

} catch (PDOException $e) {
    die("<h1>Error de conexión:</h1> " . $e->getMessage() . "<br>Asegúrate de crear la base de datos 'pokedex_clase' en phpMyAdmin primero.");
}

// ==========================================
// 2. LÓGICA DE IMPORTACIÓN (SOLO SI ES NECESARIO)
// ==========================================

// Contamos cuántos pokemones hay guardados
$stmt = $pdo->query("SELECT COUNT(*) FROM pokemones");
$cantidad = $stmt->fetchColumn();

// ¡Aquí está el truco! Solo importamos si la tabla está vacía (0 registros)
if ($cantidad == 0) {
    $api_url = "https://pokeapi.co/api/v2/pokemon?limit=150";
    $json_data = file_get_contents($api_url);
    $data = json_decode($json_data, true);

    if (isset($data['results'])) {
        $insertStmt = $pdo->prepare("INSERT INTO pokemones (numero_pokedex, nombre, imagen) VALUES (:num, :nom, :img)");
        
        foreach ($data['results'] as $index => $pokemon) {
            $numero = $index + 1;
            $nombre = ucfirst($pokemon['name']);
            $imagen = "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/$numero.png";

            $insertStmt->execute([
                ':num' => $numero,
                ':nom' => $nombre,
                ':img' => $imagen
            ]);
        }
        // Recargamos la página para mostrar los datos recién guardados
        header("Refresh:0"); 
        exit;
    }
}

// ==========================================
// 3. CONSULTA PARA MOSTRAR DATOS
// ==========================================
$consulta = $pdo->query("SELECT * FROM pokemones");
$pokemones = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pokedex Clase - Unificado</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #efefef; text-align: center; margin: 0; padding: 20px; }
        h1 { color: #333; }
        .contenedor {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .tarjeta {
            background: white;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            border: 1px solid #ddd;
        }
        .tarjeta:hover { transform: scale(1.05); border-color: #ffcb05; }
        .tarjeta img { width: 100%; max-width: 120px; height: auto; background-color: #f9f9f9; border-radius: 50%; padding: 10px;}
        .numero { color: #999; font-weight: bold; font-size: 0.9em; margin-top: 10px; }
        .nombre { font-size: 1.1em; font-weight: bold; color: #333; margin-top: 5px; }
        .estado { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; display: inline-block;}
    </style>
</head>
<body>

    <h1>Pokédex de Clase (API + PHP + SQL)</h1>
    
    <div class="estado">
        Estado de la Base de Datos: <strong><?php echo count($pokemones); ?></strong> Pokemones registrados.
    </div>

    <div class="contenedor">
        <?php foreach ($pokemones as $poke): ?>
            <div class="tarjeta">
                <img src="<?php echo $poke['imagen']; ?>" loading="lazy" alt="<?php echo $poke['nombre']; ?>">
                <div class="numero">#<?php echo str_pad($poke['numero_pokedex'], 3, '0', STR_PAD_LEFT); ?></div>
                <div class="nombre"><?php echo $poke['nombre']; ?></div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>