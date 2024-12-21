<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - Cabinet d'Avocats</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.2/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/style_client.css">
    <link rel="stylesheet" href="../css/style_.css">
    <link rel="stylesheet" href="../css/modal.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .appointment-modal {
            width: 500px;
        }
        .swal2-confirm {
            background-color: #28a745; /* Green */
            border-color: #28a745;
        }
        .swal2-cancel {
            background-color: #dc3545; /* Red */
            border-color: #dc3545;
        }
    </style>
</head>
<body>

    <header>
        <nav>
            <div class="logo">Cabinet Martin & Associés</div>
            <ul>
                <li><a href="index.html">Accueil</a></li>
                <li><a href="reservation.html">Réservations</a></li>
                <li><a href="login.php">Connexion</a></li>
                <li><a href="register.html">Inscription</a></li>
            </ul>
        </nav>
    </header>

    <br> <br> <br> 

    <?php
    require '../connexion/connexion.php';

    // Fetch client ID from URL
    $id_client = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $client_name = '';

    // Initial query to get lawyer information with all details
    $sql = "SELECT user_.id_user, biographie, derniere_diplome, adresse, nom, prenom, email, tel,
                   pourcentage_sans_acute, pourcentage_capacite_jugement, pourcentage_connaissance_approfondie 
            FROM info_avocat 
            JOIN user_ ON user_.id_user = info_avocat.id_avocat";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $avocats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle appointment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert_appointment'])) {
        $avocat_id = $_POST['avocat_id'];
        $date = $_POST['appointment_date'];
        $description = $_POST['appointment_description'];
        $rating_type = $_POST['rating_type'];

        try {
            $conn->beginTransaction();

            // Insert appointment
            $sqlInsert = "INSERT INTO reservation (id_avocat, date_reservation, description, id_client) 
                          VALUES (:id_avocat, :date_reservation, :description, :id_client)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $resultInsert = $stmtInsert->execute([
                ':id_avocat' => $avocat_id,
                ':date_reservation' => $date,
                ':description' => $description,
                ':id_client' => $id_client
            ]);

            // Update lawyer rating
            $sqlUpdate = "UPDATE info_avocat SET $rating_type = $rating_type + 1 WHERE id_avocat = :id_avocat";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $resultUpdate = $stmtUpdate->execute([':id_avocat' => $avocat_id]);

            if ($resultInsert && $resultUpdate) {
                $conn->commit();
                $message = "Le rendez-vous a été enregistré avec succès et la note de l'avocat a été mise à jour.";
            } else {
                $conn->rollBack();
                $message = "Une erreur est survenue lors de l'enregistrement du rendez-vous ou de la mise à jour de la note.";
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "Erreur: " . $e->getMessage();
        }
    }

    // Handle rating updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rating'])) {
        $avocat_id = $_POST['avocat_id'];
        $rating_type = $_POST['rating_type'];
        $action = $_POST['action']; // 'increment' or 'decrement'

        try {
            $sqlUpdate = "UPDATE info_avocat SET $rating_type = $rating_type " . ($action === 'increment' ? '+' : '-') . " 1 WHERE id_avocat = :id_avocat";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $result = $stmtUpdate->execute([':id_avocat' => $avocat_id]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Avis mis à jour avec succès.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'avis.']);
            }
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
            exit;
        }
    }
    ?>

    <?php if (isset($message)): ?>
        <div id="message" style="display:none;"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php  if ($id_client > 0) {
    try {
        $sql_client = "SELECT nom, prenom FROM user_ WHERE id_user = :id_client";
        $stmt_client = $conn->prepare($sql_client);
        $stmt_client->bindParam(':id_client', $id_client, PDO::PARAM_INT);
        $stmt_client->execute();
        $client_result = $stmt_client->fetch(PDO::FETCH_ASSOC);
        
        if ($client_result) {
          echo  $client_name ='Bienvenue '. htmlspecialchars($client_result['prenom'] . ' ' . $client_result['nom']);
        } else {
            $error_message = "Client non trouvé.";
        }
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la récupération des informations du client: " . $e->getMessage();
    }
}  ?>

    <div class="grid" id="lawyers-grid">
        <?php foreach ($avocats as $avocat): ?>
            <div class="card" data-lawyer-id="<?php echo htmlspecialchars($avocat['id_user']); ?>">
                <div class="card-inner">
                    <div class="column-icon">
                        <svg viewBox="0 0 100 120" fill="currentColor">
                            <path d="M20,0 h15 v120 h-15 Z M45,20 h15 v100 h-15 Z M70,40 h15 v80 h-15 Z M95,60 h15 v60 h-15 Z" />
                        </svg>
                    </div>
                    <div class="content">
                        <div class="city">
                            <?php echo htmlspecialchars($avocat['tel'] ?? 'Aucun Numero ') ."<br>";
                            echo htmlspecialchars($avocat['email'] ?? 'Aucun Email '); ?>
                        </div>
                        <div class="name"><?php echo htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']); ?></div>
                        <button class="arrow-container add-reservation" 
                                data-lawyer-id="<?php echo htmlspecialchars($avocat['id_user']); ?>"
                                data-lawyer-name="<?php echo htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']); ?>">
                            +
                        </button>
                    </div>
                    <div class="hover-info">
                        <h3><?php echo htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']); ?></h3>
                        <p><?php echo htmlspecialchars($avocat['biographie'] ?? ''); ?></p>
                        <p>Diplôme: <?php echo htmlspecialchars($avocat['derniere_diplome'] ?? ''); ?></p>
                        <p>Adresse: <?php echo htmlspecialchars($avocat['adresse'] ?? ''); ?></p>
                        <p>Email: <?php echo htmlspecialchars($avocat['email'] ?? ''); ?></p>
                        <p>Tél: <?php echo htmlspecialchars($avocat['tel'] ?? ''); ?></p>
                        <div class="percentages">
                            <div class="percentage">
                                <div class="percentage-value"><?php echo htmlspecialchars($avocat['pourcentage_sans_acute']); ?>%</div>
                                <div class="percentage-label">Sans acuité</div>
                            </div>
                            <div class="percentage">
                                <div class="percentage-value"><?php echo htmlspecialchars($avocat['pourcentage_capacite_jugement']); ?>%</div>
                                <div class="percentage-label">Capacité de jugement</div>
                            </div>
                            <div class="percentage">
                                <div class="percentage-value"><?php echo htmlspecialchars($avocat['pourcentage_connaissance_approfondie']); ?>%</div>
                                <div class="percentage-label">Connaissance approfondie</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <form class="rating-form" data-lawyer-id="<?php echo htmlspecialchars($avocat['id_user']); ?>">
                <label>Ajouter Votre Avis : </label>
                <div>
                    <label for="sans_acute_<?php echo $avocat['id_user']; ?>">Sans Acute</label>
                    <input type="checkbox" id="sans_acute_<?php echo $avocat['id_user']; ?>" name="pourcentage_sans_acute" 
                           <?php echo $avocat['pourcentage_sans_acute'] > 0 ? 'checked' : ''; ?>>
                </div>
                <div>
                    <label for="capacite_jugement_<?php echo $avocat['id_user']; ?>">Capacité de jugement</label>
                    <input type="checkbox" id="capacite_jugement_<?php echo $avocat['id_user']; ?>" name="pourcentage_capacite_jugement"
                           <?php echo $avocat['pourcentage_capacite_jugement'] > 0 ? 'checked' : ''; ?>>
                </div>
                <div>
                    <label for="connaissance_approfondie_<?php echo $avocat['id_user']; ?>">Connaissance approfondie</label>
                    <input type="checkbox" id="connaissance_approfondie_<?php echo $avocat['id_user']; ?>" name="pourcentage_connaissance_approfondie"
                           <?php echo $avocat['pourcentage_connaissance_approfondie'] > 0 ? 'checked' : ''; ?>>
                </div>
            </form>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.2/dist/sweetalert2.all.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addReservationButtons = document.querySelectorAll('.add-reservation');
        const message = document.getElementById('message');
        const ratingForms = document.querySelectorAll('.rating-form');

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
                const lawyerName = this.getAttribute('data-lawyer-name');
                showAppointmentForm(lawyerId, lawyerName);
            });
        });

        ratingForms.forEach(form => {
            const lawyerId = form.getAttribute('data-lawyer-id');
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateRating(lawyerId, this.name, this.checked ? 'increment' : 'decrement');
                });
            });
        });

        function showAppointmentForm(lawyerId, lawyerName) {
            Swal.fire({
                title: "Planifier un rendez-vous",
                html: `
                    <form id="appointmentForm" method="POST" class="appointment-form">
                        <input type="hidden" name="insert_appointment" value="1">
                        <input type="hidden" name="avocat_id" value="${lawyerId}">
                        
                        <div class="form-group">
                            <label for="avocat">Avocat sélectionné:</label>
                            <input type="text" id="avocat" value="${lawyerName}" readonly class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="appointmentDate">Date du rendez-vous:</label>
                            <input type="date" class="form-control" id="appointmentDate" name="appointment_date" required>
                        </div>

                        <div class="form-group">
                            <label for="appointmentDescription">Description:</label>
                            <textarea class="form-control" id="appointmentDescription" name="appointment_description" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="ratingType">Type de notation:</label>
                            <select class="form-control" id="ratingType" name="rating_type" required>
                                <option value="pourcentage_sans_acute">Sans acuité</option>
                                <option value="pourcentage_capacite_jugement">Capacité de jugement</option>
                                <option value="pourcentage_connaissance_approfondie">Connaissance approfondie</option>
                            </select>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Planifier',
                cancelButtonText: 'Annuler',
                customClass: {
                    popup: 'appointment-modal',
                    confirmButton: 'swal2-confirm',
                    cancelButton: 'swal2-cancel'
                },
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

        function updateRating(lawyerId, ratingType, action) {
            fetch('show-appointments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `update_rating=1&avocat_id=${lawyerId}&rating_type=${ratingType}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(data.message);
                    // You might want to update the UI here to reflect the new rating
                } else {
                    console.error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    });
    </script>

</body>
</html>

