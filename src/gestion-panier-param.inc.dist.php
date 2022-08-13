<?php
	// gestion basique de panier d'achat
	// (c) Patrick Pr�martin 05-06/2008
	// (c) Olf Software
	//
	// **********
	// * NE JAMAIS MODIFIER CE FICHIER
	// * 
	// * premi�re installation :
	// * dupliquer gestion-panier-param.inc.dist.php en gestion-panier-param.inc.php et n'y laisser que les param�tres modif�s ou ne devant pas prendre la valeur par d�faut.  toutes les valeurs modifi�es doivent se trouver dans gestion-panier-param.inc.php et en aucun cas �tre modifi�es ici
	// * 
	// * mise � jour :
	// * ce fichier est �cras� � chaque mise � jour et contient les param�tres par d�faut. toutes les valeurs modifi�es doivent se trouver dans gestion-panier-param.inc.php et en aucun cas �tre modifi�es ici
	// **********
	//
	// Liste des modifications :
	//	20/05/2008, pprem : cr�ation de ce programme
	//	02/06/2008, pprem : modifications diverses
	//	04/06/2008, pprem : modifications diverses
	//	11/06/2008, pprem : modifications diverses
	//	12/06/2008, pprem : modifications diverses
	//	15/06/2008, pprem : modifications diverses
	//	16/06/2008, pprem : modifications diverses
	//	17/06/2008, pprem : modifications diverses
	//	13/12/2008, pprem : modifications diverses
	//	27/02/2010, pprem : ajout des param�tres li�s � l'utilisation de Paybox

	if (! ("PutYourPrivateTokenForThisWebsite=RandomCharsSuit" == GESTION_PANIER_ID))
	{
		header("location: https://olfsoftware.fr/");
		exit;
	}

	// **********
	// ***** URL diverses utilis�es pour la gestion du panier et les liens vers les pages de contacts et autres
	$gp_url_contact = "contact.php"; // mettre URL absolue, relative ou mailto:XXX@XXX.XX
	$gp_url_panier = "mon-panier.php"; // mettre URL absolue ou relative
	$gp_url_continuer_achats = "achats.php"; // mettre URL absolue ou relative
	$_gp_site_url = "https://olfsoftware.fr/"; // penser au "/" final, ne pas mettre de page mais juste le domaine et un �ventuel dossier
	
	// **********
	// ***** infos de formatage
	$gp_erreur_class = ""; // class � mettre sur les messages d'erreur lorsqu'il y en a
	$gp_erreur_style = "color:#ff0000;background-color:#ffffff;"; // style � mettre sur les messages d'erreur lorsqu'il y en a (pris en compte si class non renseign�)
	$gp_erreur_color = ""; // color � mettre sur les messages d'erreur lorsqu'il y en a (pris en compte si class+style non renseign�s)

	// **********
	// ***** indiquez le pays par d�faut de chaque commande, cens� �tre le plus utilis�
	$gp_pays_par_defaut = "France";

	// **********
	// ***** personnalisez la liste des pays de destination, tout en choisissant le tarif qui leur sera appliqu�
	// listez les pays en les s�parant par un | (barre verticale, sur la touche 6 des claviers fran�ais)
	// ne mettre que les pays de destination qui seront affich�s dans la liste des pays du formulaire de saisie des coordonn�es du client
	// *** indiquez la liste des pays permettant d'appliquer le tarif "France" (� priori que la France)
	$gp_pays_avec_prix_fr = "France|Guadeloupe|Guyane|Martinique|La R�union|Mayotte|Saint-Pierre-et-Miquelon|Saint-Martin|Saint-Barth�lemy";
	// *** indiquez la liste des pays permettant d'appliquer le tarif "CEE" (� priori les payes de la CEE)
	$gp_pays_avec_prix_cee = "Allemagne|Autriche|Belgique|Bulgarie|Chypre|Danemark|Espagne|Estonie|Finlande|Gr�ce|Hongrie|Irlande|Lettonie|Lituanie|Luxembourg|Malte|Pays-Bas|Pologne|Portugal|R�publique Tch�que|Roumanie|Royaume-Uni|Slovaquie|Slov�nie|Su�de|Suisse|Liechtenstein|Saint Marin|Vatican|Nouvelle-Cal�donie|Polyn�sie fran�aise|Wallis-et-Futuna|Terres australes et antarctiques
