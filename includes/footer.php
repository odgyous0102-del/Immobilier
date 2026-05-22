        </main>
        
        <footer>
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3>ImmoElite</h3>
                        <p>Votre partenaire de confiance pour trouver le bien immobilier de vos rêves.</p>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Liens rapides</h3>
                        <ul>
                            <li><a href="<?php echo BASE_URL;  ?>index.php">Accueil</a></li>
                            <li><a href="<?php echo BASE_URL;  ?>proprietes/biensimmobilier.php">Propriétés</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Contact</h3>
                        <p><i class="fas fa-phone"></i> +226 04355876</p>
                        <p><i class="fas fa-envelope"></i><a href="mailto:immobilier@gmail.com" style="color: white;">immobilier@gmail.com</a></p>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Réseaux sociaux</h3>
                        <div class="social-icons">
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; <?php echo date('Y'); ?>  Les meilleurs logements sont réunis sur notre plateforme .</p>
                </div>
            </div>
        </footer>
        
        <script src="<?php echo isset($base_url) ? $base_url : ''; ?>assets/js/script.js"></script>
    </body>
</html>