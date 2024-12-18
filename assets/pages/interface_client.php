<?php
require '../connexion/connexion.php';


$sql = "SELECT user_.id_user, biographie, derniere_diplome, adresse, nom, prenom, email, tel 
        FROM info_avocat 
        JOIN user_ ON user_.id_user = info_avocat.id_avocat";

$stmt = $conn->prepare($sql);
$stmt->execute();
$avocats = $stmt->fetchAll(PDO::FETCH_ASSOC);


$sqlPourcentages = "SELECT 
    COALESCE(SUM(pourcentage_sans_acute), 0) AS count_pourcentage_sans_acute,
    COALESCE(SUM(pourcentage_capacite_jugement), 0) AS count_pourcentage_capacite_jugement,
    COALESCE(SUM(pourcentage_connaissance_approfondie), 0) AS count_pourcentage_connaissance_approfondie
    FROM info_avocat
    WHERE id_avocat = :id_avocat";

$stmtPourcentages = $conn->prepare($sqlPourcentages);


foreach ($avocats as $avocat) {
    $stmtPourcentages->execute(['id_avocat' => $avocat['id_user']]);
    $pourcentages = $stmtPourcentages->fetch(PDO::FETCH_ASSOC);
    
  
    // echo "Somme des pourcentages sans acuité : " . 
    //      htmlspecialchars($pourcentages['count_pourcentage_sans_acute'] ?? '0') . "<br>";
         
    // echo "Somme des pourcentages capacité de jugement : " . 
    //      htmlspecialchars($pourcentages['count_pourcentage_capacite_jugement'] ?? '0') . "<br>";
         
    // echo "Somme des pourcentages connaissance approfondie : " . 
    //      htmlspecialchars($pourcentages['count_pourcentage_connaissance_approfondie'] ?? '0') . "<br>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
   
<link rel="stylesheet" href="../css/style_client.css">
</head>


<body>
    <div class="grid" id="lawyers-grid">
        <?php foreach ($avocats as $avocat): 
            $stmtPourcentages->execute(['id_avocat' => $avocat['id_user']]);
            // $pourcentages = $stmtPourcentages->fetch(PDO::FETCH_ASSOC);
       
        ?>
            <div class="card">
                <div class="card-inner">
                    <div class="column-icon">
                        <svg viewBox="0 0 100 120" fill="currentColor">
                            <path d="M20,0 h15 v120 h-15 Z M45,20 h15 v100 h-15 Z M70,40 h15 v80 h-15 Z M95,60 h15 v60 h-15 Z" />
                        </svg>
                    </div>
                    <div class="content">
                        <div class="city"><?php echo htmlspecialchars($avocat['tel'] ?? 'Aucun Numero ') ."<br>";
                        
                        echo htmlspecialchars($avocat['email'] ?? 'Aucun Email '); 
                        ?></div>
                        <div class="email"></div>
                        <div class="name"><?php echo htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']); 
                        
                        $pourcentages = $stmtPourcentages->fetch(PDO::FETCH_ASSOC);
                        ?></div>
                       <form action="" method="POST">
                        <button type="submit" name="add_reservation" class="arrow-container">
                        +
                        </button>
                        </form>

                        <?php
                        if(isset($_POST['add_reservation'])){
                        echo '';
                        }
                        ?>
                    </div>
                    <div class="hover-info">
                        <h3><?php echo htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']); ?></h3>
                        <p><?php echo htmlspecialchars($avocat['biographie']); ?></p>
                        <p>Diplôme: <?php echo htmlspecialchars($avocat['derniere_diplome']); ?></p>
                        <p>Adresse: <?php echo htmlspecialchars($avocat['adresse']); ?></p>
                        <p>Email: <?php echo htmlspecialchars($avocat['email']); ?></p>
                        <p>Tél: <?php echo htmlspecialchars($avocat['tel']); ?></p>
                        <div class="percentages">
                            <div class="percentage">
                                <div class="percentage-value"><?php echo htmlspecialchars($pourcentages['count_pourcentage_sans_acute']); ?>%</div>
                                <div class="percentage-label">Sans acuité</div>
                            </div>
                            <div class="percentage">
                                <div class="percentage-value"><?php echo htmlspecialchars($pourcentages['count_pourcentage_capacite_jugement']); ?>%</div>
                                <div class="percentage-label">Capacité de jugement</div>
                            </div>
                            <div class="percentage">
                                <div class="percentage-value"><?php echo htmlspecialchars($pourcentages['count_pourcentage_connaissance_approfondie']); ?>%</div>
                                <div class="percentage-label">Connaissance approfondie</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>