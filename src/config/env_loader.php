<?php
function loadEnv()
{
    $path = dirname(__DIR__, 2) . '/.env';

    if (!file_exists($path)) {
        // Create template .env file if it doesn't exist
        file_put_contents(
            $path,
            "ADAFRUIT_API_KEY=your_adafruit_key_here\n" .
                "ADAFRUIT_USERNAME=your_username_here\n"
        );
        die("Please configure your .env file with your Adafruit credentials.");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Automatically load environment variables when included
loadEnv();
