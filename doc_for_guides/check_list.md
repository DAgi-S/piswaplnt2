# Deployment and Maintenance Checklist

## 1. Pre-Deployment Checks

### File System Checks
- [ ] All includes work via require_once or include
- [ ] No absolute paths (e.g., C:/xampp/...) are used
- [ ] Using $_SERVER['DOCUMENT_ROOT'] or relative paths
- [ ] All file permissions are correctly set
- [ ] No hardcoded credentials in files
- [ ] Debug mode is disabled in production
- [ ] Error reporting is set to production level
- [ ] All temporary files are removed
- [ ] Backup files (.bak, .old) are removed
- [ ] Version control files (.git, .svn) are removed

### Code Quality Checks
- [ ] All debugging logs are removed
- [ ] Test queries are removed
- [ ] var_dump() calls are removed
- [ ] print_r() calls are removed
- [ ] Console.log() statements are removed
- [ ] Development comments are removed
- [ ] Code is properly formatted
- [ ] No unused variables or functions
- [ ] All functions have proper error handling
- [ ] Input validation is implemented
- [ ] SQL injection prevention is in place

### Configuration Checks
- [ ] DB credentials are configured for production
- [ ] API keys are updated for production
- [ ] SMTP settings are configured for production
- [ ] File upload paths are correctly set
- [ ] Cache settings are optimized
- [ ] Session settings are secure
- [ ] Timezone is correctly set
- [ ] Character encoding is properly configured
- [ ] Memory limits are appropriate
- [ ] Execution time limits are set

## 2. Database Checks

### Structure Checks
- [ ] Export only necessary tables
- [ ] Remove test data
- [ ] Verify all tables have primary keys
- [ ] Check foreign key constraints
- [ ] Verify indexes are properly set
- [ ] Check table collations
- [ ] Verify character sets
- [ ] Check auto-increment values
- [ ] Verify default values
- [ ] Check nullable fields

### Security Checks
- [ ] User passwords are hashed
- [ ] Admin credentials are created
- [ ] Test accounts are removed
- [ ] Database user has minimal privileges
- [ ] Backup user is created
- [ ] Audit logging is enabled
- [ ] Sensitive data is encrypted
- [ ] Access logs are configured
- [ ] Error logs are configured
- [ ] Regular backup schedule is set

### Data Integrity Checks
- [ ] Foreign key relationships are valid
- [ ] Data types are correct
- [ ] Required fields are not null
- [ ] Unique constraints are enforced
- [ ] Check constraints are valid
- [ ] Default values are appropriate
- [ ] Data validation rules are enforced
- [ ] Data consistency is maintained
- [ ] Referential integrity is maintained
- [ ] Data cleanup procedures are in place

## 3. Hosting Setup

### Server Configuration
- [ ] MySQL database is created on cPanel
- [ ] SQL file is imported via phpMyAdmin
- [ ] Database user is created with proper permissions
- [ ] Database connection is tested
- [ ] PHP version is compatible
- [ ] Required PHP extensions are installed
- [ ] Memory limits are appropriate
- [ ] Execution time limits are set
- [ ] File upload limits are set
- [ ] Error reporting is configured

### Configuration Files
```php
// config.php or .env
$host = "localhost";
$user = "cpanel_db_user";
$pass = "cpanel_db_pass";
$dbname = "cpanel_db_name";
```

### Security Settings
- [ ] SSL certificate is installed
- [ ] HTTPS is enforced
- [ ] Firewall rules are set
- [ ] IP restrictions are configured
- [ ] Directory permissions are set
- [ ] File permissions are set
- [ ] Backup system is configured
- [ ] Monitoring is set up
- [ ] Error logging is configured
- [ ] Access logging is configured

## 4. Post-Deployment Checks

### Functionality Tests
- [ ] User authentication works
- [ ] Database operations work
- [ ] File uploads work
- [ ] Email sending works
- [ ] API endpoints work
- [ ] Form submissions work
- [ ] Search functionality works
- [ ] Reports generate correctly
- [ ] Export functions work
- [ ] Import functions work

### Performance Tests
- [ ] Page load times are acceptable
- [ ] Database queries are optimized
- [ ] Caching is working
- [ ] Memory usage is within limits
- [ ] CPU usage is within limits
- [ ] Network bandwidth is sufficient
- [ ] Concurrent users are supported
- [ ] Response times are acceptable
- [ ] Resource usage is monitored
- [ ] Performance bottlenecks are identified

