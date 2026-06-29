# Database Management & Connection Guide

This guide describes how to manage the databases for the Secure Auth & Profile Management System, resolve offline database errors, and maintain a full-time online database configuration.

---

## 1. Local SQLite Fallback (Auto-Enabled)
To ensure the application remains **100% online and functional** without requiring manual configuration, a local SQLite database fallback has been implemented in `php/config.php`.

* **How it works**: If the remote MySQL cloud database fails to connect (due to DNS errors, credentials, or host suspension), the PHP backend automatically initializes and connects to a local SQLite database file in the system's temporary directory (`sys_get_temp_dir()`), named `guvi_auth_system.sqlite`.
* **Behavior**: Users can register, log in, and perform all authentication operations normally. No external internet or running MySQL service is required on your computer. This bypasses permission/file-locking errors that occur when databases are stored inside synced directories like OneDrive.

---

## 2. Restoring/Resuming your Cloud MySQL (Aiven)
Your database host was set to Aiven: `mysql-12aaf2a7-pkvetrivelvvm-6fa8.h.aivencloud.com`.
Aiven automatically turns off or pauses free-tier database instances after a period of inactivity (typically 1-2 days of no connections). When it is powered off, the DNS resolution is deleted, resulting in connection errors.

### To bring it back online:
1. Log in to your [Aiven Console](https://console.aiven.io/).
2. Locate the database service named `mysql-12aaf2a7-pkvetrivelvvm-6fa8`.
3. Check the status. If it says **Powered off** or **Suspended**, click the **Power On** or **Resume** button.
4. Wait 1–3 minutes until the service status turns green and says **Running**.
5. The application will automatically resume connecting to the MySQL cloud server.

### To prevent Aiven from powering off (Keeping it Online 24/7):
Aiven powers down free instances only if there are no connections. You can keep it alive by setting up a simple cron job, scheduled task, or ping script that connects to the database at least once every 24 hours. For example, a scheduled task on your server or an uptime monitor (like UptimeRobot) hitting your web app will keep the database awake.

---

## 3. Migrating to a 24/7 Free MySQL Host (Clever Cloud)
If you want a free cloud MySQL database that **never sleeps** and remains full-time online without requiring keep-alive pings:

### Step 1: Create a Clever Cloud Account
1. Sign up on [Clever Cloud](https://www.clever-cloud.com/).
2. In the dashboard, click **Create** > **an add-on**.
3. Choose **MySQL** and select the **Shared (Free/Dev)** tier (provides 10MB storage, which is plenty for authentication records).
4. Select your preferred region and click create.

### Step 2: Retrieve Credentials
Once created, copy the connection details from the Clever Cloud console:
* **Host** (e.g., `bxxxxxxxxx-mysql.services.clever-cloud.com`)
* **Port** (usually `3306`)
* **Database Name** (starts with `b...`)
* **Username** (starts with `u...`)
* **Password**

### Step 3: Update your `.env` file
Modify your local [.env](file:///.env) file with the new details:
```ini
DB_HOST=your-clever-cloud-host.com
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## 4. Diagnostics & Testing
You can check the current status of all your database connections (MySQL/SQLite, MongoDB, and Redis) by visiting the diagnostics endpoint:
* Local Web Address: `http://localhost/php/db_status.php` (or your local web server's address)

It will return a JSON response showing the status of each database connection and whether the application is running in SQLite fallback mode or connecting to the cloud MySQL instance.
