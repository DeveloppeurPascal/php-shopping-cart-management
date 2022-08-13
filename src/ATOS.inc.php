<?php
	// gestion basique de panier d'achat
	// (c) Patrick Prémartin 05-06/2008
	// (c) Olf Software
	//
	// Liste des modifications :
	// 15/06/2008, pprem : création du fichier
	// 16/06/2008, pprem : modifications diverses
	
	if (! ("PutYourPrivateTokenForThisWebsite=RandomCharsSuit" == GESTION_PANIER_ID))
	{
		header("location: https://olfsoftware.fr/");
		exit;
	}

	function ATOS_generer_identifiant($taille)
	{
		$id = "";
		for ($j = 0; $j < $taille/5; $j++)
		{
			$num = mt_rand (0,99999);
			for ($i = 0; $i < 5; $i++)
			{
				$id = ($num % 10).$id;
				$num = floor ($num / 10);
			}
		}
		return (substr ($id, 0, $taille));
	}

	function ATOS_call_request ($session_id, $commande_code, $montant, $lang="fr") {
		global $_gp_site_url,$_ATOS_dossier,$_ATOS_merchant_id,$_gp_prefixe_num_commande;
		$parm="merchant_id=".$_ATOS_merchant_id;
		$parm=$parm." pathfile=".$_ATOS_dossier."pathfile";
		$parm=$parm." merchant_country=fr";
		$lang_autorisees = "/Fr//Ge//En//Ct//Cs//Ko//Da//Sp//Fi//It//Jp//Du//No//Pl//Po//Sw/";
		$lang = ucfirst($lang);
		if (false !== strpos($lang_autorisees,"/".$lang."/"))
		{
			$parm=$parm." language=".strtolower($lang);
		}
		else
		{
			$parm=$parm." language=en";
		}
		$parm=$parm." amount=".sprintf ("%0d", $montant*100);
		$parm=$parm." currency_code=978";
		$parm=$parm." transaction_id=".ATOS_generer_identifiant(6);
		$parm=$parm." customer_ip_address=".$_SERVER["REMOTE_ADDR"];
		$parm=$parm." automatic_response_url=".$_gp_site_url."ATOS.paiementcb-auto.php";
		$parm=$parm." cancel_return_url=".$_gp_site_url."ATOS.paiementcb-retour.php";
		$parm=$parm." normal_return_url=".$_gp_site_url."ATOS.paiementcb-retour.php";
		$parm=$parm." payment_means=CB,1,VISA,1,MASTERCARD,1";
		$parm=$parm." caddie=".$session_id;
		//	  $parm=$parm." order_id=".$commande_code;
		$parm=$parm." order_id=".$_gp_prefixe_num_commande."/".$commande_code;
		$path_bin = $_ATOS_dossier."bin/request";
		$result=exec($path_bin." ".$parm);
		$tableau = explode ("!", $result);
		$code = $tableau[1];
		$error = $tableau[2];
		$message = $tableau[3];
		if (( $code == "" ) && ($error == "" )) {
			return "<center><b><h2>Erreur appel API de paiement.</h2></b></center><p>executable request non trouve ".$path_bin."</p>";
		} else if ($code != 0) {
			return "<center><b><h2>Erreur appel API de paiement.</h2></b></center><p>message erreur : ".$error."</p>";
		} else {
			return $message;
		}
	}
?>