### Security Tests
- [ ] SQL injection is prevented
- [ ] XSS attacks are prevented
- [ ] CSRF protection is working
- [ ] File upload security is enforced
- [ ] Session security is maintained
- [ ] Password policies are enforced
- [ ] Access control is working
- [ ] Audit logging is functional
- [ ] Error handling is secure
- [ ] Data encryption is working

## 5. Maintenance Schedule

### Daily Tasks
- [ ] Check error logs
- [ ] Monitor system performance
- [ ] Verify backups
- [ ] Check security logs
- [ ] Monitor disk space
- [ ] Check database health
- [ ] Verify email functionality
- [ ] Monitor user activity
- [ ] Check for failed jobs
- [ ] Review system alerts

### Weekly Tasks
- [ ] Update system software
- [ ] Clean up temporary files
- [ ] Optimize database
- [ ] Review security logs
- [ ] Check backup integrity
- [ ] Monitor resource usage
- [ ] Review error patterns
- [ ] Check for security updates
- [ ] Review user feedback
- [ ] Update documentation

### Monthly Tasks
- [ ] Full system backup
- [ ] Security audit
- [ ] Performance review
- [ ] Database maintenance
- [ ] File system cleanup
- [ ] User account review
- [ ] Access rights review
- [ ] System updates
- [ ] Documentation updates
- [ ] Disaster recovery test

## 6. Quick Reference Checklist

### Critical Pre-Deployment Checks
- [ ] Verify database credentials in config files
- [ ] Remove all debug statements and test code
- [ ] Check file paths and includes
- [ ] Verify production settings in config files
- [ ] Test database connection
- [ ] Verify file permissions
- [ ] Check for hardcoded credentials
- [ ] Disable debug mode
- [ ] Set proper error reporting level
- [ ] Remove temporary and backup files

### Essential Database Checks
- [ ] Export production database
- [ ] Remove test data
- [ ] Verify user passwords are hashed
- [ ] Create admin account
- [ ] Check foreign key constraints
- [ ] Verify indexes
- [ ] Test database connection
- [ ] Check character encoding
- [ ] Verify data types
- [ ] Test backup and restore

### Must-Have Hosting Setup
- [ ] Create MySQL database
- [ ] Import SQL file
- [ ] Set up database user
- [ ] Configure database connection
- [ ] Install SSL certificate
- [ ] Set up HTTPS
- [ ] Configure file permissions
- [ ] Set up error logging
- [ ] Configure backup system
- [ ] Test email functionality

### Critical Post-Deployment Tests
- [ ] Test user login
- [ ] Verify database operations
- [ ] Check file uploads
- [ ] Test email sending
- [ ] Verify API endpoints
- [ ] Test form submissions
- [ ] Check search functionality
- [ ] Generate test reports
- [ ] Test export functions
- [ ] Verify import functions

### Daily Critical Tasks
- [ ] Monitor error logs
- [ ] Check system performance
- [ ] Verify backups
- [ ] Monitor security logs
- [ ] Check disk space
- [ ] Monitor database health
- [ ] Test email system
- [ ] Review user activity
- [ ] Check failed jobs
- [ ] Review system alerts

### Weekly Critical Tasks
- [ ] Update system software
- [ ] Clean temporary files
- [ ] Optimize database
- [ ] Review security logs
- [ ] Verify backup integrity
- [ ] Monitor resource usage
- [ ] Check for updates
- [ ] Review error patterns
- [ ] Check user feedback
- [ ] Update documentation

### Monthly Critical Tasks
- [ ] Perform full backup
- [ ] Conduct security audit
- [ ] Review performance
- [ ] Maintain database
- [ ] Clean file system
- [ ] Review user accounts
- [ ] Check access rights
- [ ] Apply system updates
- [ ] Update documentation
- [ ] Test disaster recovery

## 7. Emergency Procedures

### Database Issues
- [ ] Check error logs
- [ ] Verify database connection
- [ ] Test backup restore
- [ ] Check disk space
- [ ] Monitor query performance
- [ ] Review recent changes
- [ ] Check for locks
- [ ] Verify user permissions
- [ ] Test database operations
- [ ] Document issue and solution

