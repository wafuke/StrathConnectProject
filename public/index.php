<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StrathConnect - University Marketplace</title>
    <style>
        :root {
            --primary: #003366; /* Strathmore blue */
            --secondary: #FFCC00; /* Strathmore gold */
            --light: #f8f9fa;
            --dark: #212529;
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }
        
        header {
            background-color: var(--primary);
            color: var(--white);
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--white);
        }
        
        .logo span {
            color: var(--secondary);
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background-color: var(--secondary);
            color: var(--dark);
        }
        
        .login-btn {
            background-color: var(--secondary);
            color: var(--dark) !important;
            font-weight: bold !important;
        }
        
        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .hero {
            text-align: center;
            padding: 4rem 0;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem;
        }
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: #002244;
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background-color: #e6b800;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }
        
        .feature-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .feature-card h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .feature-icon {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }
        
        footer {
            background-color: var(--primary);
            color: var(--white);
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">Strath<span>Connect</span></div>
            <ul class="nav-links">
                <li><a href="#" class="active">Home</a></li>
                <li><a href="../public/products.php">Products</a></li>
                <li><a href="../public/services.php">Services</a></li>
                <li><a href="../public/login.php" class="login-btn">Login</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>Welcome to StrathConnect</h1>
            <p>The official marketplace for Strathmore University students, staff, and alumni to buy, sell, and exchange goods and services within the community.</p>
            <div class="cta-buttons">
                <a href="../public/signup.php" class="btn btn-primary">Sign Up Now</a>
            </div>
        </section>

        <section class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h3>Buy & Sell Textbooks</h3>
                <p>Find affordable course materials or sell your used textbooks to fellow students.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <h3>Tech Services</h3>
                <p>Offer or find tech services like tutoring, web development, and device repairs.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Trusted Community</h3>
                <p>Connect exclusively with verified Strathmore University members for safe transactions.</p>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>