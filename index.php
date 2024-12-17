<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'assets/connexion/connexion.php';
require 'vendor/autoload.php';

function generateMatricule() {
    return 'YC' . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'oumaymabramid@gmail.com';
        $mail->Password   = 'itsi bgoq daaf voes';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('noreply@cabinetadvocate.com', 'Cabinet ADVOCATE');
        $mail->addReplyTo('contact@cabinetadvocate.com', 'Service Client Cabinet ADVOCATE');
        $mail->addAddress($to);

        $mail->addCustomHeader('Sender', 'noreply@cabinetadvocate.com');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $tel = $_POST['tel'];
    $password = $_POST['password'];
    $post = $_POST['post'];
    $matricule = generateMatricule();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO user_ (nom, matricule, prenom, email, tel, Post, password) VALUES (:nom, :matricule, :prenom, :email, :tel, :post, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':matricule', $matricule);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':tel', $tel);
        $stmt->bindParam(':post', $post);
        $stmt->bindParam(':password', $hashedPassword);

        if ($stmt->execute()) {
            $subject = 'Inscription au Cabinet Advocate';
            $body = "
            <h1>Bonjour $prenom $nom,</h1>
            <p>Merci de vous être inscrit au Cabinet Advocate. Voici vos informations d'inscription :</p>
            <p><strong>Matricule :</strong> $matricule</p>
            <p><strong>Email :</strong> $email</p>
            <p><strong>Téléphone :</strong> $tel</p>
            <p><strong>Poste :</strong> $post</p>
            <p><strong>Mot de passe :</strong> $password</p>
            <p>Nous vous recommandons de changer votre mot de passe lors de votre première connexion.</p>
            <p>Cordialement,<br>L'équipe du Cabinet Advocate</p>
            ";
            
            if (sendEmail($email, $subject, $body)) {
                $message = "Inscription réussie ! Un email a été envoyé à $email avec vos informations.";
            } else {
                $message = "Inscription réussie, mais l'envoi de l'email a échoué. Votre matricule est : $matricule";
            }
        } else {
            $message = "Erreur lors de l'inscription. Veuillez réessayer.";
        }
    } catch (PDOException $e) {
        $message = "Erreur de base de données : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Cabinet Advocate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
        }
        input, select {
            padding: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #5cb85c;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #4cae4c;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Inscription au Cabinet Advocate</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form action="" method="POST">
            <label for="post">Votre Poste:</label>
            <select name="post" id="post" required>
                <option value="Client">Client</option>
                <option value="Avocat">Avocat</option>
            </select>

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

            <input type="submit" value="S'inscrire">
        </form>
    </div>
</body>
</html>

