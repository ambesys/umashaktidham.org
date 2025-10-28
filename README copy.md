# Uma Shakti Dham Website

## Overview
The Uma Shakti Dham website is designed for the non-profit organization Uma Shakti Dham. It features a mobile-responsive design, a donation page, and a membership portal that allows user registration and family details management. The website aims to provide a user-friendly experience while facilitating donations and member management.

## Features
- **Mobile-Responsive Design**: The website is designed to be accessible on various devices, ensuring a seamless experience for all users.
- **Donation Page**: Users can easily make contributions to support the organization.
- **Membership Portal**: Users can register, manage their profiles, and maintain family details.
- **User Dashboard**: A personalized dashboard for users to manage their information and view relevant updates.
- **Admin and Moderator Roles**: Administrative functionalities for managing user accounts and roles, including CRUD operations.

## Project Structure
The project is organized into several directories and files, each serving a specific purpose:

- **public/**: Contains publicly accessible files, including the main entry point (`index.php`), donation page (`donate.php`), and user dashboard (`dashboard.php`).
- **src/**: Contains the application logic, including controllers, models, views, services, and middleware.
- **config/**: Configuration files for the application, including database settings and user roles.
- **database/**: Contains migration and seed files for setting up the database.
- **routes/**: Defines the application routes and maps them to the appropriate controllers.
- **scripts/**: Contains scripts for setting up the application environment.
- **composer.json**: Lists the PHP dependencies required for the project.
- **.env.example**: Template for environment variables.
- **phpunit.xml**: Configuration for PHPUnit testing.

## Installation
1. Clone the repository:
   ```
   git clone <repository-url>
   cd uma-shakti-dham
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Set up the environment:
   - Copy `.env.example` to `.env` and configure your environment variables.

4. Run database migrations:
   ```
   php artisan migrate
   ```

5. Seed the database:
   ```
   php artisan db:seed
   ```

6. Start the server:
   ```
   php -S localhost:8000 -t public
   ```

## Usage
- Access the website at `http://localhost:8000`.
- Users can register, log in, and manage their profiles and family details.
- Admins can manage user accounts and roles through the admin dashboard.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any suggestions or improvements.

## License
This project is licensed under the MIT License. See the LICENSE file for details.