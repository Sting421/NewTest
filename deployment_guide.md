# Boarding House System Deployment Guide

This guide provides step-by-step instructions for deploying the Boarding House Management System to either Render.com or Avien hosting platforms.

## Prerequisites

- Git repository with your application code
- MySQL database dump file (db_schema.sql)
- PHP 7.4 or higher
- Basic understanding of web hosting concepts

## Deploying to Render.com

### 1. Prepare Your Repository

Ensure your repository includes these essential files:
- `composer.json` - For PHP dependency management
- `Procfile` - For process management (already in place)
- `.htaccess` - For URL routing
- `db_schema.sql` - Database schema

### 2. Create a Render Account

1. Go to [render.com](https://render.com/)
2. Sign up for an account or log in

### 3. Create a New Web Service

1. From your dashboard, click "New" and select "Web Service"
2. Connect to your GitHub/GitLab repository OR use "Manual Deploy" with your repository URL
3. Configure the service:
   - **Name**: boarding-house-system (or your preferred name)
   - **Environment**: PHP
   - **Region**: Choose the closest to your users
   - **Branch**: main (or your default branch)
   - **Build Command**: `composer install`
   - **Start Command**: `vendor/bin/heroku-php-apache2`
   - **Plan**: Free (or select a paid plan for production)

### 4. Set Environment Variables

In the Render dashboard, navigate to your web service, then:
1. Go to the "Environment" tab
2. Add the following variables:
   - `DB_HOST`: Your database host (from step 5)
   - `DB_NAME`: Your database name
   - `DB_USER`: Your database username
   - `DB_PASSWORD`: Your database password

### 5. Set Up Database

#### Option 1: Use Render PostgreSQL (Requires code adaptation)
1. From Render dashboard, click "New" and select "PostgreSQL"
2. Choose a name and plan
3. Note the connection details
4. Modify your code to work with PostgreSQL

#### Option 2: Use External MySQL Database
1. Use an external MySQL provider (e.g., AWS RDS, DigitalOcean, PlanetScale)
2. Create a database and note connection details
3. Import your `db_schema.sql` file

### 6. Deploy Your Application

1. Click "Deploy" in the Render dashboard
2. Monitor the build logs for any errors
3. Once deployment completes, your application will be available at the URL provided by Render

### 7. Test Your Application

1. Open the URL provided by Render
2. Verify that the login page loads correctly
3. Test user registration and login functionality
4. Test other application features

## Deploying to Avien

### 1. Sign Up for Avien Hosting

1. Visit [avien.io](https://avien.io) or their website
2. Sign up for a hosting plan that supports:
   - PHP 7.4+
   - MySQL databases
   - Custom domain setup (if needed)

### 2. Prepare Your Application Files

1. Download your application as a ZIP file
2. Ensure database connections use environment variables

### 3. Access Avien Control Panel

1. Log in to your Avien control panel
2. Navigate to the file manager or web hosting section

### 4. Upload Your Application

1. Upload your ZIP file to the server
2. Extract the files to the web root directory (often `public_html` or `www`)
3. Ensure file permissions are set correctly:
   - Directories: 755 (drwxr-xr-x)
   - Files: 644 (rw-r--r--)

### 5. Create MySQL Database

1. In the Avien control panel, go to the Databases section
2. Create a new MySQL database
3. Create a database user and assign it to your database
4. Note the database credentials

### 6. Import Database Schema

1. Access phpMyAdmin or other database management tool provided by Avien
2. Select your database
3. Use the import function to upload and execute the `db_schema.sql` file

### 7. Configure Environment Variables

1. In the Avien control panel, locate the environment variables section
2. Set the following variables:
   - `DB_HOST`: Usually 'localhost' or the provided database server
   - `DB_NAME`: Your database name
   - `DB_USER`: Your database username
   - `DB_PASSWORD`: Your database password

### 8. Configure Domain (Optional)

1. If you have a custom domain, configure it in the Avien control panel
2. Set up DNS records to point to your Avien hosting
3. Configure SSL certificate for HTTPS

### 9. Test Your Application

1. Access your application via the provided Avien URL or your custom domain
2. Verify that all features work correctly

## Troubleshooting

### Common Issues with Render

1. **Database Connection Errors**
   - Verify environment variables are set correctly
   - Check if your IP is allowed in database firewall rules

2. **Deployment Failures**
   - Check build logs for errors
   - Ensure composer.json is valid
   - Verify PHP version compatibility

3. **Application Errors**
   - Check logs in the Render dashboard
   - Enable PHP error display temporarily for debugging

### Common Issues with Avien

1. **500 Server Errors**
   - Check file permissions
   - Verify .htaccess configuration is supported
   - Check PHP version compatibility

2. **Database Connection Issues**
   - Verify database credentials
   - Check if the database server is the correct hostname

3. **White Screen/Blank Page**
   - Enable PHP error reporting in your application
   - Check server error logs

## Maintenance

### Regular Backups

1. Set up automated database backups
2. Keep copies of your application code
3. Document all configuration settings

### Performance Monitoring

1. Use the hosting provider's monitoring tools
2. Consider implementing application-level performance tracking

### Security Updates

1. Regularly update PHP and any dependencies
2. Monitor for security advisories related to your technology stack
3. Implement best practices for web application security

---

For additional help or support, please contact your hosting provider or refer to their documentation. 