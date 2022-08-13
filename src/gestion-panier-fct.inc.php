<?php
	// gestion basique de panier d'achat
	// (c) Patrick Pr�martin 05-06/2008
	// (c) Olf Software
	//
	// Liste des modifications :
	// 20/05/2008, pprem : cr�ation de ce programme
	// 02/06/2008, pprem : modifications diverses
	// 04/06/2008, pprem : ajout d'une fonction permettant d'initialiser la session (envoi du cookie en haut de source, avant tout envoi de code HTML)
	// 11/06/2008, pprem : ajout de la fonction de contr�le de la validit� d'une adresse de courrier �lectronique

	if (! ("PutYourPrivateTokenForThisWebsite=RandomCharsSuit" == GESTION_PANIER_ID))
	{
		header("location: https://olfsoftware.fr/");
		exit;
	}

	require_once(dirname(__FILE__)."/gestion-panier-lib.inc.php");

	// gp_checksum_get: retourne le code de contr�le li� aux param�tres pass�s
	function gp_checksum_get($ref="",$lib="",$prix_fr=0,$prix_cee=0,$prix_monde=0,$port_inclus="N",$poids_unitaire=0)
	{
		return md5($ref.$lib.$poids_unitaire.$prix_fr.$prix_cee.$prix_monde.$port_inclus."ndhsgetjdu");
	}

	// gp_checksum_verif: contr�le l'exactitude du code de contr�le pass� en fonction des param�tres sp�cifi�s
	function gp_checksum_verif($checksum,$ref="",$lib="",$prix_fr=0,$prix_cee=0,$prix_monde=0,$port_inclus="N",$poids_unitaire=0)
	{
		return (gp_checksum_get($ref,$lib,$prix_fr,$prix_cee,$prix_monde,$port_inclus,$poids_unitaire) == $checksum);
	}
	
	// gp_panier_est_plein: indique si le panier contient au moins une ligne article
	function gp_panier_est_plein ()
	{
		$panier = panier_load();
		return ((is_array($panier->lignes)) && (count($panier->lignes) > 0));
	}
	
	// gp_session_init: initialise la gestion du panier (cr�e le cookie de session)
	function gp_session_init ()
	{
		$nom_panier =  panier_nom_fichier();
	}
	
	// gp_verifie_email: permet de contr�ler si une adresse de courrier �lectronique est valide, retour l'email si ok, un message d'erreur dans le cas contraire
	function gp_verifie_email($email2)
	{
		$email = strtolower($email2);
		if (strlen($email) < 6) {
		return $email2." : Email trop court";
		}
		if (strlen($email) > 255) {
		return $email2." : Email trop long";
		}
		if (!ereg("@",$email)) {
		return $email2." : Le email n'a pas d'arobase (@)";
		}
		if (preg_match_all("/([^a-zA-Z0-9_\@\.\-])/i", $email, $trouve)) {
		return $email2." :  caract�re(s) interdit dans un email (".implode(", ",$trouve[0]).").";
		}
		if (!preg_match("/^([a-zA-Z0-9_]|\\-|\\.)+@(([a-zA-Z0-9_]|\\-)+\\.)+[a-zA-Z]{2,4}\$/i", $email)) {
		return $email2." : n'est pas un email valide.";
		}
		list($compte,$domaine)=split("@",$email,2);
		if ((function_exists (checkdnsrr)) && (!checkdnsrr($domaine,"MX"))) {
		return $email2." : Ce domaine ($domaine) n'accepte pas les emails";
		}
		return $email2;
	}
?>