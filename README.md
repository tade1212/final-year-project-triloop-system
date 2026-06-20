# triloop-system
Triloop is a professional-grade school management system designed to automate the complete academic lifecycle. Developed specifically for Tsinseta Lemariam Comprehensive Secondary School, the system replaces manual, error-prone paperwork with three interlocking digital loops: Scheduling, Grading, and Evaluation.
🚀 Key Features
The Grading Loop: An 8-component continuous assessment engine with real-time JavaScript validation and automated class ranking.
The Scheduling Loop: A grid-based matrix with a built-in conflict-detection algorithm to prevent double-booking of rooms and faculty.
The Evaluation Loop (360°): A weighted performance aggregator that collects anonymous feedback from students, peers, and administrators to generate a scientific "Teacher Performance Index."
Zero-Trust Security: Features an Asymmetric Handshake Reset Protocol, metadata fingerprinting for forensic auditing, and Bcrypt password hashing.
Eternal Data Archive: Uses composite database keys to maintain decades of academic history without data clashing.
Dynamic Document Generation: On-demand generation of official landscape certificates and multi-semester transcripts.
🛠 Technologies Used
Backend: PHP 8.2 (Object-Oriented Logic)
Database: MySQL / MariaDB (Relational Architecture)
Frontend: HTML5, CSS3, Bootstrap 5 (Mobile-Responsive Design)
Scripting: JavaScript (Client-side validation & DOM manipulation)
Security: BCRYPT encryption & SQL Transactional Atomicity
⚙️ Setup & Installation
Follow these steps to run the Triloop System on your local environment:
Prerequisites:
Install XAMPP (Ensure it supports PHP 8.2+).
Database Configuration:
Open phpMyAdmin and create a new database named triloop_db.
Import the triloop_db.sql file located in the root folder.
Note: The default configuration uses Port 3307. Adjust in includes/db_connect.php if necessary.
File Placement:
Clone this repository or download the ZIP.
Move the triloop folder to your XAMPP directory: C:\xampp\htdocs\triloop.
Run the System:
Start Apache and MySQL from the XAMPP Control Panel.
Open your browser and navigate to: http://localhost/triloop/index.php.
Default Admin Access:
Run create_admin.php via browser to initialize the primary administrator account, then delete the file for security.
🛡 Security Logic
The system is built on a Zero-Trust Administrative Model. Administrators cannot reset passwords unilaterally; a user-generated "Handshake Token" is required to authorize any credential changes, ensuring total account accountability and preventing unauthorized data manipulation.
Developed by: Tadele Gebregziabher - Adigrat University
Case Study: Tsinseta Lemariam Secondary School


Developed by: [Your Group Name/Adigrat University]
Case Study: Tsinseta Lemariam Secondary School
License: MIT
