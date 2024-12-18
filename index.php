<?php
require 'assets/email/email.php';
require 'vendor/autoload.php';
require_once 'assets/connexion/connexion.php';

function generateMatricule() {
    return 'AV' . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

function sanitizeInput($input) {
    if(is_array($input)) {
        foreach($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $nom = sanitizeInput($_POST['nom']);
    $prenom = sanitizeInput($_POST['prenom']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $tel = sanitizeInput($_POST['tel']);
    $password = $_POST['password']; // We'll hash this later
    $post = sanitizeInput($_POST['post']);
    $biographie = isset($_POST['biographie']) ? sanitizeInput($_POST['biographie']) : '';
    $derniere_diplome = isset($_POST['derniere_diplome']) ? sanitizeInput($_POST['derniere_diplome']) : '';
    $adresse = isset($_POST['adresse']) ? sanitizeInput($_POST['adresse']) : '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse email invalide.";
    } else {
        $matricule = generateMatricule();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Check if email already exists
            $checkEmailSql = "SELECT email FROM user_ WHERE email = :email";
            $checkEmailStmt = $conn->prepare($checkEmailSql);
            $checkEmailStmt->bindParam(':email', $email, PDO::PARAM_STR);
            $checkEmailStmt->execute();

            if ($checkEmailStmt->rowCount() > 0) {
                $message = "Cet email est déjà utilisé. Veuillez en choisir un autre.";
            } else {
                // Begin transaction
                $conn->beginTransaction();

                // Insert into user_ table
                $insertSql = "INSERT INTO user_ (nom, prenom, email, tel, password, matricule, Post) 
                              VALUES (:nom, :prenom, :email, :tel, :hashedPassword, :matricule, :post)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bindParam(':nom', $nom, PDO::PARAM_STR);
                $insertStmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
                $insertStmt->bindParam(':email', $email, PDO::PARAM_STR);
                $insertStmt->bindParam(':tel', $tel, PDO::PARAM_STR);
                $insertStmt->bindParam(':hashedPassword', $hashedPassword, PDO::PARAM_STR);
                $insertStmt->bindParam(':matricule', $matricule, PDO::PARAM_STR);
                $insertStmt->bindParam(':post', $post, PDO::PARAM_STR);

                $insertStmt->execute();

                // If the user is an Avocat, insert additional information
                if ($post === 'Avocat') {
                    $avocatSql = "INSERT INTO avocat_info (id_avocat , biographie, derniere_diplome, adresse) 
                                  VALUES (LAST_INSERT_ID(), :biographie, :derniere_diplome, :adresse)";
                    $avocatStmt = $conn->prepare($avocatSql);
                    $avocatStmt->bindParam(':biographie', $biographie, PDO::PARAM_STR);
                    $avocatStmt->bindParam(':derniere_diplome', $derniere_diplome, PDO::PARAM_STR);
                    $avocatStmt->bindParam(':adresse', $adresse, PDO::PARAM_STR);
                    $avocatStmt->execute();
                }

                // Commit transaction
                $conn->commit();

                $subject = 'Inscription au Cabinet Advocate';
                $body = "
                <h1>Bonjour $prenom $nom,</h1>
                <p>Merci de vous être inscrit au Cabinet Advocate. Voici vos informations d'inscription :</p>
                <p><strong>Matricule :</strong> $matricule</p>
                <p><strong>Email :</strong> $email</p>
                <p><strong>Téléphone :</strong> $tel</p>
                <p>Nous vous recommandons de changer votre mot de passe lors de votre première connexion.</p>
                <p>Cordialement,<br>L'équipe du Cabinet Advocate</p>
                ";
                
                if (sendEmail($email, $subject, $body)) {
                    $message = "Inscription réussie ! Un email a été envoyé à $email avec vos informations.";
                } else {
                    $message = "Inscription réussie, mais l'envoi de l'email a échoué. Veuillez contacter l'administrateur.";
                }
            }
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $message = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
            error_log("Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Cabinet Advocate</title>
    <link rel="stylesheet" href="assets/css/style_index.css">
    <script>
        function toggleAvocatFields() {
            var post = document.getElementById('post').value;
            var avocatFields = document.getElementById('avocat_fields');
            avocatFields.style.display = (post === 'Avocat') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Inscription au Cabinet Advocate</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form action="" method="POST">
            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" required>

            <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="prenom" required>

            <label for="email">Adresse email :</label>
            <input type="email" id="email" name="email" required>

            <label for="tel">Téléphone :</label>
            <input type="tel" id="tel" name="tel" required>

            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required>

            <label for="post">Votre Poste :</label>
            <select name="post" id="post" required onchange="toggleAvocatFields()">
                <option value="">Sélectionnez votre poste</option>
                <option value="Client">Client</option>
                <option value="Avocat">Avocat</option>
            </select>

            <div id="avocat_fields" style="display: none;">
                <label for="biographie">Biographie :</label>
                <textarea id="biographie" name="biographie"></textarea>

                <label for="derniere_diplome">Dernier Diplôme :</label>
                <input type="text" id="derniere_diplome" name="derniere_diplome">

                <label for="adresse">Adresse :</label>
                <input type="text" id="adresse" name="adresse">
            </div>

            <input type="submit" value="S'inscrire">
        </form>
    </div>
</body>
</html>