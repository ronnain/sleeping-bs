<?php
require 'config.php';
function connect(){
        global $dbHost, $dbName, $dbUsername, $dbPassword;
    try
    {
        $bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUsername, $dbPassword);
    }
    catch (Exception $e)
    {
        die('Erreur : ' . $e->getMessage());
    }
    if (!$bdd) {
        echo 'Echec de la connexion avec la base de données';
        die;
    }
    return $bdd;
}