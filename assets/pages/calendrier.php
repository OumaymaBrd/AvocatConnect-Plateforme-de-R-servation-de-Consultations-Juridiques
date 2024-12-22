<?php
require '../connexion/connexion.php';

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
            
            // Redirection vers interface_avocat.php après insertion réussie
            header('Location: interface_avocat.php');
            exit;
        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "Erreur lors de l'enregistrement des dates.";
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
    <title>Sélection de dates non disponibles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.js'></script>
    <style>
        #calendar { max-width: 800px; margin: 0 auto; }
        .fc-day-future { cursor: pointer; }
        .fc-day-future:hover { background-color: #f0f0f0; }
        .unavailable { background-color: #ffcccb !important; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Sélection de dates non disponibles</h1>
        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div id='calendar'></div>
        
        <div class="text-center mt-4">
            <button type="button" class="btn btn-primary" id="submitDates">Valider</button>
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

        calendar.render();

        document.getElementById('submitDates').addEventListener('click', function() {
            var unavailableDates = [];
            document.querySelectorAll('.unavailable').forEach(function(el) {
                unavailableDates.push(el.getAttribute('data-date'));
            });

            var form = document.createElement('form');
            form.method = 'post';
            form.style.display = 'none';

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_dates';
            input.value = JSON.stringify(unavailableDates);

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        });
    });
    </script>
</body>
</html>

