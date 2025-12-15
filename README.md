# Fuel Station Management System

A comprehensive web-based fuel station management system built with PHP and MySQL.

## Features

### Core Modules

- **Dashboard** - Real-time overview with station stats, fuel stocks, sales, and alerts
- **Station Management** - Manage multiple fuel stations with details, locations, and staff
- **Fuel Stock Management** - Track petrol, diesel, and kerosene levels across all stations
- **Sales Tracking** - Record and analyze sales transactions by station and fuel type
- **Inventory Management** - Track equipment, supplies, and maintenance items
- **Staff Management** - Employee directory with positions and station assignments
- **Vehicle Fleet** - Manage delivery vehicles and tanker trucks
- **Deliveries** - Schedule and track fuel deliveries
- **Expenses** - Track operational expenses by category and station
- **Clients & Partners** - Manage corporate, individual, government, and transport clients
- **Suppliers** - Maintain supplier directory with ratings and fuel types
- **Reports** - Generate comprehensive reports and analytics

### Additional Features

- **Alerts System** - Low fuel stock notifications
- **Fuel Price Management** - Track current and historical fuel prices
- **Multi-region Support** - Organize stations by regions (Dar es Salaam, Mwanza, Arusha, Dodoma)
- **User Profiles** - Staff profile management with photos
- **Audit Logs** - Track system activities and changes
- **Backup System** - Automated database backups
- **Security** - Admin authentication and access control

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: Bootstrap 5, HTML5, CSS3
- **Icons**: Bootstrap Icons
- **Server**: Apache (XAMPP)

## Installation

### Prerequisites

- XAMPP (or similar LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL/MariaDB 10.4 or higher

### Setup Instructions

1. **Clone the repository**

   ```bash
   git clone https://github.com/yourusername/fuel-station-management.git
   cd fuel-station-management
   ```

2. **Copy to XAMPP directory**

   ```bash
   cp -r * /path/to/xampp/htdocs/sameer/
   ```

3. **Database Setup**

   - Import the SQL dump file (available separately)
   - Or use phpMyAdmin to create the database

4. **Configure Database Connection**

   - Copy `config.example.php` to `config.php`
   - Edit `config.php` with your database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'myapp');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

5. **Set Permissions**

   ```bash
   chmod 755 backups/
   chmod 755 uploads/profiles/
   ```

6. **Access the Application**
   - Open browser: `http://localhost/sameer/`
   - Default admin login:
     - Username: `admin`
     - Password: `admin123`

## Database Schema

### Main Tables

- `stations` - Fuel station information with region assignment
- `regions` - Geographic regions (Dar es Salaam, Mwanza, Arusha, Dodoma)
- `fuel_stocks` - Current fuel inventory levels (petrol, diesel, kerosene)
- `sales` - Sales transactions with fuel type and amounts
- `deliveries` - Fuel delivery records with suppliers
- `expenses` - Operational expenses by station and category
- `inventory` - Equipment and supplies tracking
- `staff` - Employee records with station assignments
- `vehicles` - Fleet management (tankers, trucks, vans)
- `clients` - Customer directory (Corporate, Individual, Government, Transport)
- `suppliers` - Supplier information with ratings
- `fuel_prices` - Current and historical pricing
- `admin_users` - System administrators
- `audit_logs` - Activity tracking
- `backups` - Backup records

### Auto-Generated Codes

- Clients: `CLT-0001`, `CLT-0002`...
- Suppliers: `SUP-0001`, `SUP-0002`...
- Inventory: `INV-0001`, `INV-0002`...
- Staff: `EMP001`, `EMP002`...

## Key Features

### Real-time Dashboard

- Total stations count from database
- Live fuel stock levels
- Today's sales tracking
- Low fuel alerts (threshold-based)
- Pending deliveries count
- Top performing station

### Color-coded Alerts

- **Red Badge** - Low stock (critical)
- **Yellow Badge** - Medium stock (warning)
- **Green Badge** - Good stock (healthy)

### Data Integrity

- Foreign key relationships with CASCADE rules
- ENUM fields for standardized values
- AUTO_INCREMENT primary keys
- TIMESTAMP tracking for all records

## Security Notes

⚠️ **Important**: Before deploying to production:

1. Change the default admin password
2. Update `config.php` with secure credentials
3. Enable HTTPS/SSL
4. Set proper file permissions
5. Configure regular backups
6. Review `.gitignore` to exclude sensitive files

## Project Structure

```
sameer/
├── assets/          # CSS and static files
├── backups/         # Database backups (git ignored)
├── inc/             # Include files (header, footer, helpers)
├── uploads/         # User uploads (git ignored)
├── *.php            # Main application files
├── config.php       # Database config (git ignored)
├── config.example.php  # Config template
├── README.md        # Documentation
└── .gitignore       # Git ignore rules
```

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For issues or questions:

- Create an issue on GitHub
- Contact: <evaristkilasila@gmail.com>

## Changelog

### Version 1.0.0 (2025-12-15)

- Initial release
- Complete fuel station management system
- Multi-station and multi-region support
- Comprehensive inventory and sales tracking
- Staff, vehicle, and delivery management
- Client and supplier directories
- Backup and audit logging

---

**Built for efficient fuel station management**
