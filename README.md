# 📚 Library Management System

A web-based **Library Management System (LMS)** developed to simplify and automate library operations. The system provides separate functionality for **Students, Librarians, and Administrators**, including book management, borrowing and returning, reservations, fines, course materials, reports, and notifications.

---

## 👨‍💻 Developer

### Hagos Goytom Hadis

**Information Technology Graduate**

📍 Addis Ababa, Ethiopia

📧 **Email:** [hagosgoytom670@gmail.com](mailto:hagosgoytom670@gmail.com)

💻 **GitHub:**  
https://github.com/hagosgoytom670-art

📂 **Project Repository:**  
https://github.com/hagosgoytom670-art/library-management-system

---

## 📌 Project Overview

The **Library Management System** is designed to provide a centralized web-based platform for managing library resources and services.

The system helps reduce manual library activities by providing role-based access to students, librarians, and administrators.

### 👥 System Users

- 👨‍🎓 **Students**
- 📚 **Librarians**
- 👨‍💼 **Administrators**

Each user role has different permissions and responsibilities within the system.

---

# 🚀 Main Features

## 👨‍🎓 Student Features

Students can:

- Create an account
- Log in securely
- Manage their profile
- Browse available books
- Search for books
- View book information
- Borrow books
- View currently borrowed books
- View borrowing history
- Return books
- Request book reservations
- View reservation information
- View fines
- View fine history
- View payment history
- Make fine payments
- Download course materials
- Receive notifications

---

## 📚 Librarian Features

Librarians can:

- Access the librarian dashboard
- Add new books
- Upload book images
- Edit book information
- Delete books
- Search and check books
- Issue books to students
- Process returned books
- Check student borrowing records
- Manage book reservations
- Manage librarian schedules
- View borrowing information
- Process fine payments
- Generate library reports
- Send email notifications
- Monitor library activities

---

## 👨‍💼 Administrator Features

Administrators can:

- Access the administration dashboard
- Manage students
- Create librarian accounts
- Manage librarians
- Manage system users
- Upload course materials
- Organize course materials
- Manage library information
- View system activities
- View messages and replies
- Generate reports
- Export reports
- Manage system configuration

---

# 📖 Book Management

The system provides tools for managing the library's book collection.

Book information includes:

- Book title
- Author
- Category
- ISBN
- Publisher
- Publication year
- Available copies
- Shelf location
- Book image
- Added-by information
- Creation date

Librarians can add, edit, search, and delete books.

---

# 📚 Borrowing System

The borrowing module allows librarians to issue books to students and process returned books.

The system maintains information such as:

- Student
- Book
- Borrow date
- Due date
- Return date
- Borrowing status
- Fine amount

Students can also view their borrowing history and current borrowed books.

---

# 📌 Book Reservation

Students can request reservations for books through the reservation module.

Librarians can:

- View reservation requests
- Check reservation information
- Process reservations
- Monitor reservation activities

This helps organize book requests and improve library service management.

---

# 💰 Fine Management

The system supports library fine management.

Students can:

- View current fines
- View fine history
- View payment history
- Make fine payments

Librarians can process and manage fine payments.

---

# 📧 Email Notifications

The system integrates **PHPMailer** for email communication.

Email functionality can be used for library-related notifications such as:

- Book issue notifications
- Return notifications
- Overdue notifications
- Other library communication

---

# 📚 Course Materials

The system provides a course-material management module.

Administrators can upload educational files, while students can access and download available course materials.

Materials can be organized by:

- Course
- Chapter
- File

The database structure for the project is available in:

```text
sql/schema.sql
```

---

# 📊 Reports

The system provides reporting functionality for library management.

Reports can contain information related to:

- Books
- Borrowing
- Students
- Librarians
- Library activities
- Fines
- Other system information

Reports can also be exported where supported by the system.

---

# 🔐 Role-Based Access Control

