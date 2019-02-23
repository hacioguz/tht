<?php

namespace o;

class u_Test extends StdModule {

    private $lastParserError = '';

    private $out = [];

    var $stats = [
        'numPassed' => 0,
        'numFailed' => 0,
    ];

    static function u_new () {
        return new u_Test ();
    }

    function u_section($s) {
        $this->out []= [ 'section' => $s ];
    }

    function u_stats() {
        $s = OMap::create($this->stats);
        $s['total'] = $s['numPassed'] + $s['numFailed'];
        return $s;
    }

    function u_ok ($expression, $msg) {
        $isOk = $expression ? true : false;
        $this->stats[$isOk ? 'numPassed' : 'numFailed'] += 1;
        $this->addLine($isOk, $msg);
        return $isOk;
    }

    function addLine($result, $msg) {
        $this->out []= [ 'msg' => $msg, 'result' => $result ];
    }

    function u_dies ($callback, $msg) {
        $ex = false;
        ErrorHandler::startTrapErrors();
        try {
            $callback();
        } catch (\Exception $e) {
            $ex = true;
        }
         catch (\TypeError $e) {
            $ex = true;
        }

        $trapped = ErrorHandler::endTrapErrors();

        return $this->u_ok($ex || $trapped, 'dies - ' . $msg);
    }

    function parserDies ($code, $match, $isFuzzy = false) {

        $matchError = false;
        $this->lastParserError = '';
        try {
            Tht::module('Meta')->u_parse($code);
        } catch (\Exception $e) {
            $this->lastParserError = $e->getMessage();
            $matchError = strpos(strtolower($e->getMessage()), strtolower($match));
            if (!$matchError && !$isFuzzy) {
                // allow for matching of backticks without needing to escape
                $match = str_replace("'", "`", $match);
                return $this->parserDies($code, $match, true);
            }
        }
        return $matchError;
    }

    function u_parser_error ($code, $match, $msg=null) {
        $dies = $this->parserDies($code, $match);
        $msg = str_replace("\n", "\\n", $code) . ' | error: ' . $match;
        return $this->u_ok($dies, $msg);
    }

    function u_parser_ok ($code, $msg) {
        $dies = false;
        $err = '';
        try {
            Tht::module('Meta')->u_parse($code);
        } catch (\Exception $e) {
            $dies = true;
            $err = $e->getMessage();
        }
        $msg = str_replace("\n", "\\n", $code) . ' | ok: ' . $msg;
        if ($dies) {
            $msg .= " | GOT: " . $err;
        }

        return $this->u_ok(!$dies, $msg);
    }

    function u_last_parser_error() {
        return $this->lastParserError;
    }

    function u_results_html () {

        $this->u_section('Results');

        $str = '<style> .test-result { font-family:' . Tht::module('Css')->u_monospace_font() . "}\n\n </style>\n\n";
        foreach ($this->out as $l) {
            if (isset($l['section'])) {
                if (Tht::isMode('web')) {
                    $str .= '<a name="test-' . v($l['section'])->u_to_token_case() . '"></a>';
                    $str .= "<h2>" . $l['section'] . "</h2>\n";
                } else {
                    $str .= "\n# " . $l['section'] . "\n\n";
                }
            } else {
                $msg = $l['msg'];
                $fmtResult = $l['result'] ? '(OK)' : 'FAIL';
                if (Tht::isMode('web')) {
                    $color = $l['result'] ? '#090' : '#c33';
                    $str .= "<div class='test-result'><b style='color:$color'>" . $fmtResult . '</b>  ' . htmlspecialchars($msg) . "</div>";
                } else {
                    $str .= '  ' . $fmtResult . '  ' . $msg . "\n";
                }
            }
        }

        if (Tht::isMode('web')) {
            $str .= "<div style='font-size: 150%; margin: 2rem 0'>\n";
            $str .= "Passed:  <b style='color: #393'>" . $this->stats['numPassed'] . "</b> &nbsp;\n";
            $str .= "Failed:  <b style='color: #e33'>" . $this->stats['numFailed'] . "</b>\n\n";
            $str .= "</div>\n\n";
        } else {
            $str .= "Passed:  " . $this->stats['numPassed'] . "\n";
            $str .= "Failed:  " . $this->stats['numFailed'] . "\n\n";
        }

        return new HtmlLockString ($str);
    }

    function u_check_args() {
        $args = func_get_args();
        $mask = array_shift($args);
        ARGS($mask, $args);
        return true;
    }
}

