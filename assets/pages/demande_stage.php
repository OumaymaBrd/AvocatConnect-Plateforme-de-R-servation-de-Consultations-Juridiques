<?php
require '../connexion/connexion.php';

$message = '';
$error = '';

if(isset($_POST['envoyer'])){
    $nom = trim(htmlspecialchars($_POST['nom']));
    $prenom = trim(htmlspecialchars($_POST['prenom']));
    $email = trim(htmlspecialchars($_POST['email']));
    $duree = intval($_POST['Duree_stage_mois']);
    
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
            $sql = "INSERT INTO demande_stage (nom, prenom, email, Duree_stage_mois, validation_avocat) 
                    VALUES (:nom, :prenom, :email, :duree, :validation)";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':duree' => $duree,
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

$sql = "SELECT * FROM demande_stage ORDER BY id_stagiaire DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$stages = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Demandes de Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 30px; }
        .status-pending { color: orange; }
        .status-approved { color: green; }
        .status-rejected { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Formulaire de Demande de Stage</h2>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="stageForm" class="needs-validation mb-4" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                
                <div class="col-md-6">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input type="text" class="form-control" id="prenom" name="prenom" required>
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="col-md-6">
                    <label for="Duree_stage_mois" class="form-label">Durée du stage (mois)</label>
                    <select class="form-select" id="Duree_stage_mois" name="Duree_stage_mois" required>
                        <option value="">Choisir la durée</option>
                        <?php for($i = 2; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> mois</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" name="envoyer" class="btn btn-primary">Envoyer la demande</button>
                <button type="button" class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#stagesModal">
                    Afficher les demandes
                </button>
            </div>
        </form>

        <div class="modal fade" id="stagesModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Liste des Demandes de Stage</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        document.querySelectorAll('.update-status').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const action = this.dataset.action;
                const row = this.closest('tr');

                Swal.fire({
                    title: 'Confirmation',
                    text: `Voulez-vous vraiment ${action.toLowerCase()} cette demande de stage ?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Oui',
                    cancelButtonText: 'Non'
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateStatus(id, action, row);
                    }
                });
            });
        });

        function updateStatus(id_stagiaire, action, row) {
            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('id_stagiaire', id_stagiaire);
            formData.append('action', action);

            fetch('demande-stage.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const statusCell = row.querySelector('.status-cell');
                    statusCell.innerHTML = `<span class="status-${data.newStatus.toLowerCase().replace(/ /g, '-')}">${data.newStatus}</span>`;
                    Swal.fire('Succès', data.message, 'success');
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                console.error('Error:', error);
            });
        }
    });
    </script>
</body>
</html>

