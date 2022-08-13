<?php
	// gestion basique de panier d'achat
	// (c) Patrick Prémartin 05-06/2008
	// (c) Olf Software
	//
	// Liste des modifications :
	//	27/02/2010, pprem : création du fichier à partir de la version Paypal

	define("GESTION_PANIER_ID","PutYourPrivateTokenForThisWebsite=RandomCharsSuit");

	require_once(dirname(__FILE__)."/gestion-panier-lib.inc.php");
	require_once(dirname(__FILE__)."/PAYBOX.inc.php");

	$fp=fopen(dirname(__FILE__)."/temp/logs_commandes-".date("Ymd").".txt", "a");
	fwrite ($fp, "log du ".date("YmdHi")." - paiement par Paybox\n");
	$Pmt = new O_PmtPaybox();
	if ($Pmt->Response(true))
	{
		$session_id = $Pmt->session_id;
		$ref_commande = $Pmt->ref_commande;
		$_COOKIE["panierID"] = $session_id;
		$panier = panier_load();
		if ($Pmt->PmtOK)
		{
			// paiement accepté par la banque  
			$numero_autorisation = $Pmt->NumAuto;
			$CB_reference = $Pmt->CbId;
			$CB_date = $Pmt->CbDate;
			panier_payer_par_paybox($panier,$numero_autorisation,$CB_reference,$CB_date);
			fwrite ($fp, "paiement acepté : auto=".$numero_autorisation." - ref CB=".$CB_reference." - date CB=".$CB_date."\n");
		}
		else
		{
			$session_id = $Pmt->session_id;
			$ref_commande = $Pmt->ref_commande;
			$code_refus = $Pmt->CodeErr;
			fwrite ($fp, "paiement refusé, code_refus=".$code_refus."\n");
		}
		fwrite( $fp, "panier=\n".serialize ($panier)."\n");
	}
	else
	{
		fwrite ($fp, "appel API PAYBOX rejeté\n");
	}
	fwrite( $fp, "POST=\n".serialize ($_POST)."\n");
	fwrite( $fp, "-------------------------------------------\n");
	fclose ($fp);
?>