### Security Incidents
- [ ] Check access logs
- [ ] Review security alerts
- [ ] Verify user accounts
- [ ] Check file permissions
- [ ] Review recent changes
- [ ] Monitor system activity
- [ ] Check for vulnerabilities
- [ ] Update security measures
- [ ] Document incident
- [ ] Implement fixes

### Performance Problems
- [ ] Check server resources
- [ ] Monitor query performance
- [ ] Review caching
- [ ] Check network bandwidth
- [ ] Monitor response times
- [ ] Review recent changes
- [ ] Check for bottlenecks
- [ ] Optimize queries
- [ ] Document issues
- [ ] Implement solutions

## 8. Common Issues and Solutions

### Database Connection Issues
1. Check credentials in config file
2. Verify database server status
3. Test network connectivity
4. Check firewall settings
5. Verify user permissions
6. Test connection with different user
7. Check for database locks
8. Review error logs
9. Test backup connection
10. Document solution

### File Permission Problems
1. Check file ownership
2. Verify permission settings
3. Test file operations
4. Check directory permissions
5. Review user groups
6. Test with different users
7. Check for locked files
8. Review security settings
9. Document changes
10. Implement fixes

### Performance Optimization
1. Review query performance
2. Check index usage
3. Monitor resource usage
4. Review caching strategy
5. Check for bottlenecks
6. Optimize database
7. Review code efficiency
8. Check network performance
9. Document improvements
10. Monitor results

## Pre-deployment Checks
- [x] All required files are present in the production folder
  - ✅ Core files (core.php, db_connect.php) are present
  - ✅ Authentication files (auth_check.php) are present
  - ✅ Configuration files are present
- [x] Database connection is properly configured
  - ✅ Database connection file exists
  - ✅ Error handling is implemented
  - ✅ UTF-8 charset is set
- [x] Authentication system is working
  - ✅ Session management is implemented
  - ✅ User role checking is in place
  - ✅ Login redirection is working
- [x] Error logging is enabled
  - ✅ Error logging is configured
  - ✅ Database errors are logged
  - ✅ PHP errors are logged to php_errors.log
- [x] File permissions are set correctly
  - ✅ Core files have appropriate permissions
  - ✅ Upload directory has write permissions
  - ✅ Configuration files have restricted access
- [x] Security measures are in place
  - ✅ Input validation is implemented
  - ✅ SQL injection prevention is in place
  - ✅ XSS protection is implemented
- [x] Required directories exist
  - ✅ Includes directory exists
  - ✅ Uploads directory exists
  - ✅ Assets directory exists
- [x] Configuration files are properly set up
  - ✅ Database credentials are configured
  - ✅ System settings are defined
  - ✅ Environment variables are set

## Database Configuration Status

### Main Configuration Files
- [x] config.php
  - ✅ Local and production configurations defined
  - ✅ Environment detection implemented
  - ✅ Database credentials properly separated
  - ✅ Store URL configuration included
  - ⚠️ Password exposed in config file (should be moved to environment variables)

- [x] db_connect.php
  - ✅ Proper error handling implemented
  - ✅ UTF-8 charset configured
  - ✅ Connection pooling enabled
  - ✅ Error logging configured
  - ✅ User-friendly error messages

### Database Settings
- [x] Local Environment
  - ✅ Host: localhost
  - ✅ Username: root
  - ✅ Database: pistocklntmarch
  - ⚠️ Empty password (should be set for security)

- [x] Production Environment
  - ✅ Host: localhost
  - ✅ Username: lebawinet_pistocklntmarchuser
  - ✅ Database: lebawinet_pistocklntmarch
  - ⚠️ Password exposed in config file

### Security Recommendations
1. Move database credentials to environment variables
2. Implement password encryption
3. Add IP restrictions for database access
4. Set up database backup configuration
5. Implement connection timeout settings
6. Add SSL/TLS for database connections
7. Set up database monitoring
8. Implement connection pooling limits
9. Add database user permissions audit
10. Set up automated backup verification

### Required Actions
1. [ ] Move database credentials to .env file
2. [ ] Implement environment variable loading
3. [ ] Set up proper password encryption
4. [ ] Configure database backup system
5. [ ] Implement connection monitoring
6. [ ] Set up SSL/TLS for database
7. [ ] Configure proper user permissions
8. [ ] Set up automated backups
9. [ ] Implement connection pooling limits
10. [ ] Add database health checks 