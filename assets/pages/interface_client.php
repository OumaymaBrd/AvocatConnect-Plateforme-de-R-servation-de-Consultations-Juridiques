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
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert_appointment'])) {
    $date = $_POST['appointment_date'];
    $description = $_POST['appointment_description'];

    $sqlInsert = "INSERT INTO reservation (date_reservation, description) VALUES (:date_reservation, :description)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $result = $stmtInsert->execute([
        ':date_reservation' => $date,
        ':description' => $description
    ]);

    if ($result) {
        $message = "Le rendez-vous a été enregistré avec succès.";
    } else {
        $message = "Une erreur est survenue lors de l'enregistrement du rendez-vous.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.2/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/style_client.css">
</head>


<body>
    <?php if ($message): ?>
        <div id="message" style="display:none;"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="grid" id="lawyers-grid">
        <?php foreach ($avocats as $avocat): 
            $stmtPourcentages->execute(['id_avocat' => $avocat['id_user']]);
            $pourcentages = $stmtPourcentages->fetch(PDO::FETCH_ASSOC);
        ?>
            <div class="card" data-lawyer-id="<?php echo htmlspecialchars($avocat['id_user']); ?>">
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
                        <div class="name"><?php echo htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']); ?></div>
                        <button class="arrow-container add-reservation" data-lawyer-id="<?php echo htmlspecialchars($avocat['id_user']); ?>">
                            +
                        </button>
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
                    <div class="appointment-info" style="display: none;">
                        <h4>Rendez-vous prévu</h4>
                        <p class="appointment-date"></p>
                        <p class="appointment-description"></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.2/dist/sweetalert2.all.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addReservationButtons = document.querySelectorAll('.add-reservation');
        const message = document.getElementById('message');

        if (message) {
            Swal.fire({
                title: 'Notification',
                text: message.textContent,
                icon: message.textContent.includes('succès') ? 'success' : 'error'
            });
        }

        addReservationButtons.forEach(button => {
            button.addEventListener('click', function() {
                const lawyerId = this.getAttribute('data-lawyer-id');
                showAppointmentForm(lawyerId);
            });
        });

        function showAppointmentForm(lawyerId) {
            Swal.fire({
                title: "Planifier un rendez-vous",
                html: `
                    <form id="appointmentForm" method="POST">
                        <input type="hidden" name="insert_appointment" value="1">
                        <div class="mb-3">
                            <label for="appointmentDate" class="form-label">Choisissez une date:</label>
                            <input type="date" class="form-control" id="appointmentDate" name="appointment_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentDescription" class="form-label">Description:</label>
                            <textarea class="form-control" id="appointmentDescription" name="appointment_description" rows="3" required></textarea>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Planifier',
                cancelButtonText: 'Annuler',
                preConfirm: () => {
                    const form = document.getElementById('appointmentForm');
                    if (!form.checkValidity()) {
                        Swal.showValidationMessage('Veuillez remplir tous les champs requis');
                        return false;
                    }
                    return true;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('appointmentForm').submit();
                }
            });
        }
    });
    </script>
</body>
</html>

