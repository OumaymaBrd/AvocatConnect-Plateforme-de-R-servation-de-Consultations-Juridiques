<?php
require '../connexion/connexion.php';

$id_specialite = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupérer les dates bloquées depuis la base de données
$blockedDates = [];
try {
    $stmt = $conn->prepare("SELECT date_occuper FROM blocked_dates WHERE id_specialite = :id_specialite");
    $stmt->bindParam(':id_specialite', $id_specialite, PDO::PARAM_INT);
    $stmt->execute();
    $blockedDates = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sélection de date</title>
    <style>
        /* Style pour les dates désactivées */
        input[type="date"]::-webkit-calendar-picker-indicator {
            background: transparent;
            color: transparent;
            cursor: pointer;
            height: 100%;
            left: 0;
            position: absolute;
            right: 0;
            width: auto;
        }
    </style>
</head>
<body>
    <label for="date">Sélectionnez une date :</label>
    <input type="date" id="date" name="date" required>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var dateInput = document.getElementById('date');
            var blockedDates = <?php echo json_encode($blockedDates); ?>;
          
            var blockedDatesObj = blockedDates.map(date => new Date(date));
            
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            dateInput.setAttribute('min', today.toISOString().split('T')[0]);

            function isDateBlocked(date) {
                return blockedDatesObj.some(blockedDate => 
                    blockedDate.getFullYear() === date.getFullYear() &&
                    blockedDate.getMonth() === date.getMonth() &&
                    blockedDate.getDate() === date.getDate()
                );
            }

            // Désactiver les dates bloquées
            dateInput.addEventListener('click', function(e) {
                if(this.value && isDateBlocked(new Date(this.value))) {
                    this.value = '';
                }
            });

            dateInput.addEventListener('change', function(e) {
                var selectedDate = new Date(this.value);
                selectedDate.setHours(0, 0, 0, 0);

                // Vérifier si la date est bloquée
                if(isDateBlocked(selectedDate)) {
                    alert('Cette date n\'est pas disponible.');
                    this.value = ''; // Réinitialiser la valeur
                    return false;
                }

                // Vérifier si la date est dans le passé
                if(selectedDate < today) {
                    alert('Veuillez sélectionner une date future.');
                    this.value = '';
                    return false;
                }
            });

            // Empêcher la saisie manuelle
            dateInput.addEventListener('keydown', function(e) {
                e.preventDefault();
                return false;
            });
        });
    </script>
</body>
</html>