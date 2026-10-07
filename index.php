<?php

/*
|--------------------------------------------------------------------------
| TEMPORARY ERROR DISPLAY
|--------------------------------------------------------------------------
| This helps us see the real error instead of HTTP 500.
*/

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/

session_start();


/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database.php';


/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    /*
    | Check empty fields
    */

    if ($username === '' || $password === '') {

        $error = 'Please enter your username and password.';

    } else {

        try {

            /*
            | Find user
            */

            $statement = database()->prepare(
                'SELECT id, username, password_hash, role
                 FROM users
                 WHERE username = :username
                 LIMIT 1'
            );

            $statement->execute([
                'username' => $username
            ]);

            $user = $statement->fetch();


            /*
            | Verify password
            */

            if (
                $user &&
                password_verify(
                    $password,
                    $user['password_hash']
                )
            ) {

                /*
                | Create new session ID
                */

                session_regenerate_id(true);


                /*
                | Save user information
                */

                $_SESSION['user_id'] = (int) $user['id'];

                $_SESSION['username'] = $user['username'];

                $_SESSION['role'] = $user['role'];


                /*
                | Activity Log
                */

                log_activity(
                    (int) $user['id'],
                    'Signed in'
                );


                /*
                | Go to Dashboard
                */

                header('Location: dashboard.php');

                exit;

            } else {

                $error = 'Invalid username or password.';
            }

        } catch (PDOException $e) {

            /*
            | Display database error while troubleshooting
            */

            $error =
                'Database Error: ' .
                $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login Form</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>

    <div class="login-container">


        <!-- LOGIN FORM -->

        <form
            class="login-form"
            method="post"
            action="index.php"
        >

            <img
                src="OIP.webp"
                alt="Let good time roll logo"
                class="auth-logo"
            >

            <!-- TITLE -->

            <h2>Login</h2>


            <!-- ERROR MESSAGE -->

            <?php if ($error !== ''): ?>

                <p class="form-error">

                    <?php
                    echo htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                </p>

            <?php endif; ?>


            <!-- USERNAME -->

            <input
                type="text"
                name="username"
                placeholder="Username"
                autocomplete="username"
                required
            >


            <!-- PASSWORD -->

            <input
                type="password"
                name="password"
                placeholder="Password"
                autocomplete="current-password"
                required
            >


            <!-- LOGIN BUTTON -->

            <button type="submit">
                Login
            </button>


            <!-- REGISTER -->

            <p>

                Don't have an account?

                <a href="register.php">
                    Register
                </a>

            </p>


        </form>


        <!-- DEMO ACCOUNTS -->

        <aside
            class="demo-accounts"
            aria-label="Demo account credentials"
        >


            <div class="demo-account">

                <strong>
                    Admin Account
                </strong>

                <span>
                    Username:
                    <code>admin</code>
                </span>

                <span>
                    Password:
                    <code>Admin@12345</code>
                </span>

            </div>


            <div class="demo-account">

                <strong>
                    Staff Account
                </strong>

                <span>
                    Username:
                    <code>joe123</code>
                </span>

                <span>
                    Password:
                    <code>joey5652</code>
                </span>

            </div>


            <div class="demo-account">

                <strong>
                    User Account
                </strong>

                <span>
                    Username:
                    <code>yonzon12</code>
                </span>

                <span>
                    Password:
                    <code>yonzon5652</code>
                </span>

            </div>


        </aside>


    </div>

</body>

</html>