The system uses role-based access control to restrict functionality according to the user's role.

### 👨‍🎓 Student

Students have access to:

- Book browsing
- Book search
- Borrowing information
- Reservations
- Fines
- Course materials
- Profile management
- Notifications

### 📚 Librarian

Librarians have access to:

- Book management
- Borrowing
- Returns
- Reservations
- Student borrowing records
- Fine processing
- Reports
- Schedules
- Library activities

### 👨‍💼 Administrator

Administrators have broader system-management privileges, including:

- User management
- Student management
- Librarian management
- Course materials
- Reports
- System administration
- System activities

---

# 🛠️ Technologies Used

| Technology | Purpose |
|---|---|
| **PHP** | Backend development |
| **MySQL** | Database management |
| **HTML5** | Web page structure |
| **CSS3** | User interface styling |
| **JavaScript** | Client-side functionality and validation |
| **Bootstrap** | Responsive interface components |
| **PHPMailer** | Email communication |
| **XAMPP** | Local development environment |
| **Composer** | PHP dependency management |
| **Git** | Version control |
| **GitHub** | Source code hosting |

---

# 🗄️ Database

The system uses **MySQL** as its database management system.

The database stores information related to:

- User accounts
- Students
- Librarians
- Administrators
- Books
- Borrowing records
- Reservations
- Fines
- Payments
- Course materials
- Library activities

### Database Schema

The database schema is available at:

```text
sql/schema.sql
```

---

# 📸 System Screenshots

The following screenshots demonstrate some of the main interfaces of the Library Management System.

## 🔐 Login Page

The login page provides access to the system for authorized users.

![Library Management System Login](./screenshots/Loginpng.png)

---

## 👨‍💼 Administrator Dashboard

The administrator dashboard provides access to user management, course materials, reports, system activities, and other administrative functions.

![Administrator Dashboard](./screenshots/Admin_DashBoard.png)

---

## 📚 Librarian Dashboard

The librarian dashboard provides access to book management, borrowing, returns, reservations, schedules, reports, and other library operations.

![Librarian Dashboard](./screenshots/Librarian_DashBoard.png)

---

## 👨‍🎓 Student Dashboard

The student dashboard provides access to books, borrowing history, reservations, fines, course materials, and profile-related services.

![Student Dashboard](./screenshots/Student_Dashboard.png)

---

## 📖 Book Reservation

Students can use the reservation interface to request books from the library.

![Book Reservation](./screenshots/Request_Reaervation.png)

---

# 📂 Project Structure

```text
library-management-system/
│
├── api/
│   ├── dashboard_stats.php
│   └── recent_activity.php
│
├── dashboards/
│   ├── admin.php
│   ├── librarian.php
│   └── student.php
│
├── includes/
│   ├── auth.php
│   ├── header.php
│   └── footer.php
│
├── modules/
│   ├── admins/
│   ├── librarians/
│   └── students/
│
├── screenshots/
│   ├── Admin_DashBoard.png
│   ├── Librarian_DashBoard.png
│   ├── Loginpng.png
│   ├── Request_Reaervation.png
│   └── Student_Dashboard.png
│
├── sql/
│   └── schema.sql
│
├── uploads/
│
├── composer.json
├── composer.lock
├── db.php
├── README.md
└── LICENSE
```

---

# ⚙️ Installation

## 1. Requirements

Before running the project, install:

- **XAMPP**
- **PHP**
- **MySQL**
- **Composer**
- **Git**
- A modern web browser

---

## 2. Clone the Repository

Open PowerShell or Git Bash:

```bash
git clone https://github.com/hagosgoytom670-art/library-management-system.git
```

Move into the project directory:

```bash
cd library-management-system
```

---

## 3. Place the Project in XAMPP

Copy the project into:

```text
C:\xampp\htdocs\
```

For example:

```text
C:\xampp\htdocs\library-management-system
```

---

## 4. Start XAMPP

