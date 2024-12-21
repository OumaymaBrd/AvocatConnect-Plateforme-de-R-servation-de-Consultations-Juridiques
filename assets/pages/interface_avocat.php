<?php
require '../connexion/connexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';

// Handle form submissions for toggling or deleting reservations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($reservation_id > 0) {
        try {
            if ($action === 'toggle') {
                // First, get the current validation status
                $sql_check = "SELECT validation FROM reservation WHERE id_reservation = :id";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bindParam(':id', $reservation_id, PDO::PARAM_INT);
                $stmt_check->execute();
                $current_status = $stmt_check->fetchColumn();

                // Then, toggle the status
                $new_status = ($current_status === 'Valider') ? 'Pas encore' : 'Valider';
                $sql_update = "UPDATE reservation SET validation = :status WHERE id_reservation = :id";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bindParam(':status', $new_status, PDO::PARAM_STR);
                $stmt_update->bindParam(':id', $reservation_id, PDO::PARAM_INT);
                
                if ($stmt_update->execute()) {
                    $message = "Le statut de la réservation a été mis à jour avec succès.";
                } else {
                    $message = "Une erreur est survenue lors de la mise à jour du statut.";
                }
            } elseif ($action === 'delete') {
                $sql_update = "UPDATE reservation SET is_deleted = 1 WHERE id_reservation = :id";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bindParam(':id', $reservation_id, PDO::PARAM_INT);
                
                if ($stmt_update->execute()) {
                    $message = "La réservation a été supprimée avec succès.";
                } else {
                    $message = "Une erreur est survenue lors de la suppression.";
                }
            }
        } catch (PDOException $e) {
            $message = "Erreur: " . $e->getMessage();
        }
    }
}

if ($id > 0) {
    // Fetch lawyer statistics
    $sql_stats = "
    SELECT 
        SUM(pourcentage_sans_acute) AS count_pourcentage_sans_acute,
        SUM(pourcentage_capacite_jugement) AS count_pourcentage_capacite_jugement,
        SUM(pourcentage_connaissance_approfondie) AS count_pourcentage_connaissance_approfondie
    FROM info_avocat
    WHERE id_avocat = :id
    ";

    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_stats->execute();
    $result_stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // Fetch reservations with client information
    $sql_reservations = "
    SELECT r.id_reservation, r.id_client, r.date_reservation, r.validation, r.description,
           u.nom, u.prenom, u.Matricule
    FROM reservation r
    JOIN user_ u ON r.id_client = u.id_user
    WHERE r.id_avocat = :id AND r.is_deleted = 0
    ORDER BY r.date_reservation DESC
    ";

    $stmt_reservations = $conn->prepare($sql_reservations);
    $stmt_reservations->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_reservations->execute();
    $reservations = $stmt_reservations->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de l'avocat</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
            text-align: center;
        }
        #chart_div {
            width: 100%;
            height: 300px;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn-toggle {
            background-color: #4CAF50;
            color: white;
        }
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .action-form {
            display: inline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard de l'avocat</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($id > 0 && $result_stats): ?>
            <div id="chart_div"></div>
            <script type="text/javascript">
                google.charts.load('current', {'packages':['corechart']});
                google.charts.setOnLoadCallback(drawChart);

                function drawChart() {
                    var data = google.visualization.arrayToDataTable([
                        ['Compétence', 'Pourcentage'],
                        ['Sans acuité', <?php echo $result_stats['count_pourcentage_sans_acute']; ?>],
                        ['Capacité de jugement', <?php echo $result_stats['count_pourcentage_capacite_jugement']; ?>],
                        ['Connaissance approfondie', <?php echo $result_stats['count_pourcentage_connaissance_approfondie']; ?>]
                    ]);

                    var options = {
                        title: 'Répartition des compétences',
                        is3D: true,
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
                    chart.draw(data, options);
                }
            </script>

            <h2>Réservations</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Nom et Prénom</th>
                        <th>Matricule</th>
                        <th>Description</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reservation['date_reservation']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['nom'] . ' ' . $reservation['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['Matricule']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['description']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['validation']); ?></td>
                            <td>
                                <form method="POST" action="" class="action-form">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id_reservation']; ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <button type="submit" class="btn btn-toggle">
                                        <?php echo $reservation['validation'] === 'Valider' ? 'Laisser en attente' : 'Valider'; ?>
                                    </button>
                                </form>
                                
                                <form method="POST" action="" class="action-form">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id_reservation']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucune donnée disponible pour cet avocat.</p>
        <?php endif; ?>
    </div>
</body>
</html>

