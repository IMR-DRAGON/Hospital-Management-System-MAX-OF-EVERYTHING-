# Attachment Management System

A comprehensive drag and drop file upload system for associates to upload reports prescribed by doctors for individual patients in the Hospital Management System (HMS).

## 📁 Overview

The Attachment Management System provides a secure, user-friendly interface for associates to upload, manage, and share medical documents and reports. It integrates seamlessly with the existing HMS to maintain patient records and facilitate communication between doctors, associates, and patients.

## 🎯 Key Features

### Drag & Drop Upload Interface
- **Intuitive Interface**: Modern drag and drop file upload with visual feedback
- **Multiple File Support**: Upload multiple files simultaneously
- **File Type Validation**: Automatic validation based on category requirements
- **Size Limits**: Configurable file size limits per category
- **Progress Tracking**: Real-time upload progress indication

### Document Management
- **Categorization**: Organized document categories (Lab Reports, Imaging, Prescriptions, etc.)
- **Version Control**: Track document versions and updates
- **Status Management**: Upload, Review, Approval, Rejection workflow
- **Access Control**: Granular permission system for different user types
- **Search & Filter**: Advanced search and filtering capabilities

### Security Features
- **Secure Storage**: Files stored with unique names and organized directory structure
- **Access Permissions**: Role-based access control
- **Audit Trail**: Complete download and access history
- **Confidentiality Levels**: Public, Restricted, Confidential, Top Secret
- **Password Protection**: Optional password protection for sensitive documents

## 📊 Database Schema

### Core Tables

#### 1. `patient_attachments`
Main attachment storage with comprehensive metadata:
- File information (name, size, type, path)
- Document details (title, description, date)
- Medical information (report type, test details, notes)
- Security settings (access level, confidentiality)
- Version control and status tracking

#### 2. `attachment_categories`
Document categorization system:
- Category definitions and codes
- Allowed file types per category
- File size limits and requirements
- Active/inactive status management

#### 3. `attachment_permissions`
Granular access control:
- User-specific permissions
- Permission types (View, Download, Edit, Delete, Share)
- Expiry dates and active status
- Grant tracking and audit

#### 4. `attachment_comments`
Collaborative commenting system:
- User comments and discussions
- Internal vs. public comments
- Resolution tracking
- User attribution

#### 5. `attachment_downloads`
Download audit trail:
- Download tracking and logging
- IP address and user agent recording
- Download reason documentation
- Access pattern analysis

#### 6. `attachment_sharing`
External sharing capabilities:
- Secure sharing links with tokens
- Expiry date management
- Access level control
- Usage tracking and analytics

#### 7. `attachment_tags`
Tagging and organization:
- Custom tag creation
- Color-coded organization
- Tag-based filtering and search
- Flexible categorization

#### 8. `file_storage_locations`
Storage management:
- Multiple storage location support
- Capacity monitoring
- Storage type configuration (Local, AWS S3, Google Drive, etc.)
- Primary/backup storage management

#### 9. `attachment_notifications`
Notification system:
- Real-time notifications for uploads
- Status change alerts
- Comment notifications
- Sharing notifications

#### 10. `attachment_tag_mappings`
Tag-to-document relationships:
- Many-to-many tag associations
- Tag management and organization
- Search and filtering support

## 🚀 Installation & Setup

### 1. Database Setup
```sql
-- Run the SQL file to create all tables
mysql -u root -p hms_db < attachment_management_tables.sql
```

### 2. File Integration
Copy the following PHP files to your HMS root directory:
- `attachment-upload.php` - Drag and drop upload interface
- `patient-attachments.php` - Patient attachment management
- `attachment-details.php` - Detailed attachment view
- `attachment-share.php` - File sharing functionality
- `attachment-edit.php` - Edit attachment details
- `attachment-print.php` - Print attachment information

### 3. Directory Setup
Create upload directories with proper permissions:
```bash
mkdir -p uploads/attachments
chmod 755 uploads/attachments
```

### 4. Navigation Integration
Add the following link to your main navigation menu:
```php
<a href="patient-attachments.php" class="nav-link">
    <i class="fa fa-paperclip"></i> Attachments
</a>
```

