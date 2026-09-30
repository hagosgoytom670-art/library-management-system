# 📚 Library Management System

A web-based **Library Management System (LMS)** developed to simplify and automate library operations. The system provides separate functionality for **Students, Librarians, and Administrators**, including book management, borrowing and returning, reservations, fines, course materials, reports, and notifications.

---

## 👨‍💻 Developer

### Hagos Goytom Hadis

**Information Technology Graduate**

📍 Addis Ababa, Ethiopia

📧 **Email:** hagosgoytom670@gmail.com

💻 **GitHub:**  
https://github.com/hagosgoytom670-art

📂 **Project Repository:**  
https://github.com/hagosgoytom670-art/library-management-system

---

## 📌 Project Overview

The Library Management System is designed to provide a centralized platform for managing library resources and services.

It replaces many manual library activities with a web-based system where users can access services according to their roles.

The system supports:

- 👨‍🎓 Students
- 📚 Librarians
- 👨‍💼 Administrators

Each role has different permissions and responsibilities within the system.

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
- View borrowed books
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

- Log in to the librarian dashboard
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

The system provides tools for managing the library book collection.

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

The system also provides borrowing history for students and library staff.

---

# 📌 Book Reservation

Students can request reservations for books through the reservation module.

The librarian can then:

- View reservation requests
- Check reservation information
- Process reservations

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

The system supports organization of materials by:

- Course
- Chapter
- File

The project contains the database structure required for course-material management.

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

# 🔐 Role-Based Access

The system uses role-based access control.

### Student

Students have access to student-related services such as:

- Browsing books
- Borrowing information
- Reservations
- Fines
- Course materials
- Profile management

### Librarian

Librarians have access to library-management operations such as:

- Book management
- Borrowing
- Returns
- Reservations
- Student borrowing records
- Reports
- Schedules

### Administrator

Administrators have broader system-management privileges such as:

- User management
- Librarian management
- Student management
- Course materials
- Reports
- System administration

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

The database schema is available in:

```text
sql/schema.sql

