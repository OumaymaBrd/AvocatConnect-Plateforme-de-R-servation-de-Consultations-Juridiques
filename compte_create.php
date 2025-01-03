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

                // Get the last inserted ID
                $lastInsertId = $conn->lastInsertId();

                // If the user is an Avocat, insert additional information
                if ($post === 'Avocat') {
                    if (empty($biographie) || empty($derniere_diplome) || empty($adresse)) {
                        throw new Exception("Tous les champs pour l'avocat doivent être remplis.");
                    }

                    $avocatSql = "INSERT INTO info_avocat (id_avocat, biographie, derniere_diplome, adresse) 
                                  VALUES (:id_avocat, :biographie, :derniere_diplome, :adresse)";
                    $avocatStmt = $conn->prepare($avocatSql);
                    $avocatStmt->bindParam(':id_avocat', $lastInsertId, PDO::PARAM_INT);
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
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $message = "Une erreur est survenue lors de l'inscription : " . $e->getMessage();
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
    <!-- <link rel="stylesheet" href="assets/css/style_index.css"> -->
     <style>
 @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

:root {
  --primary-color: #4a90e2;
  --secondary-color: #f39c12;
  --background-color: #f4f7f9;
  --text-color: #333;
  --error-color: #e74c3c;
}

body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, var(--background-color), #ffffff);
  margin: 0;
  padding: 0;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
}

.container {
  background-color: #ffffff;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  width: 90%;
  max-width: 800px;
  padding: 2rem;
  transition: all 0.3s ease;
}

h2 {
  color: var(--primary-color);
  text-align: center;
  margin-bottom: 2rem;
  font-weight: 600;
}

form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: grid;
  grid-template-columns: 200px 1fr;
  align-items: center;
  gap: 1rem;
}

label {
  color: var(--text-color);
  font-weight: 400;
}

input, select, textarea {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
  width: 100%;
}

textarea {
  min-height: 100px;
  resize: vertical;
}

input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
}

select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  padding-right: 2.5rem;
}

input[type="submit"] {
  background-color: var(--primary-color);
  color: #ffffff;
  border: none;
  padding: 1rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.3s ease, transform 0.1s ease;
  margin-top: 1rem;
}

input[type="submit"]:hover {
  background-color: #3a7bc8;
}

input[type="submit"]:active {
  transform: scale(0.98);
}

.message {
  text-align: center;
  margin-bottom: 1rem;
  padding: 0.75rem;
  border-radius: 5px;
  font-weight: 400;
}

.message.error {
  background-color: #fdeaea;
  color: var(--error-color);
}

.message.success {
  background-color: #eafde8;
  color: #27ae60;
}

#avocat_fields {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.5s ease;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

#avocat_fields.active {
  max-height: 500px;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.fade-in {
  animation: fadeIn 0.5s ease forwards;
}

@media (max-width: 768px) {
  .container {
    width: 95%;
    padding: 1.5rem;
  }

  .form-group {
    grid-template-columns: 1fr;
  }

  input, select, textarea {
    font-size: 0.9rem;
  }
}


     </style>
    <script>
        function toggleAvocatFields() {
            var post = document.getElementById('post').value;
            var avocatFields = document.getElementById('avocat_fields');
            var avocatInputs = avocatFields.getElementsByTagName('input');
            var avocatTextarea = avocatFields.getElementsByTagName('textarea');
            
            if (post === 'Avocat') {
                avocatFields.style.display = 'block';
                for (var i = 0; i < avocatInputs.length; i++) {
                    avocatInputs[i].required = true;
                }
                for (var i = 0; i < avocatTextarea.length; i++) {
                    avocatTextarea[i].required = true;
                }
            } else {
                avocatFields.style.display = 'none';
                for (var i = 0; i < avocatInputs.length; i++) {
                    avocatInputs[i].required = false;
                }
                for (var i = 0; i < avocatTextarea.length; i++) {
                    avocatTextarea[i].required = false;
                }
            }
        }
    </script>
</head>
<body>
    <div class="container fade-in">
        <h2>Inscription au Cabinet Advocate</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'réussie') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form action="" method="POST">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" required>

            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" required>

            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required>

            <label for="tel">Téléphone</label>
            <input type="tel" id="tel" name="tel" required>

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>

            <label for="post">Votre Poste</label>
            <select name="post" id="post" required>
                <option value="">Sélectionnez votre poste</option>
                <option value="Client">Client</option>
                <option value="Avocat">Avocat</option>
            </select>

            <div id="avocat_fields">
                <label for="biographie">Biographie</label>
                <textarea id="biographie" name="biographie"></textarea>

                <label for="derniere_diplome">Dernier Diplôme</label>
                <input type="text" id="derniere_diplome" name="derniere_diplome">

                <label for="adresse">Adresse</label>
                <input type="text" id="adresse" name="adresse">
            </div>

            <input type="submit" value="S'inscrire">
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const postSelect = document.getElementById('post');
            const avocatFields = document.getElementById('avocat_fields');

            postSelect.addEventListener('change', function() {
                if (this.value === 'Avocat') {
                    avocatFields.classList.add('active');
                    avocatFields.querySelectorAll('input, textarea').forEach(el => el.required = true);
                } else {
                    avocatFields.classList.remove('active');
                    avocatFields.querySelectorAll('input, textarea').forEach(el => el.required = false);
                }
            });
        });
    </script>
</body>
</html>

