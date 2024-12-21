<?php
session_start();
require_once '../connexion/connexion.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricule = $_POST['matricule'];
    $email = $_POST['email'];

    try {
        $sql = "SELECT id_user, Post FROM user_ WHERE matricule = :matricule AND email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':matricule', $matricule, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_id'] = $user['id_user'];
            
            if ($user['Post'] == 'Client') {
                header('Location: interface_client.php?id=' . $user['id_user']);
            } else {
                header('Location: interface_avocat.php?id=' . $user['id_user']);
            }
            exit();
        } else {
            $error = "Matricule ou email incorrect.";
        }
    } catch(PDOException $e) {
        $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
        // For development only:
        // $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Cabinet Martin & Associés</title>
    <link rel="stylesheet" href="../css/style_.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Cabinet Martin & Associés</div>
            <ul>
                <li><a href="index.html">Accueil</a></li>
                <li><a href="reservation.html">Réservations</a></li>
                <li><a href="login.php">Connexion</a></li>
                <li><a href="register.html">Inscription</a></li>
            </ul>
        </nav>
    </header>

    <main class="auth">
        <div class="auth-container">
            <h1>Connexion</h1>
            <?php if ($error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form class="auth-form" method="POST" action="login.php">
                <div class="form-group">
                    <label for="matricule">Matricule</label>
                    <input type="text" id="matricule" name="matricule" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="submit-button">Se connecter</button>
                <p class="auth-links">
                    <a href="../../compte_create.php">Créer un compte</a> |
                    <a href="#">Mot de passe oublié ?</a>
                </p>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Cabinet Martin & Associés - Tous droits réservés</p>
    </footer>
</body>
</html>

