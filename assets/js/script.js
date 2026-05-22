// Fonctionnalité de la galerie
document.addEventListener('DOMContentLoaded', function() {
    // Changement de photo principale au clic sur les miniatures
    const thumbnails = document.querySelectorAll('.thumbnails img');
    const mainPhoto = document.querySelector('.main-photo img');
    
    if (thumbnails.length > 0 && mainPhoto) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                mainPhoto.src = this.src;
            });
        });
    }
    
    // Gestion des dates pour le formulaire de rendez-vous
    const dateInput = document.getElementById('date');
    if (dateInput) {
        // Définir la date minimale comme aujourd'hui
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
    }
    
    // Menu mobile
    const menuToggle = document.createElement('div');
    menuToggle.className = 'menu-toggle';
    menuToggle.innerHTML = '☰';
    const header = document.querySelector('header .container');
    
    if (window.innerWidth <= 768 && header) {
        header.prepend(menuToggle);
        const nav = document.querySelector('nav');
        
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
        });
    }
    
    // Ajout d'un gestionnaire d'événement pour le redimensionnement
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            const nav = document.querySelector('nav');
            if (nav) nav.classList.remove('active');
        }
    });
});

// Fonction pour afficher les messages flash
function showFlashMessage() {
    const flash = document.querySelector('.flash-message');
    if (flash) {
        setTimeout(() => {
            flash.style.display = 'none';
        }, 5000);
    }
}

showFlashMessage();
