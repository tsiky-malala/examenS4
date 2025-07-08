<?php
/**
 *  Modèle : remboursement
 *  Contient le détail de chaque échéance mensuelle
 */
require_once __DIR__ . '/../db.php';

class Remboursement
{
    /**
     * Insère une échéance mensuelle.
     */
    public static function create(
        int   $pretId,
        int   $noMois,
        int   $annee,
        int   $mois,
        float $capitalRestant,
        float $amortissement,
        float $interet,
        float $assurance,
        float $mensualiteTotale
    ): void {
        $db = getDB();
        $sql = "INSERT INTO remboursement
                (pret_id, no_mois, annee, mois,
                 capital_restant, amortissement,
                 interet, assurance, mensualite_totale)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db->prepare($sql)->execute([
            $pretId, $noMois, $annee, $mois,
            $capitalRestant, $amortissement,
            $interet, $assurance, $mensualiteTotale
        ]);
    }
}
