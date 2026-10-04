<?php
$hote = 'localhost';
$port = 3307;
$base = 'cv_db';
$user = 'root';
$mdp  = '';

try {
    $pdo = new PDO(
        "mysql:host=$hote;port=$port;dbname=$base;charset=utf8mb4",
        $user,
        $mdp,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    exit('Erreur de connexion à la base de données.');
}