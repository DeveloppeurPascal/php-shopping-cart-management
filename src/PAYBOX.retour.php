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

	$Pmt = new O_PmtPaybox();
	if ($Pmt->Response(false))
	{
		if ($Pmt->PmtOK)
		{
			header("location: ".$_gp_site_url.$gp_url_panier."?op=cbok");
		}
		else
		{
			header("location: ".$_gp_site_url.$gp_url_panier."?op=cbnok");
		}
	}
	else
	{
		print ("<p><center><b><h2>Erreur appel API de paiement.</h2></b></center></p>");
	}
?>