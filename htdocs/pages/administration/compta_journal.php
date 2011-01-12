<?php

$action = verifierAction(array('lister', 'debit','credit','ajouter', 'modifier'));
$tris_valides = array('Date', 'Evenement', 'catégorie', 'Description');
$sens_valides = array('asc', 'desc');
$smarty->assign('action', $action);

require_once dirname(__FILE__).'/../../../sources/Afup/AFUP_Compta.php';
$compta = new AFUP_Compta($bdd);


if (isset($_GET['id_periode']) && $_GET['id_periode']) 
	$id_periode=$_GET['id_periode'];
else
	$id_periode="";
echo "*".$id_periode."<br>";	 
$id_periode = $compta->obtenirPeriodeEnCours($id_periode);
$smarty->assign('id_periode', $id_periode);
echo "<pre>";
print_r($id_periode);
echo "</pre>";
$listPeriode = $compta->obtenirListPeriode();
$smarty->assign('listPeriode', $listPeriode );

	
if ($action == 'lister') {

	$periode_debut=$listPeriode[$id_periode-1]['date_debut'];
	$periode_fin=$listPeriode[$id_periode-1]['date_fin'];
	
	$journal = $compta->obtenirJournal('',$periode_debut,$periode_fin);
	$smarty->assign('journal', $journal);


//    $smarty->assign('formulaire', genererFormulaire($formulaire));
}
elseif ($action == 'debit') {
	$journal = $compta->obtenirJournal(1,$periode_debut,$periode_fin);
	$smarty->assign('journal', $journal);
}
elseif ($action == 'credit') {
	$journal = $compta->obtenirJournal(2,$periode_debut,$periode_fin);
	$smarty->assign('journal', $journal);
	
} elseif ($action == 'ajouter' || $action == 'modifier') {

  	$formulaire = &instancierFormulaire();
	
   if ($action == 'modifier')
   {
        $champsRecup = $compta->obtenir($_GET['id']);

        $champs['date_saisie']          = $champsRecup['date_ecriture'];
        $champs['idoperation']          = $champsRecup['idoperation'];
        $champs['idcategorie']          = $champsRecup['idcategorie'];
        $champs['nom_frs']          = $champsRecup['nom_frs'];
        $champs['montant']          = $champsRecup['montant'];
        $champs['description']          = $champsRecup['description'];
        $champs['numero']          = $champsRecup['numero'];
        $champs['idmode_regl']          = $champsRecup['idmode_regl'];
        $champs['date_reglement']          = $champsRecup['date_regl'];
        $champs['obs_regl']          = $champsRecup['obs_regl'];
        $champs['idevenement']          = $champsRecup['idevenement'];
        
		$formulaire->setDefaults($champs);
		//$formulaire->setDefaults($champsRecup);
		$formulaire->addElement('hidden', 'id', $_GET['id']);
   }
   
// facture associé à un évènement
   $formulaire->addElement('header'  , ''                         , 'Sélectionner un Journal');
   $formulaire->addElement('select'  , 'idoperation', 'Type d\'opération *', $compta->obtenirListOperations());
   $formulaire->addElement('select'  , 'idevenement', 'Evenement *', $compta->obtenirListEvenements());

//detail facture       
   $formulaire->addElement('header'  , ''                         , 'Détail Facture');
   
//$mois=10;
   $formulaire->addElement('date'    , 'date_saisie'     , 'Date saisie', array('language' => 'fr', 
                                                                                'format'   => 'd F Y',
  																				'minYear' => date('Y')-1, 
  																				'maxYear' => date('Y')+1));
  
  $formulaire->addElement('select'  , 'idcategorie', 'Type de compte *', $compta->obtenirListCategories());
  $formulaire->addElement('text', 'nom_frs', 'Nom fournisseurs' , array('size' => 30, 'maxlength' => 40));
   	$formulaire->addElement('text', 'numero', 'Numero facture' , array('size' => 30, 'maxlength' => 40));
   	$formulaire->addElement('textarea', 'description', 'Description', array('cols' => 42, 'rows' => 5));
	$formulaire->addElement('text', 'montant', 'Montant *' , array('size' => 30, 'maxlength' => 40));

//reglement
   $formulaire->addElement('header'  , ''                         , 'Réglement');
   $formulaire->addElement('select'  , 'idmode_regl', 'Réglement', $compta->obtenirListReglements());
   $formulaire->addElement('date'    , 'date_reglement'     , 'Date', array('language' => 'fr', 
                                                                            'format'   => 'd F Y',
   																			'minYear' => date('Y')-1, 
   																			'maxYear' => date('Y')+1));
   $formulaire->addElement('textarea', 'obs_regl'           , 'Observation', array('cols' => 42, 'rows' => 5));
   

// boutons
    $formulaire->addElement('header'  , 'boutons'                  , '');
    $formulaire->addElement('submit'  , 'soumettre'                , ucfirst($action));

   
    if ($formulaire->validate()) {
		$valeur = $formulaire->exportValues();

$date_ecriture= $valeur['date_saisie']['Y']."-".$valeur['date_saisie']['F']."-".$valeur['date_saisie']['d'] ;
$date_regl=$valeur['date_reglement']['Y']."-".$valeur['date_reglement']['F']."-".$valeur['date_reglement']['d'] ;
       
    	if ($action == 'ajouter') {
   			$ok = $compta->ajouter(
            						$valeur['idoperation'],
            						$valeur['idcategorie'],
            						$date_ecriture,
            						$valeur['nom_frs'],
            						$valeur['montant'],
            						$valeur['description'],
									$valeur['numero'],
									$valeur['idmode_regl'],
									$date_regl,
									$valeur['obs_regl'],
									$valeur['idevenement']
            						);
        } else {
   			$ok = $compta->modifier(
            						$valeur['id'],
            						$valeur['idoperation'],
            						$valeur['idcategorie'],
            						$date_ecriture,
            						$valeur['nom_frs'],
            						$valeur['montant'],
            						$valeur['description'],
									$valeur['numero'],
									$valeur['idmode_regl'],
									$date_regl,
									$valeur['obs_regl'],
									$valeur['idevenement']
            						);
        }

        if ($ok) {
            if ($action == 'ajouter') {
                AFUP_Logs::log('Ajout une écriture ' . $formulaire->exportValue('titre'));
            } else {
                AFUP_Logs::log('Modification une écriture ' . $formulaire->exportValue('titre') . ' (' . $_GET['id'] . ')');
            }
            afficherMessage('l\'écriture a été ' . (($action == 'ajouter') ? 'ajouté' : 'modifié'), 'index.php?page=compta_journal&action=lister');
        } else {
            $smarty->assign('erreur', 'Une erreur est survenue lors de ' . (($action == 'ajouter') ? "l'ajout" : 'la modification') . ' de l\'écriture');
        }
    }
    $smarty->assign('formulaire', genererFormulaire($formulaire));   
}

?>
