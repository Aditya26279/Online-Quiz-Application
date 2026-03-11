# Online Quiz Application

A web-based **Online Quiz Application** built using **PHP, MySQL, HTML, CSS, and JavaScript**.
The system allows users to register, log in, attempt quizzes, and view results, while administrators can manage quizzes, questions, and users through an admin dashboard.

---

## 📌 Features

### User Features

* User registration and login
* Secure authentication system
* Attempt quizzes online
* Instant quiz results
* User dashboard

### Admin Features

* Admin dashboard
* Create and manage quizzes
* Add, edit, and delete questions
* Manage registered users
* Monitor quiz results

---

## 🛠 Technologies Used

* **Frontend:** HTML, CSS, JavaScript
* **Backend:** PHP
* **Database:** MySQL
* **Server:** Apache (XAMPP / WAMP / LAMP)

---

## 📂 Project Structure

```
Online-Quiz-Application/
│
├── admin/
│   ├── dashboard.php
│   ├── questions.php
│   ├── quizzes.php
│   └── users.php
│
├── user/
│   ├── dashboard.php
│   ├── take_quiz.php
│   └── result.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
│
├── config.php
├── database.sql
├── setup_db.php
├── login.php
├── register.php
├── logout.php
├── index.php
└── .gitignore

## ⚙️ Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/online-quiz-application.git
```

### 2. Move Project to Server Directory

Place the project folder inside:

* **XAMPP:** `htdocs`
* **WAMP:** `www`
* **LAMP:** `/var/www/html`

Example:

```
C:\xampp\htdocs\online-quiz-application
```

### 3. Create Database

1. Open **phpMyAdmin**
2. Create a new database:

```
quiz_db
```

### 4. Import Database

1. Open the created database
2. Click **Import**
3. Select `database.sql`
4. Run the import

### 5. Configure Database Connection

Edit `config.php`:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "quiz_db";
```

---

## ▶️ Running the Application

Start **Apache** and **MySQL** in XAMPP/WAMP.

Open in browser:

```
http://localhost/online-quiz-application
```

---

## 📊 System Modules

| Module              | Description                             |
| ------------------- | --------------------------------------- |
| Authentication      | Handles login, registration, and logout |
| Quiz Management     | Admin can create and manage quizzes     |
| Question Management | Admin adds quiz questions               |
| User Dashboard      | Users can view quizzes and results      |
| Result System       | Displays quiz score after completion    |

---

## 🎯 Learning Objectives

This project demonstrates:

* Web application development using PHP
* Database integration with MySQL
* User authentication systems
* Admin panel implementation
* CRUD operations
* Basic frontend-backend integration

---

## 🚀 Future Improvements

* Timer for quizzes
* Leaderboard system
* Question categories
* Email verification
* Better UI/UX design
* Quiz analytics

---
