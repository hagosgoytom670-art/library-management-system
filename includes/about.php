<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>About Us - Library and Documentation Service Directorate | Raya University</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      background: #f0f2f5;
      color: #333;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      background: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    /* Header */
    .header {
      background: linear-gradient(135deg, #2c3e50, #3498db);
      color: white;
      padding: 30px 40px;
      text-align: center;
    }
    
    .header h1 {
      font-size: 32px;
      margin-bottom: 10px;
    }
    
    .header p {
      font-size: 16px;
      opacity: 0.9;
    }
    
    .back-link {
      display: inline-block;
      margin-top: 15px;
      color: white;
      text-decoration: none;
      background: rgba(255,255,255,0.2);
      padding: 8px 20px;
      border-radius: 25px;
      transition: all 0.3s;
    }
    
    .back-link:hover {
      background: rgba(255,255,255,0.3);
      transform: translateY(-2px);
    }
    
    /* Content */
    .content {
      padding: 40px;
    }
    
    /* Director Message Section - with admin image */
    .director-section {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 40px;
      border-left: 5px solid #3498db;
    }
    
    .director-title {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 25px;
      flex-wrap: wrap;
    }
    
    /* Admin image styling */
    .director-avatar {
      flex-shrink: 0;
      width: 90px;
      height: 90px;
      border-radius: 50%;
      object-fit: cover;
      background: #ffffff;
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
      border: 3px solid #3498db;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .director-avatar:hover {
      transform: scale(1.02);
      box-shadow: 0 12px 24px rgba(52,152,219,0.25);
    }
    
    .director-info h2 {
      color: #2c3e50;
      font-size: 24px;
    }
    
    .director-info p {
      color: #666;
      margin-top: 5px;
    }
    
    .message-content {
      font-style: italic;
      line-height: 1.8;
      color: #444;
      margin-bottom: 20px;
    }
    
    .signature {
      margin-top: 20px;
      padding-top: 15px;
      border-top: 1px solid #ddd;
    }
    
    .signature strong {
      color: #2c3e50;
    }
    
    .contact-details {
      background: white;
      padding: 15px;
      border-radius: 10px;
      margin-top: 15px;
    }
    
    .contact-details p {
      margin: 5px 0;
    }
    
    .contact-details a {
      color: #3498db;
      text-decoration: none;
    }
    
    .contact-details a:hover {
      text-decoration: underline;
    }
    
    /* Section Titles */
    .section-title {
      font-size: 28px;
      color: #2c3e50;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 3px solid #3498db;
      display: inline-block;
    }
    
    /* General Info */
    .info-card {
      background: #f8f9fa;
      padding: 25px;
      border-radius: 10px;
      margin-bottom: 30px;
    }
    
    .info-card p {
      margin-bottom: 15px;
      text-align: justify;
    }
    
    .info-highlight {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 20px;
      border-radius: 10px;
      margin: 20px 0;
      text-align: center;
    }
    
    .info-highlight h4 {
      font-size: 18px;
      margin-bottom: 10px;
    }
    
    /* Teams Grid */
    .teams-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
      margin: 30px 0;
    }
    
    .team-card {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      transition: transform 0.3s;
    }
    
    .team-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .team-icon {
      font-size: 40px;
      margin-bottom: 15px;
    }
    
    .team-card h3 {
      color: #2c3e50;
      margin-bottom: 10px;
    }
    
    .team-card p {
      color: #666;
      font-size: 14px;
      line-height: 1.6;
    }
    
    /* Staff Table */
    .staff-table-container {
      overflow-x: auto;
      margin: 30px 0;
    }
    
    .staff-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .staff-table th,
    .staff-table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #dee2e6;
    }
    
    .staff-table th {
      background: #2c3e50;
      color: white;
      font-weight: 600;
    }
    
    .staff-table tr:hover {
      background: #f8f9fa;
    }
    
    .staff-table a {
      color: #3498db;
      text-decoration: none;
    }
    
    .staff-table a:hover {
      text-decoration: underline;
    }
    
    /* Footer */
    .footer {
      background: #2c3e50;
      color: white;
      text-align: center;
      padding: 20px;
      font-size: 14px;
    }
    
    .footer a {
      color: white;
      text-decoration: underline;
    }
    
    .footer a:hover {
      color: #3498db;
    }
    
    @media (max-width: 768px) {
      .content {
        padding: 20px;
      }
      
      .director-title {
        flex-direction: column;
        text-align: center;
      }
      
      .director-avatar {
        width: 90px;
        height: 90px;
        margin-bottom: 5px;
      }
      
      .teams-grid {
        grid-template-columns: 1fr;
      }
      
      .staff-table th,
      .staff-table td {
        padding: 8px;
        font-size: 12px;
      }
    }
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>📚 Library and Documentation Service Directorate</h1>
    <p>Raya University - Empowering Knowledge, Inspiring Innovation</p>
    <a href="/lmsPro/index.php" class="back-link">← Back to Home</a>
  </div>
  
  <div class="content">
    <!-- Director's Message with Library Admin Image -->
    <div class="director-section">
      <div class="director-title">
        <!-- IMAGE FROM lmspro/assets/images/ FOLDER -->
        <!-- REPLACE 'your-library-admin.jpg' WITH YOUR ACTUAL IMAGE FILENAME -->
        <img 
          class="director-avatar" 
          src="/lmspro/assets/images/library-admin.jpg" 
          alt="Library Director - Tekle Haylekiros Assefa"
          onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?background=2c3e50&color=fff&rounded=true&size=90&name=Tekle+H';"
          loading="lazy"
        >
        <div class="director-info">
          <h2>Message from the Director</h2>
          <p>Library and Documentation Service Directorate</p>
        </div>
      </div>
      
      <div class="message-content">
        <p>Welcome to the Library and Documentation Directorate at Raya University!</p>
        <p style="margin-top: 10px;">Our library offers a welcoming and comfortable space where readers can fully immerse themselves in their academic and intellectual pursuits. We provide a wide range of services, including access to an extensive collection of books, digital resources, and internet-enabled computers. Our 24/7 operation ensures that you have uninterrupted access to knowledge and learning whenever you need it.</p>
        <p style="margin-top: 10px;">We also offer a digital library powered by SRE technology, providing seamless access to scholarly resources and fostering innovation and research. Our team of dedicated staff is committed to creating a supportive and enriching environment, assisting you in finding the information and tools you need for use.</p>
        <p style="margin-top: 10px;">At Raya University Library and Documentation, we strive to uphold excellence in service, embracing both traditional and digital platforms to meet the diverse needs of our academic community. We invite you to explore, engage, and make the most of our resources as we work together towards academic and professional growth.</p>
      </div>
      
      <div class="signature">
        <p><strong>Kind Regards,</strong></p>
        <p><strong>Tekle Haylekiros Assefa</strong></p>
        <p>Director of Library and Documentation</p>
        <div class="contact-details">
          <p>📞 Phone: <a href="tel:+251985414846">+251 985 41 48 46</a></p>
          <p>✉️ Email: <a href="mailto:TekleHaylekiros@rayu.edu.et">TekleHaylekiros@rayu.edu.et</a></p>
          <p>📍 Raya University, Maychew, Ethiopia</p>
        </div>
      </div>
    </div>
    
    <!-- General Information -->
    <h2 class="section-title">ℹ️ General Information</h2>
    <div class="info-card">
      <p>Our library is dedicated to fostering academic excellence and supporting the research activities of our university. It serves undergraduate, postgraduate, and high school students, offering a diverse range of resources to meet varying educational needs. With a considerable collection of books specifically targeted for these levels, the library ensures comprehensive coverage across all disciplines. Additionally, our library employs the Library of Congress Classification (LCC) system to efficiently organize its extensive collection, making it easy for users to locate and access materials. Above all, we continuously strive to meet the needs of all our users.</p>
      
      <p>Beyond traditional print resources, our library embraces technology to elevate the learning experience. Equipped with a digital library and internet-connected computers, it offers continuous access to digital materials and online resources. Furthermore, to address the increasing number of students at our university, we are actively working on expanding our digital library infrastructure. This initiative aims to provide a comprehensive and forward-thinking environment, ensuring students are well-equipped to thrive in today's dynamic educational landscape.</p>
      
      <div class="info-highlight">
        <h4>🌟 Our Commitment</h4>
        <p>At Raya University, our Library and Documentation Directorate remains committed to excellence, innovation, and inclusivity. We empower learners and support academic success across all disciplines.</p>
      </div>
      
      <p>Currently, our Library and Documentation Directorate comprises <strong>47 dedicated staff members</strong> working across the following teams.</p>
    </div>
    
    <!-- Teams -->
    <h2 class="section-title">👥 Our Teams</h2>
    <div class="teams-grid">
      <div class="team-card">
        <div class="team-icon">📚</div>
        <h3>Library Users Service Team</h3>
        <p>This team operates efficiently across three shifts, each coordinated by a team leader. Responsible for Circulation, Check Point, and Rounding tasks, ensuring seamless service delivery and effective management of library operations.</p>
      </div>
      
      <div class="team-card">
        <div class="team-icon">🔧</div>
        <h3>Technical Processing Team</h3>
        <p>Responsible for cataloguing, acquisition of resources, printing, and updating reading materials to ensure the library's collection remains current and accessible to users.</p>
      </div>
      
      <div class="team-card">
        <div class="team-icon">💻</div>
        <h3>Digital Library and Automation Team</h3>
        <p>Manages digital library services, ensures proper use of computers, and handles digitization and automation of library systems to enhance efficiency and provide modern, technology-driven services.</p>
      </div>
      
      <div class="team-card">
        <div class="team-icon">📄</div>
        <h3>Documentation Team</h3>
        <p>Responsible for collecting and archiving research works, projects, and providing periodical reading materials such as magazines and newspapers, enriching the library's resources for users.</p>
      </div>
    </div>
    
    <!-- Staff Directory -->
    <h2 class="section-title">📋 Library Staff Directory</h2>
    <p style="margin-bottom: 15px; color: #666;">List of Library and Documentation Staff Members with the Position of Coordinator or Above</p>
    
    <div class="staff-table-container">
      <table class="staff-table">
        <thead>
          <tr>
            <th>Full Name</th>
            <th>Position</th>
            <th>Phone Number</th>
            <th>Email</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Tekle Haylekiros Assefa</strong></td>
            <td>Director</td>
            <td><a href="tel:+251985414846">+251 985 41 48 46</a></td>
            <td><a href="mailto:teklehaylekiros@rayu.edu.et">teklehaylekiros@rayu.edu.et</a></td>
          </tr>
          <tr>
            <td>Desta Hagos Berhanu</td>
            <td>Library Users Service Team Leader</td>
            <td><a href="tel:+251920845343">+251 920 84 53 43</a></td>
            <td><a href="mailto:destahagos@rayu.edu.et">destahagos@rayu.edu.et</a></td>
          </tr>
          <tr>
            <td>Zewdu Kasahun Gebru</td>
            <td>Library Service Shift 3 Coordinator</td>
            <td><a href="tel:+251945064306">+251 945 06 43 06</a></td>
            <td><a href="mailto:zewdukasahun@rayu.edu.et">zewdukasahun@rayu.edu.et</a></td>
          </tr>
          <tr>
            <td>Tesfaye Kahsay Mesele</td>
            <td>Library Service Shift 2 Coordinator</td>
            <td><a href="tel:+251939234274">+251 939 23 42 74</a></td>
            <td><a href="mailto:tesfayekahsay@rayu.edu.et">tesfayekahsay@rayu.edu.et</a></td>
          </tr>
          <tr>
            <td>Nigus Gebresamuel Tewusak</td>
            <td>Library Service Shift 1 Coordinator</td>
            <td><a href="tel:+251914240406">+251 914 24 04 06</a></td>
            <td><a href="mailto:nigusgebresamuel@rayu.edu.et">nigusgebresamuel@rayu.edu.et</a></td>
          </tr>
          <tr>
            <td>Meresa Adhena Abrha</td>
            <td>Documentation and Ethiopian Collection Team Leader</td>
            <td><a href="tel:+251914132162">+251 914 13 21 62</a></td>
            <td><a href="mailto:meresaadhena@rayu.edu.et">meresaadhena@rayu.edu.et</a></td>
          </tr>
          <tr>
            <td>Haftamu Haileslasie Gebre</td>
            <td>Documentation, Periodical and Special Collection Service Coordinator</td>
            <td><a href="tel:+251927747886">+251 927 74 78 86</a></td>
            <td><a href="mailto:haftamuhaileslasie@rayu.edu.et">haftamuhaileslasie@rayu.edu.et</a></td>
          </tr>
          <tr>
            <td>Almaz Hagos Embaye</td>
            <td>Technical Processing Team Leader</td>
            <td><a href="tel:+251964756451">+251 964 75 64 51</a></td>
            <td><a href="mailto:almazhagos@rayu.edu.et">almazhagos@rayu.edu.et</a></td>
          </tr>
          <tr>
            <td>Kidan Atsebeha Belete</td>
            <td>Digital Library Coordinator</td>
            <td><a href="tel:+251980324748">+251 980 32 47 48</a></td>
            <td><a href="mailto:kidanatsebeha@rayu.edu.et">kidanatsebeha@rayu.edu.et</a></td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Statistics -->
    <div class="info-highlight" style="background: linear-gradient(135deg, #2c3e50, #3498db); margin-top: 20px;">
      <h4>📊 Quick Facts</h4>
      <div style="display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; margin-top: 15px;">
        <div>
          <div style="font-size: 28px; font-weight: bold;">47+</div>
          <div>Dedicated Staff</div>
        </div>
        <div>
          <div style="font-size: 28px; font-weight: bold;">24/7</div>
          <div>Digital E-Resourses</div>
        </div>
        <div>
          <div style="font-size: 28px; font-weight: bold;">4</div>
          <div>Specialized Teams</div>
        </div>
        <div>
          <div style="font-size: 28px; font-weight: bold;">LCC</div>
          <div>Classification System</div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="footer">
    <p>© <?php echo date('Y'); ?> Raya University Library and Documentation Service Directorate</p>
    <p>Main Campus, Maychew, Ethiopia | <a href="/lmsPro/includes/help.php">Help & Support</a></p>
  </div>
</div>

<script>
  // Optional: Additional fallback for director image if needed
  (function() {
    const dirImg = document.querySelector('.director-avatar');
    if (dirImg) {
      dirImg.addEventListener('error', function() {
        if (!this.getAttribute('data-fallback-set')) {
          this.setAttribute('data-fallback-set', 'true');
          this.src = 'https://ui-avatars.com/api/?background=2c3e50&color=fff&rounded=true&size=90&name=Tekle+H';
        }
      });
    }
  })();
</script>
</body>
</html>