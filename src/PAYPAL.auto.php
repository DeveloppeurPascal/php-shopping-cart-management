<?php
	// gestion basique de panier d'achat
	// (c) Patrick Prémartin 05-06/2008
	// (c) Olf Software
	//
	// Liste des modifications :
	// 15/06/2008, pprem : création du fichier
	// 16/06/2008, pprem : modifications diverses
	// 17/06/2008, pprem : modifications diverses
	//	08/02/2010, pprem : correction d'un problème avec les arrondis sur le contrôle des montnts : la somme paypal doit être la même que sur le panier, endirect ou sans les centimes

	define("GESTION_PANIER_ID","PutYourPrivateTokenForThisWebsite=RandomCharsSuit");

	require_once(dirname(__FILE__)."/gestion-panier-lib.inc.php");

	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-validate';

	foreach ($_POST as $key => $value)
	{
		$value = urlencode(stripslashes($value));
		$req .= "&$key=$value";
	}

	// post back to PayPal system to validate
	$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
	$header .= "Host: "._PAYPAL_URL.":443\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

	$fsock = fsockopen ('ssl://'._PAYPAL_URL, 443, $errno, $errstr, 30);

	// assign posted variables to local variables
	$panier_id = trim($_POST['custom']);
	$item_name = trim($_POST['item_name']);
	$item_number = trim($_POST['item_number']);
	$payment_status = trim($_POST['payment_status']);
	$payment_amount = trim($_POST['mc_gross']);
	$payment_currency = trim($_POST['mc_currency']);
	$txn_id = trim($_POST['txn_id']);
	$receiver_email = trim($_POST['receiver_email']);
	$payer_email = trim($_POST['payer_email']);

	$fp=fopen(dirname(__FILE__)."/temp/logs_commandes-".date("Ymd").".txt", "a");
	fwrite ($fp, "log du ".date("YmdHi")." - paiement par Paypal\n");
	if (!$fsock)
	{
		fwrite ($fp, "erreur socket\n");
	}
	else
	{
		fputs ($fsock, $header . $req);
		while (!feof($fsock))
		{
			$res = fgets ($fsock, 1024);
			if (strcmp ($res, "VERIFIED") == 0)
			{
				if ("completed" == strtolower($payment_status))
				{
					if (trim($_gp_paypal_email) == $receiver_email)
					{
						$_COOKIE["panierID"] = $panier_id;
						$panier = panier_load();
						if (($payment_amount == ($total = panier_calcule_montant_total ($panier))) || ($payment_amount*100 == round($total*100)))
						{
							panier_payer_par_paypal($panier,$txn_id,$payment_date);
						}
						else
						{
							fwrite ($fp, "erreur payment_amount : ".$payment_amount." au lieu de ".$total."\n");
						}
						fwrite( $fp, "panier=\n".serialize ($panier)."\n");
					}
					else
					{
						fwrite ($fp, "erreur receiver_email : \"".$receiver_email."\" au lieu de \"".$_gp_paypal_email."\"\n");
					}
				}
				else
				{
					fwrite ($fp, "erreur paiement_status : ".$payment_status."\n");
				}
			}
			else if (strcmp ($res, "INVALID") == 0)
			{
				fwrite ($fp, "erreur transaction invalide : ".$req."\n");
			}
		}
		fclose ($fsock);
	}
	fwrite( $fp, "POST=\n".serialize ($_POST)."\n");
	fwrite( $fp, "-------------------------------------------\n");
	fclose ($fp);
?>