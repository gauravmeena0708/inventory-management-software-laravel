# Laravel Inventory Management System

This project is an Inventory Management System developed using the Laravel framework (version 8). It provides a web-based interface to manage and track various assets and inventory items within an organization. The system is designed to help keep a detailed record of IT equipment, consumables, agreements, and other related resources.

Built in 2019, this application demonstrates a comprehensive approach to inventory control, including features for authentication, data management, and activity tracking.

## Key Features

*   **User Management:** Secure user registration and login functionality.
*   **Asset Tracking:** Manage and track various types of assets including:
    *   Desktops
    *   Laptops
    *   Servers
    *   Storage Devices
    *   General Devices (Printers, Network equipment, etc.)
*   **Consumables Management:** Keep track of consumable items.
*   **Agreement Management:** Store and manage contracts and agreements related to assets or services.
*   **Inventory Categorization:**
    *   Device Categories (Devcats)
    *   Manufacturers
    *   Locations
*   **Financial Tracking:** Record payments associated with assets or agreements.
*   **File Attachments:** Ability to attach files (e.g., invoices, warranty documents) to relevant records.
*   **Task Management:** Assign and track tasks related to inventory management.
*   **Official Records & Developer Info:** Maintain records of officials and developers associated with assets.
*   **Data Export:** Export data to Excel format (inferred from `maatwebsite/excel` dependency).
*   **Activity Logging:** Track significant actions performed by users within the system (inferred from `spatie/laravel-activitylog` dependency).
*   **Responsive UI:** User interface built with Bootstrap, ensuring compatibility across devices.
*   **Data Presentation:** Utilizes Datatables.js for efficient display and filtering of tabular data.

## Technologies Used

*   **Backend:**
    *   PHP (Version ^7.3 || ^8.0)
    *   Laravel Framework (Version ^8.12)
    *   Key PHP Packages:
        *   `laravel/ui`: For authentication scaffolding.
        *   `maatwebsite/excel`: For Excel export functionality.
        *   `spatie/laravel-activitylog`: For recording user activity.
        *   `fideloper/proxy`: For handling proxy server configurations.
        *   `fruitcake/laravel-cors`: For Cross-Origin Resource Sharing (CORS) headers.
        *   `guzzlehttp/guzzle`: For making HTTP requests.
*   **Frontend:**
    *   Bootstrap
    *   JavaScript
    *   Datatables.js
*   **Database:**
    *   Compatible with MySQL, PostgreSQL, SQLite, SQL Server (as per Laravel's default capabilities). The specific database used would be configured in the `.env` file.
*   **Development & Testing:**
    *   Composer: For PHP dependency management.
    *   NPM: For frontend dependency management.
    *   PHPUnit: For unit and feature testing.

## Installation Instructions

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/your-username/your-repository-name.git
    cd your-repository-name
    ```
    *(Replace `https://github.com/your-username/your-repository-name.git` and `your-repository-name` with the actual URL and project directory name if different)*

2.  **Install PHP Dependencies:**
    ```bash
    composer install
    ```

3.  **Create Environment File:**
    Copy the example environment file and then generate the application key.
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Configure Environment:**
    Open the `.env` file in a text editor and update the following settings:
    *   `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: Configure your database connection details.
    *   `APP_URL`: Set this to the URL you will use to access the application (e.g., `http://localhost:8000`).
    *   Other settings like mail driver, queue connection, etc., as needed.

5.  **Run Database Migrations and Seeders:**
    This will create the necessary database tables and populate them with initial data (if seeders are configured).
    ```bash
    php artisan migrate --seed
    ```

6.  **Install Frontend Dependencies:**
    ```bash
    npm install
    ```

7.  **Build Frontend Assets:**
    ```bash
    npm run dev
    ```
    (For development. For production, use `npm run prod`)

8.  **Start the Development Server:**
    ```bash
    php artisan serve
    ```
    The application should now be accessible at the URL specified by `php artisan serve` (usually `http://127.0.0.1:8000` or `http://localhost:8000`).

## Testing

This project uses PHPUnit for automated testing. To run the test suite, execute the following command from the project's root directory:

```bash
php artisan test
```

Make sure you have set up your testing environment correctly, which might include a separate testing database configured in `phpunit.xml` or your `.env.testing` file if applicable.

## License

This Inventory Management System is open-sourced software licensed under the [MIT License](LICENSE).
