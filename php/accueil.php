<?php

require 'bibli_generale.php';
//require 'bibli_cuiteur.php';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting( E_ALL );
tm_aff_debut('Comptoir des vignes', '../styles/page.css');
echo '<img src="../images/utilisateur.png" alt="profil">',
     '<img src="../images/panier.png" alt="panier">',
     '<img id="logo" src="../images/Logo.png" alt="logo">';
tm_aff_fin();


 ?>
