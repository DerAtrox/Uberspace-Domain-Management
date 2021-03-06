<?php
//exit;
error_reporting(0);

if (file_exists("config.php")) {
    unlink("install.php");
    header("Location: index.php");
}

if (isset($_COOKIE["msg"])) {
    $msg = $_COOKIE["msg"];
    setcookie("msg", "", 0, "/");
}

include("header.php");
include("functions/main.php");
?>

<div class="container">
    <h1>Installation
        <small>Uberspace Domain Management</small>
    </h1>
    <hr>

    <?php
    include("message.php");

    if (isset($_POST["install"]) && isset($_POST["username"]) && isset($_POST["password1"]) && isset($_POST["password2"]) && isset($_POST["mysql_host"]) && isset($_POST["mysql_user"]) && isset($_POST["mysql_pass"]) && isset($_POST["mysql_data"]) && isset($_POST["mysql_pref"]) && isset($_POST["uberspacen"])) {
        $username = $_POST["username"];
        $password1 = $_POST["password1"];
        $password2 = $_POST["password2"];

        $mysql_host = $_POST["mysql_host"];
        $mysql_user = $_POST["mysql_user"];
        $mysql_pass = $_POST["mysql_pass"];
        $mysql_data = $_POST["mysql_data"];
        $mysql_pref = $_POST["mysql_pref"];

        $ubr = $_POST["uberspacen"];

        if ($password1 != $password2) {
            setcookie("msg", "I01", time() + 60, "/");
            header("Location: install.php");
        }

        if (!preg_match("#^[a-zA-Z0-9_]*$#", $mysql_pref)) {
            setcookie("msg", "I02", time() + 60, "/");
            header("Location: install.php");
        }

        $salt = "";
        $password = makeHashSecure($password1, $salt);

        $connected = true;

        try {
            $pdo = new PDO('mysql:host=' . $mysql_host . ';dbname=' . $mysql_data, $mysql_user, $mysql_pass);
        } catch (PDOException $e) {
            echo '<div class="text-center alert alert-danger">Keine Verbindung zum MySQL Server möglich! (' . $e->getMessage() . '</div>';
            $connected = false;
            include("footer.php");
            exit;
        }

        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        if ($connected) {
            $s = $pdo->prepare("CREATE TABLE " . $mysql_pref . "users (id int(255) NOT NULL auto_increment,username varchar(256) NOT NULL,password varchar(128) NOT NULL, salt VARCHAR(256) NOT NULL, PRIMARY KEY (id))");
            $s->execute();

            $s = $pdo->prepare("CREATE TABLE " . $mysql_pref . "domains (id int(255) NOT NULL auto_increment,domain varchar(256) NOT NULL, PRIMARY KEY (id))");
            $s->execute();

            $s = $pdo->prepare("INSERT INTO " . $mysql_pref . "users (username, password, salt) VALUES (:username, :password, :salt)");
            $s->execute(array('username' => $username, 'password' => $password, 'salt' => $salt));

            /* CONFIG CREATION */
            {
                $fp = fopen('config.php', 'w');
                fputs($fp, '<?php
// main config section of uberspace domain management //

// general //
$uberspacename = "' . $ubr . '";

// mysql //
$username = "' . $mysql_user . '";
$password = "' . $mysql_pass . '";
$hostname = "' . $mysql_host . '";
$database = "' . $mysql_data . '";
$tablepre = "' . $mysql_pref . '";
?>
');
                fclose($fp);
            }

            unlink("install.php");

            setcookie("msg", "I00", time() + 60, "/");
            header("Location: index.php");
            exit;
        }
    }

    $ubr = explode("/", str_replace("/var/www/virtual/", "", realpath("install.php")))[0];
    ?>
    <div class="text-center alert alert-info">Die Installation ist einfach. Trage unten deinen Uberspace Nutzernamen,
        deine MySQL Logindaten sowie belibiege Logindaten, die du später zum einloggen in das Domain Management
        brauchst, ein und drücke auf <i>Installation starten</i>.
    </div>
    <hr>
    <div class="col-md-6">
        <form action="install.php" method="post" class="form-horizontal">
            <input type="hidden" name="install">

            <h3>Uberspace Informationen</h3>

            <div class="form-group">
                <label for="uberspacen" class="col-sm-3 control-label">Name</label>

                <div class="col-sm-9">
                    <input type="text" class="form-control" id="uberspacen" placeholder="Uberspace Nutzername"
                           name="uberspacen" required value="<?php echo $ubr; ?>">
                </div>
            </div>
            <hr>
            <h3>MySQL Logindaten</h3>

            <div class="form-group">
                <label for="mysql_host" class="col-sm-3 control-label">Hostname</label>

                <div class="col-sm-9">
                    <input type="text" class="form-control" id="mysql_host" placeholder="localhost" name="mysql_host"
                           required value="localhost">
                </div>
            </div>
            <div class="form-group">
                <label for="mysql_user" class="col-sm-3 control-label">Nutzername</label>

                <div class="col-sm-9">
                    <input type="text" class="form-control" id="mysql_user" placeholder="MySQL Nutzername"
                           name="mysql_user" required value="<?php echo $ubr; ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="mysql_pass" class="col-sm-3 control-label">Passwort</label>

                <div class="col-sm-9">
                    <input type="password" class="form-control" id="mysql_pass" placeholder="MySQL Passwort"
                           name="mysql_pass" required>
                </div>
            </div>
            <div class="form-group">
                <label for="mysql_data" class="col-sm-3 control-label">Datenbank</label>

                <div class="col-sm-9">
                    <input type="text" class="form-control" id="mysql_data" placeholder="MySQL Datenbank"
                           name="mysql_data" required value="<?php echo $ubr; ?>_">
                </div>
            </div>
            <div class="form-group">
                <label for="mysql_pref" class="col-sm-3 control-label">Tabellenprefix</label>

                <div class="col-sm-9">
                    <input type="text" class="form-control" id="mysql_data" placeholder="domain_"
                           name="mysql_pref" value="domain_">
                </div>
            </div>
            <hr>
            <h3>Logindaten</h3>

            <div class="form-group">
                <label for="username" class="col-sm-3 control-label">Nutzername</label>

                <div class="col-sm-9">
                    <input type="text" class="form-control" id="username" placeholder="Gewünschter Nutzername"
                           name="username" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password1" class="col-sm-3 control-label">Passwort</label>

                <div class="col-sm-9">
                    <input type="password" class="form-control" id="password1" placeholder="Gewünschtes Passwort"
                           name="password1" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password2" class="col-sm-3 control-label"></label>

                <div class="col-sm-9">
                    <input type="password" class="form-control" id="password2"
                           placeholder="Gewünschtes Passwort wiederholen" name="password2" required>
                </div>
            </div>
            <hr>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-lg btn-primary">Installation starten</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="container">
    <?php include("footer.php"); ?>
</div>