<?php
require_once 'config.php';

// Don't report errors over which we have no control.
$old_error_reporting = error_reporting();
error_reporting(0);
require_once 'MDB2.php';
require_once 'Auth.php';
require_once 'HTML/Page2.php';
require_once 'HTML/CSS.php';
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/select.php';
require_once 'HTML/QuickForm/submit.php';
require_once 'HTML/QuickForm/textarea.php';
require_once 'HTML/QuickForm/link.php';
require_once 'Net/IMAP.php';
error_reporting($old_error_reporting);

// Set up database.
$db = new Database($dsn);

// Set up HTML page object.
$page = new HTML_Page2();
$page->setDoctype('XHTML 1.0 Strict');
$page->addStylesheet('screen.css', 'text/css', 'screen');
$page->setTitle(SITETITLE);

// Javascript stuff.
$page->addScript('scripts.js');
$page->setAttribute('onload', 'onBodyLoad()');

// Set up structure for BlueprintCSS.
$page->addBodyContent('<div class="container">');
// Don't forget to add the following at the end of every script:
#$page->addBodyContent('</div><!-- end div.container -->');
#$page->display();


$css = new HTML_CSS();
$page->addStyleDeclaration($css);


// Authentication.
$options = array(
    'enableLogging' => true,
    'cryptType' => 'sha1',
    'users' => $users,
);
$auth = new Auth('Array', $options, 'login_form');
$auth->start();
if (isset($_GET['logout'])) {
    $auth->logout();
}
if ($auth->checkAuth()) {
    $page->addBodyContent('<p class="centre">&bull; <a href="index.php">Emails</a> &bull; <a href="inbox.php">Inbox</a> &bull; <a href="people.php">People</a> ');
    $page->addBodyContent(' &bull; <a href="?logout">Logout</a>');
    $page->addBodyContent(' &bull;</p>');
} else {
    show_login_form();
}




class Database {

    /** @var MDB2_Driver_Common The database abstraction object. */
    var $mdb2;

    /**
     * Create a new Database interface object.
     *
     * @param $dsn array|string The database Data Source Name, as used by MDB2.
     */
    function __construct($dsn) {
        $this->mdb2 = MDB2::connect($dsn);
        if ($this->mdb2 instanceof MDB2_Error || $this->mdb2 === false) {
            error($this->mdb2);
        }
        $this->mdb2->setFetchMode(MDB2_FETCHMODE_ASSOC);

    }

    /**
     * Delete a record from a table.
     *
     * @global <type> $page
     * @param <type> $tbl
     * @param <type> $id
     */
    function delete($tbl, $id) {
        global $page;
        if (isset($_REQUEST['delete_confirmation'])) {
            $this->query(
                    "DELETE FROM ".$this->esc($tbl)." WHERE id=".$this->esc($id)
            );
            $page->addBodyContent(
                    "<div class='success'>
                    <p>
                        Record deleted.
                        <a href='".$_SERVER['PHP_SELF']."'>Continue &raquo;</a>
                    </p>
                </div>"
            );
        } else {
            $page->addBodyContent($this->getHtmlForm());
        }
        $page->display();
        die();
    }

    /**
     *
     * @param <type> $sql
     * @return <type>
     */
    function query($sql) {
        $res = $this->mdb2->query($sql);
        if (PEAR::isError($res)) {
            error($res);
        } else {
            return $res;
        }
    }

    function tableExists($tbl) {
        return in_array($tbl, $this->getTables());
    }

    function getTables() {
        $tables = array();
        foreach ($this->fetchAll("SHOW TABLES") as $tbl) {
            $tbl = array_values($tbl);
            $tables[] = $tbl[0];
        }
        return $tables;
    }

    function getJoinTables($joined_to) {
        $join_syntax = '_to_';
        $out = array();
        foreach ($this->getTables() as $tbl) {
            if (stristr($tbl, $joined_to.$join_syntax)) {
                $join_table = $tbl;
                $joined_table = substr($tbl, strlen($joined_to.$join_syntax));
                $out[$join_table] = $joined_table;
            } elseif (stristr($tbl, $join_syntax.$joined_to)) {
                $join_table = $tbl;
                $joined_table = substr($tbl, 0, -(strlen($join_syntax.$joined_to)));
                $out[$join_table] = $joined_table;
            }
        }
        return $out;
    }

    function getVar($table, $id, $field) {
        $r = $this->query("SELECT $field AS x FROM $table WHERE id=".$this->esc($id));
        $row = $r->fetchRow();
        return $row['x'];
    }

    function numRows($table) {
        $r = $this->fetchAll("SELECT COUNT(*) AS num FROM $table");
        return $r[0]['num'];
    }

    function fetchAll($sql) {
        $r = $this->query($sql);
        $results = $r->fetchAll();
        return $results;
    }

    function fetchRow($sql) {
        $r = $this->query($sql);
        $results = $r->fetchRow();
        return $results;
    }

    function fetchOne($sql) {
        $r = $this->query($sql);
        $results = $r->fetchOne();
        return $results;
    }

    function esc($str) {
        return $this->mdb2->escape($str);
    }

