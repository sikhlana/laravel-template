# Laravel Template

This repository contains a highly-opinionated Laravel template boilerplate that I use for my projects. It is designed to streamline the development process by providing a pre-configured setup with my preferred tools and configurations.

## Features

- **Laravel Framework**: Built on Laravel 11, providing a robust and elegant foundation.
- **Pre-configured Packages**: Includes useful packages from well-known vendors like `spatie` and `beyondcode`.
- **Optimized for Development**: Scripts and configurations to enhance the development workflow.
- **Bun**: Utilizes Bun as the package manager and bundle runner for Vite.js, ensuring fast and efficient builds.

## Installation

1. Clone the repository:
    ```sh
    git clone https://github.com/sikhlana/laravel-template.git
    cd laravel-template
    ```

2. Install dependencies:
    ```sh
    composer install
    bun install
    ```

3. Set up environment:
    ```sh
    cp .env.example .env
    php artisan key:generate
    ```

4. Run migrations:
    ```sh
    php artisan migrate
    ```

## Usage

This template is intended to be a starting point for my Laravel projects. It includes a set of conventions and tools that I find useful for rapid development. Feel free to customize it to fit your needs.

## Contributing

Contributions are welcome! Please open an issue or submit a pull request if you have any suggestions or improvements.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