Open XAMPP Control Panel and start:

- Apache
- MySQL

---

## 5. Create the Database

Open:

```text
http://localhost/phpmyadmin
```

Create a database named:

```text
library_system
```

Then import:

```text
sql/schema.sql
```

into the `library_system` database.

---

# 🔧 Database Configuration

The database connection is configured in:

```text
db.php
```

Example configuration:

```php
<?php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "library_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
```

Update the database configuration if your local MySQL settings are different.

---

# 📦 Install PHP Dependencies

If the Composer dependencies are not already installed, run:

```bash
composer install
```

This installs the dependencies defined in:

```text
composer.json
```

---

# ▶️ Run the Application

After starting Apache and MySQL, open:

```text
http://localhost/library-management-system/
```

If your project folder is named `lmsPro`, use:

```text
http://localhost/lmsPro/
```

---

# 🔒 Security

The project includes security-related practices such as:

- Role-based access control
- Session-based authentication
- Server-side validation
- Client-side form validation
- Password management
- Access restrictions for different user roles
- Database access controls

For production deployment, additional server security and configuration should be implemented.

---

# 📁 Uploaded Files

The system handles uploaded files such as:

- Book images
- Student profile images
- Course materials

Uploaded files are excluded from Git tracking where appropriate using `.gitignore`.

This prevents large or frequently changing uploaded files from unnecessarily being stored in the source-code repository.

---

# 🔄 Version Control

The project uses **Git** for version control and **GitHub** for source-code hosting.

### Repository

🔗 https://github.com/hagosgoytom670-art/library-management-system

---

# 🎯 Project Objectives

The main objectives of the system are to:

- Automate library operations
- Reduce manual record keeping
- Improve book management
- Simplify borrowing and returning
- Support book reservations
- Manage student and librarian information
- Track fines and payments
- Provide access to course materials
- Generate useful library reports
- Improve communication through notifications
- Provide role-based access to system functionality

---

# 💡 Learning Outcomes

This project provided practical experience in:

- PHP web application development
- MySQL database design
- CRUD operations
- Authentication and authorization
- Role-based access control
- Form validation
- File upload management
- Email integration
- Database relationships
- Git and GitHub
- Responsive web development
- Software project organization

---

# 🚧 Future Improvements

Possible future improvements include:

- 📱 Improved mobile responsiveness
- 🔔 Real-time notifications
- 📊 Advanced analytics dashboards
- 🔎 Advanced book search and filtering
- 📧 Automated overdue reminders
- ☁️ Cloud deployment
- 🔐 Two-factor authentication
- 📚 Barcode and QR code integration
- 💳 Online payment gateway integration
- 🌐 Multi-language support

---

# 🤝 Contributing

Contributions, suggestions, and improvements are welcome.

To contribute:

### 1. Fork the repository

### 2. Create a new branch

```bash
git checkout -b feature/new-feature
```

### 3. Make your changes

### 4. Commit your changes

```bash
git add .
git commit -m "Add new feature"
```

### 5. Push your branch

```bash
git push origin feature/new-feature
```

### 6. Open a Pull Request

---

# 📄 License

**MIT License © 2026 Hagos Goytom Hadis**

This project is licensed under the MIT License.

The MIT License permits use, modification, distribution, and private or commercial use of the software, subject to the conditions stated in the license.

📜 **[View the full MIT License](./LICENSE)**

---

# 👨‍💻 Author

### Hagos Goytom Hadis

**Information Technology Graduate**

📧 [hagosgoytom670@gmail.com](mailto:hagosgoytom670@gmail.com)

💻 GitHub:  
https://github.com/hagosgoytom670-art

📂 Library Management System:  
https://github.com/hagosgoytom670-art/library-management-system

---

# ⭐ Project

If you find this project useful or interesting, consider giving the repository a ⭐ on GitHub.

Thank you for visiting the **Library Management System** project! 📚
