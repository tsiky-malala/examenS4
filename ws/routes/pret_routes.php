<?php
require_once __DIR__ . '/../controllers/PretController.php';

Flight::route('GET  /prets',                ['PretController','getAll']);
Flight::route('POST /prets',                ['PretController','create']);
Flight::route('POST /prets/@id/valider',    ['PretController','validate']);
Flight::route('GET  /prets/@id/pdf',        ['PretController','pdf']);
