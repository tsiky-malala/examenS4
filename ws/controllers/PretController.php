<?php
require_once __DIR__ . '/../models/Pret.php';
require_once __DIR__ . '/../lib/PdfGenerator.php';

class PretController
{
    public static function getAll()      { Flight::json(Pret::getAll()); }
    public static function create()      {
        $id = Pret::create(Flight::request()->data);
        Flight::json(['id'=>$id,'message'=>'Prêt simulé']);
    }
    public static function validate($id) {
        Pret::validate((int)$id);
        Flight::json(['id'=>$id,'message'=>'Prêt validé']);
    }
    public static function pdf($id)      {
        try {
            $bytes = PdfGenerator::build((int)$id);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename=pret_'.$id.'.pdf');
            echo $bytes;
        } catch(Exception $e) {
            Flight::halt(404,$e->getMessage());
        }
    }
}
