<?php

namespace Samwilson\EmailArchiver;

use Slim\Http\Request;
use Slim\Http\Response;

class LatexController extends Controller
{

    public function home(Request $request, Response $response, $args)
    {
        // Year.
        $year = $request->getAttribute('year', date('Y'));

        // People.
        $ppl = $this->db->query("SELECT id, name FROM people ORDER BY name ASC")->fetchAll();
        $people = [];
        foreach ($ppl as $person) {
            $people[$person['id']] = $person['name'];
        }

        // Start LaTeX output.
        $latex = "\documentclass{book}\n"
            ."\\usepackage[a4paper,margin=2cm]{geometry}\n"
            ."\\usepackage[T1]{fontenc}\n"
            ."\\usepackage{alltt}\n"
            ."\\title{Emails}\n"
            ."\author{" . $people[MAIN_USER_ID] . "}\n"
            ."\date{" . $year . "}\n"
            ."\setlength{\parindent}{0cm}\n"
            ."\begin{document}\n"
            ."\maketitle\n"
            ."\\tableofcontents\n";
        foreach ($people as $person_id => $person_name) {
            $sql = 'SELECT id, from_id, to_id, subject, date_and_time, message_body
                FROM emails
                WHERE YEAR(date_and_time) = :year
                AND (to_id = :person_id OR from_id = :person_id)
                ORDER BY date_and_time ASC';
            $params = [':year' => $year, ':person_id' => $person_id];
            $emailsQuery = $this->db->prepare($sql);
            $emailsQuery->execute($params);
            $emails = $emailsQuery->fetchAll();
            if (count($emails) > 0 && $person_id != MAIN_USER_ID) {
                $latex .= "\n\n\chapter{" . $this->texEsc($person_name) . "}\n";
                foreach ($emails as $email) {
                    $latex .= "\\textbf{" . $this->texEsc(trim($people[$email['from_id']])) . ": "
                        . date('l, F jS, g:iA', strtotime($email['date_and_time']))
                        . ".}\n\n"
                        . "\\textbf{" . $this->texEsc($email['subject']) . "}\n\n"
                        . '\\texttt{' . $this->texEsc(wordwrap(trim($email['message_body']), 78, "\n", true)) . "}\n\n"
                        . "\\vspace{0.3cm}\n";
                }
            }
        }

        $latex .= "\\end{document}\n";
        $response->getBody()->write($latex);
        return $response->withHeader('Content-Type', 'text/plain;charset=utf-8');
    }

    protected function texEsc($str)
    {
        $pat = [
            '/\\\(\s)/',
            '/\\\(\S)/',
            '/&/',
            '/%/',
            '/\$/',
            '/>>/',
            '/_/',
            '/\^/',
            '/#/',
            '/"(\s)/',
            '/"(\S)/',
            '/(*BSR_ANYCRLF)\R/'
        ];
        $rep = [
            '\textbackslash\ $1',
            '\textbackslash $1',
            '\&',
            '\%',
            '\textdollar ',
            '\textgreater\textgreater ',
            '\_',
            '\^',
            '\#',
            '\textquotedbl\ $1',
            '\textquotedbl $1',
            "\\\\\\\n"
        ];
        return preg_replace($pat, $rep, $str);
    }
}
