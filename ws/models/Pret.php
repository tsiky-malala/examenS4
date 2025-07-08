<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/InteretMensuel.php';

class Pret
{
    /** Retourne tous les prêts avec infos jointes */
    public static function getAll() {
        $db = getDB();
        return $db->query("
            SELECT p.*, c.prenom, c.nom, t.libelle, t.taux
              FROM pret p
         LEFT JOIN client     c ON p.client_id    = c.id
         LEFT JOIN type_pret  t ON p.type_pret_id = t.id
          ORDER BY p.id")->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Crée un prêt « simulé » (validated = 0) */
    public static function create(array $d) : int {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO pret (client_id,type_pret_id,montant,date_debut,duree_mois,taux_assurance,validated)
            VALUES (:c,:t,:m,:dd,:dur,:ass,0)");
        $stmt->execute([
            ':c'   => $d['client_id'],
            ':t'   => $d['type_pret_id'],
            ':m'   => $d['montant'],
            ':dd'  => $d['date_debut'],
            ':dur' => $d['duree_mois'],
            ':ass' => $d['taux_assurance'] ?? 0
        ]);
        return (int)$db->lastInsertId();
    }

    /** Valide le prêt, calcule mensualité, échéancier, intérêts mensuels */
    public static function validate(int $id) : void {
        $db = getDB();
        $db->beginTransaction();

        // 1. récupérer le prêt + taux
        $p = $db->query("
            SELECT p.*, t.taux
              FROM pret p
        JOIN type_pret t ON p.type_pret_id = t.id
             WHERE p.id = $id FOR UPDATE")->fetch(PDO::FETCH_OBJ);

        if (!$p)        throw new Exception("Prêt $id introuvable", 404);
        if ($p->validated) throw new Exception("Prêt déjà validé", 409);

        // 2. vérifier fonds disponibles
        $fondsTotal  = $db->query("SELECT IFNULL(SUM(montant),0) FROM etablissement_fonds")
                          ->fetchColumn();
        $encours     = $db->query("SELECT IFNULL(SUM(montant),0) FROM pret WHERE validated=1")
                          ->fetchColumn();
        if (($fondsTotal - $encours) < $p->montant)
            throw new Exception("Fonds insuffisants pour valider ce prêt", 409);

        // 3. calcul annuité constante (hors assurance)
        $i   = $p->taux / 100 / 12;                // taux périodique
        $n   = $p->duree_mois;
        $A   = $i ? $p->montant * $i / (1 - (1+$i)**(-$n)) : ($p->montant/$n);
        $ass = ($p->taux_assurance ?? 0) / 100 / 12;

        // 4. mettre à jour la mensualité et validated
        $db->prepare("UPDATE pret SET mensualite=?, validated=1 WHERE id=?")
           ->execute([$A, $id]);

        // 5. (re)générer échéancier & table interet_mensuel
        $db->prepare("DELETE FROM remboursement WHERE pret_id=?")->execute([$id]);
        $db->prepare("DELETE FROM interet_mensuel WHERE pret_id=?")->execute([$id]);

        $capital = $p->montant;
        $date    = new DateTime($p->date_debut);
        for ($k=1;$k<=$n;$k++) {
            $interet      = $capital * $i;
            $amort        = $A - $interet;
            $capital     -= $amort;
            $assurance    = $p->montant * $ass;
            $mensTotale   = $A + $assurance;

            // insert remboursement
            $db->prepare("
                INSERT INTO remboursement
                (pret_id,no_mois,annee,mois,capital_restant,amortissement,interet,assurance,mensualite_totale)
                VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([$id,$k,$date->format('Y'),$date->format('n'),
                          max(0,$capital),$amort,$interet,$assurance,$mensTotale]);

            // interet_mensuel (pour stats)
            $db->prepare("
                INSERT INTO interet_mensuel (pret_id,annee,mois,interet)
                VALUES (?,?,?,?)")
               ->execute([$id,$date->format('Y'),$date->format('n'),$interet+$assurance]);

            // mois suivant
            $date->modify('+1 month');
        }

        $db->commit();
    }
}
