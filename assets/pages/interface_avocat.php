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

$message = '';
$error = '';

// Récupération des stages
$sql = "SELECT * FROM demande_stage ORDER BY id_stagiaire DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$stages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la mise à jour du statut
if(isset($_POST['update_status'])) {
    $id = intval($_POST['id_stagiaire']);
    $action = $_POST['action'];
    
    $newStatus = ($action === 'Valider') ? 'Validé' : "Dans l'attente de la libération des places";
    
    try {
        $updateSql = "UPDATE demande_stage SET validation_avocat = :status WHERE id_stagiaire = :id";
        $updateStmt = $conn->prepare($updateSql);
        $result = $updateStmt->execute([':status' => $newStatus, ':id' => $id]);
        
        if($result) {
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès', 'newStatus' => $newStatus]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
        exit;
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        exit;
    }
}


$message = '';
$id_specialite = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_dates'])) {
    $selectedDates = json_decode($_POST['selected_dates'], true);
    
    if (!empty($selectedDates) && $id_specialite > 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO blocked_dates (date_occuper, id_specialite) VALUES (:date, :id_specialite)");
            $stmt->bindParam(':id_specialite', $id_specialite, PDO::PARAM_INT);
            
            $conn->beginTransaction();
            foreach ($selectedDates as $date) {
                $stmt->bindParam(':date', $date);
                $stmt->execute();
            }
            $conn->commit();
            
            echo "Les dates ont été enregistrées avec succès.";
            exit;
        } catch (PDOException $e) {
            $conn->rollBack();
            echo "Erreur lors de l'enregistrement des dates.";
            exit;
        }
    }
}

$blockedDates = [];
if ($id_specialite > 0) {
    try {
        $stmt = $conn->prepare("SELECT date_occuper FROM blocked_dates WHERE id_specialite = :id_specialite");
        $stmt->bindParam(':id_specialite', $id_specialite, PDO::PARAM_INT);
        $stmt->execute();
        $blockedDates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $message = "Erreur lors de la récupération des dates bloquées.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de l'avocat</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

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

        .container { max-width: 1200px; margin-top: 30px; }
        .status-pending { color: orange; }
        .status-approved { color: green; }
        .status-rejected { color: red; }

        #calendar { max-width: 800px; margin: 0 auto; }
        .fc-day-future { cursor: pointer; }
        .fc-day-future:hover { background-color: #f0f0f0; }
        .unavailable { background-color: #ffcccb !important; }
        #messageContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: none;
        }

        #calendar { max-width: 800px; margin: 0 auto; }
        .fc-day-future { cursor: pointer; }
        .fc-day-future:hover { background-color: #f0f0f0; }
        .unavailable { background-color: #ffcccb !important; }
        #messageContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: none;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.js'></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.js'></script>
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


<div>
      
        <button id="showStagiairesBtn" class="btn btn-primary mb-3">Afficher</button>

        <!-- Modal -->
        <div class="modal fade" id="stagiairesModal" tabindex="-1" aria-labelledby="stagiairesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="stagiairesModalLabel">Liste des Stagiaires</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Email</th>
                                        <th>Durée (mois)</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stages as $stage): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stage['id_stagiaire']); ?></td>
                                            <td><?php echo htmlspecialchars($stage['nom']); ?></td>
                                            <td><?php echo htmlspecialchars($stage['prenom']); ?></td>
                                            <td><?php echo htmlspecialchars($stage['email']); ?></td>
                                            <td><?php echo htmlspecialchars($stage['Duree_stage_mois']); ?></td>
                                            <td class="status-cell">
                                                <span class="status-<?php echo strtolower(str_replace(' ', '-', $stage['validation_avocat'])); ?>">
                                                    <?php echo htmlspecialchars($stage['validation_avocat']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-success btn-sm update-status" 
                                                        data-id="<?php echo $stage['id_stagiaire']; ?>" 
                                                        data-action="Valider">
                                                    Valider
                                                </button>
                                                <button class="btn btn-danger btn-sm update-status" 
                                                        data-id="<?php echo $stage['id_stagiaire']; ?>" 
                                                        data-action="Refuser">
                                                    Refuser
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        var modal = new bootstrap.Modal(document.getElementById('stagiairesModal'));

        $('#showStagiairesBtn').click(function() {
            modal.show();
        });

        $('.update-status').click(function() {
            var id = $(this).data('id');
            var action = $(this).data('action');
            var row = $(this).closest('tr');

            if (confirm('Voulez-vous vraiment ' + action.toLowerCase() + ' cette demande de stage ?')) {
                updateStatus(id, action, row);
            }
        });

        function updateStatus(id_stagiaire, action, row) {
            $.ajax({
                url: 'gestion-stagiaires.php',
                method: 'POST',
                data: {
                    update_status: 1,
                    id_stagiaire: id_stagiaire,
                    action: action
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        var statusCell = row.find('.status-cell');
                        statusCell.html('<span class="status-' + data.newStatus.toLowerCase().replace(/ /g, '-') + '">' + data.newStatus + '</span>');
                        alert(data.message);
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                },
                error: function() {
                    alert('Une erreur est survenue');
                }
            });
        }
    });
    </script>

<div >
        
        <div id="messageContainer" class="alert alert-success"></div>
        
        <div >
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#calendarModal">
                Sélectionner une date
            </button>
        </div>

        <div class="modal fade" id="calendarModal" tabindex="-1" aria-labelledby="calendarModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="calendarModalLabel">Sélectionnez vos dates non disponibles</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id='calendar'></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" id="submitDates">Valider</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var blockedDates = <?php echo json_encode($blockedDates); ?>;
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridYear'
            },
            selectable: true,
            dateClick: function(info) {
                if (info.date >= new Date()) {
                    info.dayEl.classList.toggle('unavailable');
                }
            },
            events: blockedDates.map(function(date) {
                return {
                    start: date,
                    display: 'background',
                    color: '#ffcccb'
                };
            })
        });

        var calendarModal = document.getElementById('calendarModal');
        calendarModal.addEventListener('shown.bs.modal', function () {
            calendar.render();
        });

        document.getElementById('submitDates').addEventListener('click', function() {
            var unavailableDates = [];
            document.querySelectorAll('.unavailable').forEach(function(el) {
                unavailableDates.push(el.getAttribute('data-date'));
            });

            var formData = new FormData();
            formData.append('selected_dates', JSON.stringify(unavailableDates));

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(message => {
                var messageContainer = document.getElementById('messageContainer');
                messageContainer.textContent = message;
                messageContainer.style.display = 'block';
                
                // Fermer le modal
                var modal = bootstrap.Modal.getInstance(calendarModal);
                modal.hide();
                
                // Cacher le message après 3 secondes
                setTimeout(function() {
                    messageContainer.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                var messageContainer = document.getElementById('messageContainer');
                messageContainer.textContent = "Une erreur s'est produite.";
                messageContainer.style.display = 'block';
            });
        });
    });
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
<!--  -->

