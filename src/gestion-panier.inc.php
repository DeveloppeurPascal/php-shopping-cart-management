<?php
	// gestion basique de panier d'achat
	// (c) Patrick Prémartin 05-06/2008
	// (c) Olf Software
	//
	// Liste des modifications :
	// 20/05/2008, pprem : création de ce programme
	// 02/06/2008, pprem : modifications diverses
	// 04/06/2008, pprem : modifications diverses
	// 05/06/2008, pprem : modifications diverses
	// 11/06/2008, pprem : modifications diverses
	// 12/06/2008, pprem : modifications diverses
	// 13/06/2008, pprem : modifications diverses
	// 15/06/2008, pprem : modifications diverses
	// 16/06/2008, pprem : modifications diverses
	// 17/06/2008, pprem : modifications diverses
	// 09/11/2008, pprem : correction d'un bogue : la région n'était pas prise en compte dans les coordonnées
	//	27/02/2010, pprem : paramétrage des différents modes de paiement disponibles sur la boutique
	//	27/02/2010, pprem : ajout de Paybox comme mode de paiement
	
	define("GESTION_PANIER_ID","PutYourPrivateTokenForThisWebsite=RandomCharsSuit");
	
	require_once(dirname(__FILE__)."/gestion-panier-fct.inc.php");
	require_once(dirname(__FILE__)."/gestion-panier-param.inc.dist.php");
	require_once(dirname(__FILE__)."/gestion-panier-lib.inc.php");
	
	$op = strtolower(stripslashes(strip_tags($_POST["op"].$_GET["op"])));
	$panier = panier_load();
	$dsp = "";
	$erreur = "";
	if ("add" == $op) // ajout d'un ou plusieurs produits dans le panier
	{
		$panier_modifie = false;
		reset($_POST);
		while (list($key,$value) = each($_POST))
		{
			if (false !== ereg("^ref([0-9]*)$",$key,$indice))
			{
				$ref = html_entity_decode(trim(strip_tags(stripslashes(($_POST["ref".$indice[1]])))));
				$lib = html_entity_decode(trim(strip_tags(stripslashes(($_POST["lib".$indice[1]])))));
				$poids_unitaire = 1.0*$_POST["poids_unitaire".$indice[1]];
				$qte = 1.0*$_POST["qte".$indice[1]];
				$prix_fr = 1.0*$_POST["prix_fr".$indice[1]];
				$prix_cee = 1.0*$_POST["prix_cee".$indice[1]];
				$prix_monde = 1.0*$_POST["prix_monde".$indice[1]];
				$port_inclus = ("O" == strtoupper($_POST["port_inclus".$indice[1]]));
				$url_produit = html_entity_decode(trim(strip_tags(stripslashes(($_POST["url_produit".$indice[1]])))));
				$url_image = html_entity_decode(trim(strip_tags(stripslashes(($_POST["url_image".$indice[1]])))));
				if (gp_checksum_verif($_POST["checksum".$indice[1]],$ref,$lib,$prix_fr,$prix_cee,$prix_monde,($port_inclus?"O":"N"),$poids_unitaire))
				{
					panier_ajout_article($panier,$ref,$lib,$poids_unitaire,$qte,$prix_fr,$prix_cee,$prix_monde,$port_inclus,$url_produit,$url_image);
					$panier_modifie = true;
				}
			}
		}
		if ($panier_modifie)
		{
			panier_save($panier);
		}
		$dsp = "affichage_panier";
	}
	else if ("plusun" == $op) // incrémente la quantité sur un produit
	{
		$ref = html_entity_decode(trim(strip_tags(stripslashes(($_GET["ref"])))));
		if (panier_plusoumoins_article($panier,$ref,1))
		{
			panier_save($panier);
		}
		$dsp = "affichage_panier";
	}
	else if ("moinsun" == $op) // décrémente la quantité sur un produit
	{
		$ref = html_entity_decode(trim(strip_tags(stripslashes(($_GET["ref"])))));
		if (panier_plusoumoins_article($panier,$ref,-1))
		{
			panier_save($panier);
		}
		$dsp = "affichage_panier";
	}
	else if ("del" == $op) // supprime une ligne article
	{
		$ref = html_entity_decode(trim(strip_tags(stripslashes(($_GET["ref"])))));
		if (panier_suppression_article($panier,$ref))
		{
			panier_save($panier);
		}
		$dsp = "affichage_panier";
	}
	else if ("affcoord" == $op) // affichage du formulaire de saisie des coordonnées
	{
		$dsp = "saisie_coordonnees";
	}
	else if ("coord" == $op) // affichage du formulaire de saisie des coordonnées
	{
		$nom = html_entity_decode(trim(strip_tags(stripslashes(($_POST["nom"])))));
		if ("" == $nom)
		{
			$erreur .= "Veuillez indiquer votre nom.\n";
		}
		$prenom = html_entity_decode(trim(strip_tags(stripslashes(($_POST["prenom"])))));
		$adresse1 = html_entity_decode(trim(strip_tags(stripslashes(($_POST["adresse1"])))));
		$adresse2 = "";
		if ("" == $adresse1)
		{
			$adresse1 = html_entity_decode(trim(strip_tags(stripslashes(($_POST["adresse2"])))));
		}
		else
		{
			$adresse2 = html_entity_decode(trim(strip_tags(stripslashes(($_POST["adresse2"])))));
		}
		$adresse3 = "";
		if ("" == $adresse1)
		{
			$adresse1 = html_entity_decode(trim(strip_tags(stripslashes(($_POST["adresse3"])))));
		}
		else if ("" == $adresse2)
		{
			$adresse2 = html_entity_decode(trim(strip_tags(stripslashes(($_POST["adresse3"])))));
		}
		else
		{
			$adresse3 = html_entity_decode(trim(strip_tags(stripslashes(($_POST["adresse3"])))));
		}
		if ("" == $adresse1)
		{
			$erreur .= "Veuillez indiquer votre adresse (numéro, rue, voie, lieu-dit, ...).\n";
		}
		$code_postal = html_entity_decode(trim(strip_tags(stripslashes(($_POST["code_postal"])))));
		$ville = html_entity_decode(trim(strip_tags(stripslashes(($_POST["ville"])))));
		if ("" == $ville)
		{
			$erreur .= "Veuillez indiquer votre ville.\n";
		}
		$region = html_entity_decode(trim(strip_tags(stripslashes(($_POST["region"])))));
		$pays = html_entity_decode(trim(strip_tags(stripslashes(($_POST["pays"])))));
		if ("" == $pays)
		{
			$erreur .= "Veuillez indiquer votre pays.\n";
		}
		$telephone = html_entity_decode(trim(strip_tags(stripslashes(($_POST["telephone"])))));
		$email = html_entity_decode(trim(strip_tags(stripslashes(($_POST["email"])))));
		if ("" == $email)
		{
			$erreur .= "Veuillez indiquer votre adresse email.\n";
		}
		else if (($ch = gp_verifie_email($email)) != $email)
		{
			$erreur .= "Votre adresse email n'est pas correcte : ";
			$erreur .= $ch."\n";
		}
		if (panier_coordonnees($panier,$nom,$prenom,$adresse1,$adresse2,$adresse3,$code_postal,$ville,$region,$pays,$telephone,$email))
		{
			if ("" == $erreur)
			{
				panier_save($panier);
			}
		}
		$dsp = ("" != $erreur)?"saisie_coordonnees":"choix_paiement";
	}
	else if ("choix" == $op) // affichage du formulaire de saisie des coordonnées
	{
		$dsp = "choix_paiement";
	}
	else if ("affchq" == $op) // affichage de la page de paiement par chèque
	{
		$dsp = "paiement_cheque1";
	}
	else if ("affchq2" == $op) // affichage de la page de confirmation après paiement par chèque
	{
		if (panier_payer_par_cheque($panier))
		{
			$dsp = "paiement_cheque2";
		}
		else
		{
			$dsp = "paiement_erreur";
		}
	}
	else if ("paypal" == $op) // affichage de la page suite à paiement chez Paypal
	{
		$dsp = "paiement_accepte";
	}
	else if ("cbok" == $op) // affichage de la page d'accord de paiement
	{
		$dsp = "paiement_accepte";
	}
	else if ("cbnok" == $op) // affichage de la page derefus de paiement
	{
		$dsp = "paiement_erreur";
	}
	else // affichage du contenu du panier
	{
		$dsp = "affichage_panier";
	}
	switch ($dsp)
	{
		case "affichage_panier" : // affichage du panier d'achat
			print(panier_affichage_bloc_articles($panier,true));
			break;
		case "saisie_coordonnees" : // affichage du formulaire de saisie des coordonnées
			print(panier_affichage_bloc_coordonnees($panier,true,$erreur));
			break;
		case "choix_paiement" : // affichage de la page de choix du moyen de paiement (récapitulant la commande et les coordonnées du client)
			print(panier_affichage_bloc_articles($panier,false));
//			print ("<p>&nbsp;</p>");
			print(panier_affichage_bloc_coordonnees($panier,false));
//			print ("<p>&nbsp;</p>");

			print("<p align=\"center\" style=\"margin-top: 0; margin-bottom: 0\">&nbsp;</p>");
			print("<table border=\"1\" align=\"center\" width=\"500\"><tr><td align=\"center\" style=\"text-align:center;\">");
			if ($_gp_ATOS_ACTIVE)
			{
				require_once(dirname(__FILE__)."/ATOS.inc.php");
				print (ATOS_call_request ($_COOKIE["panierID"], $panier->commande_code, panier_calcule_montant_total ($panier)));
			}
			if ($_gp_PAYBOX_ACTIVE)
			{
				require_once(dirname(__FILE__)."/PAYBOX.inc.php");
				print (PAYBOX_call_request ($_COOKIE["panierID"], $panier->commande_code, panier_calcule_montant_total ($panier), $panier->client->email));
			}
			if ($_gp_PAYPAL_ACTIVE)
			{
?>
<form action="https://<?= _PAYPAL_URL ?>/cgi-bin/webscr" method="post">
   <input type="hidden" name="cmd" value="_xclick">
   <input type="hidden" name="business" value="<?= $_gp_paypal_email ?>">
   <input type="hidden" name="item_name" value="<?= $_gp_paypal_NomDuSite ?>">
   <input type="hidden" name="item_number" value="1">
   <input type="hidden" name="amount" value="<?= sprintf("%0.2f",panier_calcule_montant_total ($panier)) ?>">
   <input type="hidden" name="no_shipping" value="1">
   <input type="hidden" name="no_note" value="1">
   <input type="hidden" name="currency_code" value="EUR">
   <input type="hidden" name="custom" value="<?= $_COOKIE["panierID"] ?>">
   <input type="hidden" name="notify_url" value="<?= $_gp_site_url."PAYPAL.auto.php" ?>">
   <input type="hidden" name="cancel_return" value="<?= $_gp_site_url.$gp_url_panier."?op=choix" ?>">
   <input type="hidden" name="return" value="<?= $_gp_site_url.$gp_url_panier."?op=paypal" ?>">
   <input type="hidden" name="bn" value="IC_Sample"><?php // bouton "acheter maintenant" https://www.paypal.com/fr_FR/i/btn/x-click-but23.gif ?>
   <input type="image" src="https://www.paypal.com/fr_FR/i/logo/PayPal_mark_60x38.gif" name="submit" alt="Payer ma commande avec mon compte Paypal.">
   <img alt="" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
<?php
			}
			if ($_gp_CHEQUE_ACTIVE)
			{
				print ("<a href=\"".$gp_url_panier."?op=affchq\" title=\"Pour r&eacute;gler votre commande par ch&egrave;que\"><img src=\"gp_images/cheque.jpg\" border=\"0\"</a>");
			}
			print ("<p style=\"text-align:justify\" align=\"justify\">&nbsp;<br />");
			print ("Avant de payer votre commande, veuillez contr&ocirc;ler son contenu et vos coordonn&eacute;es. Apr&egrave;s paiement, en cas d'erreur de votre part, il sera trop tard. Pensez &eacute;galement &agrave; consulter <a href=\"URLToYourGeneralSellingConditions\" title=\"Pour consulter nos conditions générales de vente\" target=\"_blank\">nos conditions g&eacute;n&eacute;rales de vente</a> car en payant, vous les acceptez... et acceptez de vous y conformer.");
			print ("</p>");
			print("</td></tr></table>");
			break;
		case "paiement_cheque1" : // affichage de la page indiquant comment payer par chèque
?>
	<p>Vous avez d&eacute;cid&eacute; de payer votre commande par ch&egrave;que. Vous devrez nous envoyer votre r&egrave;glement par ch&egrave;que bancaire en euros (&eacute;mis depuis une banque fran&ccedil;aise) accompagn&eacute; de votre bon de commande imprim&eacute; (ou recopi&eacute; sur papier libre).</p>
	<p>Si vous &ecirc;tes d'accord, veuillez <a href="<?= $gp_url_panier ?>?op=affchq2" title="Pour confirmer votre r&egrave;glement par ch&egrave;que bancaire et imprimer le r&eacute;capitulatif de la commande">valider votre r&egrave;glement par ch&egrave;que</a>, dans le cas contraire <a href="<?= $gp_url_panier ?>?op=choix" title="Pour choisir un autre mode de paiement">choisissez un autre mode de paiement</a>.</p>
<?php
			break;
		case "paiement_cheque2" : // affichage de la page confirmant que le paiement par chèque de la commande est enregistré
			print(panier_affichage_bloc_articles($panier,false,true));
			print ("<p>&nbsp;</p>");
			print(panier_affichage_bloc_coordonnees($panier,false,"",true));
			print ("<p>&nbsp;</p>");
?>
	<p>Imprimez cette page. R&eacute;digez votre ch&egrave;que de <?= panier_calcule_montant_total ($panier) ?> euros &agrave; l'ordre de <?= $gp_editeur_site ?>. Envoyez le tout &agrave;<br />
	<br />
	<?= nl2br($gp_adresse_editeur) ?></p>
	<p>Le ch&egrave;que doit &ecirc;tre en euros et provenir d'une banque en France.<br />
	Si le ch&egrave;que n'est pas &agrave; votre nom, veuillez joindre une copie de la carte d'identit&eacute; du propri&eacute;taire du ch&egrave;que, la copie de votre carte d'identit&eacute; et une copie de votre quittance de loyer ou de facture d'&eacute;lectricite ou de gaz.</p>
<?php
			break;
		case "paiement_accepte" : // affichage d'un message de confirmation de commande
?>
	<p>Votre commande a bien &eacute;t&eacute; enregistr&eacute;e, vous allez recevoir un email de confirmation.</p>
<?php
			break;
		case "paiement_erreur" : // affichage d'un message d'erreur lié au paiement
?>
	<p>Une erreur s'est produite lors de l'enregistrement de votre r&egrave;glement, veuillez <a href="<?= $gp_url_panier ?>?op=choix" title="Pour choisir un autre mode de paiement">r&eacute;essayer</a>.<br />
	&nbsp;<br />
	Si malgr&eacute; tout le probl&egrave;me persiste, <a href="<?= $gp_url_contact ?>" title="Pour nous contacter">contactez-nous</a>.</p>
<?php
			break;
		default :
			print ("Page en construction");
	}
?>