Lost & Found Portal

A web-based portal for reporting and finding lost items, built with PHP and MySQL. This application allows users to register, log in, report items they have lost or found, and receive alerts when a found item matches a lost item report. The system also includes a gamification aspect, where users earn points for successful recoveries.

🌟 Features

User Authentication: Secure user registration and login system.

Report Lost Items: Users can post details about items they have lost.

Report Found Items: Users can post details about items they have found.

Item Matching & Alerts: The system automatically checks for matches between lost and found items based on location and notifies the owner.

Gamification: Users earn points and level up for successfully returning items, encouraging participation.

Search & Filter: Easily search for lost items by name, location, or category.

Image Uploads: Users can upload images of items to provide more detail.

🛠️ Technology Stack

Backend: PHP

Database: MySQL

Frontend: HTML, CSS, Bootstrap, JavaScript, jQuery

🚀 Getting Started

Follow these instructions to set up and run the project on your local machine.

Prerequisites

XAMPP: You'll need a local server environment. Download and install XAMPP.

Installation and Setup

Clone the Repository:

Open your terminal or command prompt.

Navigate to the htdocs directory inside your XAMPP installation folder (e.g., C:/xampp/htdocs/).

Clone this repository:

git clone [https://github.com/FasihKhan224/lost-and-found-portal.git](https://github.com/FasihKhan224/lost-and-found-portal.git)


This will create a lost-and-found-portal folder inside htdocs.

Start XAMPP:

Open the XAMPP Control Panel.

Start the Apache and MySQL services.

Create the Database:

Open your web browser and go to http://localhost/phpmyadmin/.

Click on the "Databases" tab.

In the "Create database" field, enter lost_and_founds and click "Create".

Alternatively, you can run the create.php script from one of the version folders (e.g., http://localhost/lost-and-found-portal/v1/create.php) to create the database.

Import the Database Schema:

After creating the database, click on it from the left-hand sidebar in phpMyAdmin.

Click on the "Import" tab.

Click "Choose File" and select the database.sql file from one of the version folders (e.g., v1/database.sql).

Scroll down and click "Go" to import the tables and schema.

Run the Application:

You're all set! The main version of the app is in the v1 folder. Open your browser and navigate to:

http://localhost/lost-and-found-portal/v1/


You should see the login page. You can register a new user to get started.

🧪 Alternative Version (Minor Tweaks)

This repository contains two versions of the application. The primary one is in the v1 folder, it had somelogical issues when logging in so an alternative is in the minor_tweaks folder. which addresses all the issues.

If you want to try the version with minor tweaks, simply replace the PHP files inside the v1 folder with the corresponding files from the minor_tweaks folder.

⚙️ How It Works

The application flow is simple:

A user registers and logs in.

If a user loses an item, they can report it by providing details like the item name, description, and location.

If a user finds an item, they report it. The system checks if the location of the found item matches any open lost item reports.

If a match is found, an alert is sent to the user who lost the item, along with the contact information provided by the finder.

Once the item is returned, the owner can confirm it, and the finder is awarded points.

Enjoy using the Lost & Found Portal! ✨
