<?php
try {

    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("PRAGMA foreign_keys = ON;");



    //  Bus_Company 
    $db->exec("
        CREATE TABLE IF NOT EXISTS Bus_Company (
            id TEXT PRIMARY KEY,
            name TEXT UNIQUE NOT NULL,
            logo_path TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    

    //  User 
    $db->exec("
        CREATE TABLE IF NOT EXISTS User (
            id TEXT PRIMARY KEY,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('user', 'company_admin', 'admin')),
            password TEXT NOT NULL,
            company_id TEXT,
            balance REAL DEFAULT 800.0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(company_id) REFERENCES Bus_Company(id) ON DELETE SET NULL
        );
    ");

    //  Coupons 
    $db->exec("
        CREATE TABLE IF NOT EXISTS Coupons (
            id TEXT PRIMARY KEY,
            code TEXT UNIQUE NOT NULL,
            discount REAL NOT NULL,
            usage_limit INTEGER NOT NULL,
            expire_date DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    //  Trips 
    $db->exec("
        CREATE TABLE IF NOT EXISTS Trips (
            id TEXT PRIMARY KEY,
            company_id TEXT NOT NULL,
            destination_city TEXT NOT NULL,   
            arrival_time DATETIME NOT NULL,
            departure_time DATETIME NOT NULL,
            departure_city TEXT NOT NULL,
            price REAL NOT NULL,
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(company_id) REFERENCES Bus_Company(id) ON DELETE CASCADE
        );
    ");
    
    //  Tickets 
    $db->exec("
        CREATE TABLE IF NOT EXISTS Tickets (
            id TEXT PRIMARY KEY,
            trip_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            status TEXT NOT NULL CHECK(status IN ('active', 'canceled', 'expired')) DEFAULT 'active',
            total_price REAL NOT NULL, 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES User(id) ON DELETE RESTRICT,
            FOREIGN KEY(trip_id) REFERENCES Trips(id) ON DELETE CASCADE
        );
    ");

    //Booked_Seats 
    $db->exec("
        CREATE TABLE IF NOT EXISTS Booked_Seats (
            id TEXT PRIMARY KEY,
            ticket_id TEXT NOT NULL,
            seat_number INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(ticket_id, seat_number), 
            FOREIGN KEY(ticket_id) REFERENCES Tickets(id) ON DELETE CASCADE
        );
    ");

    // User_Coupons 
    $db->exec("
        CREATE TABLE IF NOT EXISTS User_Coupons (
            id TEXT PRIMARY KEY,
            coupon_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(coupon_id, user_id),
            FOREIGN KEY(coupon_id) REFERENCES Coupons(id) ON DELETE CASCADE,
            FOREIGN KEY(user_id) REFERENCES User(id) ON DELETE CASCADE
        );
    ");

    echo "Tüm tablolar başarıyla oluşturuldu.\n";

} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . PHP_EOL;
}
?>
