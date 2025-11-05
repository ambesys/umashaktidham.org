# Layout System Documentation

## Overview

The new layout system centralizes common functionality like session management, middleware, header/footer inclusion, and script loading into a reusable system.

## Key Components

### 1. Layout Class (`src/Views/layouts/Layout.php`)
Central class that handles rendering views with the main layout.

### 2. Main Layout (`src/Views/layouts/main.php`)
The main layout template that includes header, footer, and handles middleware.

### 3. App.php Route Configuration
Routes now support automatic middleware, includes, and script loading.

## Migration Example

### Before (Old users.php):
```php
<?php
session_start();
require_once '../../config/database.php';
require_once '../../src/Models/User.php';
require_once '../../src/Controllers/AdminController.php';

$adminController = new AdminController();
$users = $adminController->getAllUsers();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /uma-shakti-dham/index.php');
    exit();
}

include '../layouts/header.php';
?>

<div class="container">
    <!-- Content here -->
</div>

<?php include '../layouts/footer.php'; ?>
```

### After (New users.php):
```php
    <!-- Just the content, no boilerplate -->
    <h1>Manage Users</h1>
    <table class="table">
        <!-- Table content -->
    </table>
```

### Route Configuration in App.php:
```php
'admin/users' => [
    'middleware' => ['admin'],
    'includes' => ['Models/User.php', 'Controllers/AdminController.php'],
    'logic' => function() {
        $adminController = new AdminController();
        return ['users' => $adminController->getAllUsers()];
    }
],
```

## Benefits

1. **DRY Principle**: No more duplicate session/include/middleware code
2. **Centralized Security**: Middleware applied consistently 
3. **Automatic Dependencies**: Required files included automatically
4. **Clean Views**: Views contain only presentation logic
5. **Easy Maintenance**: Changes to layout structure in one place
6. **Script Management**: Page-specific scripts loaded automatically

## Middleware Types

- `admin`: Requires admin role
- `moderator`: Requires admin or moderator role  
- `authenticated`: Requires any logged-in user

## Usage Patterns

### Simple View (No special requirements):
Views without middleware automatically use the basic layout.

### Authenticated View with Data:
```php
'dashboard/index' => [
    'middleware' => ['authenticated'],
    'includes' => ['Controllers/DashboardController.php'],
    'options' => ['scripts' => ['dashboard.js'], 'title' => 'Dashboard'],
    'logic' => function() {
        $controller = new DashboardController();
        return ['data' => $controller->getData()];
    }
],
```

### Admin View with Multiple Dependencies:
```php
'admin/complex' => [
    'middleware' => ['admin'],
    'includes' => ['Models/User.php', 'Models/Role.php', 'Controllers/AdminController.php'],
    'options' => ['scripts' => ['admin.js', 'datatables.js']],
    'logic' => function() {
        // Complex logic here
        return ['users' => $users, 'roles' => $roles];
    }
],
```

## File Structure Impact

### Old Structure:
- Each view file: ~50 lines (30 boilerplate + 20 content)
- Duplicate middleware in every protected view
- Manual dependency management

### New Structure:
- Each view file: ~20 lines (pure content)
- Centralized middleware in routing
- Automatic dependency injection
- Single layout maintenance point

This system significantly reduces code duplication and makes the application more maintainable while providing better security through consistent middleware application.