fran�aises|Clipperton";
	// *** indiquez la liste des pays permettant d'appliquer le tarif "monde"
	$gp_pays_avec_prix_monde = "Etats-Unis|Canada|B�nin|Burkina Faso|Cameroun|Centrafrique|Comores|R�publique du Congo|C�te d'Ivoire|Djibouti|Gabon|R�publique de Guin�e|Madagascar|Mali|Mauritanie|Niger|S�n�gal|Tchad|Togo|Tunisie|Japon|Chine|Russie";

	unset($pays_libelle_abrege);
	$pays_libelle_abrege["France"]="FR";
	
	// **********
	// ***** personnalisez les listes de prix pour les articles � l'unit�, n'incluant pas les frais de port
	// indiquer en cl� le nombre d'exemplaires, le montant ou le poids � partir duquel s'applique le tarif, la valeur �tant le prix en euros
	// une "*" dans le prix indique de bloquer la commande avec un "nous contacter"
	//
	// *** choisissez le type de calcul des frais de port
	define ('_PORT_PAR_POIDS','1');
	define ('_PORT_PAR_MONTANT','2');
	define ('_PORT_PAR_QUANTITE','3');
	$gp_bareme_type = _PORT_PAR_POIDS;
	// *** � destination des pays pr�sents dans $gp_pays_avec_prix_fr
	unset($gp_bareme_prix_fr);
	$gp_bareme_prix_fr[0] = 0;
	$gp_bareme_prix_fr[10] = 1;
	$gp_bareme_prix_fr[51] = 3;
	$gp_bareme_prix_fr[281] = 5;
	$gp_bareme_prix_fr[561] = 6;
	$gp_bareme_prix_fr[1061] = 7.7;
	$gp_bareme_prix_fr[2031] = 8.5;
	$gp_bareme_prix_fr[3001] = "*"; // nous contacter � partir de 3 Kg
	// *** � destination des pays pr�sents dans $gp_pays_avec_prix_cee
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
	$gp_bareme_prix_cee[2001] = "*"; // nous contacter � partir de 2 Kg
	// *** � destination des pays pr�sents dans $gp_pays_avec_prix_monde
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
	$gp_bareme_prix_monde[2001] = "*"; // nous contacter � partir de 2 Kg

	// **********
	// ***** param�tres li�s � la validation de la commande
	$gp_email_contact = "contact@olfsoft.com"; // pour recevoir les messages de contact, sert ausi en tant qu'exp�diteur des emails de r�cap commande
	$gp_email_ticket_de_caisse = "boutique@olfsoft.com"; // pour r�ceptionner les r�capitulatifs de commande et les tickets de confirmation de paiement (CB, Paypal, ...)
	$gp_email_prefixe = "[TestGestionPanier] ";

	// **********
	//	permet d'afficher ou pas les boutons de paiement li�s � PAYPAL
	$_gp_CHEQUE_ACTIVE = false;
	
	// **********
	// ***** param�tres li�s au paiement par Paypal
	//define ('_PAYPAL_URL','www.sandbox.paypal.com');
	define ('_PAYPAL_URL','www.paypal.com');
	$_gp_paypal_email = "patric_1213607360_biz@olfsoft.com"; // test account
	$_gp_paypal_NomDuSite = "Ma boutique en ligne";
	//	permet d'afficher ou pas les boutons de paiement li�s � PAYPAL
	$_gp_PAYPAL_ACTIVE = false;
	
	// **********
	// ***** param�tres li�s au paiement (ATOS SIPS : SOGENACTIF, WEBAFFAIRES, ...)
	// emplacement des programmes ATOS et certificats
	$_ATOS_dossier = dirname(__FILE__)."/sips/";
	// indiquer votre num�ro de commer�ant (correspondant � un fichier "certif")
	$_ATOS_merchant_id = "014213245611111"; // boutique de test ATOS - SIPS
	//	permet d'afficher ou pas les boutons de paiement li�s � ATOS
	$_gp_ATOS_ACTIVE = false;
	
	// **********
	// ***** param�tres li�s � l'�diteur du site
	$gp_editeur_site = "OLF SOFTWARE";
	$gp_adresse_editeur = "OLF SOFTWARE
14 RUE CHARLES V
75004 PARIS
FRANCE";
	
	// Pr�fixe des commandes, indiqu� dans le backoffice bancaire et sur les tickets de paiement
	$_gp_prefixe_num_commande = "CD";
	
	// **********
	// ***** param�tres � utiliser pour passer par Paybox pour les paiements (adapter PAYBOX.inc.php pour les informations li�es au paiement et aux acc�s � la plateforme Paybox)
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