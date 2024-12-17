<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'oumaymabramid@gmail.com';
        $mail->Password   = 'uupe rzul tqlc onqg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Désactiver la vérification du certificat SSL
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('oumaymabramid@gmail.com', 'Matricule');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Le message n'a pas pu être envoyé. Erreur Mailer : {$mail->ErrorInfo}<br>";
        echo "Erreur détaillée : " . $e->getMessage();
        return false;
    }
}

// Utilisation de la fonction
if (isset($_POST['ok'])) {
    $to = 'oumaymabramid@gmail.com';
    $subject = 'Bonjour Oumayma';
    $body = '<h1>Bonjour</h1><p>Ceci est un test d\'envoi d\'email.</p>';
    
    if (sendEmail($to, $subject, $body)) {
        echo "Email envoyé avec succès!";
    } else {
        echo "Échec de l'envoi de l'email.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire d'envoi d'email</title>
</head>
<body>
    <form action="" method="POST">
        <label for="nom">Nom :</label>
        <input type="text" id="nom" name="nom">
        <input type="submit" name="ok" value="Envoyer">
    </form>
</body>
</html>