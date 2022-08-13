<?php
	// gestion basique de panier d'achat
	// (c) Patrick Prémartin 05-06/2008
	// (c) Olf Software
	//
	// Liste des modifications :
	// 15/06/2008, pprem : création du fichier
	// 16/06/2008, pprem : modifications diverses
	// 09/11/2008, pprem : réactivation de la v5 de l'interface et conditionnement de la v6

	define ('db_jfhjlhdgjhoith', 1);
	require_once(dirname(__FILE__)."/admin/param_site.inc.php");
	define("GESTION_PANIER_ID","PutYourPrivateTokenForThisWebsite=RandomCharsSuit");
	require_once(dirname(__FILE__)."/gestion-panier-lib.inc.php");

	$date_heure = time();
	$date_du_jour = date ("Ymd", $date_heure);
	$heure_du_jour = date ("Hns", $date_heure);

	$tableau = explode ("!", exec($_ATOS_dossier."bin/response pathfile=".$_ATOS_dossier."pathfile message=".$_POST["DATA"]));
	$infos_paiement_cb["code"] = $tableau[1];
	$infos_paiement_cb["error"] = $tableau[2];
	$infos_paiement_cb["merchant_id"] = $tableau[3];
	$infos_paiement_cb["merchant_country"] = $tableau[4];
	$infos_paiement_cb["amount"] = $tableau[5];
	$infos_paiement_cb["transaction_id"] = $tableau[6];
	$infos_paiement_cb["payment_means"] = $tableau[7];
	$infos_paiement_cb["transmission_date"] = $tableau[8];
	$infos_paiement_cb["payment_time"] = $tableau[9];
	$infos_paiement_cb["payment_date"] = $tableau[10];
	$infos_paiement_cb["response_code"] = $tableau[11];
	$infos_paiement_cb["payment_certificate"] = $tableau[12];
	$infos_paiement_cb["authorisation_id"] = $tableau[13];
	$infos_paiement_cb["currency_code"] = $tableau[14];
	$infos_paiement_cb["card_number"] = $tableau[15];
	$infos_paiement_cb["cvv_flag"] = $tableau[16];
	$infos_paiement_cb["cvv_response_code"] = $tableau[17];
	$infos_paiement_cb["bank_response_code"] = $tableau[18];
	$infos_paiement_cb["complementary_code"] = $tableau[19];
	if (true) // version 5
	{
		$infos_paiement_cb["return_context"] = $tableau[20];
		$infos_paiement_cb["caddie"] = $tableau[21]; // session_id
		$infos_paiement_cb["receipt_complement"] = $tableau[22];
		$infos_paiement_cb["merchant_language"] = $tableau[23];
		$infos_paiement_cb["language"] = $tableau[24];
		$infos_paiement_cb["customer_id"] = $tableau[25];
		$dum = split ("/", $tableau[26]);
		$infos_paiement_cb["order_id"] = $dum[1]; // le champ order_id contient "xxxx/n° commande"
		$infos_paiement_cb["customer_email"] = $tableau[27];
		$infos_paiement_cb["customer_ip_address"] = $tableau[28];
		$infos_paiement_cb["capture_day"] = $tableau[29];
		$infos_paiement_cb["capture_mode"] = $tableau[30];
		$infos_paiement_cb["data"] = $tableau[31];
	}
	else // version 6
	{
		$infos_paiement_cb["complementary_info"] = $tableau[20]; // ajout à la version 600 de l'API SIPS
		$infos_paiement_cb["return_context"] = $tableau[21];
		$infos_paiement_cb["caddie"] = $tableau[22]; // session_id
		$infos_paiement_cb["receipt_complement"] = $tableau[23];
		$infos_paiement_cb["merchant_language"] = $tableau[24];
		$infos_paiement_cb["language"] = $tableau[25];
		$infos_paiement_cb["customer_id"] = $tableau[26];
		$dum = split ("/", $tableau[27]);
		$infos_paiement_cb["order_id"] = $dum[1]; // le champ order_id contient "xxxx/n° commande"
		$infos_paiement_cb["customer_email"] = $tableau[28];
		$infos_paiement_cb["customer_ip_address"] = $tableau[29];
		$infos_paiement_cb["capture_day"] = $tableau[30];
		$infos_paiement_cb["capture_mode"] = $tableau[31];
		$infos_paiement_cb["data"] = $tableau[32];
	}
	$fp=fopen(dirname(__FILE__)."/temp/logs_commandes-".date("Ymd").".txt", "a");
	fwrite ($fp, "log du ".date("YmdHi")." - paiement par carte bancaire\n");
	if (($infos_paiement_cb["code"] == "") && ($infos_paiement_cb["error"] == ""))
	{
		fwrite ($fp, "erreur appel response\nexecutable response non trouve\n");
	}
	else if ($infos_paiement_cb["code"] != 0)
	{
		fwrite ($fp, "Erreur appel API de paiement.\nmessage erreur : ".$infos_paiement_cb["error"]."\n");
	}
	else
	{
		if ($infos_paiement_cb["response_code"] == "00")
		{
			$_COOKIE["panierID"] = $infos_paiement_cb["caddie"];
			$panier = panier_load();
			panier_payer_par_sips($panier,$infos_paiement_cb["transaction_id"],$infos_paiement_cb["authorisation_id"],$infos_paiement_cb["customer_ip_address"],$infos_paiement_cb["transmission_date"],$infos_paiement_cb["payment_date"]);
			fwrite( $fp, "panier=\n".serialize ($panier)."\n");
		}
		fwrite( $fp, "infos_paiement_cb=\n".serialize ($infos_paiement_cb)."\n");
	}
	fwrite( $fp, "-------------------------------------------\n");
	fclose ($fp);
?>