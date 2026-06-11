# Medical Chat System - HMS

## Overview
A comprehensive medical chat system that enables secure communication between patients, doctors, and associates with integrated file upload capabilities for medical records, prescriptions, and reports. This system provides a complete solution for medical communication and record management.

## 🏥 Features

### **💬 Chat Communication**
- **Patient-Doctor Chat**: Direct communication between patients and their appointed doctors
- **Patient-Associate Chat**: Communication between patients and hospital associates
- **Real-time Messaging**: Instant message delivery with auto-refresh
- **Message History**: Complete conversation history with timestamps
- **Read Status**: Message read/unread indicators

### **📁 File Upload System**
- **Medical Records**: Upload and share medical reports, lab results, X-rays
- **Prescriptions**: Digital prescription sharing between doctors and patients
- **Multiple File Types**: Support for PDF, DOC, DOCX, JPG, JPEG, PNG, TXT
- **File Categories**: Organized by type (Medical Records, Lab Reports, X-Ray, etc.)
- **File Size Limits**: Configurable size limits per file category
- **Secure Storage**: Files stored in protected directories

### **💊 Prescription Management**
- **Digital Prescriptions**: Doctors can create and send prescriptions through chat
- **Prescription History**: Complete prescription tracking and history
- **Prescription Status**: Active, Completed, Cancelled status tracking
- **File Attachments**: Prescriptions can include file attachments
- **Patient Access**: Patients can view all their prescriptions

### **📋 Medical Records Management**
- **Patient Records**: Comprehensive medical record storage and viewing
- **Record Timeline**: Chronological view of all medical records
- **Record Types**: Support for various record types (Prescription, Lab Report, X-Ray, MRI, CT Scan, Blood Test)
- **Doctor Access**: Doctors can view complete patient medical history
- **File Integration**: Records linked to uploaded files

### **🔐 Security & Access Control**
- **Role-based Access**: Different access levels for patients, doctors, and associates
- **Secure File Upload**: Validated file types and sizes
- **Session Management**: Secure user authentication
- **Data Privacy**: Protected medical information
- **Access Logging**: Track who accessed what information

## 📁 File Structure

### **Core Files**
- `medical-chat.php` - Main chat interface
- `chat-actions.php` - Chat action handler (send messages, upload files)
- `patient-medical-records.php` - Medical records viewer for doctors
- `medical_chat_system_simple.sql` - Database schema

### **Database Tables**
1. **chat_conversations** - Chat conversation management
2. **chat_messages** - Individual chat messages
3. **chat_files** - File uploads and attachments
4. **patient_medical_records** - Patient medical history
5. **prescriptions** - Prescription management
6. **chat_participants** - Conversation participants
7. **file_categories** - File type categorization

## 🚀 Installation & Setup

### **1. Database Setup**
```sql
-- Run the database schema
source medical_chat_system_simple.sql;
```

### **2. File Upload Directory**
```bash
# Create upload directory
mkdir -p uploads/medical_files
chmod 755 uploads/medical_files
```

### **3. Menu Integration**
- Chat system automatically appears in navigation menu
- Available to Admins, Employees, and Doctors
- Accessible via "Medical Chat" menu item

## 🎯 User Roles & Access

### **👤 Patients (Role 3)**
- **Can Chat With**: Doctors and Associates
- **Can Upload**: Medical records, reports, images
- **Can View**: Their own medical records and prescriptions
- **Can Receive**: Prescriptions from doctors

### **👨‍⚕️ Doctors (Role 1/2 with Doctor designation)**
- **Can Chat With**: Patients and Associates
- **Can Upload**: Medical records, prescriptions, reports
- **Can View**: Complete patient medical history
- **Can Create**: Digital prescriptions
- **Can Access**: Patient medical records viewer

### **🤝 Associates (Role 1/2)**
- **Can Chat With**: Patients and Doctors
- **Can Upload**: Medical records and reports
- **Can View**: Patient information and records
- **Can Assist**: With patient communication

## 💬 Chat Features

### **Real-time Communication**
- **Instant Messaging**: Messages appear immediately
- **Auto-refresh**: Chat updates every 5 seconds
- **Message Types**: Text, File, Prescription, Medical Record
- **Timestamps**: All messages include date and time
- **Sender Information**: Clear identification of message sender

### **File Sharing**
- **Drag & Drop**: Easy file upload interface
- **File Preview**: View files directly in chat
- **Download Options**: Download shared files
- **File Categories**: Organized by medical file type
- **Size Validation**: Automatic file size checking

### **Prescription System**
- **Digital Prescriptions**: Create prescriptions in chat
- **Prescription Templates**: Structured prescription format
- **File Attachments**: Attach prescription files
- **Status Tracking**: Monitor prescription status
- **Patient Notifications**: Automatic patient notifications

## 📊 Medical Records System

