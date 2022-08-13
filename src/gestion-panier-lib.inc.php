<?php
	// gestion basique de panier d'achat
	// (c) Patrick Prémartin 05-06/2008
	// (c) Olf Software
	//
	// Liste des modifications :
	// 26/05/2008, pprem : création de ce programme
	// 02/06/2008, pprem : modifications diverses
	// 04/06/2008, pprem : modifications diverses
	// 11/06/2008, pprem : modifications diverses
	// 12/06/2008, pprem : modifications diverses
	// 13/06/2008, pprem : modifications diverses
	// 15/06/2008, pprem : modifications diverses
	// 09/11/2008, pprem : correction d'un bogue : la région n'était pas prise en compte dans les coordonnées
	// 09/11/2008, pprem : correction d'un bogue : les frai de port n'était pas correctement calculés sur le calcul à partir de tranches de montants
	// 10/11/2008, pprem : retrait de la référence produit du message de confirmation, celle-ci n'étant pas celle présente sur les factures finales
	// 08/12/2008, pprem : modification du texte de l'email de confirmation de commande ("euros" au lieu de € et un peu plus de blabla au dessus)
	//	15/12/2008, pprem : modif du message envoyé à l'internaute pour confirmation de sa commande
	//	27/02/2010, pprem : ajout du module de paiement Paybox
	//	25/05/2010, pprem : correction de la fonction "panier_nom_fichier" qui ne concaténait pas le ID aléatoire mais faisait une opération arithmétique dessus !
	//	30/05/2010, pprem : modification de "panier_nom_fichier" pour ne pas mettre de tiret dans les numéros de session
	//	12/09/2010, pprem : correction du nom du site lors des commandes via Paybox et modification de la présentation de la liste des articles commandés dans le mail de récap de commande

	if (! ("PutYourPrivateTokenForThisWebsite=RandomCharsSuit" == GESTION_PANIER_ID))
	{
		header("location: https://olfsoftware.fr/");
		exit;
	}
	
	require_once(dirname(__FILE__)."/gestion-panier-param.inc.dist.php");
	
	// retourne le prix à pratiquer pour un pays spécifié
	function panier_choix_prix($prix_fr,$prix_cee,$prix_monde,$pays)
	{
		global $gp_pays_par_defaut,$gp_pays_avec_prix_fr,$gp_pays_avec_prix_cee,$gp_pays_avec_prix_monde;
		if (0 == $prix_cee)
		{
			$prix_cee = $prix_fr;
		}
		if (0 == $prix_monde)
		{
			$prix_monde = $prix_cee;
		}
		if (("" == $pays) || (! isset($pays)))
		{
			$pays = $gp_pays_par_defaut;
		}
		if (false !== strpos("|".strtolower($gp_pays_avec_prix_fr)."|",strtolower($pays)))
		{
			return $prix_fr;
		}
		else if (false !== strpos("|".strtolower($gp_pays_avec_prix_cee)."|",strtolower($pays)))
		{
			return $prix_cee;
		}
		else if (false !== strpos("|".strtolower($gp_pays_avec_prix_monde)."|",strtolower($pays)))
		{
			return $prix_monde;
		}
		else
		{
			return "*";
		}
	}
	
	// fournit le montant des frais de port liés à un panier.
	function panier_calcule_frais_de_port ($panier)
	{
		global $gp_pays_par_defaut,$gp_pays_avec_prix_fr,$gp_pays_avec_prix_cee,$gp_pays_avec_prix_monde;
		global $gp_bareme_type,$gp_bareme_prix_fr,$gp_bareme_prix_cee,$gp_bareme_prix_monde;
		$pays = $panier->client->pays;
		if (("" == $pays) || (! isset($pays)))
		{
			$pays = $gp_pays_par_defaut;
		}
//print ($pays);exit;
		$nb = 0; // nombre d'article pour lesquels les frais de port ne sont pas inclus
		if (is_array($panier->lignes))
		{
			$lignes = $panier->lignes;
			reset($lignes);
			while (list($key,$value) = each($lignes))
			{
				if (! $value->port_inclus)
				{
//print ("port exclu");exit;
					switch ($gp_bareme_type)
					{
						case _PORT_PAR_POIDS:
//print ("1");exit;
							$nb += $value->qte * $value->poids_unitaire;
							break;
						case _PORT_PAR_MONTANT:
//print ("2");exit;
//print ($value->qte);exit;
//print (panier_choix_prix($value->prix_fr,$value->prix_cee,$value->prix_monde,$pays));exit;
							$nb += $value->qte * panier_choix_prix($value->prix_fr,$value->prix_cee,$value->prix_monde,$pays);
							break;
						case _PORT_PAR_QUANTITE:
//print ("3");exit;
							$nb += $value->qte;
							break;
					}
				}
			}
		}
//print ($nb);exit;
		$port = 0;
		if ($nb > 0)
		{
			if (false !== strpos("|".strtolower($gp_pays_avec_prix_fr)."|",strtolower($pays)))
			{
				$tab_port = $gp_bareme_prix_fr;
			}
			else if (false !== strpos("|".strtolower($gp_pays_avec_prix_cee)."|",strtolower($pays)))
			{
				$tab_port = $gp_bareme_prix_cee;
			}
			else if (false !== strpos("|".strtolower($gp_pays_avec_prix_monde)."|",strtolower($pays)))
			{
				$tab_port = $gp_bareme_prix_monde;
			}
			else
			{
				unset($tab_port);
			}
			if (isset($tab_port))
			{
				reset($tab_port);
				while ((list($key,$value) = each($tab_port)) && ($key <= $nb))
				{
					$port = $value;
				}
			}
			else
			{
				$port = "*";
			}
		}
		return $port;
	}
	
	// fournit le montant total d'un panier.
	function panier_calcule_montant_total ($panier)
	{
		$montant_total = panier_calcule_frais_de_port ($panier);
		if ("*" !== $montant_total)
		{
			if (is_array($panier->lignes))
			{
				reset($panier->lignes);
				while (list($key,$value) = each($panier->lignes))
				{
					$montant_total += $value->qte * panier_choix_prix($value->prix_fr,$value->prix_cee,$value->prix_monde,$panier->client->pays);
				}
			}
		}
		return $montant_total;
	}
	
	// initialisation d'un panier vierge
	function panier_init()
	{
		global $gp_pays_par_defaut;
		unset ($panier);
		$panier->commande_code = time();
		$panier->init = true;
		$panier->lignes = false;
		unset ($panier->client);
		$panier->client->pays = $gp_pays_par_defaut;
		$panier->paiement = false;
		return $panier;
	}

	function panier_nettoyer_chaine ($ch)
	{
		$ch = strtolower($ch);
		$res = "";
		for ($i = 0; $i < strlen ($ch); $i++)
		{
			$c = substr ($ch, $i, 1);
			if ((($c >= "a") && ($c <= "z")) || (($c >= "0") && ($c <= "9")))
			{
				$res .= $c;
			}
		}
		return $res;
	}
	
	// génère un nom de fichier pour le panier et un identifiant de session
	function panier_nom_fichier()
	{
		$id = "";
		if ("" != $_COOKIE["panierID"])
		{
			$id = panier_nettoyer_chaine($_COOKIE["panierID"]);
		}
		if ("" == $id)
		{
			$id = date("YmdHi").panier_nettoyer_chaine(md5(uniqid(mt_rand(),true)));
			setcookie("panierID",$id);
			$_COOKIE["panierID"] = $id;
		}
		$nom_fichier = dirname(__FILE__)."/temp/panier-".$id.".txt";
		return $nom_fichier;
	}
	
	// chargement du panier en cours
	function panier_load()
	{
		$nom_fichier = panier_nom_fichier();
		if ($ch = @file_get_contents($nom_fichier))
		{
			$panier = unserialize($ch);
		}
		else
		{
			$panier = panier_init();
		}
		return $panier;
	}

	// sauvegarde d'un panier
	function panier_save($panier)
	{
		$f = fopen (panier_nom_fichier(),"w");
		flock($f, LOCK_EX);
		fwrite($f,serialize($panier));
		flock($f, LOCK_UN);
		fclose($f);
	}

	// sauvegarde d'une commande réglée
	function panier_commande_save($texte)
	{
		$f = fopen (str_replace("panier-","cde-".date("YmdHis")."-",panier_nom_fichier()),"w");
		flock($f, LOCK_EX);
		fwrite($f,$texte);
		flock($f, LOCK_UN);
		fclose($f);
	}

	// suppression d'un produit du panier
	function panier_suppression_article(&$panier,$ref)
	{
		$cle = 0;
		$trouve = false;
		if (is_array($panier->lignes))
		{
			$lignes = $panier->lignes;
			reset($lignes);
			while ((list($key,$value) = each($lignes)) && (! $trouve))
			{
				if ($ref == $value->ref)
				{
					$cle = $key;
					$trouve = true;
				}
			}
		}
		if ($trouve)
		{
			for ($i = $cle; $i < count($panier->lignes)-1; $i++)
			{
				$panier->lignes[$i] = $panier->lignes[$i+1];
			}
			unset($panier->lignes[count($panier->lignes)-1]);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// modification de la quantité d'un produit dans le panier
	function panier_plusoumoins_article(&$panier,$ref,$qte)
	{
		$cle = 0;
		$trouve = false;
		if (is_array($panier->lignes))
		{
			reset($panier->lignes);
			while ((list($key,$value) = each($panier->lignes)) && (! $trouve))
			{
				if ($ref == $value->ref)
				{
					$cle = $key;
					$trouve = true;
				}
			}
		}
		if ($trouve)
		{
			$panier->lignes[$cle]->qte += $qte;
			if (1 > $panier->lignes[$cle]->qte)
			{
				panier_suppression_article(&$panier,$ref);
			}
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// ajout d'un produit au panier
	function panier_ajout_article(&$panier,$ref,$lib,$poids_unitaire,$qte,$prix_fr,$prix_cee,$prix_monde,$port_inclus,$url_produit,$url_image)
	{
		unset($article);
		$article->ref = $ref;
		$article->lib = $lib;
		$article->qte = $qte;
		$article->poids_unitaire = $poids_unitaire;
		$article->prix_fr = $prix_fr;
		$article->prix_cee = $prix_cee;
		$article->prix_monde = $prix_monde;
		$article->port_inclus = $port_inclus;
		$article->url_produit = $url_produit;
		$article->url_image = $url_image;
		$cle = 0;
		if (is_array($panier->lignes))
		{
			$trouve = false;
			$lignes = $panier->lignes;
			reset($lignes);
			while ((list($key,$value) = each($lignes)) && (! $trouve))
			{
				if ($ref == $value->ref)
				{
					$cle = $key;
					$article->qte += $value->qte;
					$trouve = true;
				}
			}
			if (! $trouve)
			{
				$cle = count($panier->lignes);
			}
		}
		if (0 < $article->qte)
		{
			$panier->lignes[$cle] = $article;
		}
		else
		{
			panier_suppression_article(&$panier,$ref);
		}
		return true;
	}
	
	// modifie les coordonnées du client associé au panier
	function panier_coordonnees(&$panier,$nom,$prenom,$adresse1,$adresse2,$adresse3,$code_postal,$ville,$region,$pays,$telephone,$email)
	{
		$panier->client->nom = $nom;
		$panier->client->prenom = $prenom;
		$panier->client->adresse1 = $adresse1;
		$panier->client->adresse2 = $adresse2;
		$panier->client->adresse3 = $adresse3;
		$panier->client->code_postal = $code_postal;
		$panier->client->ville = $ville;
		$panier->client->region = $region;
		$panier->client->pays = $pays;
		$panier->client->telephone = $telephone;
		$panier->client->email = $email;
		return true;
	}
	
	// affichage du panier : les lignes articles, avec ou sans champs de formulaire pour leur modification
	function panier_affichage_bloc_articles($panier,$editer=false,$visu=false)
	{
		global $gp_url_contact, $gp_url_panier, $gp_url_continuer_achats;
		$res = "";
		$res .= "<p align=\"center\" style=\"margin-top: 0; margin-bottom: 0\">&nbsp;</p>";
		$res .= "<table border=\"1\" align=\"center\" width=\"500\">";
		$res .= "<thead><tr>";
		$res .= "<th>Article</th>";
		$res .= "<th>Quantit&eacute;</th>";
		$res .= "<th>Prix unitaire</th>";
		$res .= "<th>Montant</th>";
		$res .= "</tr></thead><tbody>";
		if (is_array($panier->lignes) && (0 < count($panier->lignes)))
		{
			$ok = true;
			$montant_total = 0;
			for ($i = 0; $i < count($panier->lignes); $i++)
			{
				$res .= "<tr>";
				$res .= "<td align=\"left\">";
				$res .= $panier->lignes[$i]->lib;
				if ($editer)
				{
					$res .= "&nbsp;<a href=\"".$gp_url_panier."?op=del&ref=".urlencode($panier->lignes[$i]->ref)."\" title=\"Supprime cette ligne\">(X)</a>";
				}
				$res .= "</td>";
				$res .= "<td align=\"center\">";
				if ($editer)
				{
					$res .= "<a href=\"".$gp_url_panier."?op=plusun&ref=".urlencode($panier->lignes[$i]->ref)."\" title=\"Plus un\">(+)</a>&nbsp;";
				}
				$res .= sprintf("%0d",$panier->lignes[$i]->qte);
				if ($editer)
				{
					$res .= "&nbsp;<a href=\"".$gp_url_panier."?op=moinsun&ref=".urlencode($panier->lignes[$i]->ref)."\" title=\"Moins un\">(-)</a>";
				}
				$res .= "</td>";
				$prix=panier_choix_prix($panier->lignes[$i]->prix_fr,$panier->lignes[$i]->prix_cee,$panier->lignes[$i]->prix_monde,$panier->client->pays);
				if ("*" !== $prix)
				{
					$res .= "<td align=\"right\">".sprintf("%0.2f",$prix)."</td>";
					$res .= "<td align=\"right\">".sprintf("%0.2f",($panier->lignes[$i]->qte*$prix))."</td>";
					$montant_total += $panier->lignes[$i]->qte*$prix;
				}
				else
				{
					$res .= "<td colspan=\"2\" align=\"center\">".((""!=$gp_url_contact)?"<a href=\"".$gp_url_contact."\" title=\"Pour nous contacter\">":"")."nous consulter".((""!=$gp_url_contact)?"</a>":"")."</td>";
					$ok = false;
				}
				$res .= "</tr>";
			}
			$res .= "<tr><td colspan=\"3\" align=\"right\">Total lignes</td><td align=\"right\">".sprintf("%0.2f",$montant_total)."</td></tr>";
			if ("*" !== ($montant_port=panier_calcule_frais_de_port ($panier)))
			{
				$res .= "<tr><td colspan=\"3\" align=\"right\">Frais de port</td><td align=\"right\">".sprintf("%0.2f",$montant_port)."</td></tr>";
			}
			else
			{
				$res .= "<tr><td colspan=\"3\" align=\"right\">Frais de port</td><td align=\"center\">".((""!=$gp_url_contact)?"<a href=\"".$gp_url_contact."\" title=\"Pour nous contacter\">":"")."nous consulter".((""!=$gp_url_contact)?"</a>":"")."</td></tr>";
				$ok = false;
			}
			$res .= "<tr><td colspan=\"3\" align=\"right\">Total</td><td align=\"right\">".sprintf("%0.2f",$montant_total+$montant_port)."</td></tr>";
		}
		else
		{
			$res .= "<tr><td colspan=\"4\" align=\"center\">Vous n'avez choisi aucun produit pour le moment.</td></tr>";
		}
		if (!$visu)
		{
			$res .= "<tr><td colspan=\"4\" align=\"center\"><a href=\"".$gp_url_continuer_achats."\" title=\"Pour continuer vos achats\">continuer vos achats</a></td></tr>";
			if ($ok && $editer)
			{
				$res .= "<tr><td colspan=\"4\" align=\"center\"><a href=\"".$gp_url_panier."?op=affcoord\" title=\"Pour saisir vos coordonn&eacute;es\">saisir votre adresse</a></td></tr>";
			}
			else if (! $editer)
			{
				$res .= "<tr><td colspan=\"4\" align=\"center\"><a href=\"".$gp_url_panier."\" title=\"Pour modifier votre commande\">modifier votre commande</a></td></tr>";
			}
		}
		$res .= "</tbody></table>";
		return $res;
	}
	
	// affichage du panier : les coordonnées du client, avec ou sans champs de formulaire pour leur modification
	function panier_affichage_bloc_coordonnees($panier,$editer=false,$erreur="",$visu=false)
	{
		global $gp_url_panier,$gp_erreur_class,$gp_erreur_style,$gp_erreur_color,$gp_pays_par_defaut,$gp_pays_avec_prix_fr,$gp_pays_avec_prix_cee,$gp_pays_avec_prix_monde;
		$res = "";
		if ($editer)
		{
			$res .= "<form action=\"".$gp_url_panier."\" method=\"post\"><input type=\"hidden\" name=\"op\" value=\"coord\">";
		}
		$res .= "<p align=\"center\" style=\"margin-top: 0; margin-bottom: 0\">&nbsp;</p>";
		if ("" != $erreur)
		{
			$res .= "<p>";
			if ("" != $gp_erreur_class)
			{
				$res .= "<span class=\"".$gp_erreur_class."\">".nl2br(htmlentities($erreur))."</span>";
			}
			else if ("" != $gp_erreur_style)
			{
				$res .= "<span style=\"".$gp_erreur_style."\">".nl2br(htmlentities($erreur))."</span>";
			}
			else if ("" != $gp_erreur_color)
			{
				$res .= "<font color=\"".$gp_erreur_color."\">".nl2br(htmlentities($erreur))."</font>";
			}
			else
			{
				$res .= "<span style=\"color:#ff0000;background-color:#ffffff;\">".nl2br(htmlentities($erreur))."</span>";
			}
			$res .= "</p>";
		}
		$res .= "<table border=\"1\" align=\"center\" width=\"500\">";
		$res .= "<tbody>";
		$res .= "<tr><th>Votre nom</th><td>";
		if ($editer)
		{
			$res .= "<input type=\"text\" size=\"50\" maxlength=\"50\" name=\"nom\" value=\"".htmlentities($panier->client->nom)."\">";
		}
		else
		{
			$res .= htmlentities($panier->client->nom);
		}
		$res .= "</td></tr>";
		$res .= "<tr><th>Votre pr&eacute;nom</th><td>";
		if ($editer)
		{
			$res .= "<input type=\"text\" size=\"50\" maxlength=\"50\" name=\"prenom\" value=\"".htmlentities($panier->client->prenom)."\">";
		}
		else
		{
			$res .= htmlentities($panier->client->prenom);
		}
		$res .= "</td></tr>";
		$res .= "<tr><th>Votre adresse</th><td>";
		if ($editer)
		{
			$res .= "<input type=\"text\" size=\"50\" maxlength=\"50\" name=\"adresse1\" value=\"".htmlentities($panier->client->adresse1)."\"><br />";
		}
		else
		{
			$res .= htmlentities($panier->client->adresse1);
		}
		if ($editer)
		{
			$res .= "<input type=\"text\" size=\"50\" maxlength=\"50\" name=\"adresse2\" value=\"".htmlentities($panier->client->adresse2)."\"><br />";
		}
		else
		{
			if (isset($panier->client->adresse2) && ("" != $panier->client->adresse2))
			{
				$res .= "<br />".htmlentities($panier->client->adresse2);
			}
		}
/*		if ($editer)
		{
			$res .= "<input type=\"text\" size=\"50\" maxlength=\"50\" name=\"adresse3\" value=\"".htmlentities($panier->client->adresse3)."\">";
		}
		else
		{
			if (isset($panier->client->adresse3) && ("" != $panier->client->adresse3))
			{
				$res .= "<br />".htmlentities($panier->client->adresse3);
			}
		}*/
		$res .= "</td></tr>";
		$res .= "<tr><th>Votre code postal</th><td>";
		if ($editer)
		{
			$res .= "<input type=\"text\" size=\"10\" maxlength=\"10\" name=\"code_postal\" value=\"".htmlentities($panier->client->code_postal)."\">";
		}
		else
		{
			$res .= htmlentities($panier->client->code_postal);
		}
		$res .= "</td></tr>";
		$res .= "<tr><th>Votre ville</th><td>";
		if ($editer)
		{
			$res .= "<input type=\"text\" size=\"50\" maxlength=\"50\" name=\"ville\" value=\"".htmlentities($panier->client->ville)."\">";
		}
		else
		{
			$res .= htmlentities($panier->client->ville);
		}
		$res .= "</td></tr>";
		$res .= "<tr><th>Votre r&eacute;gion</th><td>";
		if ($editer)
		{
			$res .= "<input type=\"text\" size=\"50\" maxlength=\"50\" name=\"region\" value=\"".htmlentities($panier->client->region)."\">";
		}
		else
		{
			$res .= htmlentities($panier->client->region);
		}
		$res .= "</td></tr>";
		$res .= "<tr><th>Votre pays</th><td>";
		if ($editer)
		{
			$liste_pays = explode("|",$gp_pays_avec_prix_fr."|".$gp_pays_avec_prix_cee."|".$gp_pays_avec_prix_monde);
			sort($liste_pays);
			if ("" == $panier->client->pays)
			{
				$panier->client->pays = $gp_pays_par_defaut;
			}
			$res .= "<select name=\"pays\">";
			$pays = "";
			reset ($liste_pays);
			while (list($key,$value)=each($liste_pays))
			{
				if ($pays != $value)
				{
					$pays = $value;
					if ($panier->client->pays == $pays)
					{
						$res .= "<option value=\"".htmlentities($pays)."\" selected=\"selected\">".htmlentities($pays)."</option>";
					}
					else
					{
						$res .= "<option value=\"".htmlentities($pays)."\">".htmlentities($pays)."</option>";
					}
				}
			}
			$res .= "</select>";
		}
		else
		{
			$res .= htmlentities((""!=$panier->client->pays)?$panier->client->pays:$gp_pays_par_defaut);
		}
		$res .= "</td></tr>";
		$res .= "<tr><th>Votre t&eacute;l&eacute;phone</th><td>";
		if ($editer)
		{
			$res .= "<input type=\"text\" size=\"50\" maxlength=\"50\" name=\"telephone\" value=\"".htmlentities($panier->client->telephone)."\">";
		}
		else
		{
			$res .= htmlentities($panier->client->telephone);
		}
		$res .= "</td></tr>";
		$res .= "<tr><th>Votre email</th><td>";
		if ($editer)
		{
			$res .= "<input type=\"text\" size=\"50\" maxlength=\"50\" name=\"email\" value=\"".htmlentities($panier->client->email)."\">";
		}
		else
		{
			$res .= htmlentities($panier->client->email);
		}
		$res .= "</td></tr>";
		if (!$visu)
		{
			if ($editer)
			{
				$res .= "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"enregistrer votre adresse\"></td></tr>";
			}
			else
			{
				$res .= "<tr><td colspan=\"2\" align=\"center\"><a href=\"".$gp_url_panier."?op=affcoord\" title=\"Pour modifier votre adresse\">modifier votre adresse</a></td></tr>";
			}
		}
		$res .= "</tbody></table>";
		if ($editer)
		{
			$res .= "</form>";
		}
		return $res;
	}
	
	function panier_get_texte($panier)
	{
		$res = "Produits commandés :\n";
		if (is_array($panier->lignes))
		{
			reset ($panier->lignes);
			while (list($key,$ligne) = each($panier->lignes))
			{
				$prix = panier_choix_prix($ligne->prix_fr,$ligne->prix_cee,$ligne->prix_monde,$panier->client->pays);
				//$res .= "- ".$ligne->qte." x ".sprintf("%0.2f",$prix)." euros = ".sprintf("%0.2f",$prix*$ligne->qte)." euros => réf. ".$ligne->ref." : ".$ligne->lib."\n";
				//$res .= "- ".$ligne->qte." x ".sprintf("%0.2f",$prix)." euros = ".sprintf("%0.2f",$prix*$ligne->qte)." euros => ".$ligne->lib."\n";
				$res .= "- ".$ligne->lib." (".$ligne->ref.")\n".$ligne->qte." x ".sprintf("%0.2f",$prix)." euros = ".sprintf("%0.2f",$prix*$ligne->qte)." euros\n";
			}
		}
		else
		{
			$res .= "- aucun\n";
		}
		$res .= "- frais de port : ".sprintf("%0.2f",panier_calcule_frais_de_port ($panier))." euros\n";
		$res .= "- montant total : ".sprintf("%0.2f",panier_calcule_montant_total ($panier))." euros\n";
		$res .= "\n";
		$res .= "Adresse de livraison :\n";
		if (is_object($panier->client))
		{
			$res .= "- nom : ".$panier->client->nom."\n";
			$res .= "- prénom : ".$panier->client->prenom."\n";
			$res .= "- adresse :\n";
			if ("" != $panier->client->adresse1)
			{
				$res .= $panier->client->adresse1."\n";
			}
			if ("" != $panier->client->adresse2)
			{
				$res .= $panier->client->adresse2."\n";
			}
			if ("" != $panier->client->adresse3)
			{
				$res .= $panier->client->adresse3."\n";
			}
			$res .= "- code postal : ".$panier->client->code_postal."\n";
			$res .= "- ville : ".$panier->client->ville."\n";
			$res .= "- région : ".$panier->client->region."\n";
			$res .= "- pays : ".$panier->client->pays."\n";
			$res .= "- téléphone : ".$panier->client->telephone."\n";
			$res .= "- email : ".$panier->client->email."\n";
		}
		else
		{
			$res .= "- aucune\n";
		}
		$res .= "\n";
		$res .= "Informations de paiement :\n";
		if (is_object($panier->paiement))
		{
			$res .= "- mode de paiement : ".$panier->paiement->mode_paiement."\n";
			if ("" != $panier->paiement->transaction)
			{
				$res .= "- numéro de transaction : ".$panier->paiement->transaction."\n";
			}
			if ("" != $panier->paiement->autorisation)
			{
				$res .= "- numéro d'autorisation : ".$panier->paiement->autorisation."\n";
			}
			if ("" != $panier->paiement->ip)
			{
				$res .= "- IP : ".$panier->paiement->ip."\n";
			}
			if ("" != $panier->paiement->transmission_date)
			{
				$res .= "- date de transmission : ".$panier->paiement->transmission_date."\n";
			}
			if ("" != $panier->paiement->paiement_date)
			{
				$res .= "- date de paiement : ".$panier->paiement->paiement_date."\n";
			}
		}
		else
		{
			$res .= "- aucune\n";
		}
		$res .= "\n";
		return $res;
	}

	function panier_payer(&$panier,$infos_paiement,$MDND_type_paiement=PMT_CB_MDND)
	{
		global $gp_email_contact,$gp_email_ticket_de_caisse,$gp_email_prefixe, $_gp_prefixe_num_commande, $pays_libelle_abrege,$_gp_site_nom;

		$res = true;
		if (isset($panier->paiement->mode_paiement) && (($panier->paiement->mode_paiement != $infos_paiement->mode_paiement) || (isset($panier->paiement->transaction) && ($panier->paiement->transaction != $infos_paiement->transaction))))
		{
			@mail($gp_email_ticket_de_caisse,"[Commande] probleme de reglement","Double paiement enregistré pour la commande suivante :\n\n".panier_get_texte($panier));
			$res = false;
		}
		$panier->paiement = $infos_paiement;
		$aff_panier = panier_get_texte($panier);
		@mail($gp_email_ticket_de_caisse,$gp_email_prefixe."Commande enregistrée",$aff_panier);
		@mail($panier->client->email,$gp_email_prefixe."Commande enregistrée","Bonjour\n\nVous venez de passer la commande suivante. Elle a bien été enregistrée par notre système et sera traitée dans les plus brefs délais.\n\nCordialement\n\nPatrick, du service clients\n\n".$aff_panier,"From: ".$gp_email_contact."\nReply-To: ".$gp_email_contact."\n");
		panier_commande_save($aff_panier);
		// TODO : add your API to upload command to your system
		@unlink(panier_nom_fichier());
		return $res;
	}

	function panier_payer_par_cheque(&$panier)
	{
		unset($infos_paiement);
		$infos_paiement->mode_paiement = "chèque";
		$infos_paiement->ip = $_SERVER["REMOTE_ADDR"];
		return panier_payer(&$panier,$infos_paiement);
	}

	function panier_payer_par_paypal(&$panier,$transaction,$paiement_date)
	{
		unset($infos_paiement);
		$infos_paiement->mode_paiement = "Paypal";
		$infos_paiement->transaction = $transaction;
		$infos_paiement->paiement_date = $paiement_date;
		return panier_payer(&$panier,$infos_paiement);
	}

	function panier_payer_par_sips(&$panier,$transaction,$autorisation,$ip,$transmission_date,$paiement_date)
	{
		unset($infos_paiement);
		$infos_paiement->mode_paiement = "carte bancaire (SIPS)";
		$infos_paiement->transaction = $transaction;
		$infos_paiement->autorisation = $autorisation;
		$infos_paiement->ip = $ip;
		$infos_paiement->transmission_date = substr($transmission_date,6,2)."/".substr($transmission_date,4,2)."/".substr($transmission_date,0,4)." ".substr($transmission_date,8,2).":".substr($transmission_date,10,2).":".substr($transmission_date,12,2);
		$infos_paiement->paiement_date = substr($paiement_date,6,2)."/".substr($paiement_date,4,2)."/".substr($paiement_date,0,4);
		return panier_payer(&$panier,$infos_paiement,"SG");
	}

	function panier_payer_par_paybox(&$panier,$autorisation,$reference_cb,$date_cb)
	{
		unset($infos_paiement);
		$infos_paiement->mode_paiement = "carte bancaire (PAYBOX)";
		$infos_paiement->transaction = $reference_cb;
		$infos_paiement->autorisation = $autorisation;
		$infos_paiement->paiement_date = substr($date_cb,0,2)."/".substr($date_cb,2,2)."/".substr($date_cb,4,4);
		return panier_payer(&$panier,$infos_paiement,"BX");
	}
?>