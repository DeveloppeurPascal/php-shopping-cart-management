<?php
//	24/11/09, Altermatt F. : création d'une classe générale Paybox
//	10/02/2010, pprem : adaptation du source à la gestion de panier

//----
if (!defined("CLASSE_PAYBOX"))
{
	define("_PAYBOX_PATH", "/PathToPayboxCGIFolder/paybox.cgi");
}

if (!defined("CLASSE_PAYBOX"))	
{
 define("CLASSE_PAYBOX", true);

 $PBX_TabVar=array('M' => 'montant',
				   'R' => 'refCmde', //référence commande est  "code_société;id_panier;"
				   'T' => 'idTransac',
				   'A' => 'numAuto',
				   'P' => 'typPmt',
				   'C' => 'typCarte',
				   'S' => 'numTransac',
				   'Y' => 'paysBank',
				   'E' => 'Erreur',
				   'D' => 'dateValid',
				   'I' => 'paysIp',
				   'N' => 'numCarteD',
				   'H' => 'prtCarte',
				   'G' => 'Garantie',
				   'O' => 'Enrol',
				   'F' => 'Authen',
				   'J' => 'numCarteF',
				   'W' => 'dateTrait');
	
 //-----
 class O_PmtPaybox
 {				  
	 var $RetTxt;  // texte de l'erreur , '@' si erreur de prix

	 //----------------------------------
	 //variables de retour
	 var $session_id,$ref_commande;
	 var $PmtOK; // true si ok
	 //----------------------------------
	 var $NumAuto,$CodeErr;// numéro autorisation,code d'erreur
	 var $RetCode;			
	 var $CbId,$CbDate,$CbStatus;	 //ID=	$merchant_country + $merchant_id + $transaction_id	, DATE=	$payment_date + $payment_time
	
  //--------------------------------------------------------------------------
  function O_PmtPaybox()
  {		   		
     $this->PmtOK=false;  
	 $this->CodeErr='';
	 
	 $this->RetTxt='';	
	 $this->CbId='';
	 $this->CbDate='';
	 $this->CbStatus=0;
  }
				
  //--------------------------------------------------------------------------
  function Request($session_id,$commande_code,$prix,$mail,$soc='YourShoppingID',$lang='FR',$delai=0,$tabret=0) //$tabret['auto'],$tabret['cancel'],$tabret['normal']
  {
		global $gp_url_panier, $_gp_site_url, $PBX_TabVar, $_gp_prefixe_num_commande;
	 
     $parm = "PBX_MODE=4"; //ligne de cmde
  
  	 switch($soc)
	 {
	 case 'TST':			
	 			$parm .= " PBX_SITE=1999888 PBX_RANG=99 PBX_IDENTIFIANT=2";
	 			break;
	 case 'YourShoppingID':			
	 			$parm .= " PBX_SITE=1999888 PBX_RANG=99 PBX_IDENTIFIANT=2";
	 			break;
	 default:
	 			$parm .= " PBX_SITE=1999888 PBX_RANG=99 PBX_IDENTIFIANT=2";
	 			break;
	 }
	 
     $prix = $prix * 100;
     $parm .= " PBX_TOTAL=".$prix;
     $parm .= " PBX_DEVISE=978";	//euro
     if (100*$prix < 100)  { $this-> RetTxt='@'; return(false); }
	 
    $parm .= " PBX_CMD=".$_gp_prefixe_num_commande.$commande_code."-".$session_id; 	// référence de la commande
	$parm .= " PBX_PORTEUR=".$mail;

	
    $safeMode=ini_get('safe_mode');
	
	$parm .= " PBX_RETOUR=";
	$v=false;
    foreach($PBX_TabVar as $let => $var) 
	{
		if ($safeMode) 
		{
			if($v)
			{
				$parm .= ';';
			}
		}
		else
		{
			if($v)
			{
				$parm .= "\;";
			}
		}
		$parm .= 'x'.$let.':'.$let;
		$v=true;
	}

    if($lang=='EN') $parm .= " PBX_LANGUE=GBR";
	 
		 
	$parm .= " PBX_RUF1=POST";    //méthode POST pour le http-retour
	$parm .= " PBX_OUTPUT=C";	    //simple formulaire sans le submit
	$parm .= " PBX_TYPEPAIEMENT=CARTE"; //on ne demande que les cartes
	//$parm="$parm PBX_TYPECARTE=XXX";//(on le mettra a la fin)
  
	$parm .= " PBX_REPONDRE_A=".(("" == $tabret['auto'])?$_gp_site_url."PAYBOX.auto.php":$tabret['auto']);
	$parm .= " PBX_ANNULE=".(("" == $tabret['cancel'])?$_gp_site_url.$gp_url_panier:$tabret['cancel']);
	$parm .= " PBX_EFFECTUE=".(("" == $tabret['normal'])?$_gp_site_url."PAYBOX.retour.php":$tabret['normal']);
	$parm .= " PBX_REFUSE=".(("" == $tabret['normal'])?$_gp_site_url."PAYBOX.retour.php":$tabret['normal']);
	$picto_cb = ("" == $tabret['picto_cb'])?$_gp_site_url."gp_images/logo-cb.jpg":$tabret['picto_cb'];
	$picto_visa = ("" == $tabret['picto_visa'])?$_gp_site_url."gp_images/logo-visa.jpg":$tabret['picto_visa'];
	$picto_master = ("" == $tabret['picto_master'])?$_gp_site_url."gp_images/logo-mastercard.jpg":$tabret['picto_master'];
	$picto_ecb = ("" == $tabret['picto_ecb'])?$_gp_site_url."gp_images/logo-ecartebleue.jpg":$tabret['picto_ecb'];
	 
	if($delai>0) $parm .= " PBX_DIFF=".$delai; 	
//$this->RetTxt=$parm;
//return(true);	 
	
    $path_bin = _PAYBOX_PATH;
	unset($tabR);

//$this->RetTxt="<br><br>$path_bin $parm<br><br>"; 
	
    $result=exec($path_bin." ".$parm,$tabR);
	//mail("support@olfsoft.com","paiementpaybox",$path_bin." ".$parm);
	$nb=count($tabR);
/*	* / 
		for($i=0;$i<$nb;$i++) 
		{
			$ll=$tabR[$i];
			$ll=str_replace("<","{",$ll);
			$ret.=$ll.'<br>';
		}
		$this->RetTxt.=$ret;
		return(true);	 
/* */	
    $dataform='';
	for($i=0;$i<$nb;$i++) $dataform.=$tabR[$i];
/* */	
	
	$ret=$dataform.'<INPUT TYPE=hidden name="PBX_TYPECARTE" value="XXXXX">';
	//--------CB
	$ret.='<input type="image" src="'.$picto_cb.'" alt="cb" onclick="document.PAYBOX.PBX_TYPECARTE.value=\'CB\';submit();" /> ';
	//--------VISA
	$ret.='<input type="image" src="'.$picto_visa.'" alt="visa" onclick="document.PAYBOX.PBX_TYPECARTE.value=\'VISA\';submit();" /> ';
	//--------MASTERCARD
	$ret.='<input type="image" src="'.$picto_master.'" alt="eurocard-mastercard" onclick="document.PAYBOX.PBX_TYPECARTE.value=\'EUROCARD_MASTERCARD\';submit();" /> ';
	//--------E-CB
	$ret.='<input type="image" src="'.$picto_ecb.'" alt="e-card" onclick="document.PAYBOX.PBX_TYPECARTE.value=\'E_CARD\';submit();" /> ';
	//--------
	$ret.='</form>';
	
    $this->RetTxt=$ret;
	return(true);	 
  }		   
			 
  
  function Response($isAutoResponse=false)
  {
	global $PBX_TabVar;
	$Tb=array();
	
	if($isAutoResponse)
	{
	//NUT on peut faire ici le test de l'IP du serveur
	
		//recup des variables en mode POST
		foreach($PBX_TabVar as $let => $nom) 
		{
			$var='x'.$let;
			$Tb[$nom] = $_POST[$var];
		}
	}
	else
	{
		//recup des variables en mode GET
		foreach($PBX_TabVar as $let => $nom) 
		{
			$var = 'x'.$let;
			$Tb[$nom] = $_GET[$var];
//print $nom=	$Tb[$nom].'<br>';		
//print serialize($_GET);
		}
	}

	/*
 montant:M;
 refCmde:R;
 idTransac:T //un identifiant de Transaction (numéro d’appel séquentiel PAYBOX SERVICES),
 numAuto:A   //le numéro d’Autorisation (numéro remis par le centre d’autorisation) : URL encodé,
 typPmt:P    //le type de Paiement retenu (carte, PAYNOVA, SYMPASS, …),
 typCarte:C  //le type de Carte retenu (CB, VISA, EUROCARD_MASTERCARD, AMEX, …),
 numTransac:S //le numéro de la tranSaction (identifiant unique de la transaction),
 paysBank:Y	//le code paYs de la banque émettrice de la carte : norme ISO 3166 (code alphabétique),
 Erreur:E	//le code Erreur de la transaction 
 dateValid:D //Date de fin de validité de la carte du porteur (Format AAMM),
 paysIp:I   //Le code pays de l’adresse IP de l’internaute : norme ISO 3166 (code alphabétique) 
 numCarteD:N // 6 premiers chiffres (« bin6 ») du numéro de carte de l’acheteur : URL encodé,
 prtCarte:H //Empreinte de la carte,
 Garantie:G //Garanti du paiement (O ou N). Programme 3-D Secure,
 Enrol:O   //EnrOlement du porteur/acheteur au programme 3-D Secure. Y:Porteur enrôlé,
 Authen:F   //authentification porteur
 numCarteF:J //2 derniers chiffres du numéro de carte du porteur,
 dateTrait:W //Date de traitement de la transaction sur la plateforme PAYBOX.
 sign:K		//  Signature sur les variables de l’URL ([voir page 23]) : URL encodé,
 */
 
	// Initialisation du chemin du fichier de log (à modifier)
    //   ex :
    //    -> Windows : $logfile="c:\\repertoire\\log\\logfile.txt";
    //    -> Unix    : $logfile="/home/repertoire/log/logfile.txt";
    //
	// Ouverture du fichier de log en append
	if($isAutoResponse)
	{
		$fp=fopen(dirname(__FILE__)."/temp/logs_commandes-".date("Ymd").".txt", "a");
	  	fwrite( $fp, "-Paybox-\n");		
	    foreach($PBX_TabVar as $let => $nom) 
		{
			fwrite( $fp, $nom." (".$let.") : ".$Tb[$nom]."\n");
		}
	  	fwrite( $fp, "-------------------------------------------\n");		
		fclose($fp);	   
    }		
	//on initialise les variables de retour	   
	
	$tbr=split('-',$Tb['refCmde']);
	$this->session_id=$tbr[1]; // session
	$this->ref_commande=$tbr[0]; // commande (PrefixeSite.NumCommande)
	$this->NumAuto=$Tb['numAuto'];
	$this->RetCode=$Tb['Erreur'];
  	if($this->RetCode=="00000") $this->PmtOK=true;
		else $this->CodeErr='XX-'.$this->RetCode;

	$this->CbId=$this->ref_commande.'.'.$Tb['numTransac'].'.'.$Tb['idTransac'];
	$this->CbDate=$Tb['dateTrait'];
	$this->CbStatus=0;

	return(true);	
  }
  
    //juste pour mettre à jour le champ paiement
  function MajPmt($pmt)
  {
	   $n=strpos($pmt,'-');
	   if((is_numeric($n))&&($n>0)) $pmt=substr($pmt,0,$n); // on sépare les infos, on prend que la 1ère, on élimine
	   if(substr($pmt,0,2)!="BX") $pmt="BX"; //sécu, si par ex on paye un CHQ
  	   if($this->PmtOK)
	   { // paiement accepté par la banque  
	        $pmt.='-'.$this->NumAuto;  
	   }
	   else	   
	   { //pmt refusé
	        $pmt.='-'.$this->CodeErr;
	   }
	   return($pmt);
  }


 //--------------------------------------------------------------------------
 } /* fin class */
} /* fin define */

	function PAYBOX_call_request ($session_id, $commande_code, $montant, $client_email, $lang="FR")
	{
		global $_gp_site_url,$PAYBOX_societe;

		$Pmt=new O_PmtPaybox();

		//le tableau $tabret contient les noms des fichiers url retour
		$tabret["auto"] = $_gp_site_url."PAYBOX.auto.php";
		$tabret["cancel"] = $_gp_site_url."PAYBOX.retour.php";
		$tabret["normal"] = $_gp_site_url."PAYBOX.retour.php";  
					
		//les logos des modes de paiement
		$tabret["picto_cb"]=$_gp_site_url."gp_images/logo-cb.jpg";
		$tabret["picto_visa"]=$_gp_site_url."gp_images/logo-visa.jpg";
		$tabret["picto_master"]=$_gp_site_url."gp_images/logo-mastercard.jpg";
		$tabret["picto_ecb"]=$_gp_site_url."gp_images/logo-ecartebleue.jpg";

		if ($Pmt->Request($session_id, $commande_code, $montant, $client_email, $PAYBOX_societe, $lang, 0, $tabret))
		{
			return $Pmt->RetTxt;
		}
		else
		{
			return "<center><b><h2>Erreur appel API de paiement.</h2></b></center>";
		}
	}

?>