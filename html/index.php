<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ds Barber</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav>
            <a href="/html/index.html" class="logo"><img src="/Ds_Barber_Logo.png" width="130px" height="120px"></a>
            <div class="auth-buttons">
                <a href="login.html" class="login-btn-nav">Login</a>
                <a href="signup.php" class="signup-btn-nav">Cadastro</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="hero-container">
            <section class="hero">
                <div class="hero-content">
                    <h1>A Arte de um Corte Perfeito</h1>
                    <p>Experimente precisão e estilo em nossa barbearia moderna. Seu próximo visual te espera.</p>
                    <a href="schedule.html" class="cta-button">Agendar um Corte</a>
                </div>
            </section>
        </div>

        <section class="services" id="services">
            <h2>Nossos Serviços Premium</h2>
            <p>De cortes clássicos a estilos modernos, nossos serviços são projetados para fazer você parecer e se sentir o seu melhor.</p>
            <div class="service-cards">
                <div class="card">
                    <i class="fas fa-cut"></i>
                    <h3>Corte Clássico</h3>
                    <p>Um corte de cabelo atemporal e de precisão, adaptado às suas preferências.</p>
                </div>
                <div class="card">
                    <i class="fas fa-fire"></i>
                    <h3>Barba & Toalha Quente</h3>
                    <p>Um luxuoso barbear com toalha quente e aparo de barba para a melhor experiência de cuidado.</p>
                </div>
                <div class="card">
                    <i class="fas fa-spa"></i>
                    <h3>Tratamento Capilar</h3>
                    <p>Revitalize seu cabelo com nossos tratamentos nutritivos e fortalecedores.</p>
                </div>
            </div>
        </section>

        <section class="gallery" id="gallery">
            <h2>Galeria de Cortes</h2>
            <p>Inspire-se com alguns dos nossos trabalhos.</p>
            <div class="gallery-cards">
                <div class="gallery-card">
                    <img src="https://images.unsplash.com/photo-1567894339828-a0b253a4e98a?q=80&w=1974&auto=format&fit=crop" alt="Corte de cabelo 1">
                    <div class="gallery-card-overlay">
                        <h3>Corte Moderno</h3>
                    </div>
                </div>
                <div class="gallery-card">
                    <img src="https://images.unsplash.com/photo-1621605815971-fbc98d665976?q=80&w=1974&auto=format&fit=crop" alt="Corte de cabelo 2">
                    <div class="gallery-card-overlay">
                        <h3>Barba Desenhada</h3>
                    </div>
                </div>
                <div class="gallery-card">
                    <img src="https://images.unsplash.com/photo-1622288432453-53145a5b42a9?q=80&w=1974&auto=format&fit=crop" alt="Corte de cabelo 3">
                    <div class="gallery-card-overlay">
                        <h3>Corte Clássico</h3>
                    </div>
                </div>
                <div class="gallery-card">
                    <img src="https://images.unsplash.com/photo-1582022359448-3c09193b5d19?q=80&w=1974&auto=format&fit=crop" alt="Corte de cabelo 4">
                    <div class="gallery-card-overlay">
                        <h3>Corte Infantil</h3>
                    </div>
                </div>
            </div>
        </section>

        <section class="location" id="location">
            <h2>Nossa Localização</h2>
            <p>Venha nos visitar no coração de Lisboa.</p>
            <div class="map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3908.8532221848154!2d-39.28341150197513!3d-11.562378092698328!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x7139d03db49c939%3A0xc23e4f8cb7cd365!2sR.%20Duque%20de%20Caxias%2C%2061%20-%20Centro%2C%20Concei%C3%A7%C3%A3o%20do%20Coit%C3%A9%20-%20BA%2C%2048730-000%2C%20Brazil!5e0!3m2!1sen!2spt!4v1761444239822!5m2!1sen!2spt" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <a href="#" class="logo"><img src="/Ds_Barber_Logo.png" width="85px" height="80px"></a>
                <p>Estilo e precisão em cada corte. A sua barbearia moderna.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Serviços</h3>
                <ul>
                    <li><a href="#">Corte de Cabelo</a></li>
                    <li><a href="#">Design de Barba</a></li>
                    <li><a href="#">Tratamento Capilar</a></li>
                    <li><a href="#">Toalha Quente</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contato</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Av. do Estilo, 123, Lisboa</li>
                    <li><i class="fas fa-envelope"></i> contato@dsbarber.com</li>
                    <li><i class="fas fa-phone"></i> +351 123 456 789</li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Horário de Funcionamento</h3>
                <ul>
                    <li>Seg - Sex: 9:00 - 20:00</li>
                    <li>Sábado: 10:00 - 18:00</li>
                    <li>Domingo: Fechado</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2024 Ds Barber. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>