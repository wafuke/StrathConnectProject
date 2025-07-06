<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StrathConnect - Campus Marketplace</title>
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
      opacity: 0;
      transform: translateY(20px);
      transition: all 1s ease;
    }

    body.loaded {
      opacity: 1;
      transform: translateY(0);
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
      font-size: 2.8rem;
      margin-bottom: 1rem;
      color: var(--primary);
    }

    .hero p {
      font-size: 1.2rem;
      max-width: 800px;
      margin: 0 auto 2rem;
      color: #444;
    }

    .cta-buttons {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-top: 2rem;
      flex-wrap: wrap;
    }

    .btn {
      padding: 0.9rem 1.7rem;
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
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      margin: 4rem 0;
    }

    .feature-card {
      background: var(--white);
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease;
    }

    .feature-card:hover {
      transform: translateY(-5px);
    }

    .feature-card h3 {
      color: var(--primary);
      margin-bottom: 1rem;
      font-size: 1.3rem;
    }

    .feature-icon {
      font-size: 2.5rem;
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
        <li><a href="../public/services_home.php">Services</a></li>
        <li><a href="../public/login.php" class="login-btn">Login</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <section class="hero">
      <h1>Welcome to StrathConnect</h1>
      <p>The campus marketplace for Strathmore University students: buy, sell, or exchange products and services easily and securely.</p>
      <div class="cta-buttons">
        <a href="../public/signup.php" class="btn btn-primary">Get Started</a>
        <a href="../public/products.php" class="btn btn-secondary">View Products</a>
      </div>
    </section>

    <section class="features">
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-book"></i>
        </div>
        <h3>Textbooks & Course Materials</h3>
        <p>Find affordable textbooks or sell your used ones to other students on campus.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-laptop-code"></i>
        </div>
        <h3>Tech & Creative Services</h3>
        <p>Offer or hire services like tutoring, web design, photography, and more.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-users"></i>
        </div>
        <h3>Student Community</h3>
        <p>Connect and trade safely within the Strathmore student community.</p>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; <?= date('Y'); ?> StrathConnect. All rights reserved.</p>
  </footer>

  <script>
    // Add ease-in effect on page load
    window.addEventListener('load', () => {
      document.body.classList.add('loaded');
    });
  </script>
</body>
</html>
