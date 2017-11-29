<?php


class DateFormatConverter
{

    /**
     * @var array
     **/
    protected $ldmlToPhp = [];

    public function __construct(array $ldmlToPhp)
    {
        $this->ldmlToPhp = $this->escapePlaceHolders($ldmlToPhp);
    }

    public function convert($format)
    {

//         echo "\nTOKENIZED: ";

        $tokens = $this->tokenize($format);
//         print_r($tokens);

//         print_r($this->splitString($format));
        $parsed = [];

        foreach ($tokens as $token) {

            if (!$token) {
                $parsed[] = '';
                continue;
            }

            if (isset($this->ldmlToPhp[$token])) {
                $parsed[] = $this->ldmlToPhp[$token];
                continue;
            }

            $parsed[] = $token;
        }

//          print_r($tokens);
//          print_r($parsed);

        return implode($parsed);
        return $format;
    }

    public function convertDateTimeFormat($pattern, $dateFormat, $timeFormat)
    {
        $tokenized = implode($this->tokenize($pattern));

        return str_replace(['{1}', '{0}'], [$dateFormat, $timeFormat], $tokenized);
    }

    protected function tokenize($pattern, $locale='en') {

        // get format tokens
        $comment = false;
        $tokens  = [];
        $orig    = '';

        $split = $this->splitString($pattern);

        foreach ($split as $i=>$char) {

            if ($split[$i] == "'") {
                $comment = $comment ? false : true;
                if (isset($split[$i+1]) && ($split[$i+1] == "'")) {
                    $comment = $comment ? false : true;
                    $tokens[] = "\\'";
                    ++$i;
                }

                $orig = '';
                continue;
            }

            if ($comment) {
                $tokens[] = '\\' . $split[$i];
                $orig = '';
            } else {
                $orig .= $split[$i];
                if (!isset($split[$i+1]) || (isset($orig[0]) && ($orig[0] != $split[$i+1]))) {
                    $tokens[] = $orig;
                    $orig  = '';
                }
            }

        }

        return $tokens;

    }

    protected function splitString($string, $encoding='UTF-8')
    {

        $strlen = mb_strlen($string);
        $chars = [];

        while ($strlen) {
            $chars[] = mb_substr($string, 0, 1, $encoding); 
            $string = mb_substr($string, 1, $strlen, $encoding); 
            $strlen = mb_strlen($string); 
        }

        return $chars;
    }
    
    protected function escapePlaceHolders($ldmlToPhp)
    {

        $escaped = [];

        foreach ($ldmlToPhp as $ldml=>$php) {
            $escaped[$ldml] = $this->escapePlaceHolder($php);
        }

        return $escaped;
    }

    protected function escapePlaceHolder($placeHolder)
    {

        if (strpos($placeHolder, '{') !== 0) {
            return $placeHolder;
        }

        $escaped = [];

        foreach (str_split($placeHolder) as $char) {
            $escaped[] = '\\' . "$char";
        }

        return implode($escaped);

    }
}
