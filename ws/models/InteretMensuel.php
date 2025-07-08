<?php
require_once __DIR__ . '/../db.php';

/**
 *  Modèle interet_mensuel
 *  — N’enregistre plus rien de nouveau ; sert uniquement aux stats.
 *  — Les agrégats ignorent les prêts non validés.
 */
class InteretMensuel
{
    /*--- création utilisée par Pret::create() (inchangée) ---*/
    public static function create(int $pretId, int $mois, int $annee, float $interet): void
    {
        getDB()->prepare(
            "INSERT INTO interet_mensuel (pret_id, mois, annee, interet)
             VALUES (?, ?, ?, ?)"
        )->execute([$pretId, $mois, $annee, $interet]);
    }

    /*--- agrégat global EF ---*/
    public static function getSumByMonth(int $m0,int $y0,int $m1,int $y1): array
    {
        $sql = "
          SELECT im.annee, im.mois, SUM(im.interet) total
            FROM interet_mensuel im
       INNER JOIN pret p ON im.pret_id = p.id
           WHERE p.validated = 1
             AND (im.annee > :y0 OR (im.annee = :y0 AND im.mois >= :m0))
             AND (im.annee < :y1 OR (im.annee = :y1 AND im.mois <= :m1))
        GROUP BY im.annee, im.mois
        ORDER BY im.annee, im.mois";
        $db = getDB(); $st=$db->prepare($sql); $st->execute([
          ':y0'=>$y0,':m0'=>$m0,':y1'=>$y1,':m1'=>$m1]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /*--- agrégat par client ---*/
    public static function getByClient(
        int $cid,int $m0,int $y0,int $m1,int $y1): array
    {
        $sql = "
          SELECT im.annee, im.mois, SUM(im.interet) total
            FROM interet_mensuel im
       INNER JOIN pret p ON im.pret_id = p.id
           WHERE p.validated = 1
             AND p.client_id = :cid
             AND (im.annee > :y0 OR (im.annee = :y0 AND im.mois >= :m0))
             AND (im.annee < :y1 OR (im.annee = :y1 AND im.mois <= :m1))
        GROUP BY im.annee, im.mois
        ORDER BY im.annee, im.mois";
        $st=getDB()->prepare($sql); $st->execute([
          ':cid'=>$cid,':y0'=>$y0,':m0'=>$m0,':y1'=>$y1,':m1'=>$m1]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
