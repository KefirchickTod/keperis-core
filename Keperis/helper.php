<?php

namespace Keperis;

use Keperis\Container;
use Keperis\Core\easyCreateHTML;
use Keperis\Http\Uri;
use Keperis\Router\Route;
use Keperis\Router\Router;

if (!function_exists('debug')) {
    /**
     * many argument
     * debug data with exit status
     *
     */
    function debug()
    {
        echo "<pre>";
        $debugInfo = debug_backtrace();
        foreach ($debugInfo as $info) {
            echo "File " . ($info['file'] ?? '') . ": <br>line: " . ($info['line'] ?? '') . "  <hr><br>";
        }
        $argc = func_get_args();
        foreach ($argc as $value) {

            var_dump($value);
            echo "</pre>";
        }
        exit;
    }
}

if (!function_exists('resource')) {

    /**
     * @param $names
     * @param array $param
     * @return string|\Keperis\View\View
     */
    function resource($names, $param = [])
    {


        return \Keperis\View\ViewFactory::make($names, $param);

//        static $resource;
//
//        if (!$resource) {
//            $resource = new Resource();
//        }
//        /**
//         * @var $response \Keperis\Http\Response
//         */
//        $response = \container()->get('response');
//        $resource->set(!is_array($names) ? [$names] : $names, $param);
//        return $resource;
    }
}

if (!function_exists('router')) {
    /**
     * @return Router
     * @throws Exception
     */
    function router()
    {
        return app()->container->get('router');
    }
}


if (!function_exists('html')) {
    function html()
    {
        static $html;
        if ($html && $html instanceof easyCreateHTML) {
            return $html->clean();
        }
        $html = easyCreateHTML::create();
        return $html;
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return \container()->env->get($key, $default);
    }
}

if (!function_exists('structure')) {
    function structure(): \Keperis\Eloquent\Provide\Structure
    {
        static $strucutre;
        if ($strucutre && $strucutre instanceof \Keperis\Eloquent\Provide\Structure) {
            return $strucutre;
        }
        $strucutre = new Keperis\Eloquent\Provide\Structure();
        return $strucutre;
    }
}


if (!function_exists('container')) {
    function container(): Container
    {
        return app()->container;
    }
}

if (!function_exists('uri')) {
    /**
     * @return Uri
     */
    function uri()
    {
        return \container()->get('request')->getUri();
    }
}


if (!function_exists('view')) {
    function view($name, $arg = [], ...$additional)
    {
        return resource('body', array_merge([
            'content' => resource($name, $arg),
        ], compact(...$additional)));
    }
}

if (!function_exists('post')) {
    function post($key, $dafault = null)
    {
        return \container()->get('request')->getParsedBody()[$key] ?? $dafault;
    }
}


if (!function_exists('setting')) {
    function setting(): \Keperis\Collection
    {
        return \container()->get('setting');
    }
}

