<?php

// S'assure que la session est démarrée
require_once __DIR__ . '/api/db_connect.php';
require_once __DIR__ . '/api/auth_functions.php';

logoutUser(); // Appelle la fonction de déconnexion

header('Location: /'); // Redirige vers la page d'accueil
exit();