### **Record Types**
- **Prescriptions**: Doctor prescriptions and medications
- **Lab Reports**: Laboratory test results
- **X-Ray Images**: X-ray and imaging results
- **MRI/CT Scans**: Advanced imaging results
- **Blood Tests**: Blood test results and reports
- **Other Documents**: Miscellaneous medical documents

### **Record Management**
- **Timeline View**: Chronological record display
- **Search & Filter**: Find specific records quickly
- **File Integration**: Records linked to uploaded files
- **Access Control**: Role-based record access
- **Audit Trail**: Track record access and modifications

### **Doctor Features**
- **Complete History**: View all patient medical records
- **Record Timeline**: Chronological medical history
- **File Downloads**: Download patient files
- **Prescription History**: View all prescriptions
- **Chat Integration**: Access records from chat interface

## 🔧 Technical Features

### **Database Design**
- **Normalized Structure**: Efficient database design
- **Indexed Queries**: Optimized for performance
- **Foreign Key Relationships**: Data integrity
- **Audit Fields**: Created/updated timestamps

### **File Management**
- **Secure Upload**: Validated file uploads
- **File Validation**: Type and size checking
- **Unique Filenames**: Prevent file conflicts
- **Organized Storage**: Structured file organization

### **Security Features**
- **Input Validation**: Sanitized user inputs
- **SQL Injection Prevention**: Prepared statements
- **File Type Validation**: Allowed file types only
- **Access Control**: Role-based permissions
- **Session Security**: Secure user sessions

## 📱 User Interface

### **Chat Interface**
- **Modern Design**: Clean, professional interface
- **Responsive Layout**: Works on all devices
- **Real-time Updates**: Live message updates
- **File Upload**: Easy file sharing
- **Message History**: Complete conversation history

### **Medical Records Viewer**
- **Timeline Interface**: Chronological record display
- **Card-based Layout**: Easy-to-read record cards
- **File Previews**: Quick file access
- **Search Functionality**: Find records quickly
- **Export Options**: Download records

### **Mobile Responsive**
- **Touch-friendly**: Optimized for mobile devices
- **Responsive Design**: Adapts to screen size
- **Mobile Navigation**: Easy mobile navigation
- **Touch Interactions**: Mobile-optimized interactions

## 🚀 Usage Guide

### **Starting a Conversation**
1. Click "New Conversation" button
2. Select conversation type (Patient-Doctor, Patient-Associate)
3. Choose the other party
4. Start chatting immediately

### **Uploading Files**
1. Click "Upload File" button in chat
2. Select file category
3. Add file description
4. Choose file to upload
5. File appears in chat immediately

### **Creating Prescriptions (Doctors)**
1. Click "Prescription" button in chat
2. Enter prescription details
3. Optionally attach prescription file
4. Send prescription to patient
5. Prescription appears in chat and medical records

### **Viewing Medical Records (Doctors)**
1. Access patient medical records
2. View chronological timeline
3. Download files and records
4. Access from chat interface
5. Complete patient history available

## 🔒 Security Considerations

### **Data Protection**
- **Encrypted Storage**: Secure file storage
- **Access Logging**: Track all access
- **Role-based Access**: Appropriate permissions
- **Data Validation**: Input sanitization
- **Session Security**: Secure user sessions

### **Privacy Compliance**
- **Patient Privacy**: Protected medical information
- **Access Control**: Role-based restrictions
- **Audit Trail**: Complete access logging
- **Data Retention**: Configurable retention policies
- **Secure Communication**: Encrypted data transmission

## 🎯 Benefits

### **For Patients**
- **Easy Communication**: Direct chat with doctors
- **File Sharing**: Upload medical records easily
- **Prescription Access**: Digital prescription management
- **Record Access**: View own medical history
- **Convenience**: 24/7 access to medical communication

### **For Doctors**
- **Patient Communication**: Direct patient contact
- **Record Access**: Complete patient history
- **Prescription Management**: Digital prescription system
- **File Sharing**: Easy file exchange
- **Efficiency**: Streamlined communication

### **For Hospital**
- **Improved Communication**: Better patient-doctor interaction
- **Digital Records**: Reduced paper usage
- **Efficiency**: Streamlined processes
- **Patient Satisfaction**: Enhanced patient experience
- **Compliance**: Better record management

## 🔮 Future Enhancements

### **Planned Features**
- **Video Calls**: Integrated video calling
- **Voice Messages**: Voice message support
- **AI Integration**: AI-powered medical assistance
- **Mobile App**: Dedicated mobile application
- **Advanced Analytics**: Communication analytics

### **Technical Improvements**
- **Real-time Updates**: WebSocket implementation
- **Advanced Security**: Enhanced encryption
- **Performance Optimization**: Improved speed
- **API Integration**: Third-party integrations
- **Cloud Storage**: Cloud-based file storage

---

**Note**: This medical chat system is designed specifically for hospital environments and includes features tailored for medical communication and record management. Always ensure proper backup procedures and test in a development environment before production deployment.
