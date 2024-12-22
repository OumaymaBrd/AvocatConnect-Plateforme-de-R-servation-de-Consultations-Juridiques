<?php
require '../connexion/connexion.php';

$message = '';
$error = '';

if(isset($_POST['envoyer'])){
    // Récupération et nettoyage des données
    $nom = trim(htmlspecialchars($_POST['nom']));
    $prenom = trim(htmlspecialchars($_POST['prenom']));
    $email = trim(htmlspecialchars($_POST['email']));
    $duree = intval($_POST['Duree_stage_mois']);
    
    // Validation des données
    $isValid = true;
    
    if(empty($nom) || empty($prenom) || empty($email)){
        $error = "Tous les champs sont obligatoires";
        $isValid = false;
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Format d'email invalide";
        $isValid = false;
    }
    
    if(!in_array($duree, [2,3,4,5,6])){
        $error = "La durée du stage doit être entre 2 et 6 mois";
        $isValid = false;
    }
    
    if($isValid){
        try {
            $sql = "INSERT INTO demande_stage (Duree_stage_mois, nom, prenom, email, validation_avocat) 
                    VALUES (:duree, :nom, :prenom, :email, :validation)";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                ':duree' => $duree,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':validation' => "Dans l'attente de la libération des places"
            ]);
            
            if($result){
                $message = "Votre demande de stage a été enregistrée avec succès";
            } else {
                $error = "Une erreur est survenue lors de l'enregistrement";
            }
        } catch(PDOException $e) {
            $error = "Erreur: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Stage</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .container {
            max-width: 600px;
            margin-top: 50px;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Formulaire de Demande de Stage</h2>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="stageForm" class="needs-validation" novalidate>
            <div class="form-group">
                <label for="nom">Nom : </label>
                <input type="text" 
                       class="form-control" 
                       id="nom" 
                       name="nom" 
                       value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>"
                       required>
                <div class="invalid-feedback">
                    Veuillez entrer votre nom
                </div>
            </div>

            <div class="form-group">
                <label for="prenom">Prénom : </label>
                <input type="text" 
                       class="form-control" 
                       id="prenom" 
                       name="prenom"
                       value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>"
                       required>
                <div class="invalid-feedback">
                    Veuillez entrer votre prénom
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email : </label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required>
                <div class="invalid-feedback">
                    Veuillez entrer une adresse email valide
                </div>
            </div>

            <div class="form-group">
                <label for="Duree_stage_mois">Durée du stage (en mois) : </label>
                <select class="form-control" 
                        id="Duree_stage_mois" 
                        name="Duree_stage_mois" 
                        required>
                    <option value="">Sélectionnez la durée</option>
                    <?php for($i = 2; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" 
                                <?php echo (isset($_POST['Duree_stage_mois']) && $_POST['Duree_stage_mois'] == $i) ? 'selected' : ''; ?>>
                            <?php echo $i; ?> mois
                        </option>
                    <?php endfor; ?>
                </select>
                <div class="invalid-feedback">
                    Veuillez sélectionner la durée du stage
                </div>
            </div>

            <button type="submit" name="envoyer" class="btn btn-primary">Envoyer la demande</button>
        </form>
    </div>

    <script>
    // Validation côté client
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
    </script>
</body>
</html>