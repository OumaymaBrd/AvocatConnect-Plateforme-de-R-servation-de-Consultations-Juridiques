<?php
require_once '../connexion/connexion.php';

// Fonction pour nettoyer les entrées
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = sanitizeInput($_POST['nom']);
    $prenom = sanitizeInput($_POST['prenom']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $dureeStage = sanitizeInput($_POST['dureeStage']);
    $tel = sanitizeInput($_POST['tel']);
    $motivation = sanitizeInput($_POST['motivation']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse email invalide.";
    } else {
        try {
            $conn->beginTransaction();

            // Insert into demande_stage table
            $insertSql = "INSERT INTO demande_stage (nom, prenom, email, tel, Duree_stage_mois) 
                          VALUES (:nom, :prenom, :email, :tel, :Duree_stage_mois)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $insertStmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
            $insertStmt->bindParam(':email', $email, PDO::PARAM_STR);
            $insertStmt->bindParam(':tel', $tel, PDO::PARAM_STR);
            $insertStmt->bindParam(':Duree_stage_mois', $dureeStage, PDO::PARAM_STR);
           

            $insertStmt->execute();

            $conn->commit();
            $message = "Votre demande de stage a été enregistrée avec succès.";
        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "Une erreur est survenue lors de l'enregistrement de votre demande. Veuillez réessayer.";
            error_log("Erreur PDO : " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Stage - Cabinet d'Avocat</title>
    <link rel="stylesheet" href="../css/style_stage.css">
</head>
<body>
    <h1>Demande de Stage - Cabinet d'Avocat</h1>
    <?php if (!empty($message)): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <label for="nom">Nom :</label>
        <input type="text" id="nom" name="nom" required>

        <label for="prenom">Prénom :</label>
        <input type="text" id="prenom" name="prenom" required>

        <label for="email">Email :</label>
        <input type="email" id="email" name="email" required>

        <label for="tel">Téléphone :</label>
        <input type="tel" id="tel" name="tel" required>

        <label for="dureeStage">Durée du stage (en mois) :</label>
        <select id="dureeStage" name="dureeStage" required>
            <option value="">Sélectionnez la durée</option>
            <option value="2">2 mois</option>
            <option value="3">3 mois</option>
            <option value="4">4 mois</option>
            <option value="5">5 mois</option>
            <option value="6">6 mois</option>
        </select>

        <label for="motivation">Lettre de motivation :</label>
        <textarea id="motivation" name="motivation" required></textarea>

        <input type="submit" value="Soumettre la demande">
    </form>
</body>
</html>