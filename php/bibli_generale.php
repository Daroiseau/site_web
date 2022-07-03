<?php

define('BD_SERVER', 'localhost');
define('BD_NAME', 'burgeySW_bd');
define('BD_USER', 'root');
define('BD_PASS', 'tanguy');
define('IS_DEV', true);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting( E_ALL );



/**
* création du debut de l'entête html
*
* @param string    $name    nom de la page a générer
* @param string    $path    nom de la page a générer
*/
function tm_aff_debut(string $name, string $path=''):void{
  echo 	'<!DOCTYPE html>',
  		  '<html lang="fr">',
  		    '<head>',
  			     '<meta charset="utf-8">',
  			     '<title>',$name,'</title>';
             if ($path != '') {
               echo '<link rel="stylesheet" href="',$path,'">';
             }
  		   echo '</head>',
  		'<body>';
}


/**
* création de la fin de l'entête html
*
*/
function tm_aff_fin():void{
  echo 	'</body>',
      '</html>';
}





/**
 * Arrêt du script si erreur de base de données
 *
 * Affichage d'un message d'erreur, puis arrêt du script
 * Fonction appelée quand une erreur 'base de données' se produit :
 *      - lors de la phase de connexion au serveur MySQL
 *      - ou lorsque l'envoi d'une requête échoue
 *
 * @param array    $err    Informations utiles pour le débogage
 */
function tm_bd_erreur_exit(array $err):void {
    ob_end_clean();  // Suppression de tout ce qui a pu être déja généré

    echo    '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">',
            '<title>Erreur',
            IS_DEV ? ' base de données': '', '</title>',
            '</head><body>';
    if (IS_DEV){
        // Affichage de toutes les infos contenues dans $err
        echo    '<h4>', $err['titre'], '</h4>',
                '<pre>',
                    '<strong>Erreur mysqli</strong> : ',  $err['code'], "\n",
                    utf8_encode($err['message']), "\n";
                    //$err['message'] est une chaîne encodée en ISO-8859-1
        if (isset($err['autres'])){
            echo "\n";
            foreach($err['autres'] as $cle => $valeur){
                echo    '<strong>', $cle, '</strong> :', "\n", $valeur, "\n";
            }
        }
        echo    "\n",'<strong>Pile des appels de fonctions :</strong>', "\n", $err['appels'],
                '</pre>';
    }
    else {
        echo 'Une erreur s\'est produite';
    }

    echo    '</body></html>';

    if (! IS_DEV){
        // Mémorisation des erreurs dans un fichier de log
        $fichier = fopen('error.log', 'a');
        if($fichier){
            fwrite($fichier, '['.date('d/m/Y').' '.date('H:i:s')."]\n");
            fwrite($fichier, $err['titre']."\n");
            fwrite($fichier, "Erreur mysqli : {$err['code']}\n");
            fwrite($fichier, utf8_encode($err['message'])."\n");
            if (isset($err['autres'])){
                foreach($err['autres'] as $cle => $valeur){
                    fwrite($fichier,"{$cle} :\n{$valeur}\n");
                }
            }
            fwrite($fichier,"Pile des appels de fonctions :\n");
            fwrite($fichier, "{$err['appels']}\n\n");
            fclose($fichier);
        }
    }
    exit(1);        // ==> ARRET DU SCRIPT
}





/**
 *  Ouverture de la connexion à la base de données en gérant les erreurs.
 *
 *  En cas d'erreur de connexion, une page "propre" avec un message d'erreur
 *  adéquat est affiché ET le script est arrêté.
 *
 *  @return mysqli  objet connecteur à la base de données
 */
function tm_bd_connect(): mysqli {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try{
        $conn = mysqli_connect(BD_SERVER, BD_USER, BD_PASS, BD_NAME);
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur de connexion';
        $err['code'] = $e->getCode();
        $err['message'] = $e->getMessage();
        $err['appels'] = $e->getTraceAsString(); //Pile d'appels
        $err['autres'] = array('Paramètres' =>   'BD_SERVER : '. BD_SERVER
                                                    ."\n".'BD_USER : '. BD_USER
                                                    ."\n".'BD_PASS : '. BD_PASS
                                                    ."\n".'BD_NAME : '. BD_NAME);
        tm_bd_erreur_exit($err); // ==> ARRET DU SCRIPT
    }
    try{
        //mysqli_set_charset() définit le jeu de caractères par défaut à utiliser lors de l'envoi
        //de données depuis et vers le serveur de base de données.
        mysqli_set_charset($conn, 'utf8');
        return $conn;     // ===> Sortie connexion OK
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur lors de la définition du charset';
        $err['code'] = $e->getCode();
        $err['message'] = $e->getMessage();
        $err['appels'] = $e->getTraceAsString();
        tm_bd_erreur_exit($err); // ==> ARRET DU SCRIPT
    }
}

