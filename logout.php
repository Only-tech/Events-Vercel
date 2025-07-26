<?php

// S'assure que la session est démarrée
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/auth_functions.php';

logoutUser(); // Appelle la fonction de déconnexion

header('Location: /'); // Redirige vers la page d'accueil
exit();
