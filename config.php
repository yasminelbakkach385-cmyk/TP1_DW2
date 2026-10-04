<?php
$hote = 'localhost';
$port = 3306;
$base = 'cv_db';
$user = 'root';
$mdp  = '';

try {
    // 1. Connexion au serveur MySQL (sans choisir de base : elle n'existe peut-être pas encore)
    $pdo = new PDO(
        "mysql:host=$hote;port=$port;charset=utf8mb4",
        $user,
        $mdp,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // 2. Créer la base si elle n'existe pas, puis l'utiliser
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$base` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `$base`");

    // 3. Créer les tables si elles n'existent pas (on lit shema.sql, requête par requête)
    if ($pdo->query("SHOW TABLES LIKE 'utilisateur'")->rowCount() === 0) {
        $sql = file_get_contents(__DIR__ . '/shema.sql');
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $requete) {
            $pdo->exec($requete);
        }
    }
} catch (PDOException $e) {
    exit('Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage()));
}