## 📋 Key SQL Queries

### File Upload
```sql
-- Upload new attachment
INSERT INTO patient_attachments (patient_id, associate_id, doctor_id, category_id, original_filename, stored_filename, file_path, file_size_bytes, document_title, document_date, report_type, created_by)
VALUES (1, 1, 1, 1, 'lab_report.pdf', 'att_20240315_001.pdf', '/uploads/attachments/2024/03/15/att_20240315_001.pdf', 2048576, 'Blood Test Report', '2024-03-15', 'Lab Report', 'admin');
```

### File Retrieval
```sql
-- Get attachments for patient
SELECT pa.*, ac.category_name, a.first_name as associate_name, d.first_name as doctor_name
FROM patient_attachments pa
JOIN attachment_categories ac ON pa.category_id = ac.category_id
JOIN associates a ON pa.associate_id = a.associate_id
JOIN doctors d ON pa.doctor_id = d.doctor_id
WHERE pa.patient_id = 1 AND pa.status != 'Deleted'
ORDER BY pa.document_date DESC;
```

### Permission Management
```sql
-- Grant permission to user
INSERT INTO attachment_permissions (attachment_id, user_type, user_id, permission_type, granted_by)
VALUES (1, 'Doctor', 1, 'View', 'admin');

-- Check user access
SELECT pa.attachment_id, pa.access_level, p.permission_type
FROM patient_attachments pa
LEFT JOIN attachment_permissions p ON pa.attachment_id = p.attachment_id 
    AND p.user_type = 'Doctor' AND p.user_id = 1 AND p.is_active = TRUE
WHERE pa.attachment_id = 1 
  AND (pa.access_level = 'Public' OR p.permission_type IS NOT NULL);
```

### Download Tracking
```sql
-- Record download
INSERT INTO attachment_downloads (attachment_id, downloaded_by, ip_address, download_reason)
VALUES (1, 'doctor@hospital.com', '192.168.1.100', 'Patient consultation');

-- Get download statistics
SELECT COUNT(*) as total_downloads, COUNT(DISTINCT downloaded_by) as unique_downloaders
FROM attachment_downloads WHERE attachment_id = 1;
```

## 🎯 Usage Guide

### 1. Uploading Files
1. Navigate to Patient Attachments → Upload Files
2. Select patient, doctor, and associate
3. Choose document category
4. Drag and drop files or click to select
5. Fill in document information
6. Set access level and confidentiality
7. Click Upload Files

### 2. Managing Attachments
1. Go to Patient Attachments
2. Use filters to find specific documents
3. View, download, or manage attachments
4. Update status and add comments
5. Share files externally if needed

### 3. Setting Permissions
1. Navigate to attachment details
2. Manage user permissions
3. Set access levels and expiry dates
4. Monitor access logs

### 4. Adding Comments
1. Open attachment details
2. Add comments and notes
3. Mark as internal if needed
4. Track comment resolution

## 🔧 Configuration

### File Upload Settings
Configure upload limits in database:
```sql
-- Update category file size limits
UPDATE attachment_categories SET max_file_size_mb = 50 WHERE category_code = 'IMG';

-- Update allowed file types
UPDATE attachment_categories SET allowed_file_types = '["pdf", "jpg", "jpeg", "png", "dcm"]' WHERE category_code = 'IMG';
```

### Storage Configuration
Set up storage locations:
```sql
-- Add new storage location
INSERT INTO file_storage_locations (location_name, storage_path, storage_type, max_capacity_gb)
VALUES ('AWS S3 Backup', 's3://hms-attachments/', 'AWS S3', 5000);
```

### Security Settings
Configure access levels and permissions:
```sql
-- Set default access level
UPDATE patient_attachments SET access_level = 'Restricted' WHERE access_level IS NULL;

-- Enable password protection for confidential files
UPDATE patient_attachments SET password_protected = TRUE WHERE is_confidential = TRUE;
```

## 📈 Reports Available

1. **Upload Statistics**: File upload trends and patterns
2. **Download Reports**: Access patterns and usage analytics
3. **Storage Usage**: Disk space utilization and capacity planning
4. **Permission Reports**: Access control and security analysis
5. **Category Analysis**: Document distribution by category
6. **User Activity**: Associate and doctor activity reports
7. **Compliance Reports**: Audit trail and regulatory compliance

