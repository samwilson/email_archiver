<?php
require_once 'common.php';
header("Content-Type:text/plain");
if (!empty($_REQUEST['year'])) $year = $_REQUEST['year']; else $year = date('Y');

$ppl = $db->fetchAll("SELECT * FROM people ORDER BY name ASC");
foreach ($ppl as $person) {
    $people[$person['id']] = $person['name'];
}

echo "\documentclass{book}
\\usepackage[a4paper,margin=2cm]{geometry}
\\usepackage[T1]{fontenc}
\\usepackage{alltt}
\\title{Emails}
\author{}
\date{".$year."}
\setlength{\parindent}{0cm}
\begin{document}
\maketitle
\\tableofcontents

";

foreach ($people as $person_id=>$person_name) {
    $sql = "SELECT * FROM emails
        WHERE YEAR(date_and_time)=".$db->esc($year)."
        AND (
                to_id = ".$db->esc($person_id)."
                OR from_id = ".$db->esc($person_id)."
        )
        ORDER BY date_and_time ASC";
    $emails = $db->fetchAll($sql);
    if (count($emails)>0 && $person_id!=9) {
        echo "\chapter{".texEsc($person_name)."}\n";
        foreach ($emails as $email) {
            echo "\\textbf{".trim(texEsc($people[$email['from_id']])).", ".date('l, F jS, g:iA',strtotime($email['date_and_time'])).".}\n\n";
            echo "\\textbf{".texEsc($email['subject'])."}\n\n";
            echo wordwrap(trim(texEsc($email['message_body'])))."\n\n";
            echo "\\vspace{0.3cm}\n";
        }
    }
}

echo "\end{document}";

function texEsc($str) {
    $pat = array('/\\\(\s)/',          '/\\\(\S)/',         '/&/', '/%/', '/\$/',        '/>>/',                       '/_/', '/\^/', '/#/', '/"(\s)/',           '/"(\S)/'         );
    $rep = array('\textbackslash\ $1', '\textbackslash $1', '\&',  '\%',  '\textdollar ', '\textgreater\textgreater ', '\_',  '\^', '\#',  '\textquotedbl\ $1', '\textquotedbl $1');
    return preg_replace($pat, $rep, $str);
}

?>