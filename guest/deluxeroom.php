<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Deluxe Rooms - Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Arial', sans-serif;
      line-height: 1.6;
      color: #333;
    }

    /* Header Styles */
    .top-header {
      background: #018000;
      color: white;
      padding: 10px 0;
      font-size: 14px;
    }

    .top-header-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
    }

    .contact-info {
      display: flex;
      gap: 30px;
    }

    .contact-info span {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .social-links a {
      color: white;
      margin-left: 20px;
      text-decoration: none;
      font-size: 16px;
    }

    .social-links a:hover {
      opacity: 0.8;
    }

    /* Main Navigation */
    .main-nav {
      background: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .nav-container {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .logo img {
      height: 70px;
      width: auto;
    }

    .logo-text h1 {
      color: #018000;
      font-size: 28px;
      margin-bottom: 5px;
      font-weight: 600;
    }

    .logo-text small {
      color: #666;
      font-size: 13px;
    }

    .nav-menu {
      display: flex;
      list-style: none;
      gap: 40px;
      align-items: center;
    }

    .nav-menu a {
      text-decoration: none;
      color: #333;
      font-weight: 500;
      transition: color 0.3s;
      position: relative;
      font-size: 16px;
    }

    .nav-menu a:hover {
      color: #018000;
    }

    .nav-menu a::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 0;
      height: 2px;
      background: #018000;
      transition: width 0.3s;
    }

    .nav-menu a:hover::after {
      width: 100%;
    }

    .book-now-btn {
      background: #018000;
      color: white !important;
      padding: 15px 30px;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
      text-decoration: none;
      display: inline-block;
      font-size: 16px;
    }

    .book-now-btn:hover {
      background: #016000;
      color: white !important;
    }

    /* Dropdown Styles */
    .dropdown {
      position: relative;
    }

    .dropdown-toggle {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .dropdown-toggle i {
      font-size: 12px;
      transition: transform 0.3s;
    }

    .dropdown:hover .dropdown-toggle i {
      transform: rotate(180deg);
    }

    .dropdown-menu {
      position: absolute;
      top: 100%;
      left: 0;
      background: white;
      min-width: 200px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      border-radius: 8px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
      padding: 10px 0;
      margin-top: 10px;
    }

    .dropdown:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .dropdown-menu li {
      list-style: none;
    }

    .dropdown-menu a {
      display: block;
      padding: 12px 20px;
      color: #333;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s;
      font-size: 14px;
    }

    .dropdown-menu a:hover {
      background: #f8f9fa;
      color: #018000;
      padding-left: 25px;
    }

    .dropdown-menu a::after {
      display: none;
    }

    /* Hero Section */
    .hero {
      height: 60vh;
      background: linear-gradient(rgba(1, 128, 0, 0.4), rgba(1, 128, 0, 0.4)), url('images/family retreat_deluxe room.jpg');
      background-size: cover;
      background-position: center;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
    }

    .hero-content {
      max-width: 800px;
      padding: 0 30px;
    }

    .hero h1 {
      font-size: 3.5em;
      margin-bottom: 20px;
      font-weight: 300;
      line-height: 1.2;
    }

    .hero p {
      font-size: 1.3em;
      margin-bottom: 30px;
      opacity: 0.95;
      line-height: 1.6;
    }

    /* Room Section */
    .rooms-section {
      padding: 100px 0;
      background: #f9f9f9;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 30px;
    }

    .section-header {
      text-align: center;
      margin-bottom: 80px;
    }

    .section-header h2 {
      font-size: 3em;
      color: #018000;
      margin-bottom: 20px;
      font-weight: 300;
      line-height: 1.2;
    }

    .section-header p {
      font-size: 1.2em;
      color: #666;
      max-width: 700px;
      margin: 0 auto;
      line-height: 1.6;
    }

    .rooms-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 40px;
      margin-top: 60px;
    }

    .room-card {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .room-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 50px rgba(0,0,0,0.15);
    }

    .room-image {
      height: 250px;
      overflow: hidden;
      position: relative;
    }

    .room-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s;
    }

    .room-card:hover .room-image img {
      transform: scale(1.05);
    }

    .room-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      background: #018000;
      color: white;
      padding: 8px 15px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .room-content {
      padding: 30px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .room-title {
      font-size: 1.6em;
      color: #018000;
      margin-bottom: 15px;
      font-weight: 600;
    }

    .room-description {
      color: #666;
      margin-bottom: 25px;
      line-height: 1.7;
      font-size: 1em;
    }

    .room-features {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 25px;
    }

    .feature-tag {
      background: #e8f5e8;
      color: #018000;
      padding: 8px 15px;
      border-radius: 25px;
      font-size: 12px;
      font-weight: 500;
    }

    .room-price {
      font-size: 1.5em;
      color: #018000;
      font-weight: 600;
      margin-bottom: 25px;
    }

    .price-details {
      font-size: 0.9em;
      color: #888;
      margin-bottom: 20px;
    }

    .room-actions {
      display: flex;
      gap: 15px;
      margin-top: auto;
    }

    .btn-primary {
      background: #018000;
      color: white;
      padding: 15px 25px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-block;
      text-align: center;
      flex: 1;
      font-size: 14px;
    }

    .btn-primary:hover {
      background: #016000;
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: transparent;
      color: #018000;
      padding: 15px 25px;
      border: 2px solid #018000;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-block;
      text-align: center;
      flex: 1;
      font-size: 14px;
    }

    .btn-secondary:hover {
      background: #018000;
      color: white;
      transform: translateY(-2px);
    }

    /* Amenities Section */
    .amenities-section {
      padding: 100px 0;
      background: white;
    }

    .amenities-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
      margin-top: 60px;
    }

    .amenity-item {
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 25px;
      background: #f9f9f9;
      border-radius: 12px;
      transition: all 0.3s ease;
    }

    .amenity-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .amenity-icon {
      font-size: 2em;
      color: #018000;
      width: 50px;
      text-align: center;
    }

    .amenity-text h4 {
      color: #018000;
      margin-bottom: 8px;
      font-size: 1.1em;
    }

    .amenity-text p {
      color: #666;
      font-size: 0.9em;
      line-height: 1.5;
    }

    /* Footer */
    .footer {
      background: #018000;
      color: #fff;
      padding: 60px 0 0;
      font-family: 'Arial', sans-serif;
    }
    .footer-main {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 40px;
      flex-wrap: wrap;
      padding: 0 30px;
    }
    .footer-newsletter {
      flex: 2;
      min-width: 320px;
    }
    .footer-newsletter h2 {
      font-size: 2em;
      font-weight: 600;
      margin-bottom: 18px;
      color: #fff;
    }
    .footer-newsletter p {
      font-size: 1.1em;
      margin-bottom: 25px;
      color: #e0ffe0;
    }
    .newsletter-form {
      display: flex;
      align-items: center;
      border-bottom: 2px solid #fff;
      max-width: 400px;
      margin-bottom: 25px;
    }
    .newsletter-form input[type="email"] {
      background: transparent;
      border: none;
      outline: none;
      color: #fff;
      font-size: 1em;
      padding: 12px 0;
      flex: 1;
    }
    .newsletter-form input::placeholder {
      color: #e0ffe0;
      opacity: 1;
    }
    .newsletter-form button {
      background: none;
      border: none;
      color: #fff;
      font-size: 1.3em;
      cursor: pointer;
      padding: 0 10px;
      transition: color 0.2s;
    }
    .newsletter-form button:hover {
      color: #b6ffb6;
    }
    .footer-social {
      margin-top: 18px;
      display: flex;
      gap: 18px;
    }
    .footer-social a {
      color: #fff;
      font-size: 1.3em;
      transition: color 0.2s;
    }
    .footer-social a:hover {
      color: #b6ffb6;
    }
    .footer-links {
      flex: 3;
      display: flex;
      gap: 60px;
      min-width: 320px;
      justify-content: flex-end;
      flex-wrap: wrap;
    }
    .footer-link-col {
      min-width: 140px;
    }
    .footer-link-col h4 {
      color: #fff;
      font-size: 1.1em;
      margin-bottom: 15px;
      font-weight: 600;
      letter-spacing: 1px;
    }
    .footer-link-col ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .footer-link-col ul li {
      margin-bottom: 10px;
    }
    .footer-link-col ul li a {
      color: #e0ffe0;
      text-decoration: none;
      font-size: 1em;
      transition: color 0.2s;
    }
    .footer-link-col ul li a:hover {
      color: #fff;
      text-decoration: underline;
    }
    .footer-bottom {
      background: #016000;
      color: #b6ffb6;
      text-align: center;
      padding: 18px 0 12px;
      font-size: 1em;
      margin-top: 40px;
    }
    @media (max-width: 900px) {
      .footer-main {
        flex-direction: column;
        gap: 40px;
        align-items: flex-start;
      }
      .footer-links {
        width: 100%;
        justify-content: flex-start;
        gap: 40px;
      }
    }
    @media (max-width: 600px) {
      .footer-main {
        padding: 0 10px;
      }
      .footer-links {
        flex-direction: column;
        gap: 25px;
      }
      .footer-link-col {
        min-width: 0;
      }
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .rooms-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
      }
      
      .nav-container {
        padding: 15px 25px;
      }
    }

    @media (max-width: 768px) {
      .nav-menu {
        display: none;
      }

      .hero h1 {
        font-size: 2.5em;
      }

      .section-header h2 {
        font-size: 2.2em;
      }

      .rooms-grid {
        grid-template-columns: 1fr;
        gap: 30px;
      }

      .amenities-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .room-actions {
        flex-direction: column;
      }

      .footer-content {
        flex-direction: column;
        text-align: center;
        gap: 25px;
      }

      .footer-links {
        justify-content: center;
        gap: 30px;
      }
      
      .nav-container {
        padding: 15px 20px;
      }
    }

    @media (max-width: 480px) {
      .hero h1 {
        font-size: 2em;
      }

      .section-header h2 {
        font-size: 1.8em;
      }

      .room-content {
        padding: 20px;
      }

      .amenity-item {
        padding: 20px;
      }
    }
    /* Footer */
    .footer {
      background: #018000;
      color: #fff;
      padding: 60px 0 0;
      font-family: 'Arial', sans-serif;
    }
    .footer-main {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 40px;
      flex-wrap: wrap;
      padding: 0 30px;
    }
    .footer-newsletter {
      flex: 2;
      min-width: 320px;
    }
    .footer-newsletter h2 {
      font-size: 2em;
      font-weight: 600;
      margin-bottom: 18px;
      color: #fff;
    }
    .footer-newsletter p {
      font-size: 1.1em;
      margin-bottom: 25px;
      color: #e0ffe0;
    }
    .newsletter-form {
      display: flex;
      align-items: center;
      border-bottom: 2px solid #fff;
      max-width: 400px;
      margin-bottom: 25px;
    }
    .newsletter-form input[type="email"] {
      background: transparent;
      border: none;
      outline: none;
      color: #fff;
      font-size: 1em;
      padding: 12px 0;
      flex: 1;
    }
    .newsletter-form input::placeholder {
      color: #e0ffe0;
      opacity: 1;
    }
    .newsletter-form button {
      background: none;
      border: none;
      color: #fff;
      font-size: 1.3em;
      cursor: pointer;
      padding: 0 10px;
      transition: color 0.2s;
    }
    .newsletter-form button:hover {
      color: #b6ffb6;
    }
    .footer-social {
      margin-top: 18px;
      display: flex;
      gap: 18px;
    }
    .footer-social a {
      color: #fff;
      font-size: 1.3em;
      transition: color 0.2s;
    }
    .footer-social a:hover {
      color: #b6ffb6;
    }
    .footer-links {
      flex: 3;
      display: flex;
      gap: 60px;
      min-width: 320px;
      justify-content: flex-end;
      flex-wrap: wrap;
    }
    .footer-link-col {
      min-width: 140px;
    }
    .footer-link-col h4 {
      color: #fff;
      font-size: 1.1em;
      margin-bottom: 15px;
      font-weight: 600;
      letter-spacing: 1px;
    }
    .footer-link-col ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .footer-link-col ul li {
      margin-bottom: 10px;
    }
    .footer-link-col ul li a {
      color: #e0ffe0;
      text-decoration: none;
      font-size: 1em;
      transition: color 0.2s;
    }
    .footer-link-col ul li a:hover {
      color: #fff;
      text-decoration: underline;
    }
    .footer-bottom {
      background: #016000;
      color: #b6ffb6;
      text-align: center;
      padding: 18px 0 12px;
      font-size: 1em;
      margin-top: 40px;
    }
    @media (max-width: 900px) {
      .footer-main {
        flex-direction: column;
        gap: 40px;
        align-items: flex-start;
      }
      .footer-links {
        width: 100%;
        justify-content: flex-start;
        gap: 40px;
      }
    }
    @media (max-width: 600px) {
      .footer-main {
        padding: 0 10px;
      }
      .footer-links {
        flex-direction: column;
        gap: 25px;
      }
      .footer-link-col {
        min-width: 0;
      }
    }

    /* Cookies Policy Popup */
    .cookies-popup {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(0, 0, 0, 0.9);
      color: white;
      padding: 20px;
      z-index: 10000;
      transform: translateY(100%);
      transition: transform 0.3s ease;
    }

    .cookies-popup.show {
      transform: translateY(0);
    }

    .cookies-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
    }

    .cookies-text {
      flex: 1;
      min-width: 300px;
    }

    .cookies-text h3 {
      color: #018000;
      margin-bottom: 10px;
      font-size: 1.2em;
    }

    .cookies-text p {
      font-size: 0.9em;
      line-height: 1.5;
      margin-bottom: 10px;
    }

    .cookies-text a {
      color: #018000;
      text-decoration: underline;
    }

    .cookies-text a:hover {
      color: #016000;
    }

    .cookies-buttons {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }

    .btn-accept {
      background: #018000;
      color: white;
      padding: 12px 25px;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
      font-size: 14px;
    }

    .btn-accept:hover {
      background: #016000;
    }

    .btn-decline {
      background: transparent;
      color: white;
      padding: 12px 25px;
      border: 2px solid white;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 14px;
    }

    .btn-decline:hover {
      background: white;
      color: #333;
    }

    /* Privacy Policy Modal */
    .privacy-modal {
      display: none;
      position: fixed;
      z-index: 10001;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(5px);
    }

    .privacy-modal.show {
      display: block;
    }

    .privacy-content {
      background-color: white;
      margin: 5% auto;
      padding: 30px;
      border-radius: 15px;
      width: 90%;
      max-width: 800px;
      max-height: 80vh;
      overflow-y: auto;
      position: relative;
    }

    .privacy-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .privacy-header h2 {
      color: #018000;
      font-size: 1.8em;
      margin: 0;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 2em;
      cursor: pointer;
      color: #666;
      transition: color 0.3s;
    }

    .close-modal:hover {
      color: #018000;
    }

    .privacy-section {
      margin-bottom: 25px;
    }

    .privacy-section h3 {
      color: #018000;
      font-size: 1.3em;
      margin-bottom: 10px;
    }

    .privacy-section p {
      color: #555;
      line-height: 1.6;
      margin-bottom: 10px;
    }

    .privacy-section ul {
      color: #555;
      line-height: 1.6;
      margin-left: 20px;
    }

    .privacy-section li {
      margin-bottom: 5px;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .container {
        padding: 0 25px;
      }
      
      .nav-container {
        padding: 15px 25px;
      }
      
      .hero h1 {
        font-size: 3.5em;
      }
      
      .section-header h2 {
        font-size: 2.5em;
      }
      
      .welcome-content,
      .contact-content {
        gap: 60px;
      }

      .rooms-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
      }
    }

    @media (max-width: 768px) {
      .nav-menu {
        display: none;
      }

      .hero {
        height: 70vh;
      }

      .hero h1 {
        font-size: 2.8em;
      }

      .hero p {
        font-size: 1.2em;
      }

      .welcome-section,
      .rooms-section,
      .amenities-section,
      .contact-section {
        padding: 70px 0;
      }

      .section-header {
        margin-bottom: 50px;
      }

      .section-header h2 {
        font-size: 2.2em;
      }

      .welcome-content,
      .contact-content {
        grid-template-columns: 1fr;
        gap: 50px;
      }

      .rooms-grid {
        grid-template-columns: 1fr;
        gap: 30px;
      }

      .amenities-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
      }

      .contact-info {
        grid-template-columns: 1fr;
        gap: 25px;
      }

      .hero-buttons {
        flex-direction: column;
        align-items: center;
        gap: 20px;
      }

      .contact-info {
        flex-direction: column;
        gap: 15px;
      }

      .nav-container {
        padding: 15px 20px;
      }

      .container {
        padding: 0 20px;
      }

      .footer-content {
        flex-direction: column;
        text-align: center;
        gap: 25px;
      }

      .footer-links {
        justify-content: center;
        gap: 30px;
      }

      .cookies-content {
        flex-direction: column;
        text-align: center;
      }

      .cookies-text {
        min-width: auto;
      }

      .cookies-buttons {
        justify-content: center;
      }

      .privacy-content {
        margin: 10% auto;
        padding: 20px;
        width: 95%;
      }

      .privacy-header h2 {
        font-size: 1.5em;
      }
    }

    @media (max-width: 480px) {
      .hero h1 {
        font-size: 2.2em;
      }

      .section-header h2 {
        font-size: 1.8em;
      }

      .welcome-text h3,
      .contact-info h3 {
        font-size: 1.8em;
      }

      .room-content {
        padding: 20px;
      }

      .amenity-card {
        padding: 30px 20px;
      }

      .contact-item {
        padding: 25px 15px;
      }
    }
  </style>
