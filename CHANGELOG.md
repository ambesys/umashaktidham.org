# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- Database config path resolution in production server layouts
- File inclusion paths in Layout and main templates for consistency
- Role retrieval in header.php to use correct session variable (`user_role`)
- Default role_id assignment in user registration

### Added
- Debug logging for role ID in getRoleName method
- Enhanced OAuth user data extraction with additional fields (gender, birth_year, etc.)
- Improved OAuth user creation process with better error handling
- Comprehensive logging throughout OAuth authentication flow

### Changed
- Refactored OAuth authentication to include role name retrieval and session management
- Enhanced user data normalization from OAuth providers (Google/Facebook)
- Improved layout consistency by adjusting padding and spacing in CSS
- Updated file inclusion logic to prioritize src/ directory paths

### Technical Details
- **Files Modified:**
  - `src/Views/layouts/main.php` - Fixed database config path from `../../config/database.php` to `../../../config/database.php`
  - `src/Views/layouts/Layout.php` - Updated includeFiles method to prioritize src/ paths
  - `src/Views/layouts/header.php` - Fixed session variable reference from `$_SESSION['role']` to `$_SESSION['user_role']`
  - `src/Services/AuthService.php` - Added getRoleName() method and improved role handling
  - `src/Services/OAuthService.php` - Enhanced user data extraction and creation process
  - `src/Controllers/OAuthController.php` - Added role conversion for OAuth logins
  - `src/Services/SessionService.php` - Updated to store role names in session
  - `config/database.php` - Added SQLite fallback for development environments
  - `public/index.php` - Removed container padding for better layout consistency
  - `public/assets/css/main.css` - Adjusted h1 padding for improved responsiveness

### Security
- OAuth authentication now properly handles user roles and permissions
- Session management improvements for better security

### Performance
- Optimized file inclusion paths to reduce path resolution overhead
- Improved database connection handling with fallback mechanisms