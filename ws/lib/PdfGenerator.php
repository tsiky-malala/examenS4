<?php
require_once __DIR__ . '/fpdf.php';     // mets ici fpdf.php v1.86
require_once __DIR__ . '/../db.php';

class PdfGenerator
{
    public static function build(int $pretId): string
    {
        $db = getDB();
        $pret = $db->query(
            "SELECT p.*, c.prenom, c.nom, t.libelle, t.taux
               FROM pret p
          JOIN client    c ON p.client_id    = c.id
          JOIN type_pret t ON p.type_pret_id = t.id
              WHERE p.id=$pretId AND p.validated=1"
        )->fetch(PDO::FETCH_OBJ);

        if (!$pret) throw new Exception("Prêt introuvable ou non validé.");

        $total = $db->query("SELECT SUM(interet) FROM interet_mensuel WHERE pret_id=$pretId")
                    ->fetchColumn() ?: 0;

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,utf8_decode("Contrat de prêt n° $pret->id"),0,1,'C');

        $pdf->SetFont('Arial','',12);
        $pdf->Ln(4);
        $pdf->Cell(0,8,utf8_decode("Emprunteur : $pret->prenom $pret->nom"),0,1);
        $pdf->Cell(0,8,utf8_decode("Type : $pret->libelle ($pret->taux %)"),0,1);
        $pdf->Cell(0,8,'Montant : '.number_format($pret->montant,0,'',' ').' Ar',0,1);
        $pdf->Cell(0,8,'Mensualité : '.number_format($pret->mensualite,2,',',' ').' Ar',0,1);
        $pdf->Cell(0,8,"Durée : $pret->duree_mois mois",0,1);
        $pdf->Cell(0,8,"Début : $pret->date_debut",0,1);
        $pdf->Cell(0,8,'Total intérêts+assurance : '.number_format($total,0,'',' ').' Ar',0,1);

        $pdf->Ln(10);
        $txt="Je soussigné(e) $pret->prenom $pret->nom reconnais avoir pris connaissance "
            ."des conditions du présent prêt et m'engage à le rembourser selon l'échéancier.";
        $pdf->MultiCell(0,7,utf8_decode($txt));

        $pdf->Ln(25);
        $pdf->Cell(0,8,'Signature emprunteur : ______________________',0,1);

        return $pdf->Output('S');
    }
}
