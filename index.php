<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Récupérer les 6 derniers biens immobiliers disponibles avec TOUTES leurs photos
try {
    $stmt = $db->prepare("SELECT b.*, c.libelle as categorie, 
                         (SELECT GROUP_CONCAT(p.url) FROM photo p WHERE p.id_bien = b.id_bien) as photos
                         FROM biensimmobilier b
                         JOIN categorie c ON b.id_categorie = c.id_categorie
                         WHERE b.statut = 'validé'
                         ORDER BY b.id_bien DESC
                         LIMIT 6");
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer les données pour le diaporama
    foreach ($properties as &$property) {
        $property['photos'] = $property['photos'] ? explode(',', $property['photos']) : ['default.jpg'];
    }
    unset($property); // Détruire la référence
    
} catch (PDOException $e) {
    $properties = [];
    error_log("Erreur lors de la récupération des biens: " . $e->getMessage());
}
?>

<style>
/* Styles de base */
.hero-section {
    background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/images/hero-bg.jpg');
    background-size: cover;
    background-position: center;
    color: white;
    text-align: center;
    padding: 100px 20px;
    margin-bottom: 50px;
    overflow: hidden;
}

/* Animations pour les titres */
.hero-section h1 {
    font-size: 2.5rem;
    margin-bottom: 20px;
    animation: fadeInDown 1s ease-out, floatTitle 3s ease-in-out infinite 1s;
    transform-origin: center bottom;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.hero-section p {
    font-size: 1.2rem;
    margin-bottom: 30px;
    animation: fadeInUp 1s ease-out 0.3s both;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.featured-properties h2,
.services-container h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 2rem;
    color: #2c3e50;
    position: relative;
    display: inline-block;
    padding-bottom: 10px;
}

.featured-properties h2::after,
.services-container h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: #007bff;
    animation: underlineGrow 1s ease-out 0.5s both;
}

/* Keyframes pour les animations */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes floatTitle {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes underlineGrow {
    from {
        width: 0;
    }
    to {
        width: 80px;
    }
}

/* Animation pour le bouton */
.btn {
    display: inline-block;
    padding: 12px 30px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: all 0.3s;
    animation: bounceIn 1s ease-out 0.5s both;
    position: relative;
    overflow: hidden;
}

.btn::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: 0.5s;
}

.btn:hover::after {
    left: 100%;
}

@keyframes bounceIn {
    0% {
        opacity: 0;
        transform: scale(0.3);
    }
    50% {
        opacity: 1;
        transform: scale(1.1);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
    }
}

.btn:hover {
    background: #0056b3;
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

/* Styles pour le diaporama amélioré */
.property-image-container {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
    border-radius: 8px 8px 0 0;
}

.property-slideshow {
    display: flex;
    width: 100%;
    height: 100%;
    position: relative;
    transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.property-slide {
    min-width: 100%;
    height: 100%;
    flex-shrink: 0;
}

.property-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.property-slide img:hover {
    transform: scale(1.05);
}

.slide-nav {
    position: absolute;
    top: 50%;
    width: 100%;
    display: flex;
    justify-content: space-between;
    transform: translateY(-50%);
    z-index: 1;
    opacity: 0;
    transition: opacity 0.3s;
}

.property-image-container:hover .slide-nav {
    opacity: 1;
}

.slide-nav button {
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    margin: 0 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.3s;
}

.slide-nav button:hover {
    background: rgba(0,0,0,0.8);
    transform: scale(1.1);
}

.slide-dots {
    position: absolute;
    bottom: 15px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: 8px;
    z-index: 1;
}

.slide-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
    transition: all 0.3s;
}

.slide-dot:hover {
    background: rgba(255,255,255,0.8);
}

.slide-dot.active {
    background: white;
    transform: scale(1.2);
}

/* Autres styles */
.featured-properties {
    margin-bottom: 50px;
    padding: 0 20px;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.property-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background: white;
}

.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.property-details {
    padding: 15px;
}

.property-details h3 {
    margin-top: 0;
    color: #2c3e50;
    font-size: 1.3rem;
}

.property-meta {
    color: #7f8c8d;
    font-size: 0.9em;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
}

.property-price {
    font-weight: bold;
    color: #e74c3c;
    font-size: 1.2em;
    margin: 10px 0;
}

.property-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.property-actions .btn {
    padding: 8px 15px;
    font-size: 0.9em;
    animation: none;
}

.property-location {
    font-size: 0.8em;
    color: #7f8c8d;
    max-width: 50%;
    text-align: right;
}

/* Section services */
.services-section {
    background: #f8f9fa;
    padding: 50px 20px;
    margin-bottom: 50px;
}

.services-container {
    max-width: 1200px;
    margin: 0 auto;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    text-align: center;
}

.service-card {
    padding: 25px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.service-card i {
    font-size: 2.5rem;
    color: #007bff;
    margin-bottom: 15px;
    display: inline-block;
    transition: transform 0.3s ease;
}

.service-card:hover i {
    transform: scale(1.2);
}

.service-card h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .properties-grid {
        grid-template-columns: 1fr;
    }
    
    .hero-section {
        padding: 60px 20px;
    }
    
    .hero-section h1 {
        font-size: 2rem;
    }
    
    .slide-nav button {
        width: 30px;
        height: 30px;
        font-size: 1rem;
    }
}
</style>

<div class="hero-section">
    <h1>Bienvenue sur notre plateforme immobilière</h1>
    <p>Trouvez la propriété de vos rêves</p>
    <a href="proprietes/biensimmobilier.php" class="btn">Voir les propriétés</a>
</div>
<!-- Message pour les visiteurs non connectés -->
<?php if (!isset($_SESSION['user'])): ?>
<div class="visitor-message" style="background-color: #f8f9fa; padding: 15px; text-align: center; margin-bottom: 20px; border-left: 4px solid #007bff;">
    <p style="margin: 0; font-size: 1.1em; color: #2c3e50;">
        <strong>Connectez-vous</strong> pour accéder à l'ensemble de nos propriétés et bénéficier de fonctionnalités exclusives.
        <a href="login.php" style="color: #007bff; text-decoration: underline; margin-left: 10px;">Se connecter</a>
        ou 
        <a href="register.php" style="color: #007bff; text-decoration: underline;">S'inscrire</a>
    </p>
</div>
<?php endif; ?>
<!-- Section propriétés à la une -->
<section class="featured-properties">
    <div class="container">
        <h2>Nos dernières propriétés</h2>
        <div class="properties-grid">
            <?php foreach ($properties as $property): ?>
                <div class="property-card">
                    <div class="property-image-container">
                        <div class="property-slideshow" id="slideshow-<?= $property['id_bien'] ?>">
                            <?php foreach ($property['photos'] as $index => $photo): ?>
                                <div class="property-slide">
                                    <img src="assets/uploads/<?= htmlspecialchars($photo) ?>" 
                                         alt="<?= htmlspecialchars($property['type']) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="slide-nav">
                            <button onclick="prevSlide(<?= $property['id_bien'] ?>)">❮</button>
                            <button onclick="nextSlide(<?= $property['id_bien'] ?>)">❯</button>
                        </div>
                        <div class="slide-dots" id="dots-<?= $property['id_bien'] ?>">
                            <?php foreach ($property['photos'] as $index => $photo): ?>
                                <div class="slide-dot <?= $index === 0 ? 'active' : '' ?>" 
                                     onclick="goToSlide(<?= $property['id_bien'] ?>, <?= $index ?>)"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="property-details">
                        <h3><?= htmlspecialchars($property['type']) ?></h3>
                        <div class="property-meta">
                            <span><?= htmlspecialchars($property['categorie']) ?></span>
                            <span><?= htmlspecialchars($property['taille']) ?> m²</span>
                        </div>
                        <div class="property-price"><?= number_format($property['prix'], 0, ',', ' ') ?> FCFA</div>
                        <div class="property-actions">
                            <a href="proprietes/details.php?id_bien=<?= $property['id_bien'] ?>" class="btn">Voir détails</a>
                            
                            <span class="property-location"><?= htmlspecialchars($property['adresse']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($properties)): ?>
                <p>Aucune propriété disponible pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Section services -->
<section class="services-section">
    <div class="services-container">
        <h2>Nos services</h2>
        <div class="services-grid">
            <div class="service-card">
                <i>🏠</i>
                <h3>Vente immobilière</h3>
                <p>Découvrez notre sélection exclusive de biens à vendre dans toute la région.</p>
            </div>
            <div class="service-card">
                <i>🔑</i>
                <h3>Location</h3>
                <p>Des locations adaptées à tous les budgets et besoins.</p>
            </div>
            <div class="service-card">
                <i>👨‍💼</i>
                <h3>Conseil expert</h3>
                <p>Notre équipe d'experts vous accompagne dans tous vos projets.</p>
            </div>
        </div>
    </div>
</section>

<script>
// Variables pour gérer les diaporamas
const sliders = {};

// Initialisation des diaporamas
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($properties as $property): ?>
        initSlider(<?= $property['id_bien'] ?>, <?= count($property['photos']) ?>);
    <?php endforeach; ?>
    
    // Animation des titres au scroll
    const animateOnScroll = (elements, animation) => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add(animation);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        elements.forEach(el => observer.observe(el));
    };
    
    // Appliquer aux titres des sections
    animateOnScroll(document.querySelectorAll('.featured-properties h2, .services-container h2'), 'animate');
});

