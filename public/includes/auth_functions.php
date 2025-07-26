<?php

require_once 'db_connect.php';

/**
 * Enregistre un nouvel utilisateur.
 * @param string $username Nom d'utilisateur.
 * @param string $email Adresse email.
 * @param string $password Mot de passe en clair.
 * @return bool Vrai si l'inscription est réussie, faux sinon.
 */
function registerUser($username, $email, $password)
{
    global $pdo; // Accède à l'objet PDO global

    // Vérifie si l'utilisateur ou l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $stmt->execute(['username' => $username, 'email' => $email]);
    if ($stmt->fetch()) {
        $_SESSION['message'] = "Le nom d'utilisateur ou l'email existe déjà.";
        return false;
    }

    // Hache le mot de passe avant de le stocker
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Prépare et exécute la requête d'insertion
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $password_hash
        ]);
        $_SESSION['message'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        return true;
    } catch (PDOException $e) {
        // Gère les erreurs d'insertion
        $_SESSION['message'] = "Erreur lors de l'inscription : " . $e->getMessage();
        return false;
    }
}

/**
 * Connecte un utilisateur.
 * @param string $email Email de l'utilisateur.
 * @param string $password Mot de passe en clair.
 * @return bool Vrai si la connexion est réussie, faux sinon.
 */
function loginUser($email, $password)
{
    global $pdo;

    // Récupére l'utilisateur par email
    $stmt = $pdo->prepare("SELECT id, username, password_hash, is_admin FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // Vérifie si l'utilisateur existe et si le mot de passe est correct
    if ($user && password_verify($password, $user['password_hash'])) {
        // Démarre la session et stocker les informations de l'utilisateur
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin']; // Stocker le statut admin
        $_SESSION['message'] = "Connexion réussie ! Bienvenue, " . $user['username'] . ".";
        return true;
    } else {
        $_SESSION['message'] = "Email ou mot de passe incorrect.";
        return false;
    }
}

/**
 * Déconnecte l'utilisateur en détruisant la session.
 */
function logoutUser()
{
    // Détruit toutes les variables de session
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // détruit la session
    session_destroy();
    $_SESSION['message'] = "Vous avez été déconnecté.";
}

/**
 * Vérifie si l'utilisateur est connecté.
 * @return bool Vrai si connecté, faux sinon.
 */
function isUserLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est un administrateur.
 * @return bool Vrai si admin, faux sinon.
 */
function isUserAdmin()
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Récupère tous les utilisateurs.
 * @return array Liste de tous les utilisateurs.
 */
function getAllUsers()
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des utilisateurs : " . $e->getMessage());
        return [];
    }
}

/**
 * Supprime un utilisateur par son ID.
 * @param int $userId ID de l'utilisateur.
 * @return bool Vrai si la suppression est réussie, faux sinon.
 */
function deleteUser($userId)
{
    global $pdo;
    try {
        // Prépare et exécute la requête de suppression
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $userId]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression de l'utilisateur : " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour le statut admin d'un utilisateur.
 * @param int $userId ID de l'utilisateur.
 * @param bool $isAdmin Nouveau statut admin.
 * @return bool Vrai si la mise à jour est réussie, faux sinon.
 */
function updateUserAdminStatus($userId, $isAdmin)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_admin = :is_admin WHERE id = :id");
        return $stmt->execute(['is_admin' => $isAdmin, 'id' => $userId]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour du statut admin de l'utilisateur : " . $e->getMessage());
        return false;
    }
}