</head>
<body>
<body>
  <!-- Cookies Policy Popup -->
  <div id="cookiesPopup" class="cookies-popup">
    <div class="cookies-content">
      <div class="cookies-text">
        <h3>🍪 Cookies & Privacy Policy</h3>
        <p>We use cookies to enhance your browsing experience and analyze our website traffic. By clicking "Accept", you consent to our use of cookies and agree to our <a href="#" id="privacyLink">Privacy Policy</a>.</p>
      </div>
      <div class="cookies-buttons">
        <button class="btn-decline" onclick="declineCookies()">Decline</button>
        <button class="btn-accept" onclick="acceptCookies()">Accept</button>
      </div>
    </div>
  </div>

  <!-- Privacy Policy Modal -->
  <div id="privacyModal" class="privacy-modal">
    <div class="privacy-content">
      <div class="privacy-header">
        <h2>Privacy Policy</h2>
        <button class="close-modal" onclick="closePrivacyModal()">&times;</button>
      </div>
      
      <div class="privacy-section">
        <h3>Information We Collect</h3>
        <p>Villa Valore Hotel collects information you provide directly to us, such as when you make a reservation, contact us, or sign up for our services. This may include:</p>
        <ul>
          <li>Name, email address, phone number, and mailing address</li>
          <li>Payment information and booking details</li>
          <li>Preferences and special requests</li>
          <li>Feedback and reviews</li>
        </ul>
      </div>

      <div class="privacy-section">
        <h3>How We Use Your Information</h3>
        <p>We use the information we collect to:</p>
        <ul>
          <li>Process and manage your reservations</li>
          <li>Provide customer service and support</li>
          <li>Send you important updates about your stay</li>
          <li>Improve our services and website</li>
          <li>Comply with legal obligations</li>
        </ul>
      </div>

      <div class="privacy-section">
        <h3>Cookies and Tracking</h3>
        <p>Our website uses cookies to enhance your experience. These cookies help us:</p>
        <ul>
          <li>Remember your preferences and settings</li>
          <li>Analyze website traffic and usage patterns</li>
          <li>Provide personalized content and recommendations</li>
          <li>Ensure website security and functionality</li>
        </ul>
      </div>

      <div class="privacy-section">
        <h3>Information Sharing</h3>
        <p>We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
        <ul>
          <li>With your explicit consent</li>
          <li>To comply with legal requirements</li>
          <li>To protect our rights and safety</li>
          <li>With trusted service providers who assist in our operations</li>
        </ul>
      </div>

      <div class="privacy-section">
        <h3>Data Security</h3>
        <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure.</p>
      </div>

      <div class="privacy-section">
        <h3>Your Rights</h3>
        <p>You have the right to:</p>
        <ul>
          <li>Access and update your personal information</li>
          <li>Request deletion of your data</li>
          <li>Opt-out of marketing communications</li>
          <li>Withdraw consent at any time</li>
        </ul>
      </div>

      <div class="privacy-section">
        <h3>Contact Us</h3>
        <p>If you have any questions about this Privacy Policy or our data practices, please contact us at:</p>
        <p><strong>Email:</strong> villavalorehotel@gmail.com<br>
        <strong>Phone:</strong> 0912-345-6789<br>
        <strong>Address:</strong> Biga I, Silang, Cavite</p>
      </div>

      <div class="privacy-section">
        <h3>Updates to This Policy</h3>
        <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last Updated" date.</p>
        <p><strong>Last Updated:</strong> <?php echo date('F j, Y'); ?></p>
      </div>
    </div>
  </div>

  <!-- FAQ Modal -->
  <div id="faqModal" class="privacy-modal">
    <div class="privacy-content">
      <div class="privacy-header">
        <h2>Frequently Asked Questions</h2>
        <button class="close-modal" onclick="closeFaqModal()">&times;</button>
      </div>
      <div class="privacy-section">
        <h3>What time is check-in and check-out?</h3>
        <p>Check-in is from 2:00 PM, and check-out is until 12:00 PM. Early check-in and late check-out are subject to availability.</p>
      </div>
      <div class="privacy-section">
        <h3>Is breakfast included in the room rate?</h3>
        <p>Breakfast is available for an additional fee unless specified in your booking. Please check your reservation details.</p>
      </div>
      <div class="privacy-section">
        <h3>Do you offer free WiFi?</h3>
        <p>Yes, complimentary high-speed WiFi is available throughout the hotel for all guests.</p>
      </div>
      <div class="privacy-section">
        <h3>Is parking available?</h3>
        <p>Yes, we offer free secure parking for all hotel guests.</p>
      </div>
      <div class="privacy-section">
        <h3>Can I cancel or modify my reservation?</h3>
        <p>Yes, you can cancel or modify your reservation according to the cancellation policy stated in your booking confirmation.</p>
      </div>
      <div class="privacy-section">
        <h3>Are pets allowed?</h3>
        <p>Unfortunately, pets are not allowed in the hotel premises.</p>
      </div>
      <div class="privacy-section">
        <h3>How can I contact the hotel?</h3>
        <p>You can reach us via phone at 0912-345-6789 or email at villavalorehotel@gmail.com, or visit our <a href="contact.php">Contact page</a>.</p>
      </div>
    </div>
  </div>

  <!-- Terms of Use Modal -->
  <div id="termsModal" class="privacy-modal">
    <div class="privacy-content">
      <div class="privacy-header">
        <h2>Terms of Use</h2>
        <button class="close-modal" onclick="closeTermsModal()">&times;</button>
      </div>
      <div class="privacy-section">
        <h3>Acceptance of Terms</h3>
        <p>By accessing and using the Villa Valore Hotel website, you agree to comply with and be bound by these Terms of Use. If you do not agree, please do not use our site.</p>
      </div>
      <div class="privacy-section">
        <h3>Reservations & Payments</h3>
        <p>All reservations are subject to availability. Payment details provided must be valid and authorized. Cancellations and modifications are subject to our policies.</p>
      </div>
      <div class="privacy-section">
        <h3>Guest Conduct</h3>
        <p>Guests are expected to behave respectfully and comply with hotel rules. We reserve the right to refuse service to anyone violating our policies.</p>
      </div>
      <div class="privacy-section">
        <h3>Intellectual Property</h3>
        <p>All content on this website is the property of Villa Valore Hotel and may not be used or reproduced without permission.</p>
      </div>
      <div class="privacy-section">
        <h3>Limitation of Liability</h3>
        <p>Villa Valore Hotel is not liable for any damages arising from the use of this website or your stay, except as required by law.</p>
      </div>
      <div class="privacy-section">
        <h3>Changes to Terms</h3>
        <p>We may update these Terms of Use at any time. Continued use of the site constitutes acceptance of the new terms.</p>
      </div>
      <div class="privacy-section">
        <h3>Contact</h3>
        <p>If you have questions about these Terms, please contact us at villavalorehotel@gmail.com.</p>
      </div>
    </div>
  </div>
  <!-- Top Header -->
  <div class="top-header">
    <div class="top-header-content">
      <div class="contact-info">
        <span><i class="fas fa-phone"></i> 0912-345-6789</span>
        <span><i class="fas fa-envelope"></i> villavalorehotel@gmail.com</span>
        <span><i class="fas fa-map-marker-alt"></i> Biga I, Silang, Cavite</span>
      </div>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
      </div>
    </div>
  </div>

  <!-- Main Navigation -->
  <nav class="main-nav">
    <div class="nav-container">
      <div class="logo">
        <img src="images/villavalorelogo.png" alt="Villa Valore Logo">
        <div class="logo-text">
          <h1>Villa Valore Hotel</h1>
          <small>BIGA I, SILANG, CAVITE</small>
        </div>
      </div>
      <ul class="nav-menu">
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle">Rooms <i class="fas fa-chevron-down"></i></a>
          <ul class="dropdown-menu">
            <li><a href="standardroom.php">Standard Room</a></li>
            <li><a href="deluxeroom.php">Deluxe Room</a></li>
            <li><a href="suiteroom.php">Suite Room</a></li>
          </ul>
        </li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="booking.php" class="book-now-btn">Book Now</a></li>
      </ul>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <h1>Deluxe Rooms</h1>
      <p>Spacious accommodations with premium amenities for families and groups</p>
    </div>
  </section>

  <!-- Rooms Section -->
  <section class="rooms-section">
    <div class="container">
      <div class="section-header">
        <h2>Our Deluxe Rooms</h2>
        <p>Experience luxury and comfort in our spacious deluxe accommodations</p>
      </div>
      <div class="rooms-grid">
        <!-- Room 1 -->
        <div class="room-card">
          <div class="room-image">
            <img src="images/family retreat_deluxe room.jpg" alt="Family Retreat Deluxe Room">
            <div class="room-badge">Family Favorite</div>
          </div>
          <div class="room-content">
            <h3 class="room-title">Family Retreat Deluxe Room</h3>
            <p class="room-description">Perfect for families seeking comfort and space. Features a king bed and sofa bed, ideal for quality family time.</p>
            <div class="room-features">
              <span class="feature-tag">1 King Bed</span>
              <span class="feature-tag">1 Sofa Bed</span>
              <span class="feature-tag">Up to 4 Guests</span>
              <span class="feature-tag">38 sq m</span>
            </div>
            <div class="room-price">₱2,200</div>
            <div class="price-details">per night • Free cancellation</div>
            <div class="room-actions">
              <a href="booking.php?room=family_retreat_deluxe" class="btn-primary">Book Now</a>
              <a href="#" class="btn-secondary">View Details</a>
            </div>
          </div>
        </div>

        <!-- Room 2 -->
        <div class="room-card">
          <div class="room-image">
            <img src="images/luxe escape_deluxe room.png" alt="Luxe Escape Deluxe Room">
            <div class="room-badge">Premium View</div>
          </div>
          <div class="room-content">
            <h3 class="room-title">Luxe Escape Deluxe Room</h3>
            <p class="room-description">Elegant room with panoramic views and premium amenities. Perfect for couples seeking a romantic getaway.</p>
            <div class="room-features">
              <span class="feature-tag">1 King Bed</span>
              <span class="feature-tag">1 Sofa Bed</span>
              <span class="feature-tag">Up to 4 Guests</span>
              <span class="feature-tag">40 sq m</span>
            </div>
            <div class="room-price">₱2,400</div>
            <div class="price-details">per night • Free cancellation</div>
            <div class="room-actions">
              <a href="booking.php?room=luxe_escape_deluxe" class="btn-primary">Book Now</a>
              <a href="#" class="btn-secondary">View Details</a>
            </div>
          </div>
        </div>

        <!-- Room 3 -->
        <div class="room-card">
          <div class="room-image">
            <img src="images/premier loft_deluxe room.jpg" alt="Premier Loft Deluxe Room">
            <div class="room-badge">Executive Style</div>
          </div>
          <div class="room-content">
            <h3 class="room-title">Premier Loft Deluxe Room</h3>
            <p class="room-description">Sophisticated loft-style room with separate living area. Ideal for business travelers or extended stays.</p>
            <div class="room-features">
              <span class="feature-tag">1 King Bed</span>
              <span class="feature-tag">1 Sofa Bed</span>
              <span class="feature-tag">Up to 4 Guests</span>
              <span class="feature-tag">42 sq m</span>
            </div>
            <div class="room-price">₱2,600</div>
            <div class="price-details">per night • Free cancellation</div>
            <div class="room-actions">
              <a href="booking.php?room=premier_loft_deluxe" class="btn-primary">Book Now</a>
              <a href="#" class="btn-secondary">View Details</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Amenities Section -->
  <section class="amenities-section">
    <div class="container">
      <div class="section-header">
        <h2>Deluxe Room Amenities</h2>
        <p>Enhanced amenities and services for your deluxe experience</p>
      </div>
      <div class="amenities-grid">
        <div class="amenity-item">
          <div class="amenity-icon">
            <i class="fas fa-wifi"></i>
          </div>
          <div class="amenity-text">
            <h4>Premium WiFi</h4>
            <p>High-speed internet with extended bandwidth</p>
          </div>
        </div>
        <div class="amenity-item">
          <div class="amenity-icon">
            <i class="fas fa-snowflake"></i>
          </div>
          <div class="amenity-text">
            <h4>Climate Control</h4>
            <p>Individual air conditioning and heating</p>
          </div>
        </div>
        <div class="amenity-item">
          <div class="amenity-icon">
            <i class="fas fa-tv"></i>
          </div>
          <div class="amenity-text">
            <h4>Smart TV</h4>
            <p>55" flat-screen with streaming services</p>
          </div>
        </div>
        <div class="amenity-item">
          <div class="amenity-icon">
            <i class="fas fa-bath"></i>
          </div>
          <div class="amenity-text">
            <h4>En-suite Bathroom</h4>
            <p>Spacious bathroom with premium toiletries</p>
          </div>
        </div>
        <div class="amenity-item">
          <div class="amenity-icon">
            <i class="fas fa-coffee"></i>
          </div>
          <div class="amenity-text">
            <h4>Coffee Station</h4>
            <p>Nespresso machine with premium coffee</p>
          </div>
        </div>
        <div class="amenity-item">
          <div class="amenity-icon">
            <i class="fas fa-concierge-bell"></i>
          </div>
          <div class="amenity-text">
            <h4>Concierge Service</h4>
            <p>Personalized assistance and recommendations</p>
          </div>
        </div>
        <div class="amenity-item">
          <div class="amenity-icon">
            <i class="fas fa-couch"></i>
          </div>
          <div class="amenity-text">
            <h4>Living Area</h4>
            <p>Comfortable seating and work space</p>
          </div>
        </div>
        <div class="amenity-item">
          <div class="amenity-icon">
            <i class="fas fa-utensils"></i>
          </div>
          <div class="amenity-text">
            <h4>Room Service</h4>
            <p>Extended hours with gourmet menu</p>
          </div>
        </div>
      </div>
    </div>
  </section>

   <!-- Footer -->
   <footer class="footer">
    <div class="footer-main">
      <div class="footer-newsletter">
        <h2>Stay in Touch</h2>
        <p>Sign up to receive the latest news, exclusive offers, and updates from Villa Valore Hotel.</p>
        <form class="newsletter-form" onsubmit="event.preventDefault();">
          <input type="email" placeholder="Email Address" required />
          <button type="submit" title="Subscribe"><i class="fas fa-arrow-right"></i></button>
        </form>
        <div class="footer-social">
          <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
        </div>
      </div>
      <div class="footer-links">
        <div class="footer-link-col">
          <h4>Hotel</h4>
          <ul>
            <li><a href="about.php">About Us</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="standardroom.php">Rooms</a></li>
            <li><a href="booking.php">Book Now</a></li>
          </ul>
        </div>
        <div class="footer-link-col">
          <h4>Info</h4>
          <ul>
            <li><a href="#" onclick="openFaqModal();return false;">FAQ</a></li>
            <li><a href="#" onclick="openTermsModal();return false;">Terms of Use</a></li>
            <li><a href="#" onclick="openPrivacyModal();return false;">Privacy Policy</a></li>
            <li><a href="contact.php">Location</a></li>
          </ul>
        </div>
        <div class="footer-link-col">
          <h4>Connect</h4>
          <ul>
            <li><a href="#">Facebook</a></li>
            <li><a href="#">Instagram</a></li>
            <li><a href="#">Twitter</a></li>
            <li><a href="mailto:villavalorehotel@gmail.com">Email Us</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      &copy; <?php echo date('Y'); ?> Villa Valore Hotel. All rights reserved.
    </div>
  </footer>

  <!-- Privacy Policy Modal -->
  <div id="privacyModal" class="privacy-modal">
    <div class="privacy-content">
      <div class="privacy-header">
        <h2>Privacy Policy</h2>
        <button class="close-modal" onclick="closePrivacyModal()">&times;</button>
      </div>
      <div class="privacy-section">
        <h3>Information We Collect</h3>
        <p>Villa Valore Hotel collects information you provide directly to us, such as when you make a reservation, contact us, or sign up for our services. This may include:</p>
        <ul>
          <li>Name, email address, phone number, and mailing address</li>
          <li>Payment information and booking details</li>
          <li>Preferences and special requests</li>
          <li>Feedback and reviews</li>
        </ul>
      </div>
      <div class="privacy-section">
        <h3>How We Use Your Information</h3>
        <p>We use the information we collect to:</p>
        <ul>
          <li>Process and manage your reservations</li>
          <li>Provide customer service and support</li>
          <li>Send you important updates about your stay</li>
          <li>Improve our services and website</li>
          <li>Comply with legal obligations</li>
        </ul>
      </div>
      <div class="privacy-section">
        <h3>Cookies and Tracking</h3>
        <p>Our website uses cookies to enhance your experience. These cookies help us:</p>
        <ul>
          <li>Remember your preferences and settings</li>
          <li>Analyze website traffic and usage patterns</li>
          <li>Provide personalized content and recommendations</li>
          <li>Ensure website security and functionality</li>
        </ul>
      </div>
      <div class="privacy-section">
        <h3>Information Sharing</h3>
        <p>We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
        <ul>
          <li>With your explicit consent</li>
          <li>To comply with legal requirements</li>
          <li>To protect our rights and safety</li>
          <li>With trusted service providers who assist in our operations</li>
        </ul>
      </div>
      <div class="privacy-section">
        <h3>Data Security</h3>
        <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure.</p>
      </div>
      <div class="privacy-section">
        <h3>Your Rights</h3>
        <p>You have the right to:</p>
        <ul>
          <li>Access and update your personal information</li>
          <li>Request deletion of your data</li>
          <li>Opt-out of marketing communications</li>
          <li>Withdraw consent at any time</li>
        </ul>
      </div>
      <div class="privacy-section">
        <h3>Contact Us</h3>
        <p>If you have any questions about this Privacy Policy or our data practices, please contact us at:</p>
        <p><strong>Email:</strong> villavalorehotel@gmail.com<br>
        <strong>Phone:</strong> 0912-345-6789<br>
        <strong>Address:</strong> Biga I, Silang, Cavite</p>
      </div>
      <div class="privacy-section">
        <h3>Updates to This Policy</h3>
        <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last Updated" date.</p>
        <p><strong>Last Updated:</strong> <?php echo date('F j, Y'); ?></p>
      </div>
    </div>
  </div>

  <!-- FAQ Modal -->
  <div id="faqModal" class="privacy-modal">
    <div class="privacy-content">
      <div class="privacy-header">
        <h2>Frequently Asked Questions</h2>
        <button class="close-modal" onclick="closeFaqModal()">&times;</button>
      </div>
      <div class="privacy-section">
        <h3>What time is check-in and check-out?</h3>
        <p>Check-in is from 2:00 PM, and check-out is until 12:00 PM. Early check-in and late check-out are subject to availability.</p>
      </div>
      <div class="privacy-section">
        <h3>Is breakfast included in the room rate?</h3>
        <p>Breakfast is available for an additional fee unless specified in your booking. Please check your reservation details.</p>
      </div>
      <div class="privacy-section">
        <h3>Do you offer free WiFi?</h3>
        <p>Yes, complimentary high-speed WiFi is available throughout the hotel for all guests.</p>
      </div>
      <div class="privacy-section">
        <h3>Is parking available?</h3>
        <p>Yes, we offer free secure parking for all hotel guests.</p>
      </div>
      <div class="privacy-section">
        <h3>Can I cancel or modify my reservation?</h3>
        <p>Yes, you can cancel or modify your reservation according to the cancellation policy stated in your booking confirmation.</p>
      </div>
      <div class="privacy-section">
        <h3>Are pets allowed?</h3>
        <p>Unfortunately, pets are not allowed in the hotel premises.</p>
      </div>
      <div class="privacy-section">
        <h3>How can I contact the hotel?</h3>
        <p>You can reach us via phone at 0912-345-6789 or email at villavalorehotel@gmail.com, or visit our <a href="contact.php">Contact page</a>.</p>
      </div>
    </div>
  </div>

  <!-- Terms of Use Modal -->
  <div id="termsModal" class="privacy-modal">
    <div class="privacy-content">
      <div class="privacy-header">
        <h2>Terms of Use</h2>
        <button class="close-modal" onclick="closeTermsModal()">&times;</button>
      </div>
      <div class="privacy-section">
        <h3>Acceptance of Terms</h3>
        <p>By accessing and using the Villa Valore Hotel website, you agree to comply with and be bound by these Terms of Use. If you do not agree, please do not use our site.</p>
      </div>
      <div class="privacy-section">
        <h3>Reservations & Payments</h3>
        <p>All reservations are subject to availability. Payment details provided must be valid and authorized. Cancellations and modifications are subject to our policies.</p>
      </div>
      <div class="privacy-section">
        <h3>Guest Conduct</h3>
        <p>Guests are expected to behave respectfully and comply with hotel rules. We reserve the right to refuse service to anyone violating our policies.</p>
      </div>
      <div class="privacy-section">
        <h3>Intellectual Property</h3>
        <p>All content on this website is the property of Villa Valore Hotel and may not be used or reproduced without permission.</p>
      </div>
      <div class="privacy-section">
        <h3>Limitation of Liability</h3>
        <p>Villa Valore Hotel is not liable for any damages arising from the use of this website or your stay, except as required by law.</p>
      </div>
      <div class="privacy-section">
        <h3>Changes to Terms</h3>
        <p>We may update these Terms of Use at any time. Continued use of the site constitutes acceptance of the new terms.</p>
      </div>
      <div class="privacy-section">
        <h3>Contact</h3>
        <p>If you have questions about these Terms, please contact us at villavalorehotel@gmail.com.</p>
      </div>
    </div>
  </div>

  <script>
    // Cookies and Privacy Policy functionality
    document.addEventListener('DOMContentLoaded', function() {
      // Check if user has already accepted cookies
      const cookiesAccepted = localStorage.getItem('cookiesAccepted');
      
      if (!cookiesAccepted) {
        // Show cookies popup after a short delay
        setTimeout(() => {
          document.getElementById('cookiesPopup').classList.add('show');
        }, 1000);
      }

      // Privacy policy link click handler
      document.getElementById('privacyLink').addEventListener('click', function(e) {
        e.preventDefault();
        openPrivacyModal();
      });

      // Close modal when clicking outside
      document.getElementById('privacyModal').addEventListener('click', function(e) {
        if (e.target === this) {
          closePrivacyModal();
        }
      });

      // Close modal with Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closePrivacyModal();
          closeFaqModal();
          closeTermsModal();
        }
      });
    });

    function acceptCookies() {
      // Set cookie acceptance in localStorage
      localStorage.setItem('cookiesAccepted', 'true');
      localStorage.setItem('cookiesAcceptedDate', new Date().toISOString());
      
      // Hide the popup
      document.getElementById('cookiesPopup').classList.remove('show');
      
      // Optional: Show a brief confirmation message
      showNotification('Thank you! Your preferences have been saved.', 'success');
    }

    function declineCookies() {
      // Set cookie decline in localStorage
      localStorage.setItem('cookiesAccepted', 'false');
      localStorage.setItem('cookiesDeclinedDate', new Date().toISOString());
      
      // Hide the popup
      document.getElementById('cookiesPopup').classList.remove('show');
      
      // Optional: Show a brief confirmation message
      showNotification('You have declined cookies. Some features may be limited.', 'info');
    }

    function openPrivacyModal() {
      document.getElementById('privacyModal').classList.add('show');
      document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    function closePrivacyModal() {
      document.getElementById('privacyModal').classList.remove('show');
      document.body.style.overflow = ''; // Restore scrolling
    }

    function openFaqModal() {
      document.getElementById('faqModal').classList.add('show');
      document.body.style.overflow = 'hidden';
    }
    function closeFaqModal() {
      document.getElementById('faqModal').classList.remove('show');
      document.body.style.overflow = '';
    }
    function openTermsModal() {
      document.getElementById('termsModal').classList.add('show');
      document.body.style.overflow = 'hidden';
    }
    function closeTermsModal() {
      document.getElementById('termsModal').classList.remove('show');
      document.body.style.overflow = '';
    }

    function showNotification(message, type = 'info') {
      // Create notification element
      const notification = document.createElement('div');
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#018000' : '#666'};
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 10002;
        font-size: 14px;
        max-width: 300px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
      `;
      notification.textContent = message;
      
      // Add to page
      document.body.appendChild(notification);
      
      // Animate in
      setTimeout(() => {
        notification.style.transform = 'translateX(0)';
      }, 100);
      
      // Remove after 3 seconds
      setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
          document.body.removeChild(notification);
        }, 300);
      }, 3000);
    }

    // Optional: Add analytics tracking (only if cookies are accepted)
    function trackEvent(eventName, eventData = {}) {
      const cookiesAccepted = localStorage.getItem('cookiesAccepted');
      if (cookiesAccepted === 'true') {
        // Here you would typically send data to your analytics service
        console.log('Analytics Event:', eventName, eventData);
      }
    }

    // Track page views if cookies are accepted
    if (localStorage.getItem('cookiesAccepted') === 'true') {
      trackEvent('page_view', {
        page: window.location.pathname,
        title: document.title
      });
    }
  </script>

</body>
</html> 