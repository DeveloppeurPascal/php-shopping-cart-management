<?php
	// gestion basique de panier d'achat
	// (c) Patrick Prémartin 05-06/2008
	// (c) Olf Software
	//
	// **********
	// * NE JAMAIS MODIFIER CE FICHIER
	// * 
	// * première installation :
	// * dupliquer gestion-panier-param.inc.dist.php en gestion-panier-param.inc.php et n'y laisser que les paramètres modifés ou ne devant pas prendre la valeur par défaut.  toutes les valeurs modifiées doivent se trouver dans gestion-panier-param.inc.php et en aucun cas être modifiées ici
	// * 
	// * mise à jour :
	// * ce fichier est écrasé à chaque mise à jour et contient les paramètres par défaut. toutes les valeurs modifiées doivent se trouver dans gestion-panier-param.inc.php et en aucun cas être modifiées ici
	// **********
	//
	// Liste des modifications :
	//	20/05/2008, pprem : création de ce programme
	//	02/06/2008, pprem : modifications diverses
	//	04/06/2008, pprem : modifications diverses
	//	11/06/2008, pprem : modifications diverses
	//	12/06/2008, pprem : modifications diverses
	//	15/06/2008, pprem : modifications diverses
	//	16/06/2008, pprem : modifications diverses
	//	17/06/2008, pprem : modifications diverses
	//	13/12/2008, pprem : modifications diverses
	//	27/02/2010, pprem : ajout des paramètres liés à l'utilisation de Paybox

	if (! ("PutYourPrivateTokenForThisWebsite=RandomCharsSuit" == GESTION_PANIER_ID))
	{
		header("location: https://olfsoftware.fr/");
		exit;
	}

	// **********
	// ***** URL diverses utilisées pour la gestion du panier et les liens vers les pages de contacts et autres
	$gp_url_contact = "contact.php"; // mettre URL absolue, relative ou mailto:XXX@XXX.XX
	$gp_url_panier = "mon-panier.php"; // mettre URL absolue ou relative
	$gp_url_continuer_achats = "achats.php"; // mettre URL absolue ou relative
	$_gp_site_url = "https://olfsoftware.fr/"; // penser au "/" final, ne pas mettre de page mais juste le domaine et un éventuel dossier
	
	// **********
	// ***** infos de formatage
	$gp_erreur_class = ""; // class à mettre sur les messages d'erreur lorsqu'il y en a
	$gp_erreur_style = "color:#ff0000;background-color:#ffffff;"; // style à mettre sur les messages d'erreur lorsqu'il y en a (pris en compte si class non renseigné)
	$gp_erreur_color = ""; // color à mettre sur les messages d'erreur lorsqu'il y en a (pris en compte si class+style non renseignés)

	// **********
	// ***** indiquez le pays par défaut de chaque commande, censé être le plus utilisé
	$gp_pays_par_defaut = "France";

	// **********
	// ***** personnalisez la liste des pays de destination, tout en choisissant le tarif qui leur sera appliqué
	// listez les pays en les séparant par un | (barre verticale, sur la touche 6 des claviers français)
	// ne mettre que les pays de destination qui seront affichés dans la liste des pays du formulaire de saisie des coordonnées du client
	// *** indiquez la liste des pays permettant d'appliquer le tarif "France" (à priori que la France)
	$gp_pays_avec_prix_fr = "France|Guadeloupe|Guyane|Martinique|La Réunion|Mayotte|Saint-Pierre-et-Miquelon|Saint-Martin|Saint-Barthélemy";
	// *** indiquez la liste des pays permettant d'appliquer le tarif "CEE" (à priori les payes de la CEE)
	$gp_pays_avec_prix_cee = "Allemagne|Autriche|Belgique|Bulgarie|Chypre|Danemark|Espagne|Estonie|Finlande|Grèce|Hongrie|Irlande|Lettonie|Lituanie|Luxembourg|Malte|Pays-Bas|Pologne|Portugal|République Tchèque|Roumanie|Royaume-Uni|Slovaquie|Slovénie|Suède|Suisse|Liechtenstein|Saint Marin|Vatican|Nouvelle-Calédonie|Polynésie française|Wallis-et-Futuna|Terres australes et antarctiques