/**
 * Envoie une requête SQL au serveur de BdD en gérant les erreurs.
 *
 * En cas d'erreur, une page propre avec un message d'erreur est affichée et le
 * script est arrêté. Si l'envoi de la requête réussit, cette fonction renvoie :
 *      - un objet de type mysqli_result dans le cas d'une requête SELECT
 *      - true dans le cas d'une requête INSERT, DELETE ou UPDATE
 *
 * @param   mysqli              $bd     Objet connecteur sur la base de données
 * @param   string              $sql    Requête SQL
 * @return  mysqli_result|bool  Résultat de la requête
 */
function tm_bd_send_request(mysqli $bd, string $sql): mysqli_result|bool {
    try{
        return mysqli_query($bd, $sql);
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur de requête';
        $err['code'] = $e->getCode();
        $err['message'] = $e->getMessage();
        $err['appels'] = $e->getTraceAsString();
        $err['autres'] = array('Requête' => $sql);
        tm_bd_erreur_exit($err);    // ==> ARRET DU SCRIPT
    }
}



/**
 * affiche une ligne de formaulaire avec deux colone
 *
 * @param   string     $input    type d'input a afficher
 * @param   string     $name     nom de l'input
 * @param   string     $text     text a afficher dans la colone de gauche
 * @param   string     $value    valeur par default du champ de l'input ("" pour ne pas en avoir)
 * @param   string     $opt      option a rajouter ("" pour ne pas en avoir)
 * @param   string     $id       nom de l'id de l'input (ne rien mettre pour qu'il soit égale au nom de l'input)
 */
function tm_aff_ligne_input(string $input, string $name, string $text, string $value, string $opt, string $id = null):void{
  if ($id == null) {
    $id = $name;
  }
  echo '<tr>',
    '<td><label for="'.$id.'">'.$text.'</label></td>',
    '<td><input type="'.$input.'" name="'.$name.'" id=\"'.$id.'" value="'.$value.'" '.$opt.'></td>',
  '</tr>';
}


/**
* ferme la session et redirige l'utilisateur
*
* @param string $page  page ou rediriger
*/
function tm_session_exit(string $page):void{
  session_destroy();
  session_unset();
  setcookie("usID", "", time()-3600);
  header('Location: '.$page.'');
}


/**
* test si un utilisateur est authentifié
*
* @return bool  le resultat du test
*/
function tm_est_authentifie():bool{
  if (! empty($_SESSION['usID'])) {
    return true;
  }
  return false;
}

function user_can_connect(mysqli $bd, string $pass, string $pseudo):bool{
  $sql = "SELECT usPasse FROM users WHERE usPseudo = '$pseudo'";
  $password = tm_bd_send_request($bd, $sql);
  $tab_password = mysqli_fetch_row($password);
  return password_verify($pass, $tab_password[0]);
}


/** Contrôle des clés contenus dans $_POST ou $_GET
* Soit $x l’ensemble des clés du tableau superglobal à tester* Renvoie true  $obligatoires est inclus dans $x ET
* $x est inclus dans {$obligatoires U $facultatives}*/
function parametres_controle(string $tab, array $obligatoires, array $facultatives = array()) : bool {
  // $tab permet de choisir le tableau super-global
  // dont on souhaite contrôler les clés ($_GET ou $_POST)// => 2 valeurs possibles: 'GET' ou 'POST'
  $x = strtolower($tab) == 'post' ? $_POST : $_GET;
  $x = array_keys($x);
  if (count(array_diff($obligatoires, $x)) > 0){
    return false;
  }
  if (count(array_diff($x, array_merge($obligatoires,$facultatives))) > 0) {
    return false;
  }
  return true;
}



function tm_mb_ereg_match_all($pattern, $subject, array &$subpatterns)
{
    if (!mb_ereg_search_init($subject, $pattern)) {
        return false;
    }
    $subpatterns = array();
    while ($r = mb_ereg_search_regs()) {
        $subpatterns[] = $r;
    }
    return true;
}

?>