function initSlider(bienId, totalSlides) {
    sliders[bienId] = {
        currentIndex: 0,
        totalSlides: totalSlides,
        interval: null
    };
    
    // Démarrer le défilement automatique
    startAutoSlide(bienId);
}

function startAutoSlide(bienId) {
    sliders[bienId].interval = setInterval(() => {
        nextSlide(bienId);
    }, 5000); // Change de slide toutes les 5 secondes
}

function stopAutoSlide(bienId) {
    clearInterval(sliders[bienId].interval);
}

function showSlide(bienId, index) {
    const slideshow = document.getElementById(`slideshow-${bienId}`);
    const dots = document.querySelectorAll(`#dots-${bienId} .slide-dot`);
    
    // Mise à jour de la position avec transition fluide
    slideshow.style.transform = `translateX(-${index * 100}%)`;
    
    // Mise à jour des points indicateurs
    dots.forEach(dot => dot.classList.remove('active'));
    dots[index].classList.add('active');
    
    // Mise à jour de l'index courant
    sliders[bienId].currentIndex = index;
}

function prevSlide(bienId) {
    stopAutoSlide(bienId);
    const slider = sliders[bienId];
    const newIndex = (slider.currentIndex - 1 + slider.totalSlides) % slider.totalSlides;
    showSlide(bienId, newIndex);
    startAutoSlide(bienId);
}

function nextSlide(bienId) {
    stopAutoSlide(bienId);
    const slider = sliders[bienId];
    const newIndex = (slider.currentIndex + 1) % slider.totalSlides;
    showSlide(bienId, newIndex);
    startAutoSlide(bienId);
}

function goToSlide(bienId, index) {
    stopAutoSlide(bienId);
    showSlide(bienId, index);
    startAutoSlide(bienId);
}
</script>

<?php require_once 'includes/footer.php'; ?>