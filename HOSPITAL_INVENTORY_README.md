# Hospital Inventory Management System

## Overview
A comprehensive hospital inventory management system with automated calculations, notifications, and tracking features. This system is designed specifically for hospital environments and includes features for managing medical supplies, equipment, and amenities.

## Features

### 🔐 Access Control
- **Admin Access (Role 1)**: Full access to all inventory features
- **Employee Access (Role 2)**: Full access to inventory management
- **Patient/Donor Access**: No access to inventory system
- Secure authentication and authorization

### 📦 Inventory Management
- **Item Management**: Add, edit, and manage inventory items
- **Category Organization**: Organize items by categories (Medications, Medical Supplies, etc.)
- **Stock Tracking**: Real-time stock level monitoring
- **Batch Management**: Track items by batch numbers and expiry dates
- **Supplier Management**: Manage supplier information and purchase orders

### 🧮 Automated Calculations
- **Hospital Amenities**: Automated calculation of hospital amenities status
- **Inventory Value**: Real-time calculation of inventory value by category
- **Stock Levels**: Automatic monitoring of stock levels
- **Cost Analysis**: Track unit costs and total inventory value

### 🔔 Notification System
- **Low Stock Alerts**: Automatic notifications when items reach minimum levels
- **Expiry Warnings**: Alerts for items expiring within 30 days
- **Critical Items**: Special handling for critical medical items
- **Automated Generation**: Background notification generation
- **Priority Levels**: Critical, High, Medium, Low priority notifications

### 📊 Tracking & Expiry Management
- **Batch Tracking**: Track items by batch with expiry dates
- **Expiry Monitoring**: Monitor items expiring soon or already expired
- **Stock Movements**: Complete audit trail of all stock movements
- **Disposal Management**: Proper disposal of expired items

### 📈 Reporting System
- **Summary Reports**: Overview of inventory status
- **Low Stock Reports**: Items requiring attention
- **Expiry Reports**: Items expiring soon
- **Movement Reports**: Stock movement history
- **Value Reports**: Inventory value analysis
- **Export Options**: Print, PDF, Excel export capabilities

## File Structure

### Core Files
- `hospital-inventory.php` - Main inventory management interface
- `inventory-automation.php` - Automated calculations and monitoring
- `expiry-management.php` - Expiry tracking and batch management
- `inventory-reports.php` - Comprehensive reporting system
- `inventory-notifications.php` - Notification management interface
- `auto-notifications.php` - Automated notification generation

### Database Files
- `hospital_inventory_tables.sql` - Complete database schema
- Includes all necessary tables for inventory management

## Database Schema

### Core Tables
1. **inventory_categories** - Item categories
2. **inventory_items** - Main inventory items
3. **inventory_batches** - Batch tracking with expiry dates
4. **stock_movements** - Complete audit trail
5. **hospital_amenities** - Hospital amenities tracking
6. **inventory_notifications** - Notification system
7. **suppliers** - Supplier management
8. **purchase_orders** - Purchase order management

### Key Features
- **Expiry Tracking**: Automatic expiry date monitoring
- **Batch Management**: Track items by batch numbers
- **Stock Movements**: Complete audit trail
- **Notifications**: Automated alert system
- **Value Calculation**: Real-time inventory valuation

## Installation Instructions

### 1. Database Setup
```sql
-- Run the database schema
source hospital_inventory_tables.sql;
```

### 2. File Upload
- Upload all PHP files to your HMS directory
- Ensure proper file permissions

### 3. Access Control
- System automatically restricts access to Admins (role=1) and Employees (role=2)
- Patients and Donors cannot access inventory system

### 4. Menu Integration
- Inventory menu automatically appears for authorized users
- Located in the main navigation menu

## Usage Guide

### Adding New Items
1. Navigate to Hospital Inventory
2. Click "Add New Item"
3. Fill in item details:
   - Item Code (unique identifier)
   - Item Name
   - Category
   - Initial Stock
   - Minimum Stock Level
   - Reorder Level
   - Unit Cost
   - Critical/Perishable flags

### Managing Stock
1. **Adding Stock**: Use "Add" button to increase stock
2. **Removing Stock**: Use "Remove" button to decrease stock
3. **Batch Management**: Add batches with expiry dates
4. **Stock Movements**: All movements are automatically tracked

### Monitoring Expiry
1. Navigate to Expiry Management
2. View items expiring within 30 days
3. Dispose expired items
4. Track batch information

### Generating Reports
1. Navigate to Inventory Reports
2. Select report type:
   - Summary Report
   - Low Stock Report
   - Expiring Items Report
   - Stock Movements Report
   - Inventory Value Report
3. Set date ranges and filters
4. Generate and export reports

### Managing Notifications
1. Navigate to Inventory Notifications
2. View all system-generated notifications
3. Mark notifications as read
4. Delete old notifications

## Automated Features

### Notification Generation
- **Low Stock**: Automatic alerts when items reach reorder levels
- **Expiry Warnings**: Alerts at 30, 15, 7, 3, and 1 days before expiry
- **Expired Items**: Immediate alerts for expired items
- **Reorder Required**: Critical alerts for items below minimum levels

### Calculation Automation
- **Hospital Amenities**: Automatic status calculation
- **Inventory Value**: Real-time value calculations
- **Stock Status**: Automatic stock level monitoring
- **Category Analysis**: Automated category-wise analysis

### Background Processing
- Set up cron job for automated notifications:
```bash
0 * * * * /usr/bin/php /path/to/auto-notifications.php?run=auto
```

## Security Features

### Access Control
- Role-based access control
- Session-based authentication
- SQL injection prevention
- XSS protection

### Data Validation
- Input sanitization
- Data type validation
- Required field validation
- Range validation for numeric fields

## Best Practices

### Inventory Management
1. **Regular Audits**: Conduct regular inventory audits
2. **Expiry Monitoring**: Check expiry reports daily
3. **Stock Levels**: Maintain appropriate stock levels
4. **Critical Items**: Pay special attention to critical items

### Notification Management
1. **Regular Checks**: Check notifications daily
2. **Priority Handling**: Address critical notifications immediately
3. **Cleanup**: Regularly clean up old notifications

### Reporting
1. **Regular Reports**: Generate reports weekly/monthly
2. **Trend Analysis**: Use reports for trend analysis
3. **Cost Control**: Monitor inventory costs regularly

## Troubleshooting

### Common Issues
1. **Access Denied**: Ensure user has proper role (Admin/Employee)
2. **Database Errors**: Check database connection and table existence
3. **Notification Issues**: Verify auto-notifications cron job
4. **Report Generation**: Check date ranges and filters

### Support
- Check database connection in `includes/connection.php`
- Verify user roles and permissions
- Check file permissions and paths
- Review error logs for detailed information

## Future Enhancements

### Planned Features
- Barcode scanning integration
- Mobile app support
- Advanced analytics dashboard
- Integration with procurement systems
- Automated reorder suggestions
- Multi-location inventory support

### Customization Options
- Custom notification intervals
- Additional report types
- Custom categories and fields
- Integration with existing HMS modules

## Technical Specifications

### Requirements
- PHP 7.0+
- MySQL 5.7+
- Web server (Apache/Nginx)
- Modern web browser

### Dependencies
- Bootstrap 4
- jQuery
- DataTables
- Font Awesome

### Performance
- Optimized database queries
- Efficient indexing
- Minimal resource usage
- Scalable architecture

---

**Note**: This system is designed specifically for hospital environments and includes features tailored for medical inventory management. Always ensure proper backup procedures and test in a development environment before production deployment.