    /**
     *
     * @global <type> $page
     * @param <type> $tbl
     * @param <type> $data
     * @return integer|false ID of updated/inserted record, or false.
     */
    function save($tbl, $data) {
        global $page;
        $fields = $this->getFieldNames($tbl);
        if ( isset($data['id']) && is_numeric($data['id']) && $data['id']>0 ) {
            $sql = "UPDATE `$tbl` SET ";
        } else {
            $sql = "INSERT INTO `$tbl` SET ";
        }
        foreach ($fields as $field) {
            if (isset($data[$field]) && $field!='id') {
                if ($this->getFieldType($tbl,$field)=='int(1)') {
                    $field_data = $this->stringToOneOrZero($data[$field]);
                } else {
                    $field_data = $data[$field];
                }
                $sql .= "`$field` = '".$this->esc($field_data)."', ";
            }
        }
        $sql = substr($sql, 0, -2); // Remove last comma-space.
        if ( isset($data['id']) && is_numeric($data['id']) && $data['id']>0 ) {
            $sql .= " WHERE id='".$this->esc($data['id'])."'";
        }
        if ($result = $this->query($sql)) {
            if (!empty($_REQUEST['return_to'])) {
                header('Location:'.$_REQUEST['return_to']);
            }
            if ( isset($data['id']) && is_numeric($data['id']) && $data['id']>0 ) {
                return $data['id'];
            } else {
                return $this->mdb2->lastInsertID();
            }
        } else {
            return false;
        }
    }

    function stringToOneOrZero($str) {
        if ( $str != ''
             && $str != null
             && !empty($str)
             && isset($str)
             && strcasecmp($str, 'false') != 0
             && strcasecmp($str, 'off') != 0
        ) {
            return 1;
        } else {
            return 0;
        }
    }

    function getFieldNames($tbl) {
        $sql = "DESCRIBE ".$this->esc($tbl);
        $res = $this->mdb2->query($sql);
        if (PEAR::isError($res)) {
            die($res->message."<br>Table: $tbl");
        } else {
            $desc = $res->fetchAll();
            $fields = array();
            foreach ($desc as $col) {
                $fields[] = $col['field'];
            }
            return $fields;
        }
    }

    function getFieldType($tbl, $field) {
        $sql = "DESCRIBE ".$this->esc($tbl);
        $res = $this->query($sql);
        $desc = $res->fetchAll();
        foreach ($desc as $col) {
            if ($col['field']==$field) {
                return $col['type'];
            }
        }
        return false;
    }

    function getReferencedTableName($tbl, $field) {
        $info = $this->fetchAll("SHOW CREATE TABLE ".$this->esc($tbl));
        preg_match("|FOREIGN KEY \(`$field`\) REFERENCES `(.*?)`|", $info[0]['create table'], $matches);
        if (isset($matches[1])) {
            return $matches[1];
        } else {
            return false;
        }
    }

    function getDefaultFieldValues($tbl) {
        $out = array();
        $sql = "DESCRIBE ".$this->esc($tbl);
        $res = $this->query($sql);
        foreach ($res->fetchAll() as $field_desc) {
            if ( $field_desc['type']=='datetime' && empty($field_desc['default']) ) {
                $field_desc['default'] = date('Y-m-d H:i:s');
            }
            $out[$field_desc['field']] = $field_desc['default'];
        }
        return $out;
    }

}


function error($error) {
    $page = new HTML_Page2();
    $title = 'Error ' . $error->getCode() . ': ' . $error->getType();
    $page->setTitle($title);
    $page->addStyleDeclaration("
        body { background-color:darkslategray; color:yellow; margin:3em }
        table { border:1px solid yellow; border-collapse:collapse }
        th { text-align:left; border-bottom:1px solid yellow }
    ");
    //ob_start();
    //print_r($error->getBacktrace());
    //$backtrace = ob_get_clean();
    $page->addBodyContent("
        <h1>$title</h1>
        <p><strong>" . $error->getMessage() . "</strong></p>
        <h2>Debug Information</h2>
        <pre>" . $error->getDebugInfo() . "</pre>
        <h2>Backtrace</h2>
        <table style='width:100%'>
            <tr>
                <th>File</th>
                <th>Line</th>
                <th>Class</th>
                <th>Function</th>
            </tr>");
    foreach ($error->getBacktrace() as $bt) {
        $file = (isset($bt['file'])) ? $bt['file'] : '';
        $line = (isset($bt['line'])) ? $bt['line'] : '';
        $class = (isset($bt['class'])) ? $bt['class'] : '';
        $function = (isset($bt['function'])) ? $bt['function'] : '';
        $page->addBodyContent("<tr>
            <td>$file</td>
            <td>$line</td>
            <td>$class</td>
            <td>$function</td>
            </tr>");
    }
    $page->addBodyContent("</table>");
    $page->display();
    die();
}



function show_login_form() {
    global $auth, $page;
    $page->setTitle('Please Log In');
    $page->addStyleDeclaration('#login-form {margin:10% auto; width:20%;}');
    $page->addBodyContent(login_form($auth->getUsername(), $auth->getStatus(), $auth));
    $page->display();
    die();
}



function login_form($username = null, $status = null, &$auth = null) {

    if ($status==AUTH_EXPIRED) {
        $status = 'Your session has expired. Please login again.';
    } elseif ($status== AUTH_IDLED) {
        $status = 'You have been idle for too long.  Please login again.';
    } elseif ($status==AUTH_WRONG_LOGIN) {
        $status = 'Incorrect username or password.';
    } elseif ($status==AUTH_SECURITY_BREACH) {
        $status = 'A security problem was detected.  Please login again.';
    } else {
        $status = 'Please log in.';
    }
    $form = new HTML_QuickForm('login-form','post',$_SERVER['PHP_SELF']);
    $form->removeAttribute('name');
    $form->addElement('header','',$status);
    $form->addElement('text','username','Username: ',array('id'=>'focus-me'));
    $form->addElement('password','password','Password: ');
    $form->addElement('submit', 'login', 'Login');
    return $form;
}
