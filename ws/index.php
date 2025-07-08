<?php
// ✅ Gestion du CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// ✅ Répondre directement à la requête OPTIONS (préflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'vendor/autoload.php';
require 'db.php';

require 'routes/fond_routes.php';
require 'routes/type_pret_routes.php';
require 'routes/client_routes.php';
require 'routes/pret_routes.php';
require 'routes/statistique_routes.php';


Flight::start();