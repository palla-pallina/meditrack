#### Hardware (Arduino)
- Medicine dispenser with **5 carousel compartments** capable of storing *19 or 49 oral medications each*
- ESP32 controls the rotation of the carousel, aligns the correct compartment for dispensing, and manages the release of medications one drug at a time

#### Mobile App (Kotlin)
- **Connect to Firebase Database**
	- Log in for Nurse
	- View patient lists and track drug administration status.
	- View a summary of daily medication schedules.
	- Receive alerts, notifications and reminders for scheduled medication intake.
	- Confirm medication administration.

#### Website Dashboard (PHP + Javascript)
- **XAMPP MySQL**
- **MySQL to Firebase via REST API**
	- Log In Page (All Users)
	- Reset/Create Password Page (All Users)
	- User Staff Management (Admin)
	- Patient Profile Management (Clerk)
	- Medication and Inventory Management (Pharmacist)
	- Digital Prescription Management (Head Nurse)
	- Assign Medicine Administration (Head Nurse)

---

# How to set up the source code

**NOTE**
- For Windows users, need mag install ng apache server. See tutorial: (How to install apache)[https://clevertechie.com/guides/install-apache-windows-11]
- If hindi makapag install ng apache, deploying will still work (pages will open) pero hindi gagana CRUD and logout functions.

**Setting up (without apache)
1. Download the source code by clicking "Code" and selecting "Download ZIP"
2. Extract zip file sa gusto mong directory (pwede sa htdocs)
3. Access by typing address (ex. `file:///home/pau/Documents/meditrack_web/public/login.html`) or double clicking the file on your file manager