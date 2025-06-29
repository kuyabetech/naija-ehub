<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Naija eHub - Your one-stop platform for Nigerian e-services like NIN, BVN, JAMB, CAC, and more.">
  <title>Naija eHub - Nigerian e-Services Portal</title>
  <link rel="stylesheet" href="css/main.css">c
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #067c3c;
      --primary-hover: #045a2b;
      --secondary-color: #0e9e4a;
      --text-color: #1a1a1a;
      --bg-light: #f8f9fa;
      --bg-white: #ffffff;
      --text-light: #e6f4ea;
      --font-size-base: 1rem;
      --shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    [data-theme="dark"] {
      --primary-color: #0e9e4a;
      --primary-hover: #0cc558;
      --text-color: #e0e0e0;
      --bg-light: #1c2526;
      --bg-white: #2d2d2d;
      --text-light: #b0b0b0;
    }
    [data-contrast="high"] {
      --text-color: #000000;
      --bg-light: #ffffff;
      --bg-white: #ffffff;
      --text-light: #000000;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg-light);
      color: var(--text-color);
      scroll-behavior: smooth;
      font-size: var(--font-size-base);
      line-height: 1.6;
      margin: 0;
    }
    .main-header {
      background: var(--bg-white);
      box-shadow: var(--shadow);
      padding: 1.2rem 0;
      position: sticky;
      top: 0;
      z-index: 1000;
      transition: background 0.3s;
    }
    .main-header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1.5rem;
    }
    .main-header h1 {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--primary-color);
    }
    .main-header nav {
      display: flex;
      gap: 1rem;
      align-items: center;
    }
    .hero-section {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: var(--text-light);
      padding: 5rem 0;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .hero-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://source.unsplash.com/random/1920x1080?nigeria') no-repeat center/cover;
      opacity: 0.1;
      z-index: 0;
    }
    .hero-section .container {
      position: relative;
      z-index: 1;
    }
    .hero-section h2 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      animation: fadeIn 1s ease-in;
    }
    .hero-section p {
      font-size: 1.2rem;
      max-width: 600px;
      margin: 0 auto 2rem;
    }
    .search-bar {
      max-width: 600px;
      margin: 2rem auto;
      display: flex;
      gap: 0.5rem;
      background: var(--bg-white);
      padding: 0.5rem;
      border-radius: 50px;
      box-shadow: var(--shadow);
    }
    .search-bar input {
      flex: 1;
      padding: 0.8rem 1rem;
      border: none;
      font-size: 1rem;
      background: transparent;
      color: var(--text-color);
    }
    .search-bar select {
      padding: 0.8rem;
      border: none;
      background: transparent;
      color: var(--text-color);
    }
    .search-bar input:focus, .search-bar select:focus {
      outline: none;
    }
    .service-preview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
      padding: 0 1rem;
    }
    .service-card {
      background: var(--bg-white);
      border-radius: 12px;
      box-shadow: var(--shadow);
      padding: 2rem;
      text-align: center;
      transition: transform 0.3s, box-shadow 0.3s;
      position: relative;
      overflow: hidden;
    }
    .service-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 30px rgba(6,124,60,0.2);
    }
    .service-card i {
      font-size: 3.5rem;
      color: var(--primary-color);
      margin-bottom: 1.2rem;
      transition: color 0.3s;
    }
    .service-card h4 {
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 0.8rem;
    }
    .service-card p {
      font-size: 1rem;
      color: var(--text-color);
      margin-bottom: 1.5rem;
    }
    .service-card .btn {
      background: var(--primary-color);
      color: #fff;
      border: none;
      padding: 0.7rem 1.5rem;
      border-radius: 25px;
      font-size: 1rem;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.3s, transform 0.2s;
    }
    .service-card .btn:hover {
      background: var(--primary-hover);
      transform: scale(1.05);
    }
    .service-status {
      position: absolute;
      top: 15px;
      right: 15px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #28a745;
      transition: background 0.3s;
    }
    .service-status.offline {
      background: #dc3545;
    }
    .how-it-works, .trust-security, .testimonials-section, .faq-section, .blog-section, .contact-section {
      padding: 4rem 0;
      background: var(--bg-white);
      text-align: center;
    }
    .how-it-works h3, .trust-security h3, .testimonials-section h3, .faq-section h3, .blog-section h3, .contact-section h3 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
    }
    .how-it-works .steps {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }
    .how-it-works .step {
      padding: 1.5rem;
      background: var(--bg-light);
      border-radius: 10px;
      transition: transform 0.3s;
    }
    .how-it-works .step:hover {
      transform: translateY(-5px);
    }
    .trust-security p, .contact-section p {
      max-width: 800px;
      margin: 0 auto 2rem;
      font-size: 1.1rem;
    }
    .testimonials-section .testimonial {
      max-width: 600px;
      margin: 1.5rem auto;
      padding: 2rem;
      background: var(--bg-light);
      border-radius: 10px;
      font-style: italic;
      box-shadow: var(--shadow);
    }
    .faq-section .faq-item {
      max-width: 800px;
      margin: 1rem auto;
      text-align: left;
      background: var(--bg-light);
      border-radius: 8px;
      overflow: hidden;
    }
    .faq-section .faq-question {
      font-weight: 600;
      cursor: pointer;
      padding: 1.2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background 0.3s;
    }
    .faq-section .faq-question:hover {
      background: var(--primary-color);
      color: #fff;
    }
    .faq-section .faq-answer {
      display: none;
      padding: 1.2rem;
      background: var(--bg-white);
      border-top: 1px solid var(--bg-light);
    }
    .faq-section .faq-answer.active {
      display: block;
    }
    .blog-section .blog-posts {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }
    .blog-post {
      background: var(--bg-light);
      border-radius: 10px;
      overflow: hidden;
      transition: transform 0.3s;
    }
    .blog-post:hover {
      transform: translateY(-5px);
    }
    .blog-post img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      loading: lazy;
    }
    .blog-post-content {
      padding: 1.5rem;
    }
    .contact-section form {
      max-width: 600px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .contact-section input, .contact-section textarea {
      padding: 1rem;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      background: var(--bg-light);
      color: var(--text-color);
      transition: border 0.3s;
    }
    .contact-section input:focus, .contact-section textarea:focus {
      border-color: var(--primary-color);
      outline: none;
    }
    .contact-section button {
      background: var(--primary-color);
      color: #fff;
      border: none;
      padding: 1rem;
      border-radius: 25px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: background 0.3s, transform 0.2s;
    }
    .contact-section button:hover {
      background: var(--primary-hover);
      transform: scale(1.05);
    }
    .newsletter-section {
      background: var(--primary-color);
      color: var(--text-light);
      padding: 3rem 0;
      text-align: center;
    }
    .newsletter-section h3 {
      font-size: 1.8rem;
      margin-bottom: 1rem;
    }
    .newsletter-section form {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      max-width: 600px;
      margin: 0 auto;
    }
    .newsletter-section input {
      padding: 1rem;
      border: none;
      border-radius: 25px;
      width: 70%;
      font-size: 1rem;
      background: #fff;
      color: var(--text-color);
    }
    .newsletter-section button {
      background: var(--bg-white);
      color: var(--primary-color);
      border: none;
      padding: 1rem 2rem;
      border-radius: 25px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: background 0.3s;
    }
    .newsletter-section button:hover {
      background: var(--primary-hover);
      color: #fff;
    }
    .footer {
      background: var(--bg-light);
      color: var(--text-color);
      text-align: center;
      padding: 2rem 0;
      font-size: 0.9rem;
      border-top: 1px solid #e0e0e0;
    }
    .footer a {
      color: var(--primary-color);
      text-decoration: none;
      margin: 0 0.5rem;
    }
    .footer a:hover {
      text-decoration: underline;
    }
    .back-to-top, .chat-toggle {
      position: fixed;
      bottom: 20px;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: opacity 0.3s, transform 0.3s;
      box-shadow: var(--shadow);
    }
    .back-to-top {
      right: 20px;
      background: var(--primary-color);
      color: #fff;
      opacity: 0;
      visibility: hidden;
    }
    .back-to-top.show {
      opacity: 1;
      visibility: visible;
    }
    .chat-toggle {
      right: 80px;
      background: var(--secondary-color);
      color: #fff;
    }
    .chat-toggle:hover, .back-to-top:hover {
      transform: scale(1.1);
    }
    .chat-window {
      position: fixed;
      bottom: 80px;
      right: 20px;
      width: 300px;
      background: var(--bg-white);
      border-radius: 10px;
      box-shadow: var(--shadow);
      display: none;
      flex-direction: column;
      max-height: 400px;
      overflow: hidden;
    }
    .chat-window.active {
      display: flex;
    }
    .chat-header {
      background: var(--primary-color);
      color: #fff;
      padding: 1rem;
      font-weight: 600;
    }
    .chat-body {
      flex: 1;
      padding: 1rem;
      overflow-y: auto;
      background: var(--bg-light);
    }
    .chat-message {
      margin: 0.5rem 0;
      padding: 0.8rem;
      border-radius: 8px;
      max-width: 80%;
    }
    .chat-message.user {
      background: var(--primary-color);
      color: #fff;
      margin-left: auto;
    }
    .chat-message.bot {
      background: #e9ecef;
      color: var(--text-color);
    }
    .chat-input {
      display: flex;
      padding: 1rem;
      background: var(--bg-white);
      border-top: 1px solid #ddd;
    }
    .chat-input input {
      flex: 1;
      padding: 0.8rem;
      border: 1px solid #ddd;
      border-radius: 20px;
      margin-right: 0.5rem;
    }
    .chat-input button {
      background: var(--primary-color);
      color: #fff;
      border: none;
      padding: 0.8rem;
      border-radius: 20px;
      cursor: pointer;
    }
    .dark-mode-toggle, .accessibility-toggle, .language-toggle {
      background: var(--primary-color);
      color: #fff;
      border: none;
      padding: 0.6rem 1.2rem;
      border-radius: 25px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background 0.3s;
      margin-left: 0.5rem;
    }
    .dark-mode-toggle:hover, .accessibility-toggle:hover, .language-toggle:hover {
      background: var(--primary-hover);
    }
    .accessibility-panel, .language-panel {
      position: fixed;
      top: 100px;
      right: 20px;
      background: var(--bg-white);
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: var(--shadow);
      display: none;
      z-index: 1000;
    }
    .accessibility-panel.active, .language-panel.active {
      display: block;
    }
    .accessibility-panel label, .language-panel label {
      display: block;
      margin: 0.8rem 0;
    }
    @media (max-width: 900px) {
      .service-preview, .how-it-works .steps, .blog-section .blog-posts {
        grid-template-columns: 1fr;
      }
      .service-card {
        width: 100%;
        max-width: 350px;
        margin: 0 auto;
      }
      .newsletter-section form, .contact-section form {
        flex-direction: column;
        align-items: center;
      }
      .newsletter-section input, .contact-section input, .contact-section textarea {
        width: 100%;
      }
      .newsletter-section button, .contact-section button {
        width: 100%;
      }
      .chat-window {
        width: 90%;
        right: 5%;
      }
    }
    @media (max-width: 600px) {
      .hero-section {
        padding: 3rem 0;
      }
      .hero-section h2 {
        font-size: 1.8rem;
      }
      .service-card {
        padding: 1.5rem;
      }
      .service-card i {
        font-size: 2.5rem;
      }
      .main-header h1 {
        font-size: 1.5rem;
      }
      .main-header nav {
        flex-wrap: wrap;
        gap: 0.5rem;
      }
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <header class="main-header">
    <div class="container">
      <h1>Naija eHub</h1>
      <nav>
        <a href="auth/login.php" class="btn">Login</a>
        <a href="auth/register.php" class="btn btn-primary">Register</a>
        <button class="dark-mode-toggle" onclick="toggleDarkMode()">Dark Mode</button>
        <button class="accessibility-toggle" onclick="toggleAccessibilityPanel()">Accessibility</button>
        <button class="language-toggle" onclick="toggleLanguagePanel()">Language</button>
      </nav>
    </div>
  </header>
  
  <div class="accessibility-panel" id="accessibility-panel">
    <h4>Accessibility Options</h4>
    <label>
      Font Size:
      <select onchange="adjustFontSize(this.value)">
        <option value="1">Normal</option>
        <option value="1.2">Large</option>
        <option value="1.4">Extra Large</option>
      </select>
    </label>
    <label>
      High Contrast:
      <input type="checkbox" onchange="toggleHighContrast(this.checked)">
    </label>
  </div>
  
  <div class="language-panel" id="language-panel">
    <h4>Select Language</h4>
    <label><input type="radio" name="language" value="en" checked onchange="changeLanguage('en')"> English</label>
    <label><input type="radio" name="language" value="yo" onchange="changeLanguage('yo')"> Yoruba</label>
    <label><input type="radio" name="language" value="ig" onchange="changeLanguage('ig')"> Igbo</label>
    <label><input type="radio" name="language" value="ha" onchange="changeLanguage('ha')"> Hausa</label>
  </div>
  
  <main class="hero-section">
    <div class="container">
      <h2 data-lang-en="All Essential Nigerian e-Services in One Place" 
          data-lang-yo="Gbogbo Awọn Iṣẹ e-Services Naijiria ni Ibi Kan" 
          data-lang-ig="Ihe niile dị mkpa Nigerian e-Services n'otu ebe" 
          data-lang-ha="Duk Mahimman Sabis na Nigeria a Wuri Daya">All Essential Nigerian e-Services in One Place</h2>
      <p data-lang-en="Access, verify, and manage your NIN, BVN, JAMB, CAC, news publishing, and more. Fast, secure, and reliable digital services for every Nigerian."
         data-lang-yo="Wọle, ṣe idaniloju, ati ṣakoso NIN rẹ, BVN, JAMB, CAC, atẹjade iroyin, ati siwaju sii. Yara, ailewu, ati awọn iṣẹ oni-nọmba ti o gbẹkẹle fun gbogbo ọmọ Naijiria."
         data-lang-ig="Nweta, nyochaa, ma jikwaa NIN gị, BVN, JAMB, CAC, mbipụta akụkọ, na ndị ọzọ. Ngwa ngwa, nchekwa, na ọrụ dijitalụ a pụrụ ịdabere na ya maka onye Nigeria ọ bụla."
         data-lang-ha="Sami, tabbatar, da sarrafa NIN, BVN, JAMB, CAC, buga labarai, da ƙari. Mai sauri, mai aminci, da sabis na dijital mai aminci ga kowane ɗan Najeriya.">Access, verify, and manage your NIN, BVN, JAMB, CAC, news publishing, and more. Fast, secure, and reliable digital services for every Nigerian.</p>
      <div class="search-bar">
        <input type="text" placeholder="Search services (e.g., NIN, BVN, JAMB)..." oninput="searchServices(this.value)">
        <select onchange="filterServices(this.value)">
          <option value="all">All Categories</option>
          <option value="nin">NIN</option>
          <option value="bvn">BVN</option>
          <option value="cac">CAC</option>
          <option value="jamb">JAMB</option>
          <option value="news">News</option>
        </select>
      </div>
      <div class="service-preview">
        <div class="service-card" data-service="nin">
          <div class="service-status" data-status="online"></div>
          <i class="fas fa-id-card"></i>
          <h4 data-lang-en="NIN Services" data-lang-yo="Awọn Iṣẹ NIN" data-lang-ig="Ọrụ NIN" data-lang-ha="Sabis na NIN">NIN Services</h4>
          <p data-lang-en="Verify, update, or personalize your National Identification Number." 
             data-lang-yo="Ṣe idaniloju, ṣe imudojuiwọn, tabi ṣe adani Nọmba Idanimọ Orilẹ-ede rẹ." 
             data-lang-ig="Nyochaa, melite, ma ọ bụ hazie Nọmba Nchọpụta Mba gị." 
             data-lang-ha="Tabbatar, sabunta, ko kuma keɓance Lambar Shaidar Ƙasa ta ku.">Verify, update, or personalize your National Identification Number.</p>
          <a href="services/nin.php" class="btn">Explore NIN</a>
        </div>
        <div class="service-card" data-service="bvn">
          <div class="service-status" data-status="online"></div>
          <i class="fas fa-fingerprint"></i>
          <h4 data-lang-en="BVN Services" data-lang-yo="Awọn Iṣẹ BVN" data-lang-ig="Ọrụ BVN" data-lang-ha="Sabis na BVN">BVN Services</h4>
          <p data-lang-en="Retrieve, verify, or update your Bank Verification Number easily." 
             data-lang-yo="Gba pada, ṣe idaniloju, tabi ṣe imudojuiwọn Nọmba Idaniloju Banki rẹ ni irọrun." 
             data-lang-ig="Weghachite, nyochaa, ma ọ bụ melite Nọmba Nyocha Bank gị n'ụzọ dị mfe." 
             data-lang-ha="Dawo, tabbatar, ko sabunta Lambar Tabbatar da Banki cikin sauƙi.">Retrieve, verify, or update your Bank Verification Number easily.</p>
          <a href="services/bvn.php" class="btn">Explore BVN</a>
        </div>
        <div class="service-card" data-service="cac">
          <div class="service-status offline" data-status="offline"></div>
          <i class="fas fa-building"></i>
          <h4 data-lang-en="CAC Registration" data-lang-yo="Iforukọsilẹ CAC" data-lang-ig="Ndebanye aha CAC" data-lang-ha="Rijistar CAC">CAC Registration</h4>
          <p data-lang-en="Register or verify your business with the Corporate Affairs Commission." 
             data-lang-yo="Forukọsilẹ tabi ṣe idaniloju iṣowo rẹ pẹlu Igbimọ Awọn Ọrọ Ile-iṣẹ." 
             data-lang-ig="Deba aha ma ọ bụ nyochaa azụmahịa gị na Kọmishọna Ụlọ Ọrụ." 
             data-lang-ha="Rijista ko tabbatar da kasuwancin ku tare da Hukumar Harkokin Kasuwanci.">Register or verify your business with the Corporate Affairs Commission.</p>
          <a href="services/cac.php" class="btn">Explore CAC</a>
        </div>
        <div class="service-card" data-service="jamb">
          <div class="service-status" data-status="online"></div>
          <i class="fas fa-graduation-cap"></i>
          <h4 data-lang-en="JAMB Services" data-lang-yo="Awọn Iṣẹ JAMB" data-lang-ig="Ọrụ JAMB" data-lang-ha="Sabis na JAMB">JAMB Services</h4>
          <p data-lang-en="Register, check results, or correct your JAMB data." 
             data-lang-yo="Forukọsilẹ, ṣayẹwo awọn abajade, tabi ṣe atunṣe data JAMB rẹ." 
             data-lang-ig="Deba aha, lelee nsonaazụ, ma ọ bụ mezie data JAMB gị." 
             data-lang-ha="Rijista, duba sakamako, ko gyara bayanan JAMB ɗin ku.">Register, check results, or correct your JAMB data.</p>
          <a href="services/jamb.php" class="btn">Explore JAMB</a>
        </div>
        <div class="service-card" data-service="news">
          <div class="service-status" data-status="online"></div>
          <i class="fas fa-newspaper"></i>
          <h4 data-lang-en="News Publishing" data-lang-yo="Atẹjade Iroyin" data-lang-ig="Mbipụta Akụkọ" data-lang-ha="Buga Labarai">News Publishing</h4>
          <p data-lang-en="Publish, manage, or view news articles and announcements." 
             data-lang-yo="Ṣe atẹjade, ṣakoso, tabi wo awọn nkan iroyin ati awọn ikede." 
             data-lang-ig="Bipụta, jikwaa, ma ọ bụ lelee akụkọ na ọkwa." 
             data-lang-ha="Buga, sarrafa, ko duba labaran labarai da sanarwa.">Publish, manage, or view news articles and announcements.</p>
          <a href="services/news.php" class="btn">Explore News</a>
        </div>
      </div>
    </div>
  </main>
  
  <section class="how-it-works">
    <div class="container">
      <h3 data-lang-en="How It Works" data-lang-yo="Bí Ó Ṣe Ṣiṣẹ" data-lang-ig="Kedu Ka Ọ Sị Rụọ Ọrụ" data-lang-ha="Yadda Yake Aiki">How It Works</h3>
      <div class="steps">
        <div class="step">
          <h4>1. Sign Up</h4>
          <p>Create an account to access all services securely.</p>
        </div>
        <div class="step">
          <h4>2. Choose a Service</h4>
          <p>Select from NIN, BVN, JAMB, CAC, or other services.</p>
        </div>
        <div class="step">
          <h4>3. Verify & Manage</h4>
          <p>Follow simple steps to verify or manage your details.</p>
        </div>
      </div>
    </div>
  </section>
  
  <section class="trust-security">
    <div class="container">
      <h3 data-lang-en="Trust & Security" data-lang-yo="Igbẹkẹle & Aabo" data-lang-ig="Ntụkwasị obi & Nchekwa" data-lang-ha="Amincewa & Tsaro">Trust & Security</h3>
      <p data-lang-en="Your data is protected with state-of-the-art encryption and complies with Nigerian data protection regulations. We are certified by leading security standards to ensure your peace of mind."
         data-lang-yo="Data rẹ ni aabo pẹlu fifi koodu si ti o dara julọ ati pe o ni ibamu pẹlu awọn ofin aabo data Naijiria. A ni ifọwọsi nipasẹ awọn iṣedede aabo ti o ṣe itọsọna lati rii daju pe o ni alaafia."
         data-lang-ig="A na-echekwa data gị site na izo ya nke ọgbara ọhụrụ ma na-agbaso iwu nchekwa data Nigeria. Anyị enwetala asambodo site na ụkpụrụ nchekwa na-eduga iji hụ na udo nke uche gị."
         data-lang-ha="An kiyaye bayananku tare da ɓoyewar fasaha ta zamani kuma ta dace da dokokin kare bayanai na Najeriya. Mun samu shaidar daga manyan ka'idojin tsaro don tabbatar da kwanciyar hankalin ku.">Your data is protected with state-of-the-art encryption and complies with Nigerian data protection regulations. We are certified by leading security standards to ensure your peace of mind.</p>
    </div>
  </section>
  
  <section class="testimonials-section">
    <div class="container">
      <h3 data-lang-en="What Our Users Say" data-lang-yo="Ohun ti Awọn Olumulo Wa Sọ" data-lang-ig="Ihe Ndị Ọrụ Anyị Na-ekwu" data-lang-ha="Abin da Masu Amfani da Mu Suke Cewa">What Our Users Say</h3>
      <div class="testimonial">
        <p>"Naija eHub made verifying my NIN so easy and fast. The platform is user-friendly and reliable!"</p>
        <p><strong>- Amaka O., Lagos</strong></p>
      </div>
      <div class="testimonial">
        <p>"I used Naija eHub to register my business with CAC, and the process was seamless. Highly recommend!"</p>
        <p><strong>- Chinedu M., Abuja</strong></p>
      </div>
    </div>
  </section>
  
  <section class="faq-section">
    <div class="container">
      <h3 data-lang-en="Frequently Asked Questions" data-lang-yo="Awọn Ibeere Ti a Sọ Pọ" data-lang-ig="Ajụjụ Ndị a Na-ajụkarị" data-lang-ha="Tambayoyin da Aka Saba Tambaya">Frequently Asked Questions</h3>
      <div class="faq-item">
        <div class="faq-question" onclick="toggleFAQ(this)">
          <span data-lang-en="How do I verify my NIN?" 
                data-lang-yo="Bawo ni MO ṣe le ṣe idaniloju NIN mi?" 
                data-lang-ig="Kedu ka m ga-esi nyochaa NIN m?" 
                data-lang-ha="Yadda zan tabbatar da NIN na?">How do I verify my NIN?</span>
          <span>▼</span>
        </div>
        <div class="faq-answer" data-lang-en="You can verify your NIN by navigating to the NIN Services section, entering your details, and following the prompts. Ensure you have your NIN number ready." 
             data-lang-yo="O le ṣe idaniloju NIN rẹ nipa lilọ si apakan Awọn Iṣẹ NIN, titẹ awọn alaye rẹ, ati tẹle awọn itọsọna. Rii daju pe o ni nọmba NIN rẹ ni imurasilẹ." 
             data-lang-ig="Ị nwere ike nyochaa NIN gị site n'ịga na ngalaba Ọrụ NIN, tinye nkọwa gị, ma soro ntụzịaka. Jide n'aka na ị nwere nọmba NIN gị njikere." 
             data-lang-ha="Kuna iya tabbatar da NIN ɗin ku ta hanyar shiga sashen Sabis na NIN, shigar da bayananku, kuma bi umarni. Tabbatar cewa kuna da lambar NIN ɗin ku a shirye.">You can verify your NIN by navigating to the NIN Services section, entering your details, and following the prompts. Ensure you have your NIN number ready.</div>
      </div>
      <div class="faq-item">
        <div class="faq-question" onclick="toggleFAQ(this)">
          <span data-lang-en="Is my data secure on Naija eHub?" 
                data-lang-yo="Ṣe data mi ni aabo lori Naija eHub?" 
                data-lang-ig="Data m ọ nọ na nchekwa na Naija eHub?" 
                data-lang-ha="Shin bayanana suna da aminci a Naija eHub?">Is my data secure on Naija eHub?</span>
          <span>▼</span>
        </div>
        <div class="faq-answer" data-lang-en="Yes, we use advanced encryption and security protocols to protect your data. Your privacy is our priority." 
             data-lang-yo="Bẹẹni, a lo fifi koodu si ti ilọsiwaju ati awọn ilana aabo lati daabobo data rẹ. Asiri rẹ ni pataki wa." 
             data-lang-ig="Ee, anyị na-eji izo ya dị elu na usoro nchekwa iji chekwaa data gị. Nzuzo gị bụ ihe kacha anyị mkpa." 
             data-lang-ha="Ee, muna amfani da ɓoyewar ci gaba da ka'idojin tsaro don kare bayananku. Sirrin ku shine fifikonmu.">Yes, we use advanced encryption and security protocols to protect your data. Your privacy is our priority.</div>
      </div>
    </div>
  </section>
  
  <section class="blog-section">
    <div class="container">
      <h3 data-lang-en="Latest Updates & Tips" data-lang-yo="Awọn Imudojuiwọn Tuntun & Awọn Imọran" data-lang-ig="Mmelite & Ndụmọdụ Kacha Ọhụrụ" data-lang-ha="Sabuntawa & Shawarwari na Ƙarshe">Latest Updates & Tips</h3>
      <div class="blog-posts">
        <div class="blog-post">
          <img src="https://source.unsplash.com/random/300x200?technology" alt="Blog post image">
          <div class="blog-post-content">
            <h4>How to Verify Your NIN in 5 Minutes</h4>
            <p>Learn the quick steps to verify your NIN using Naija eHub's seamless platform.</p>
            <a href="blog/nin-verification.php" class="btn">Read More</a>
          </div>
        </div>
        <div class="blog-post">
          <img src="https://source.unsplash.com/random/300x200?business" alt="Blog post image">
          <div class="blog-post-content">
            <h4>Top Tips for CAC Registration</h4>
            <p>Discover expert advice for registering your business with CAC effortlessly.</p>
            <a href="blog/cac-tips.php" class="btn">Read More</a>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <section class="contact-section">
    <div class="container">
      <h3 data-lang-en="Contact Us" data-lang-yo="Kan si Wa" data-lang-ig="Kpọtụrụ Anyị" data-lang-ha="Tuntube Mu">Contact Us</h3>
      <p data-lang-en="Have questions or need support? Reach out to our team, and we'll get back to you promptly." 
         data-lang-yo="Ṣe o ni awọn ibeere tabi nilo atilẹyin? Kan si ẹgbẹ wa, a yoo pada si ọ ni kiakia." 
         data-lang-ig="Ị nwere ajụjụ ma ọ bụ chọọ nkwado? Kpọtụrụ ndị otu anyị, anyị ga-alaghachikwute gị ngwa ngwa." 
         data-lang-ha="Kuna da tambayoyi ko kuna buƙatar tallafi? Tuntube ƙungiyarmu, za mu mayar muku da sauri.">Have questions or need support? Reach out to our team, and we'll get back to you promptly.</p>
      <form id="contact-form" onsubmit="submitContactForm(event)">
        <input type="text" placeholder="Your Name" required>
        <input type="email" placeholder="Your Email" required>
        <textarea placeholder="Your Message" rows="5" required></textarea>
        <button type="submit">Send Message</button>
      </form>
    </div>
  </section>
  
  <section class="newsletter-section">
    <div class="container">
      <h3 data-lang-en="Stay Updated with Naija eHub" 
          data-lang-yo="Duro Ni Imudojuiwọn pẹlu Naija eHub" 
          data-lang-ig="Nọgide na Mmelite na Naija eHub" 
          data-lang-ha="Ci gaba da Sabuntawa tare da Naija eHub">Stay Updated with Naija eHub</h3>
      <p data-lang-en="Subscribe to our newsletter for the latest updates on services and features." 
         data-lang-yo="Ṣe alabapin si iwe iroyin wa fun awọn imudojuiwọn tuntun lori awọn iṣẹ ati awọn ẹya." 
         data-lang-ig="Denye aha na akwụkwọ akụkọ anyị maka mmelite kacha ọhụrụ na ọrụ na atụmatụ." 
         data-lang-ha="Yi rajista ga labarunmu don samun sabbin abubuwan da suka shafi sabis da fasali.">Subscribe to our newsletter for the latest updates on services and features.</p>
      <form id="newsletter-form" onsubmit="subscribeNewsletter(event)">
        <input type="email" placeholder="Enter your email" required>
        <button type="submit">Subscribe</button>
      </form>
    </div>
  </section>
  
  <footer class="footer">
    <div class="container">
      <p>© <?php echo date('Y'); ?> Naija eHub. All rights reserved.</p>
      <p>
        <a href="about.php">About</a> |
        <a href="privacy.php">Privacy Policy</a> |
        <a href="terms.php">Terms of Service</a>
      </p>
    </div>
  </footer>
  
  <button class="back-to-top" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
  </button>
  
  <button class="chat-toggle" onclick="toggleChat()">
    <i class="fas fa-comment"></i>
  </button>
  
  <div class="chat-window" id="chat-window">
    <div class="chat-header">Naija eHub Support</div>
    <div class="chat-body" id="chat-body">
      <div class="chat-message bot">Welcome! How can we assist you today?</div>
    </div>
    <div class="chat-input">
      <input type="text" placeholder="Type your message..." id="chat-input">
      <button onclick="sendChatMessage()">Send</button>
    </div>
  </div>
  
  <!-- Spinner Overlay -->
  <?php include __DIR__ . '../includes/spinner.php'; ?>
 
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
  <script>
    // Dark Mode Toggle
    function toggleDarkMode() {
      const body = document.body;
      const currentTheme = body.getAttribute('data-theme');
      body.setAttribute('data-theme', currentTheme === 'dark' ? 'light' : 'dark');
      localStorage.setItem('theme', body.getAttribute('data-theme'));
    }

    // High Contrast Toggle
    function toggleHighContrast(isChecked) {
      document.body.setAttribute('data-contrast', isChecked ? 'high' : 'normal');
      localStorage.setItem('contrast', isChecked ? 'high' : 'normal');
    }

    // Load Saved Theme and Contrast
    document.addEventListener('DOMContentLoaded', () => {
      const savedTheme = localStorage.getItem('theme') || 'light';
      const savedContrast = localStorage.getItem('contrast') || 'normal';
      document.body.setAttribute('data-theme', savedTheme);
      document.body.setAttribute('data-contrast', savedContrast);
      document.querySelector('input[type="checkbox"]').checked = savedContrast === 'high';
      updateLanguage('en');
    });

    // Search and Filter Services
    function searchServices(query) {
      const cards = document.querySelectorAll('.service-card');
      const lowerQuery = query.toLowerCase();
      cards.forEach(card => {
        const service = card.getAttribute('data-service').toLowerCase();
        card.style.display = service.includes(lowerQuery) ? 'block' : 'none';
      });
    }

    function filterServices(category) {
      const cards = document.querySelectorAll('.service-card');
      cards.forEach(card => {
        const service = card.getAttribute('data-service');
        card.style.display = category === 'all' || service === category ? 'block' : 'none';
      });
    }

    // Newsletter Subscription
    function subscribeNewsletter(event) {
      event.preventDefault();
      const email = event.target.querySelector('input').value;
      alert(`Thank you for subscribing with ${email}!`);
      event.target.reset();
    }

    // Contact Form Submission
    function submitContactForm(event) {
      event.preventDefault();
      const name = event.target.querySelector('input[type="text"]').value;
      const email = event.target.querySelector('input[type="email"]').value;
      alert(`Thank you, ${name}! Your message has been sent.`);
      event.target.reset();
    }

    // Back to Top
    const backToTopButton = document.querySelector('.back-to-top');
    window.addEventListener('scroll', () => {
      backToTopButton.classList.toggle('show', window.scrollY > 300);
    });

    function scrollToTop() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // FAQ Toggle
    function toggleFAQ(element) {
      const answer = element.nextElementSibling;
      const isActive = answer.classList.contains('active');
      document.querySelectorAll('.faq-answer').forEach(ans => ans.classList.remove('active'));
      if (!isActive) answer.classList.add('active');
    }

    // Accessibility Options
    function toggleAccessibilityPanel() {
      document.getElementById('accessibility-panel').classList.toggle('active');
      document.getElementById('language-panel').classList.remove('active');
    }

    function adjustFontSize(scale) {
      document.documentElement.style.setProperty('--font-size-base', `${scale}rem`);
      localStorage.setItem('font-size', scale);
    }

    // Language Switcher
    function toggleLanguagePanel() {
      document.getElementById('language-panel').classList.toggle('active');
      document.getElementById('accessibility-panel').classList.remove('active');
    }

    function changeLanguage(lang) {
      localStorage.setItem('language', lang);
      updateLanguage(lang);
    }

    function updateLanguage(lang) {
      document.querySelectorAll('[data-lang-en]').forEach(el => {
        el.textContent = el.getAttribute(`data-lang-${lang}`);
      });
    }

    // Chat Support
    function toggleChat() {
      const chatWindow = document.getElementById('chat-window');
      chatWindow.classList.toggle('active');
    }

    function sendChatMessage() {
      const input = document.getElementById('chat-input');
      const chatBody = document.getElementById('chat-body');
      const message = input.value.trim();
      if (!message) return;

      const userMessage = document.createElement('div');
      userMessage.className = 'chat-message user';
      userMessage.textContent = message;
      chatBody.appendChild(userMessage);

      const botMessage = document.createElement('div');
      botMessage.className = 'chat-message bot';
      botMessage.textContent = 'Thank you for your message! Our team will assist you shortly.';
      chatBody.appendChild(botMessage);

      chatBody.scrollTop = chatBody.scrollHeight;
      input.value = '';
    }

    // Mock Service Status Update
    function updateServiceStatus() {
      const statuses = {
        nin: 'online',
        bvn: 'online',
        cac: 'offline',
        jamb: 'online',
        news: 'onoine'
      };
      document.querySelectorAll('.service-status').forEach(status => {
        const service = status.parentElement.getAttribute('data-service');
        status.className = `service-status ${statuses[service] || 'online'}`;
      });
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
      updateServiceStatus();
      setInterval(updateServiceStatus, 60000); // Update every minute
    });
  </script>
</body>
</html>