<?php
require '../connexion/connexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $sql = "
    SELECT 
       
        SUM(pourcentage_sans_acute) AS count_pourcentage_sans_acute,
        SUM(pourcentage_capacite_jugement) AS count_pourcentage_capacite_jugement,
        SUM(pourcentage_connaissance_approfondie) AS count_pourcentage_connaissance_approfondie
    FROM info_avocat
    WHERE id_avocat = :id
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques de l'avocat</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
  
        h1 {
            color: #333;
            text-align: center;
        }
        #chart_div {
            width: 800px;
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Statistiques de l'avocat</h1>
        <?php if ($id > 0 && $result): ?>
            <div id="chart_div"></div>
            <script type="text/javascript">
                google.charts.load('current', {'packages':['corechart']});
                google.charts.setOnLoadCallback(drawChart);

                function drawChart() {
                    var data = google.visualization.arrayToDataTable([
                        ['Compétence', 'Pourcentage'],
                        ['Sans acuité', <?php echo $result['count_pourcentage_sans_acute']; ?>],
                        ['Capacité de jugement', <?php echo $result['count_pourcentage_capacite_jugement']; ?>],
                        ['Connaissance approfondie', <?php echo $result['count_pourcentage_connaissance_approfondie']; ?>]
                    ]);

                    var options = {
                        title: 'Répartition des compétences',
                        is3D: true,
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
                    chart.draw(data, options);
                }
            </script>
        <?php else: ?>
            <p>Aucune donnée disponible pour cet avocat.</p>
        <?php endif; ?>
    </div>
</body>
</html>