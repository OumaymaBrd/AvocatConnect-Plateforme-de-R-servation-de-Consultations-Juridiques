<?php
// Paramètres de connexion à la base de données
$host = 'localhost';
$dbname = 'consultations_juridiques'; // Nom de votre base de données
$username = 'root';        // Nom d'utilisateur par défaut de WAMP
$password = '';           // Mot de passe par défaut de WAMP (vide)

try {
    // Création de la connexion PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Configuration des attributs PDO
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->exec("SET NAMES utf8");
    
} catch(PDOException $e) {
    // En cas d'erreur, afficher le message
    die("Erreur de connexion : " . $e->getMessage());
}
?>

