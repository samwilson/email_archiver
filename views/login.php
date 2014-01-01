<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <title>Log In</title>
        <link rel="stylesheet" href="screen.css" media="all" />
    </head>
    <body class="login-page">

        <h1 class="centre">Log In</h1>


        <form action="login.php" method="post" class="login-form">
            <table>
                <?php if ($login_failed): ?>
                <tr>
                    <td colspan="2" class="notice message login-failed">
                        Login failed; please try again.
                    </td>
                </tr>
                <?php endif ?>
                <tr>
                    <td><label for="username">Username:</label></td>
                    <td>
                        <input id="username" type="text" name="username" />
                        <?php echo htmlentities($mail_server['suffix']) ?>
                    </td>
                </tr>
                <tr>
                    <td><label for="password">Password:</label></td>
                    <td><input id="password" type="password" name="password" /></td>
                </tr>
                <tr>
                    <td></td>
                    <td><input type="submit" name="login" value="Login" /></td>
                </tr>
            </table>
        </form>

    </body>
</html>
