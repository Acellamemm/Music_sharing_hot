# FusionMix

This project is a PHP/MySQL DJ community app with uploads, likes, comments, and SoundCloud embeds.

## Render deployment

Render can deploy this app as a PHP service using the included `Dockerfile`, or with a PHP runtime if you prefer.

### Recommended setup

1. Push your repo to GitHub.
2. On Render, create a new Web Service and connect your GitHub repo.
3. Select `Docker` as the environment if you want the included `Dockerfile` to be used.
4. Set the following environment variables in Render:

   - `DB_HOST`
   - `DB_USER`
   - `DB_PASSWORD`
   - `DB_NAME`

5. If you use a managed database on Render, point `DB_HOST` to the database host and set credentials accordingly.
6. Deploy and confirm the site loads.

### Local development

1. Copy `.env.example` to `.env` if you want local environment values.
2. Start a local MySQL database and ensure your `registration` database exists.
3. Run the app with a local PHP server or Apache.

### Notes

- `connection.php` now reads database credentials from environment variables.
- Do not commit `.env` to source control.