if (!function_exists('closetags')) {
    function closetags($html)
    {
        preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openedtags = $result[1];

        preg_match_all('#</([a-z]+)>#iU', $html, $result);
        $closedtags = $result[1];
        $len_opened = count($openedtags);
        if (count($closedtags) == $len_opened) {
            return $html;
        }
        $openedtags = array_reverse($openedtags);
        for ($i = 0; $i < $len_opened; $i++) {
            if (!in_array($openedtags[$i], $closedtags)) {
                $html .= '</' . $openedtags[$i] . '>';
            } else {
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }
        return $html;
    }
}


if (!function_exists('change_key')) {
    function change_key($key, $new_key, &$arr, $rewrite = true)
    {
        if (!array_key_exists($new_key, $arr) || $rewrite) {
            $arr[$new_key] = $arr[$key];
            unset($arr[$key]);
            return true;
        }
        return false;
    }
}

if (!function_exists('route')) {
    function route($lookup, $parrams = [], string $pattern = null)
    {
        /** @var  $router  Route */
        /** @var $route Route */
        $router = container()->get('router');
        $route = $router->lookupRoute($lookup);
        $route = $pattern ? $route->withPattern($pattern) : $route;
        return $route->getPath($parrams);
    }
}


if (!function_exists('session')) {
    /**
     * @return \Keperis\Http\Session
     */

    function session()
    {
        return \container()->get('session');
    }
}

if (!function_exists('charsArray')) {
    function charsArray()
    {
        static $charsArray;

        if (isset($charsArray)) {
            return $charsArray;
        }

        return $charsArray = [
            '0'    => ['??', '???', '??', '???'],
            '1'    => ['??', '???', '??', '???'],
            '2'    => ['??', '???', '??', '???'],
            '3'    => ['??', '???', '??', '???'],
            '4'    => ['???', '???', '??', '??', '???'],
            '5'    => ['???', '???', '??', '??', '???'],
            '6'    => ['???', '???', '??', '??', '???'],
            '7'    => ['???', '???', '??', '???'],
            '8'    => ['???', '???', '??', '???'],
            '9'    => ['???', '???', '??', '???'],
            'a'    => [
                '??',
                '??',
                '???',
                '??',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '???',
                '???',
                '??',
                '???',
                '??',
                '??',
            ],
            'b'    => ['??', '??', '??', '???', '???', '???', '??'],
            'c'    => ['??', '??', '??', '??', '??', '???'],
            'd'    => ['??', '??', '??', '??', '??', '??', '??', '???', '???', '???', '??', '??', '??', '??', '???', '???', '???', '???', '??'],
            'e'    => [
                '??',
                '??',
                '???',
                '???',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '???',
            ],
            'f'    => ['??', '??', '??', '??', '???', '???', '??', '??'],
            'g'    => ['??', '??', '??', '??', '??', '??', '??', '???', '???', '??', '???', '??'],
            'h'    => ['??', '??', '??', '??', '??', '??', '???', '???', '???', '???', '??'],
            'i'    => [
                '??',
                '??',
                '???',
                '??',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '???',
                '???',
                '???',
                '??',
                '???',
                '???',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '??????',
                '??',
                '???',
                '???',
                '??',
                '???',
                '??',
            ],
            'j'    => ['??', '??', '??', '???', '??', '???'],
            'k'    => ['??', '??', '??', '??', '??', '??', '??', '???', '???', '???', '??', '???', '??'],
            'l'    => ['??', '??', '??', '??', '??', '??', '??', '??', '???', '???', '???', '??'],
            'm'    => ['??', '??', '??', '???', '???', '???', '??', '??'],
            'n'    => ['??', '??', '??', '??', '??', '??', '??', '??', '??', '???', '???', '???', '??'],
            'o'    => [
                '??',
                '??',
                '???',
                '??',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??????',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '??',
            ],
            'p'    => ['??', '??', '???', '???', '??', '???', '??', '??'],
            'q'    => ['???', '???'],
            'r'    => ['??', '??', '??', '??', '??', '??', '???', '???', '??'],
            's'    => ['??', '??', '??', '??', '??', '??', '??', '??', '??', '???', '??', '???', '???', '??'],
            't'    => ['??', '??', '??', '??', '??', '??', '??', '???', '???', '??', '???', '???', '???', '??'],
            'u'    => [
                '??',
                '??',
                '???',
                '??',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '??',
                '??',
            ],
            'v'    => ['??', '???', '??', '???', '??'],
            'w'    => ['??', '??', '??', '???', '???', '???'],
            'x'    => ['??', '??', '???'],
            'y'    => ['??', '???', '???', '???', '???', '??', '??', '??', '??', '??', '??', '??', '??', '??', '???', '???'],
            'z'    => ['??', '??', '??', '??', '??', '??', '???', '???', '???', '??'],
            'aa'   => ['??', '???', '??'],
            'ae'   => ['??', '??'],
            'ai'   => ['???'],
            'ch'   => ['??', '???', '???', '??'],
            'dj'   => ['??', '??'],
            'dz'   => ['??', '???', '????'],
            'ei'   => ['???'],
            'gh'   => ['??', '???'],
            'ii'   => ['???'],
            'ij'   => ['??'],
            'kh'   => ['??', '??', '???'],
            'lj'   => ['??'],
            'nj'   => ['??'],
            'oe'   => ['??', '??', '??'],
            'oi'   => ['???'],
            'oii'  => ['???'],
            'ps'   => ['??'],
            'sh'   => ['??', '???', '??', '??'],
            'shch' => ['??'],
            'ss'   => ['??'],
            'sx'   => ['??'],
            'th'   => ['??', '??', '??', '??', '??', '??'],
            'ts'   => ['??', '???', '???'],
            'ue'   => ['??'],
            'uu'   => ['???'],
            'ya'   => ['??'],
            'yu'   => ['??'],
            'zh'   => ['??', '???', '??'],
            '(c)'  => ['??'],
            'A'    => [
                '??',
                '??',
                '???',
                '??',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '???',
                '??',
                '??',
                '??',
                '???',
                '??',
            ],
            'B'    => ['??', '??', '???', '???'],
            'C'    => ['??', '??', '??', '??', '??', '???'],
            'D'    => ['??', '??', '??', '??', '??', '??', '???', '???', '??', '??', '???'],
            'E'    => [
                '??',
                '??',
                '???',
                '???',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
            ],
            'F'    => ['??', '??', '???'],
            'G'    => ['??', '??', '??', '??', '??', '??', '???'],
            'H'    => ['??', '??', '??', '???'],
            'I'    => [
                '??',
                '??',
                '???',
                '??',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
            ],
            'J'    => ['???'],
            'K'    => ['??', '??', '???'],
            'L'    => ['??', '??', '??', '??', '??', '??', '??', '???', '???'],
            'M'    => ['??', '??', '???'],
            'N'    => ['??', '??', '??', '??', '??', '??', '??', '???'],
            'O'    => [
                '??',
                '??',
                '???',
                '??',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '??',
            ],
            'P'    => ['??', '??', '???'],
            'Q'    => ['???'],
            'R'    => ['??', '??', '??', '??', '??', '???'],
            'S'    => ['??', '??', '??', '??', '??', '??', '??', '???'],
            'T'    => ['??', '??', '??', '??', '??', '??', '???'],
            'U'    => [
                '??',
                '??',
                '???',
                '??',
                '???',
                '??',
                '???',
                '???',
                '???',
                '???',
                '???',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '??',
                '???',
                '??',
                '??',
            ],
            'V'    => ['??', '???'],
            'W'    => ['??', '??', '??', '???'],
            'X'    => ['??', '??', '???'],
            'Y'    => ['??', '???', '???', '???', '???', '??', '???', '???', '???', '??', '??', '??', '??', '??', '??', '???'],
            'Z'    => ['??', '??', '??', '??', '??', '???'],
            'AE'   => ['??', '??'],
            'Ch'   => ['??'],
            'Dj'   => ['??'],
            'Dz'   => ['??'],
            'Gx'   => ['??'],
            'Hx'   => ['??'],
            'Ij'   => ['??'],
            'Jx'   => ['??'],
            'Kh'   => ['??'],
            'Lj'   => ['??'],
            'Nj'   => ['??'],
            'Oe'   => ['??'],
            'Ps'   => ['??'],
            'Sh'   => ['??', '??'],
            'Shch' => ['??'],
            'Ss'   => ['???'],
            'Th'   => ['??', '??', '??'],
            'Ts'   => ['??'],
            'Ya'   => ['??', '????'],
            'Yu'   => ['??', '????'],
            'Zh'   => ['??'],
            ' '    => [
                "\xC2\xA0",
                "\xE2\x80\x80",
                "\xE2\x80\x81",
                "\xE2\x80\x82",
                "\xE2\x80\x83",
                "\xE2\x80\x84",
                "\xE2\x80\x85",
                "\xE2\x80\x86",
                "\xE2\x80\x87",
                "\xE2\x80\x88",
                "\xE2\x80\x89",
                "\xE2\x80\x8A",
                "\xE2\x80\xAF",
                "\xE2\x81\x9F",
                "\xE3\x80\x80",
                "\xEF\xBE\xA0",
            ],
        ];
    }
}

if (!function_exists('languageSpecificCharsArray')) {
    function languageSpecificCharsArray($language)
    {
        static $languageSpecific;

        if (!isset($languageSpecific)) {
            $languageSpecific = [
                'bg' => [
                    ['??', '??', '??', '??', '??', '??', '??', '??'],
                    ['h', 'H', 'sht', 'SHT', 'a', '??', 'y', 'Y'],
                ],
                'da' => [
                    ['??', '??', '??', '??', '??', '??'],
                    ['ae', 'oe', 'aa', 'Ae', 'Oe', 'Aa'],
                ],
                'de' => [
                    ['??', '??', '??', '??', '??', '??'],
                    ['ae', 'oe', 'ue', 'AE', 'OE', 'UE'],
                ],
                'he' => [
                    ['??', '??', '??', '??', '??', '??'],
                    ['??', '??', '??', '??', '??', '??'],
                    ['??', '??', '??', '??', '??', '??'],
                    ['??', '??', '??', '??', '??', '??', '??', '??', '??'],
                ],
                'ro' => [
                    ['??', '??', '??', '??', '??', '??', '??', '??', '??', '??'],
                    ['a', 'a', 'i', 's', 't', 'A', 'A', 'I', 'S', 'T'],
                ],
            ];
        }

        return $languageSpecific[$language] ?? null;
    }
}

if (!function_exists('ascii')) {
    function ascii($value, $language = 'en')
    {
        $languageSpecific = languageSpecificCharsArray($language);

        if (!is_null($languageSpecific)) {
            $value = str_replace($languageSpecific[0], $languageSpecific[1], $value);
        }

        foreach (charsArray() as $key => $val) {
            $value = str_replace($val, $key, $value);
        }

        return preg_replace('/[^\x20-\x7E]/u', '', $value);
    }
}

if (!function_exists('slug')) {

    function slug($title, $separator = '-', $language = 'en')
    {
        $title = $language ? ascii($title, $language) : $title;

        // Convert all dashes/underscores into separator
        $flip = $separator === '-' ? '_' : '-';

        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);

        // Replace @ with the word 'at'
        $title = str_replace('@', $separator . 'at' . $separator, $title);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', strtolower($title));

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);

        return trim($title, $separator);
    }
}