# Blog Platform

A modern blog platform built with PHP and MySQL, featuring user authentication, post management, and data analytics. The platform is currently hosted at [https://comp3421.site/].

## Project Structure

```
├── admin/          # Admin panel files
├── api/            # API endpoints
├── classes/        # PHP classes
├── config/         # Configuration files
├── database/       # Database related files
├── includes/       # Common includes
├── middleware/     # Middleware components
└── *.php           # Main application files
```

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache recommended)

## Setup Instructions

### Local Development Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/24053438d/COMP3421-Blog
   cd COMP3421-Blog
   ```

2. Configure the database:
   - Create a MySQL database named `blog_platform`
   - Update the database configuration in `config/database.php`:
     ```php
     private $host = "localhost";
     private $db_name = "blog_platform";
     private $username = "your_username";
     private $password = "your_password";
     ```

3. Import the database schema:
   - Locate the SQL file in the `database` directory
   - Import it into your MySQL database

4. Configure your web server:
   - Point your web server's document root to the project directory
   - Ensure mod_rewrite is enabled if using Apache

### Running the Features

1. **User Registration**
   - Visit [https://comp3421.site/register.php]
   - Fill in the registration form (username, email, and password)
   - Users can create accounts with email
   - Password strength requirements enforced
   - Unique username validation
   - Unique email validation

2. **Authentication**
   - Password reset functionality via reset link
   - Logout functionality with session cleanup

3. **Creating Posts**
   - Log in to your account using email and password
   - Click "Create Post" in the navigation
   - Use the editor to create your content
   - Publish or save as draft

4. **Managing Posts**
   - Access "My Posts" from your profile
   - Edit and manage existing posts
   - Edit past posts (Draft/Published/Archived)
   - Manage comments (Approve/Delete)

5. **Admin Features**
   - Log in with admin credentials:
     - Email: admin123@gmail.com
     - Password: admin123
   - Access the admin panel at `/admin` or click Admin in navigation bar
   - Manage posts and view detailed of the post
     - Title
     - Author
     - Date
     - Status (Published/Archived)
     - Actions (View/Edit/Delete)
   - Manage comments
     - Approve/Delete pending comments 
     - Delete approved comments

6. ### Analytics (Admin Only)
**Admin Credentials:**
   - Email: admin123@gmail.com
   - Password: admin123

 - **Tracking Features**
  - Press "Analytics"
  - Sorting function by date or date range
  - Post view counts
  - Page view counts
  - Active Users 
  - User Interactions 
  - Page Load Performance 
  - Geographical Distribution 

7. ### Security Log (Admin Only)
**Admin Credentials:**
- Email: admin123@gmail.com
- Password: admin123

- **Security Features**
  - Press "Security Logs"
  - Sorting function by date
  - View security events including:
    - Event ID
    - Event type
    - User ID
    - IP address
    - Date & Time
    - View Details(email)