françaises|Clipperton";
	// *** indiquez la liste des pays permettant d'appliquer le tarif "monde"
	$gp_pays_avec_prix_monde = "Etats-Unis|Canada|Bénin|Burkina Faso|Cameroun|Centrafrique|Comores|République du Congo|Côte d'Ivoire|Djibouti|Gabon|République de Guinée|Madagascar|Mali|Mauritanie|Niger|Sénégal|Tchad|Togo|Tunisie|Japon|Chine|Russie";

	unset($pays_libelle_abrege);
	$pays_libelle_abrege["France"]="FR";
	
	// **********
	// ***** personnalisez les listes de prix pour les articles à l'unité, n'incluant pas les frais de port
	// indiquer en clé le nombre d'exemplaires, le montant ou le poids à partir duquel s'applique le tarif, la valeur étant le prix en euros
	// une "*" dans le prix indique de bloquer la commande avec un "nous contacter"
	//
	// *** choisissez le type de calcul des frais de port
	define ('_PORT_PAR_POIDS','1');
	define ('_PORT_PAR_MONTANT','2');
	define ('_PORT_PAR_QUANTITE','3');
	$gp_bareme_type = _PORT_PAR_POIDS;
	// *** à destination des pays présents dans $gp_pays_avec_prix_fr
	unset($gp_bareme_prix_fr);
	$gp_bareme_prix_fr[0] = 0;
	$gp_bareme_prix_fr[10] = 1;
	$gp_bareme_prix_fr[51] = 3;
	$gp_bareme_prix_fr[281] = 5;
	$gp_bareme_prix_fr[561] = 6;
	$gp_bareme_prix_fr[1061] = 7.7;
	$gp_bareme_prix_fr[2031] = 8.5;
	$gp_bareme_prix_fr[3001] = "*"; // nous contacter à partir de 3 Kg
	// *** à destination des pays présents dans $gp_pays_avec_prix_cee
	unset($gp_bareme_prix_cee);
	$gp_bareme_prix_cee[0] = 0;
	$gp_bareme_prix_cee[10] = 0.65;
	$gp_bareme_prix_cee[20] = 1.25;
	$gp_bareme_prix_cee[50] = 1.5;
	$gp_bareme_prix_cee[100] = 4;
	$gp_bareme_prix_cee[250] = 6;
	$gp_bareme_prix_cee[500] = 8.50;
	$gp_bareme_prix_cee[1000] = 11;
	$gp_bareme_prix_cee[1500] = 12.30;
	$gp_bareme_prix_cee[2001] = "*"; // nous contacter à partir de 2 Kg
	// *** à destination des pays présents dans $gp_pays_avec_prix_monde
	unset($gp_bareme_prix_monde);
	$gp_bareme_prix_monde[0] = 0;
	$gp_bareme_prix_monde[10] = 0.85;
	$gp_bareme_prix_monde[20] = 1.7;
	$gp_bareme_prix_monde[50] = 2.3;
	$gp_bareme_prix_monde[100] = 5.5;
	$gp_bareme_prix_monde[250] = 7.2;
	$gp_bareme_prix_monde[500] = 10.5;
	$gp_bareme_prix_monde[1000] = 14;
	$gp_bareme_prix_monde[1500] = 16.50;
	$gp_bareme_prix_monde[2001] = "*"; // nous contacter à partir de 2 Kg

	// **********
	// ***** paramètres liés à la validation de la commande
	$gp_email_contact = "contact@olfsoft.com"; // pour recevoir les messages de contact, sert ausi en tant qu'expéditeur des emails de récap commande
	$gp_email_ticket_de_caisse = "boutique@olfsoft.com"; // pour réceptionner les récapitulatifs de commande et les tickets de confirmation de paiement (CB, Paypal, ...)
	$gp_email_prefixe = "[TestGestionPanier] ";

	// **********
	//	permet d'afficher ou pas les boutons de paiement liés à PAYPAL
	$_gp_CHEQUE_ACTIVE = false;
	
	// **********
	// ***** paramètres liés au paiement par Paypal
	//define ('_PAYPAL_URL','www.sandbox.paypal.com');
	define ('_PAYPAL_URL','www.paypal.com');
	$_gp_paypal_email = "patric_1213607360_biz@olfsoft.com"; // test account
	$_gp_paypal_NomDuSite = "Ma boutique en ligne";
	//	permet d'afficher ou pas les boutons de paiement liés à PAYPAL
	$_gp_PAYPAL_ACTIVE = false;
	
	// **********
	// ***** paramètres liés au paiement (ATOS SIPS : SOGENACTIF, WEBAFFAIRES, ...)
	// emplacement des programmes ATOS et certificats
	$_ATOS_dossier = dirname(__FILE__)."/sips/";
	// indiquer votre numéro de commerçant (correspondant à un fichier "certif")
	$_ATOS_merchant_id = "014213245611111"; // boutique de test ATOS - SIPS
	//	permet d'afficher ou pas les boutons de paiement liés à ATOS
	$_gp_ATOS_ACTIVE = false;
	
	// **********
	// ***** paramètres liés à l'éditeur du site
	$gp_editeur_site = "OLF SOFTWARE";
	$gp_adresse_editeur = "OLF SOFTWARE
14 RUE CHARLES V
75004 PARIS
FRANCE";
	
	// Préfixe des commandes, indiqué dans le backoffice bancaire et sur les tickets de paiement
	$_gp_prefixe_num_commande = "CD";
	
	// **********
	// ***** paramètres à utiliser pour passer par Paybox pour les paiements (adapter PAYBOX.inc.php pour les informations liées au paiement et aux accès à la plateforme Paybox)
	define("_PAYBOX_PATH", "/home/sips/bin/paybox.cgi");
	$_gp_PAYBOX_ACTIVE = false;
	$PAYBOX_societe = ""; // TST = Test, YourShoppingID = the ID you use to parameter your shop for Paybox system

	// **********
	// A RETIRER DU FICHIER gestion-panier-param.inc.php
	if (file_exists(dirname(__FILE__)."/gestion-panier-param.inc.php"))
	{
		require_once(dirname(__FILE__)."/gestion-panier-param.inc.php");
	}
	// **********
?>