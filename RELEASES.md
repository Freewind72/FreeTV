# FreeTV Releases

## v1.0.0 (2025-06-23)

### Features
- Video playback system supporting both direct URLs and embed codes
- User authentication system with registration and login
- Comment system with nickname support
- Dark theme UI with responsive design
- Admin dashboard with the following features:
  - Site settings management (title, subtitle, video content, footer)
  - Comment moderation
  - User management
  - Popup announcement system

### Technical Details
- Built with PHP and SQLite
- Automatic database structure upgrades
- First registered user becomes admin automatically
- Mobile-friendly responsive design
- Security features:
  - Password hashing using PHP's password_hash()
  - Session-based authentication
  - XSS protection with htmlspecialchars()
  - CSRF protection for forms

### Admin Features
- Customizable site settings:
  - Site title and subtitle
  - Video content (URL or embed code)
  - Footer text
  - Popup announcements
- Comment management:
  - View all comments
  - Delete comments
  - Nickname support for comments
- User management:
  - View all users
  - Delete users
  - View registration IPs
  - Password hashes visible for verification

### UI/UX Improvements
- Modern dark theme design
- Responsive layout for all screen sizes
- Clean and intuitive admin interface
- Improved form styling and input validation
- Mobile-optimized tables with responsive columns

### Security
- Secure login system
- Password hashing
- Session management
- Input sanitization
- SQL injection prevention using prepared statements

### Installation
- Simple installation process
- Automatic database creation
- Default admin credentials provided
- Automatic table structure updates