## 🛡️ Security Features

### File Security
- **Secure Storage**: Files stored with unique, non-guessable names
- **Access Control**: Role-based permissions and access levels
- **Audit Trail**: Complete access and download logging
- **Virus Scanning**: Integration with antivirus scanning
- **Encryption**: Optional file encryption for sensitive documents

### User Security
- **Authentication**: Integration with HMS authentication
- **Authorization**: Granular permission system
- **Session Management**: Secure session handling
- **IP Tracking**: Download and access IP logging

### Data Protection
- **Privacy Controls**: Patient data protection
- **Confidentiality Levels**: Multi-level security classification
- **Retention Policies**: Automated file retention and cleanup
- **Backup Management**: Regular backup and recovery procedures

## 🔄 Workflow Management

### Upload Workflow
1. **File Selection**: Drag and drop or click to select files
2. **Validation**: File type and size validation
3. **Categorization**: Assign appropriate category and metadata
4. **Upload**: Secure file transfer to storage
5. **Database Entry**: Create database record with metadata
6. **Notification**: Notify relevant users of new upload
7. **Review**: Optional review and approval process

### Approval Workflow
1. **Upload**: Associate uploads document
2. **Review**: Doctor reviews document
3. **Approval**: Document approved or rejected
4. **Notification**: Status change notifications
5. **Access**: Approved documents become accessible

## 🚨 Alerts & Notifications

The system provides automatic notifications for:
- New file uploads
- Status changes (approval/rejection)
- Comment additions
- File sharing invitations
- Permission changes
- Storage capacity warnings
- Security violations

## 📊 Key Performance Indicators (KPIs)

### Upload Metrics
- Files uploaded per day/week/month
- Upload success rate
- Average file size
- Category distribution

### Access Metrics
- Download frequency
- User engagement
- Popular documents
- Access patterns

### Storage Metrics
- Storage utilization
- File growth rate
- Cleanup efficiency
- Backup success rate

## 🔧 Advanced Features

### Mobile Support
- **Responsive Design**: Mobile-friendly interface
- **Touch Upload**: Touch-optimized file upload
- **Mobile Preview**: File preview on mobile devices
- **Offline Sync**: Offline capability for critical functions

### Integration Capabilities
- **HMS Integration**: Seamless integration with existing HMS
- **API Support**: RESTful API for external integrations
- **Webhook Support**: Real-time notifications via webhooks
- **Third-party Storage**: Support for cloud storage providers

### Analytics Dashboard
- **Real-time Metrics**: Live upload and access statistics
- **Trend Analysis**: Historical data and trend analysis
- **User Behavior**: User interaction and behavior analytics
- **Performance Monitoring**: System performance and health metrics

## 📞 Support

For technical support or feature requests, please contact the system administrator or refer to the main HMS documentation.

---

**Note**: This attachment management system is designed to integrate seamlessly with your existing HMS. Ensure all database connections, file permissions, and user authentication are properly configured before deployment.

## 🎯 Quick Start Checklist

- [ ] Run database setup script
- [ ] Copy PHP files to HMS directory
- [ ] Create upload directories with proper permissions
- [ ] Update navigation menu
- [ ] Configure file upload limits
- [ ] Set up storage locations
- [ ] Configure user permissions
- [ ] Test file upload functionality
- [ ] Train associates on system usage
- [ ] Set up monitoring and alerts
- [ ] Go live with production data

## 🔍 Best Practices

### File Organization
- Use descriptive document titles
- Categorize files appropriately
- Add relevant tags for easy searching
- Maintain consistent naming conventions

### Security
- Set appropriate access levels
- Use confidential marking for sensitive documents
- Regularly review permissions
- Monitor access logs for anomalies

### Performance
- Optimize file sizes before upload
- Use appropriate file formats
- Regular cleanup of old files
- Monitor storage capacity

### User Training
- Provide comprehensive training materials
- Create user guides and tutorials
- Establish support procedures
- Regular system updates and training
