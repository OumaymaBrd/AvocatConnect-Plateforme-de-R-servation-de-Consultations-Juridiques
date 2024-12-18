<?php
require '../connexion/connexion.php';

// Requête SQL pour afficher les avocats
$sql = "SELECT user_.id_user, biographie, derniere_diplome, adresse, nom, prenom, email, tel 
        FROM info_avocat 
        JOIN user_ ON user_.id_user != info_avocat.id_avocat";

$stmt = $conn->prepare($sql);
$stmt->execute();
$avocats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Requête SQL pour les pourcentages
$sqlPourcentages = "SELECT 
    SUM(pourcentage_sans_acute) AS count_pourcentage_sans_acute,
    SUM(pourcentage_capacite_jugement) AS count_pourcentage_capacite_jugement,
    SUM(pourcentage_connaissance_approfondie) AS count_pourcentage_connaissance_approfondie
    FROM info_avocat
    WHERE id_avocat = :id_avocat";

$stmtPourcentages = $conn->prepare($sqlPourcentages);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Le même contenu que dans la version HTML -->
</head>
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 2rem;
            background-color: #ffffff;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }

        @media (max-width: 1200px) {
            .grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            position: relative;
            aspect-ratio: 1;
            cursor: pointer;
        }

        .card-inner {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 3px solid #0A2759;
            background-color: white;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover .card-inner {
            background-color: #0087CD;
        }

        .content {
            position: relative;
            height: 100%;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            z-index: 2;
        }

        .column-icon {
            position: absolute;
            inset: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
            opacity: 0.1;
        }

        .column-icon svg {
            width: 60%;
            height: 60%;
            color: #C5A572;
        }

        .city {
            font-size: 0.875rem;
            text-transform: uppercase;
            color: #0A2759;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }

        .name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0A2759;
            margin-bottom: 1rem;
            transition: color 0.3s ease;
        }

        .card:hover .city,
        .card:hover .name {
            color: black;
        }

        .arrow-container {
            width: 2rem;
            height: 2rem;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .card:hover .arrow-container {
            transform: translateX(0.5rem);
        }

        .arrow {
            width: 1rem;
            height: 1rem;
            color: #0A2759;
        }
    </style>
<body>
    <div class="grid" id="lawyers-grid">
        <?php foreach ($avocats as $avocat): 
            // Récupérer les pourcentages pour cet avocat
            $stmtPourcentages->execute(['id_avocat' => $avocat['id_user']]);
            $pourcentages = $stmtPourcentages->fetch(PDO::FETCH_ASSOC);
        ?>
            <div class="card">
                <div class="card-inner">
                    <div class="column-icon">
                        <svg viewBox="0 0 100 120" fill="currentColor">
                            <path d="M20,0 h15 v120 h-15 Z M45,20 h15 v100 h-15 Z M70,40 h15 v80 h-15 Z M95,60 h15 v60 h-15 Z" />
                        </svg>
                    </div>
                    <div class="content">
                        <div class="city"><?php echo htmlspecialchars($avocat['ville'] ?? 'Ville non spécifiée'); ?></div>
                        <div class="name"><?php echo htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']); ?></div>
                        <div class="arrow-container">
                            <svg class="arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </div>
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