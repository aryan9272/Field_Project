<h1 align="center">🌐 Campus Lost &amp; Found System (L&amp;F)</h1> <p align="center"> A robust and secure web application to streamline the process of reporting, tracking, and reuniting users with their lost and found items. </p> <hr> <h2>📄 Project Report</h2> <p> The complete project report is included in this repository and also contains screenshots for reference. </p> <hr> <h2>📖 Overview</h2> <p> This platform is designed to centralize the entire Lost &amp; Found process, making it easier for users to report items and track their status. It enhances community welfare by improving efficiency and providing a seamless experience for both casual users and system administrators. </p> <hr> <h2>✨ Key Features</h2> <ol> <li> <b>Secure User Authentication</b><br> Complete system for user Sign-up, Login, and Password Reset using secure tokens. </li> <br> <li> <b>Item Reporting</b><br> Users can report lost or found items with details such as type, category, and location through the central API. </li> <br> <li> <b>Interactive Dashboard</b><br> A personalized dashboard (<code>index.php</code>) displays active item reports and allows users to track their submissions. </li> <br> <li> <b>Notification System</b><br> Provides alerts for potential item matches and status updates on user reports. </li> <br> <li> <b>Full Item Lifecycle Management</b><br> Supports claim requests and allows items to be marked as <b>Resolved</b> after successful return. </li> <br> <li> <b>Dedicated Admin Panel</b><br> Administrators (<code>admin.php</code>) can manage all reports, update statuses, modify user roles, and delete accounts. </li> <br> <li> <b>Public Statistics</b><br> The landing page (<code>home.php</code>) displays key metrics such as total users and resolved cases. </li> </ol> <hr> <h2>🗂️ Important Pages</h2> <ul> <li><b>home.php</b> — Landing page with statistics</li> <li><b>index.php</b> — User dashboard</li> <li><b>admin.php</b> — Admin panel</li> </ul> <hr> <h2>🎯 Purpose</h2> <p> The goal of this project is to provide a centralized digital solution for managing lost and found items efficiently, reducing manual effort and increasing the chances of successful item recovery. </p> <hr>




## 🚀 Features

### 🔐 User Features

-   User signup & login system
-   Upload images of lost/found items
-   Search for items by keywords
-   View item details
-   Reset forgotten passwords
-   Responsive UI

### 🛠 Admin Features

-   Manage all item reports
-   View system statistics
-   Moderate uploaded content
-   Update item status

### 📡 Technical Features

-   PHP backend
-   MySQL database
-   Secure password hashing
-   API endpoints for async operations
-   Image uploads stored server-side

## 🧰 Tech Stack

-   Frontend: HTML, CSS, JavaScript
-   Backend: PHP
-   Database: MySQL
-   Server: XAMPP / Apache
-   Version Control: Git + GitHub

## 📁 Project Structure

    📁 Campus-Lost-and-Found-System
    │
    ├── 📁 sql
    │   └── lost_and_found_db.sql
    │
    ├── 📁 static
    │   ├── 📁 css
    │   └── 📁 images
    │
    ├── 📁 uploads
    │
    ├── admin.php
    ├── api.php
    ├── CNAME
    ├── db_connect.php
    ├── forgot_password.php
    ├── get_stats.php
    ├── home.php
    ├── index.php
    ├── login.php
    ├── README.md
    ├── reset.php
    ├── signup.php
    └── test.php

## 🗄️ Database Setup

1.  Open phpMyAdmin\

2.  Create a database (e.g., `lost_and_found`)

3.  Import SQL file:

        sql/lost_and_found_db.sql

4.  Update database config in `db_connect.php`.

## ⚙️ How to Run the Project Locally

``` sh
git clone https://github.com/your-username/Campus-Lost-and-Found-System.git
```

Move the project into XAMPP `htdocs/`, start Apache & MySQL, then open:

    http://localhost/Campus-Lost-and-Found-System/

## 🔌 API Endpoints

  Endpoint                     Method   Description
  ---------------------------- -------- -------------
  api.php?action=add_item      POST     Add item
  api.php?action=get_items     GET      Fetch items
  api.php?action=delete_item   POST     Delete item
  get_stats.php                GET      Stats

## 🤝 Contributing

1.  Fork\
2.  Create branch\
3.  Commit\
4.  PR

## 📝 License

MIT License.


<p align="center"> ⭐ If you find this project useful, consider giving it a star! </p>
