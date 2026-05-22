<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole('client');

$userId = $_SESSION['user_id'];

// Récupérer les favoris du client avec leurs images
$favoritesStmt = $db->prepare("SELECT b.*, 
                              (SELECT p.url FROM photo p WHERE p.id_bien = b.id_bien LIMIT 1) as photo_url
                              FROM bienfavoris f
                              JOIN biensimmobilier b ON f.id_bien = b.id_bien
                              WHERE f.id_utilisateur = ?");
$favoritesStmt->execute([$userId]);
$favorites = $favoritesStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les rendez-vous du client avec plus d'informations
$appointmentsStmt = $db->prepare("SELECT r.*, b.type, b.adresse, b.prix,
                                 (SELECT p.url FROM photo p WHERE p.id_bien = b.id_bien LIMIT 1) as photo_url
                                 FROM rendezvous r
                                 JOIN biensimmobilier b ON r.id_bien = b.id_bien
                                 WHERE r.id_client = ? AND r.date_rdv >= CURDATE()
                                 ORDER BY r.date_rdv");
$appointmentsStmt->execute([$userId]);
$appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="dashboard-container">
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success animate-fade-in">
        <?php if (is_array($_SESSION['success_message']) && isset($_SESSION['success_message']['image'])): ?>
            <div class="alert-image">
                <img src="../assets/uploads/<?php echo htmlspecialchars($_SESSION['success_message']['image']); ?>" 
                     alt="<?php echo htmlspecialchars($_SESSION['success_message']['type'] ?? ''); ?>">
            </div>
        <?php endif; ?>
        <div class="alert-content">
            <h3>Succès !</h3>
            <p><?php echo htmlspecialchars(is_array($_SESSION['success_message']) ? $_SESSION['success_message']['message'] : $_SESSION['success_message']); ?></p>
        </div>
        <button class="alert-close">&times;</button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

    <?php if (isset($_SESSION['info_message'])): ?>
        <div class="alert alert-info animate-fade-in">
            <div class="alert-content">
                <h3>Information</h3>
                <p><?php echo htmlspecialchars($_SESSION['info_message']); ?></p>
            </div>
            <button class="alert-close">&times;</button>
        </div>
        <?php unset($_SESSION['info_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error animate-fade-in">
            <div class="alert-content">
                <h3>Erreur</h3>
                <p><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
            </div>
            <button class="alert-close">&times;</button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="dashboard-header">
        <h1 class="welcome-title">Bienvenue, <span><?php echo htmlspecialchars($_SESSION['user_prenom']); ?></span></h1>
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($favorites); ?></h3>
                    <p>Biens favoris</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($appointments); ?></h3>
                    <p>Rendez-vous</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-section animate-slide-up">
        <div class="section-header">
            <h2><i class="fas fa-heart"></i> Vos biens favoris</h2>
            <?php if (!empty($favorites)): ?>
                <div class="section-actions">
                    <button class="btn btn-secondary" id="toggle-favorites-view">
                        <i class="fas fa-th-list"></i> Changer vue
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($favorites)): ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken"></i>
                <h3>Aucun bien favori</h3>
                <p>Vous n'avez pas encore ajouté de biens à vos favoris.</p>
                <a href="../proprietes/biensimmobilier.php" class="btn btn-primary">Parcourir les biens</a>
            </div>
        <?php else: ?>
            <div class="properties-grid" id="favorites-grid">
                <?php foreach ($favorites as $bien): ?>
                    <div class="property-card">
                        <div class="property-badge">
                            <span class="badge badge-<?php echo strtolower($bien['type']); ?>">
                                <?php echo htmlspecialchars($bien['type']); ?>
                            </span>
                        </div>
                        <div class="property-image-container">
                            <img src="../assets/uploads/<?php echo htmlspecialchars($bien['photo_url'] ?? 'default.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($bien['type']); ?>"
                                 class="property-image">
                            <div class="property-overlay">
                                <a href="../proprietes/details.php?id_bien=<?php echo $bien['id_bien']; ?>" class="btn btn-icon">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="retirer_favori.php?id_bien=<?php echo $bien['id_bien']; ?>" class="btn btn-icon btn-danger">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        <div class="property-details">
                            <h3><?php echo htmlspecialchars($bien['titre'] ?? $bien['type']); ?></h3>
                            <p class="property-address">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($bien['adresse']); ?>
                            </p>
                            
                            <p class="property-price"><?php echo number_format($bien['prix'], 0, ',', ' '); ?> FCFA</p>
                            <div class="property-actions">
                                <a href="../proprietes/details.php?id_bien=<?php echo $bien['id_bien']; ?>" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Voir détails
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-section animate-slide-up">
        <div class="section-header">
            <h2><i class="fas fa-calendar-alt"></i> Vos rendez-vous à venir</h2>
            <?php if (!empty($appointments)): ?>
                <div class="section-actions">
                    <button class="btn btn-secondary" id="toggle-appointments-view">
                        <i class="fas fa-th-list"></i> Changer vue
                    </button>
                    <a href="../proprietes/biensimmobilier.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouveau rendez-vous
                    </a>
                </div>
            <?php else: ?>
                <div class="section-actions">
                    <a href="../proprietes/biensimmobilier.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouveau rendez-vous
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($appointments)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>Aucun rendez-vous programmé</h3>
                <p>Vous n'avez pas de visite prévue pour le moment.</p>
                <a href="../proprietes/biensimmobilier.php" class="btn btn-primary">Prendre rendez-vous</a>
            </div>
        <?php else: ?>
            <div class="appointments-container" id="appointments-container">
                <?php foreach ($appointments as $rdv): ?>
                    <div class="appointment-card">
                        <div class="appointment-date">
                            <div class="date-day"><?php echo date('d', strtotime($rdv['date_rdv'])); ?></div>
                            <div class="date-month"><?php echo strtoupper(date('M', strtotime($rdv['date_rdv']))); ?></div>
                            
                        </div>
                        <div class="appointment-details">
                            <div class="appointment-property">
                                <img src="../assets/uploads/<?php echo htmlspecialchars($rdv['photo_url'] ?? 'default.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($rdv['type']); ?>"
                                     class="property-thumbnail">
                                <div class="property-info">
                                    <h4><?php echo htmlspecialchars($rdv['objet']); ?></h4>
                                    <p><?php echo htmlspecialchars($rdv['type']); ?> - <?php echo number_format($rdv['prix'], 0, ',', ' '); ?> FCFA</p>
                                    <p class="property-address">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($rdv['adresse']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="appointment-status">
                                <span class="status-badge status-<?php echo $rdv['statut']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $rdv['statut'])); ?>
                                </span>
                            </div>
                            <div class="appointment-actions">
                                <?php if ($rdv['statut'] === 'en_attente'): ?>
                                    <a href="annuler_rdv.php?id_rdv=<?php echo $rdv['id_rdv']; ?>" class="btn btn-sm btn-danger">
                                        <i class="fas fa-times"></i> Annuler
                                    </a>
                                <?php endif; ?>
                                <a href="../proprietes/details.php?id_bien=<?php echo $rdv['id_bien']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-info-circle"></i> Détails
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<!-- Modern CSS with animations and responsive design -->
<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --danger-color: #f72585;
    --success-color: #4cc9f0;
    --warning-color: #f8961e;
    --dark-color: #212529;
    --light-color: #f8f9fa;
    --gray-color: #6c757d;
    --border-radius: 8px;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: var(--dark-color);
    background-color: #f5f7fa;
}

.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.welcome-title {
    font-size: 2rem;
    color: var(--dark-color);
}

.welcome-title span {
    color: var(--primary-color);
    font-weight: 600;
}

.quick-stats {
    display: flex;
    gap: 15px;
}

.stat-card {
    background: white;
    padding: 15px 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.stat-content h3 {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.stat-content p {
    color: var(--gray-color);
    font-size: 0.9rem;
}

.dashboard-section {
    margin-bottom: 40px;
    padding: 25px;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.section-header h2 {
    font-size: 1.5rem;
    color: var(--dark-color);
    display: flex;
    align-items: center;
    gap: 10px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray-color);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #dee2e6;
}

.empty-state h3 {
    margin-bottom: 10px;
    color: var(--dark-color);
}

.empty-state p {
    margin-bottom: 20px;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

/* Style pour la vue en liste des favoris */
.properties-grid.list-view {
    grid-template-columns: 1fr;
}

.properties-grid.list-view .property-card {
    display: flex;
    flex-direction: row;
    height: auto;
}

.properties-grid.list-view .property-image-container {
    width: 250px;
    height: 180px;
    flex-shrink: 0;
}

.properties-grid.list-view .property-details {
    flex: 1;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.properties-grid.list-view .property-actions {
    margin-top: 0;
    align-self: flex-end;
}

.property-card {
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: var(--transition);
    position: relative;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.property-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    z-index: 2;
}

.badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
}

.badge-appartement {
    background-color: #3a86ff;
}

.badge-maison {
    background-color: #8338ec;
}

.badge-terrain {
    background-color: #ff006e;
}

.badge-villa {
    background-color: #fb5607;
}

.property-image-container {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.property-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.property-card:hover .property-image {
    transform: scale(1.05);
}

.property-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    opacity: 0;
    transition: var(--transition);
}

.property-card:hover .property-overlay {
    opacity: 1;
}

.property-details {
    padding: 20px;
}

.property-details h3 {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: var(--dark-color);
}

.property-address {
    color: var(--gray-color);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
}

.property-meta {
    display: flex;
    gap: 15px;
    margin: 15px 0;
    font-size: 0.85rem;
    color: var(--gray-color);
}

.property-meta i {
    margin-right: 5px;
}

.property-price {
    font-weight: bold;
    color: var(--primary-color);
    font-size: 1.2rem;
    margin: 15px 0;
}

.property-actions {
    margin-top: 15px;
}

.appointments-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Style pour la vue en tableau des rendez-vous */
.appointments-container.table-view {
    display: table;
    width: 100%;
    border-collapse: collapse;
}

.appointments-container.table-view .appointment-card {
    display: table-row;
    border-bottom: 1px solid #eee;
}

.appointments-container.table-view .appointment-date,
.appointments-container.table-view .appointment-details,
.appointments-container.table-view .appointment-property,
.appointments-container.table-view .appointment-status,
.appointments-container.table-view .appointment-actions {
    display: table-cell;
    padding: 15px;
    vertical-align: middle;
}

.appointments-container.table-view .appointment-date {
    width: 100px;
    background: none;
    color: inherit;
    flex-direction: row;
    justify-content: flex-start;
    gap: 5px;
}

.appointments-container.table-view .date-day,
.appointments-container.table-view .date-month {
    display: inline;
    font-size: 1rem;
}

.appointments-container.table-view .appointment-property {
    display: flex;
    align-items: center;
    gap: 15px;
}

.appointments-container.table-view .property-thumbnail {
    width: 60px;
    height: 45px;
}

.appointment-card {
    display: flex;
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: var(--transition);
}

.appointment-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.appointment-date {
    min-width: 80px;
    background: var(--primary-color);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 15px;
}

.date-day {
    font-size: 1.8rem;
    font-weight: bold;
    line-height: 1;
}

.date-month {
    font-size: 0.9rem;
    text-transform: uppercase;
    margin: 5px 0;
}

.date-time {
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.appointment-details {
    flex: 1;
    padding: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

.appointment-property {
    flex: 1;
    min-width: 250px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.property-thumbnail {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.property-info h4 {
    font-size: 1rem;
    margin-bottom: 5px;
}

.property-info p {
    font-size: 0.85rem;
    color: var(--gray-color);
    margin-bottom: 5px;
}

.appointment-status {
    min-width: 120px;
    text-align: center;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-en_attente {
    background-color: #fff3cd;
    color: #856404;
}

.status-confirme {
    background-color: #d4edda;
    color: #155724;
}

.status-annule {
    background-color: #f8d7da;
    color: #721c24;
}

.status-termine {
    background-color: #e2e3e5;
    color: #383d41;
}

.appointment-actions {
    display: flex;
    gap: 10px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 15px;
    border-radius: var(--border-radius);
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    gap: 8px;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: white;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.btn-secondary:hover {
    background-color: #f0f4ff;
    transform: translateY(-2px);
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: #d1145a;
    transform: translateY(-2px);
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
}

.btn-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    padding: 0;
    justify-content: center;
}

.alert {
    padding: 15px 20px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    box-shadow: var(--box-shadow);
}

.alert::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
}

.alert-success {
    background-color: #f0fdf4;
    color: #166534;
    border-left: 5px solid #166534;
}

.alert-info {
    background-color: #f0f9ff;
    color: #0369a1;
    border-left: 5px solid #0369a1;
}

.alert-error {
    background-color: #fef2f2;
    color: #b91c1c;
    border-left: 5px solid #b91c1c;
}

.alert-image {
    width: 60px;
    height: 60px;
    border-radius: 4px;
    overflow: hidden;
    margin-right: 15px;
}

.alert-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.alert-content {
    flex: 1;
}

.alert-content h3 {
    font-size: 1rem;
    margin-bottom: 5px;
}

.alert-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
    transition: var(--transition);
    margin-left: 15px;
}

.alert-close:hover {
    opacity: 1;
    transform: scale(1.1);
}

/* Animations */
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

.animate-slide-up {
    animation: slideUp 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive design */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .quick-stats {
        width: 100%;
        flex-direction: column;
    }
    
    .properties-grid {
        grid-template-columns: 1fr;
    }
    
    .properties-grid.list-view .property-card {
        flex-direction: column;
    }
    
    .properties-grid.list-view .property-image-container {
        width: 100%;
    }
    
    .appointments-container.table-view,
    .appointments-container.table-view .appointment-card,
    .appointments-container.table-view .appointment-date,
    .appointments-container.table-view .appointment-details,
    .appointments-container.table-view .appointment-property,
    .appointments-container.table-view .appointment-status,
    .appointments-container.table-view .appointment-actions {
        display: block;
    }
    
    .appointment-card {
        flex-direction: column;
    }
    
    .appointment-date {
        flex-direction: row;
        justify-content: space-between;
        padding: 10px 15px;
    }
    
    .date-day, .date-month {
        margin: 0;
    }
}
</style>

<!-- JavaScript for interactive elements -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Close alert buttons
    document.querySelectorAll('.alert-close').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.alert').style.opacity = '0';
            setTimeout(() => {
                this.closest('.alert').remove();
            }, 300);
        });
    });
    
    // Toggle favorites view
    const toggleFavoritesBtn = document.getElementById('toggle-favorites-view');
    if (toggleFavoritesBtn) {
        toggleFavoritesBtn.addEventListener('click', function() {
            const grid = document.getElementById('favorites-grid');
            grid.classList.toggle('list-view');
            
            if (grid.classList.contains('list-view')) {
                this.innerHTML = '<i class="fas fa-th-large"></i> Changer vue';
            } else {
                this.innerHTML = '<i class="fas fa-th-list"></i> Changer vue';
            }
        });
    }
    
    // Toggle appointments view
    const toggleAppointmentsBtn = document.getElementById('toggle-appointments-view');
    if (toggleAppointmentsBtn) {
        toggleAppointmentsBtn.addEventListener('click', function() {
            const container = document.getElementById('appointments-container');
            container.classList.toggle('table-view');
            
            if (container.classList.contains('table-view')) {
                this.innerHTML = '<i class="fas fa-th-large"></i> Changer vue';
            } else {
                this.innerHTML = '<i class="fas fa-th-list"></i> Changer vue';
            }
        });
    }
    
    // Auto-hide success messages after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        });
    }, 5000);
});
</script>