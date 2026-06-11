# Hospital Management System (HMS) 🏥

A comprehensive, multi-role Hospital Management System built with PHP and MySQL. This system streamlines healthcare administration by providing dedicated dashboards and workflows for Administrators, Doctors, Patients, Associates, and Blood Donors.

## 🌟 Key Features

### 👨‍💼 Administration & Management
* **Centralized Dashboard**: Real-time overview of hospital operations, appointments, and active associates.
* **Employee Management**: Manage doctors, nurses, and general staff profiles and credentials.
* **Inventory Control**: Track hospital inventory and medical supplies.
* **Blood Bank Management**: Monitor blood stock levels, handle patient blood requests, and manage donor registries.

### 🩺 Doctor Workflow
* **Schedules & Appointments**: Manage daily schedules and view booked appointments.
* **Patient Care**: Issue digital prescriptions and review patient medical records and attachments.
* **Medical Chat**: Secure internal communication with peers and administration.

### 🤝 Associate & Assistant System
* **Associate Dashboard**: Dedicated portal for medical assistants and patient coordinators.
* **Doctor Assignments**: Associates can be officially assigned to specific doctors, automatically syncing their patient lists.
* **Task Management**: Admins can assign trackable tasks to associates with due dates and progress bars.
* **Payroll Tracking**: Associates can view their expected monthly net paycheck and upcoming payday directly on their dashboard.

### 🛌 Patient Experience
* **Patient Portal**: Secure login for patients to view their medical history.
* **Online Booking**: Patients can schedule appointments with active doctors.
* **Document Management**: Upload and view medical attachments (X-rays, lab results, etc.).
* **Blood Requests**: Direct interface to request blood from the hospital's Blood Bank.

### 🩸 Donor Portal
* **Donor Registration**: Individuals can register to donate blood.
* **Donor Dashboard**: Manage donation history and availability status.

## 🛠️ Technology Stack
* **Backend**: PHP (Core)
* **Database**: MySQL (`hms_db`)
* **Frontend**: HTML5, CSS3, JavaScript, jQuery
* **Styling**: Bootstrap 4 Framework, FontAwesome Icons

## 🚀 Installation & Setup

1. **Prerequisites**: Ensure you have XAMPP, WAMP, or an equivalent local server environment installed.
2. **Clone the Repository**:
   ```bash
   git clone https://github.com/yourusername/hms.git
   ```
3. **Database Setup**:
   * Open phpMyAdmin (`http://localhost/phpmyadmin`).
   * Create a new database named `hms_db`.
   * Import the SQL schema (located in the database folder or root).
4. **Configuration**:
   * Ensure your local server is running.
   * Place the project folder in your `htdocs` (XAMPP) or `www` (WAMP) directory.
   * Update the database connection variables in `includes/connection.php` if your local MySQL uses a password.
5. **Run the Application**:
   * Navigate to `http://localhost/hms` in your web browser.

## 🔐 Default Roles & Architecture

The system uses a unified authentication gateway with role-based routing (`$_SESSION['role']`):
* **Admin (Role 1)**: Full system access.
* **Doctor (Role 2)**: Patient care and scheduling.
* **Patient (Role 3)**: Personal health and bookings.
* **Donor (Role 4)**: Blood donation management.
* **Associate (Role 5)**: Task execution and doctor assistance.

## 🤝 Contributing
Contributions, issues, and feature requests are welcome! Feel free to check the issues page.

## 📝 License
This project is for educational and portfolio purposes. Feel free to use and modify it for your own learning!
