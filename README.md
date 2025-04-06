# Allo Bot

Allo Bot is a Slack bot designed to help users query their remaining allowances for company benefits via Expensify. It utilizes Slack slash commands to interact with the Expensify API.

## Features

*   Log in to Expensify securely via Slack.
*   List available benefit categories from Expensify.
*   Check the current status of your allowances.

## Setup

1.  **Clone the repository:**
    ```bash
    git clone <your-repository-url>
    cd allo-bot
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    npm install && npm run build # If you have frontend assets
    ```

3.  **Set up environment variables:**
    *   Copy the example environment file:
        ```bash
        cp .env.example .env
        ```
    *   Generate an application key:
        ```bash
        php artisan key:generate
        ```
    *   Update your `.env` file with the necessary credentials:
        *   `APP_NAME`, `APP_URL`, `APP_ENV`
        *   Database connection details (`DB_CONNECTION`, `DB_HOST`, etc.)
        *   Queue connection details (`QUEUE_CONNECTION`)
        *   `SLACK_SIGNING_SECRET`: Your Slack app's Signing Secret.
        *   `SLACK_SLASH_COMMAND_VERIFICATION_TOKEN`: Your Slack app's Verification Token (may vary depending on how Slack commands are handled - ensure this matches your Spatie Slash Command handler setup).
        *   Expensify Credentials: Add variables for your Expensify Partner User ID and Secret. Ensure these are securely stored and accessed by the `ExpensifyService`. **(Note: The exact variable names `EXPENSIFY_PARTNER_USER_ID` and `EXPENSIFY_PARTNER_USER_SECRET` are assumed; please update if different.)**

4.  **Run database migrations (if applicable):**
    ```bash
    php artisan migrate
    ```

5.  **Configure your web server:** Point your web server (Nginx, Apache) to the `public` directory.

6.  **Set up a queue worker (if using queues):**
    ```bash
    php artisan queue:work
    ```
    (Consider using Supervisor or similar to keep the queue worker running).

7.  **Configure Slack App:**
    *   Create a new Slack App.
    *   Enable Slash Commands.
    *   Set the Request URL to your application's endpoint that handles the slash commands (e.g., `https://your-app-url.com/api/slack/command`).
    *   Install the app to your workspace.

## Usage

Interact with the bot using the `/allowances` slash command in Slack:

*   **Login to Expensify:**
    ```
    /allowances login <your_expensify_partner_id> <your_expensify_password_or_secret>
    ```
    
*   **List Categories:**
    ```
    /allowances categories
    ```
    Shows the Expensify categories relevant to your allowances.

*   **Check Status:**
    ```
    /allowances balance
    ```
    Displays your current allowance status based on Expensify data.

*   **Get Help:**
    ```
    /allowances help
    ```
    Shows the available commands.

## Contributing

Please refer to `CONTRIBUTING.md` for details (if applicable).

## License

This project is licensed under the [MIT License](LICENSE.md) (or specify your chosen license).
