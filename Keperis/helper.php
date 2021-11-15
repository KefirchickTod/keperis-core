<?php


use Keperis\Container;
use Keperis\Core\easyCreateHTML;
use Keperis\Router\Router;
use Keperis\Structure\Structure;

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
function marshalUriFromSapi(array $server, array $headers)
{
    /**
     * Retrieve a header value from an array of headers using a case-insensitive lookup.
     *
     * @param array $headers Key/value header pairs
     * @param mixed $default Default value to return if header not found
     * @return mixed
     */
    $getHeaderFromArray = function (string $name, array $headers, $default = null) {
        $header = strtolower($name);
        $headers = array_change_key_case($headers, CASE_LOWER);
        if (array_key_exists($header, $headers)) {
            $value = is_array($headers[$header]) ? implode(', ', $headers[$header]) : $headers[$header];
            return $value;
        }

        return $default;
    };

    /**
     * Marshal the host and port from HTTP headers and/or the PHP environment.
     *
     * @return array Array of two items, host and port, in that order (can be
     *     passed to a list() operation).
     */
    $marshalHostAndPort = function (array $headers, array $server) use ($getHeaderFromArray) : array {
        /**
         * @param string|array $host
         * @return array Array of two items, host and port, in that order (can be
         *     passed to a list() operation).
         */
        $marshalHostAndPortFromHeader = function ($host) {
            if (is_array($host)) {
                $host = implode(', ', $host);
            }

            $port = null;

            // works for regname, IPv4 & IPv6
            if (preg_match('|\:(\d+)$|', $host, $matches)) {
                $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
                $port = (int)$matches[1];
            }

            return [$host, $port];
        };

        /**
         * @return array Array of two items, host and port, in that order (can be
         *     passed to a list() operation).
         */
        $marshalIpv6HostAndPort = function (array $server, ?int $port): array {
            $host = '[' . $server['SERVER_ADDR'] . ']';
            $port = $port ?: 80;
            if ($port . ']' === substr($host, strrpos($host, ':') + 1)) {
                // The last digit of the IPv6-Address has been taken as port
                // Unset the port so the default port can be used
                $port = null;
            }
            return [$host, $port];
        };

        static $defaults = ['', null];

        $forwardedHost = $getHeaderFromArray('x-forwarded-host', $headers, false);
        if ($forwardedHost !== false) {
            return $marshalHostAndPortFromHeader($forwardedHost);
        }

        $host = $getHeaderFromArray('host', $headers, false);
        if ($host !== false) {
            return $marshalHostAndPortFromHeader($host);
        }

        if (!isset($server['SERVER_NAME'])) {
            return $defaults;
        }

        $host = $server['SERVER_NAME'];
        $port = isset($server['SERVER_PORT']) ? (int)$server['SERVER_PORT'] : null;

        if (!isset($server['SERVER_ADDR'])
            || !preg_match('/^\[[0-9a-fA-F\:]+\]$/', $host)
        ) {
            return [$host, $port];
        }

        // Misinterpreted IPv6-Address
        // Reported for Safari on Windows
        return $marshalIpv6HostAndPort($server, $port);
    };

    $marshalRequestPath = function (array $server): string {
        // IIS7 with URL Rewrite: make sure we get the unencoded url
        // (double slash problem).
        $iisUrlRewritten = $server['IIS_WasUrlRewritten'] ?? null;
        $unencodedUrl = $server['UNENCODED_URL'] ?? '';
        if ('1' === $iisUrlRewritten && !empty($unencodedUrl)) {
            return $unencodedUrl;
        }

        $requestUri = $server['REQUEST_URI'] ?? null;

        if ($requestUri !== null) {
            return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
        }

        $origPathInfo = $server['ORIG_PATH_INFO'] ?? null;
        if (empty($origPathInfo)) {
            return '/';
        }

        return $origPathInfo;
    };

    $uri = \App\Keperis\Http\Uri::creat(new \App\Keperis\Http\ServerData($_SERVER));

    // URI scheme
    $scheme = 'http';
    $marshalHttpsValue = function ($https): bool {
        if (is_bool($https)) {
            return $https;
        }

        if (!is_string($https)) {
            throw new InvalidArgumentException(sprintf(
                'SAPI HTTPS value MUST be a string or boolean; received %s',
                gettype($https)
            ));
        }

        return 'on' === strtolower($https);
    };
    if (array_key_exists('HTTPS', $server)) {
        $https = $marshalHttpsValue($server['HTTPS']);
    } elseif (array_key_exists('https', $server)) {
        $https = $marshalHttpsValue($server['https']);
    } else {
        $https = false;
    }

    if ($https
        || strtolower($getHeaderFromArray('x-forwarded-proto', $headers, '')) === 'https'
    ) {
        $scheme = 'https';
    }
    $uri = $uri->withScheme($scheme);

    // Set the host
    [$host, $port] = $marshalHostAndPort($headers, $server);
    if (!empty($host)) {
        $uri = $uri->withHost($host);
        if (!empty($port)) {
            $uri = $uri->withPort($port);
        }
    }

    // URI path
    $path = $marshalRequestPath($server);

    // Strip query string
    $path = explode('?', $path, 2)[0];

    // URI query
    $query = '';
    if (isset($server['QUERY_STRING'])) {
        $query = ltrim($server['QUERY_STRING'], '?');
    }

    // URI fragment
    $fragment = '';
    if (strpos($path, '#') !== false) {
        [$path, $fragment] = explode('#', $path, 2);
    }
    return $uri->withPath($path)->withFragment($fragment)->withQuery($query);
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

if (!function_exists('get')) {

    function get($key, $default = null)
    {
        return valid($_GET, $key, $default);
//        $request = app()->container->get('request');
//        /** @var $request \App\Keperis\Http\Request */
//        return $request->getBody();
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
            '0'    => ['°', '₀', '۰', '０'],
            '1'    => ['¹', '₁', '۱', '１'],
            '2'    => ['²', '₂', '۲', '２'],
            '3'    => ['³', '₃', '۳', '３'],
            '4'    => ['⁴', '₄', '۴', '٤', '４'],
            '5'    => ['⁵', '₅', '۵', '٥', '５'],
            '6'    => ['⁶', '₆', '۶', '٦', '６'],
            '7'    => ['⁷', '₇', '۷', '７'],
            '8'    => ['⁸', '₈', '۸', '８'],
            '9'    => ['⁹', '₉', '۹', '９'],
            'a'    => [
                'à',
                'á',
                'ả',
                'ã',
                'ạ',
                'ă',
                'ắ',
                'ằ',
                'ẳ',
                'ẵ',
                'ặ',
                'â',
                'ấ',
                'ầ',
                'ẩ',
                'ẫ',
                'ậ',
                'ā',
                'ą',
                'å',
                'α',
                'ά',
                'ἀ',
                'ἁ',
                'ἂ',
                'ἃ',
                'ἄ',
                'ἅ',
                'ἆ',
                'ἇ',
                'ᾀ',
                'ᾁ',
                'ᾂ',
                'ᾃ',
                'ᾄ',
                'ᾅ',
                'ᾆ',
                'ᾇ',
                'ὰ',
                'ά',
                'ᾰ',
                'ᾱ',
                'ᾲ',
                'ᾳ',
                'ᾴ',
                'ᾶ',
                'ᾷ',
                'а',
                'أ',
                'အ',
                'ာ',
                'ါ',
                'ǻ',
                'ǎ',
                'ª',
                'ა',
                'अ',
                'ا',
                'ａ',
                'ä',
                'א',
            ],
            'b'    => ['б', 'β', 'ب', 'ဗ', 'ბ', 'ｂ', 'ב'],
            'c'    => ['ç', 'ć', 'č', 'ĉ', 'ċ', 'ｃ'],
            'd'    => ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ', 'ｄ', 'ד'],
            'e'    => [
                'é',
                'è',
                'ẻ',
                'ẽ',
                'ẹ',
                'ê',
                'ế',
                'ề',
                'ể',
                'ễ',
                'ệ',
                'ë',
                'ē',
                'ę',
                'ě',
                'ĕ',
                'ė',
                'ε',
                'έ',
                'ἐ',
                'ἑ',
                'ἒ',
                'ἓ',
                'ἔ',
                'ἕ',
                'ὲ',
                'έ',
                'е',
                'ё',
                'э',
                'є',
                'ə',
                'ဧ',
                'ေ',
                'ဲ',
                'ე',
                'ए',
                'إ',
                'ئ',
                'ｅ',
            ],
            'f'    => ['ф', 'φ', 'ف', 'ƒ', 'ფ', 'ｆ', 'פ', 'ף'],
            'g'    => ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ', 'ｇ', 'ג'],
            'h'    => ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ', 'ｈ', 'ה'],
            'i'    => [
                'í',
                'ì',
                'ỉ',
                'ĩ',
                'ị',
                'î',
                'ï',
                'ī',
                'ĭ',
                'į',
                'ı',
                'ι',
                'ί',
                'ϊ',
                'ΐ',
                'ἰ',
                'ἱ',
                'ἲ',
                'ἳ',
                'ἴ',
                'ἵ',
                'ἶ',
                'ἷ',
                'ὶ',
                'ί',
                'ῐ',
                'ῑ',
                'ῒ',
                'ΐ',
                'ῖ',
                'ῗ',
                'і',
                'ї',
                'и',
                'ဣ',
                'ိ',
                'ီ',
                'ည်',
                'ǐ',
                'ი',
                'इ',
                'ی',
                'ｉ',
                'י',
            ],
            'j'    => ['ĵ', 'ј', 'Ј', 'ჯ', 'ج', 'ｊ'],
            'k'    => ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ', 'ک', 'ｋ', 'ק'],
            'l'    => ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ', 'ｌ', 'ל'],
            'm'    => ['м', 'μ', 'م', 'မ', 'მ', 'ｍ', 'מ', 'ם'],
            'n'    => ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န', 'ნ', 'ｎ', 'נ'],
            'o'    => [
                'ó',
                'ò',
                'ỏ',
                'õ',
                'ọ',
                'ô',
                'ố',
                'ồ',
                'ổ',
                'ỗ',
                'ộ',
                'ơ',
                'ớ',
                'ờ',
                'ở',
                'ỡ',
                'ợ',
                'ø',
                'ō',
                'ő',
                'ŏ',
                'ο',
                'ὀ',
                'ὁ',
                'ὂ',
                'ὃ',
                'ὄ',
                'ὅ',
                'ὸ',
                'ό',
                'о',
                'و',
                'ို',
                'ǒ',
                'ǿ',
                'º',
                'ო',
                'ओ',
                'ｏ',
                'ö',
            ],
            'p'    => ['п', 'π', 'ပ', 'პ', 'پ', 'ｐ', 'פ', 'ף'],
            'q'    => ['ყ', 'ｑ'],
            'r'    => ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ', 'ｒ', 'ר'],
            's'    => ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ', 'ſ', 'ს', 'ｓ', 'ס'],
            't'    => ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ', 'ｔ', 'ת'],
            'u'    => [
                'ú',
                'ù',
                'ủ',
                'ũ',
                'ụ',
                'ư',
                'ứ',
                'ừ',
                'ử',
                'ữ',
                'ự',
                'û',
                'ū',
                'ů',
                'ű',
                'ŭ',
                'ų',
                'µ',
                'у',
                'ဉ',
                'ု',
                'ူ',
                'ǔ',
                'ǖ',
                'ǘ',
                'ǚ',
                'ǜ',
                'უ',
                'उ',
                'ｕ',
                'ў',
                'ü',
            ],
            'v'    => ['в', 'ვ', 'ϐ', 'ｖ', 'ו'],
            'w'    => ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ', 'ｗ'],
            'x'    => ['χ', 'ξ', 'ｘ'],
            'y'    => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ي', 'ယ', 'ｙ'],
            'z'    => ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ', 'ｚ', 'ז'],
            'aa'   => ['ع', 'आ', 'آ'],
            'ae'   => ['æ', 'ǽ'],
            'ai'   => ['ऐ'],
            'ch'   => ['ч', 'ჩ', 'ჭ', 'چ'],
            'dj'   => ['ђ', 'đ'],
            'dz'   => ['џ', 'ძ', 'דז'],
            'ei'   => ['ऍ'],
            'gh'   => ['غ', 'ღ'],
            'ii'   => ['ई'],
            'ij'   => ['ĳ'],
            'kh'   => ['х', 'خ', 'ხ'],
            'lj'   => ['љ'],
            'nj'   => ['њ'],
            'oe'   => ['ö', 'œ', 'ؤ'],
            'oi'   => ['ऑ'],
            'oii'  => ['ऒ'],
            'ps'   => ['ψ'],
            'sh'   => ['ш', 'შ', 'ش', 'ש'],
            'shch' => ['щ'],
            'ss'   => ['ß'],
            'sx'   => ['ŝ'],
            'th'   => ['þ', 'ϑ', 'θ', 'ث', 'ذ', 'ظ'],
            'ts'   => ['ц', 'ც', 'წ'],
            'ue'   => ['ü'],
            'uu'   => ['ऊ'],
            'ya'   => ['я'],
            'yu'   => ['ю'],
            'zh'   => ['ж', 'ჟ', 'ژ'],
            '(c)'  => ['©'],
            'A'    => [
                'Á',
                'À',
                'Ả',
                'Ã',
                'Ạ',
                'Ă',
                'Ắ',
                'Ằ',
                'Ẳ',
                'Ẵ',
                'Ặ',
                'Â',
                'Ấ',
                'Ầ',
                'Ẩ',
                'Ẫ',
                'Ậ',
                'Å',
                'Ā',
                'Ą',
                'Α',
                'Ά',
                'Ἀ',
                'Ἁ',
                'Ἂ',
                'Ἃ',
                'Ἄ',
                'Ἅ',
                'Ἆ',
                'Ἇ',
                'ᾈ',
                'ᾉ',
                'ᾊ',
                'ᾋ',
                'ᾌ',
                'ᾍ',
                'ᾎ',
                'ᾏ',
                'Ᾰ',
                'Ᾱ',
                'Ὰ',
                'Ά',
                'ᾼ',
                'А',
                'Ǻ',
                'Ǎ',
                'Ａ',
                'Ä',
            ],
            'B'    => ['Б', 'Β', 'ब', 'Ｂ'],
            'C'    => ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ', 'Ｃ'],
            'D'    => ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ', 'Ｄ'],
            'E'    => [
                'É',
                'È',
                'Ẻ',
                'Ẽ',
                'Ẹ',
                'Ê',
                'Ế',
                'Ề',
                'Ể',
                'Ễ',
                'Ệ',
                'Ë',
                'Ē',
                'Ę',
                'Ě',
                'Ĕ',
                'Ė',
                'Ε',
                'Έ',
                'Ἐ',
                'Ἑ',
                'Ἒ',
                'Ἓ',
                'Ἔ',
                'Ἕ',
                'Έ',
                'Ὲ',
                'Е',
                'Ё',
                'Э',
                'Є',
                'Ə',
                'Ｅ',
            ],
            'F'    => ['Ф', 'Φ', 'Ｆ'],
            'G'    => ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ', 'Ｇ'],
            'H'    => ['Η', 'Ή', 'Ħ', 'Ｈ'],
            'I'    => [
                'Í',
                'Ì',
                'Ỉ',
                'Ĩ',
                'Ị',
                'Î',
                'Ï',
                'Ī',
                'Ĭ',
                'Į',
                'İ',
                'Ι',
                'Ί',
                'Ϊ',
                'Ἰ',
                'Ἱ',
                'Ἳ',
                'Ἴ',
                'Ἵ',
                'Ἶ',
                'Ἷ',
                'Ῐ',
                'Ῑ',
                'Ὶ',
                'Ί',
                'И',
                'І',
                'Ї',
                'Ǐ',
                'ϒ',
                'Ｉ',
            ],
            'J'    => ['Ｊ'],
            'K'    => ['К', 'Κ', 'Ｋ'],
            'L'    => ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल', 'Ｌ'],
            'M'    => ['М', 'Μ', 'Ｍ'],
            'N'    => ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν', 'Ｎ'],
            'O'    => [
                'Ó',
                'Ò',
                'Ỏ',
                'Õ',
                'Ọ',
                'Ô',
                'Ố',
                'Ồ',
                'Ổ',
                'Ỗ',
                'Ộ',
                'Ơ',
                'Ớ',
                'Ờ',
                'Ở',
                'Ỡ',
                'Ợ',
                'Ø',
                'Ō',
                'Ő',
                'Ŏ',
                'Ο',
                'Ό',
                'Ὀ',
                'Ὁ',
                'Ὂ',
                'Ὃ',
                'Ὄ',
                'Ὅ',
                'Ὸ',
                'Ό',
                'О',
                'Ө',
                'Ǒ',
                'Ǿ',
                'Ｏ',
                'Ö',
            ],
            'P'    => ['П', 'Π', 'Ｐ'],
            'Q'    => ['Ｑ'],
            'R'    => ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ', 'Ｒ'],
            'S'    => ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ', 'Ｓ'],
            'T'    => ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ', 'Ｔ'],
            'U'    => [
                'Ú',
                'Ù',
                'Ủ',
                'Ũ',
                'Ụ',
                'Ư',
                'Ứ',
                'Ừ',
                'Ử',
                'Ữ',
                'Ự',
                'Û',
                'Ū',
                'Ů',
                'Ű',
                'Ŭ',
                'Ų',
                'У',
                'Ǔ',
                'Ǖ',
                'Ǘ',
                'Ǚ',
                'Ǜ',
                'Ｕ',
                'Ў',
                'Ü',
            ],
            'V'    => ['В', 'Ｖ'],
            'W'    => ['Ω', 'Ώ', 'Ŵ', 'Ｗ'],
            'X'    => ['Χ', 'Ξ', 'Ｘ'],
            'Y'    => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ', 'Ｙ'],
            'Z'    => ['Ź', 'Ž', 'Ż', 'З', 'Ζ', 'Ｚ'],
            'AE'   => ['Æ', 'Ǽ'],
            'Ch'   => ['Ч'],
            'Dj'   => ['Ђ'],
            'Dz'   => ['Џ'],
            'Gx'   => ['Ĝ'],
            'Hx'   => ['Ĥ'],
            'Ij'   => ['Ĳ'],
            'Jx'   => ['Ĵ'],
            'Kh'   => ['Х'],
            'Lj'   => ['Љ'],
            'Nj'   => ['Њ'],
            'Oe'   => ['Œ'],
            'Ps'   => ['Ψ'],
            'Sh'   => ['Ш', 'ש'],
            'Shch' => ['Щ'],
            'Ss'   => ['ẞ'],
            'Th'   => ['Þ', 'Θ', 'ת'],
            'Ts'   => ['Ц'],
            'Ya'   => ['Я', 'יא'],
            'Yu'   => ['Ю', 'יו'],
            'Zh'   => ['Ж'],
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
                    ['х', 'Х', 'щ', 'Щ', 'ъ', 'Ъ', 'ь', 'Ь'],
                    ['h', 'H', 'sht', 'SHT', 'a', 'А', 'y', 'Y'],
                ],
                'da' => [
                    ['æ', 'ø', 'å', 'Æ', 'Ø', 'Å'],
                    ['ae', 'oe', 'aa', 'Ae', 'Oe', 'Aa'],
                ],
                'de' => [
                    ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü'],
                    ['ae', 'oe', 'ue', 'AE', 'OE', 'UE'],
                ],
                'he' => [
                    ['א', 'ב', 'ג', 'ד', 'ה', 'ו'],
                    ['ז', 'ח', 'ט', 'י', 'כ', 'ל'],
                    ['מ', 'נ', 'ס', 'ע', 'פ', 'צ'],
                    ['ק', 'ר', 'ש', 'ת', 'ן', 'ץ', 'ך', 'ם', 'ף'],
                ],
                'ro' => [
                    ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
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

if (!function_exists('valid')) {
    function valid($array, $key, $default = null)
    {
        if (!empty($array) && isset($array[$key])) {
            return $array[$key];
        }
        return $default;
    }
}

if(!function_exists('env')){
    function env(string $key, $default = null){
        return \container()->env->get($key, $default);
    }
}

if (!function_exists('structure')) {
    function structure(): Structure
    {
        static $strucutre;
        if ($strucutre && $strucutre instanceof Structure) {
            return $strucutre;
        }
        $strucutre = new Structure();
        return $strucutre;
    }
}

if (!function_exists('getUserNameArray')) {
    function getUserNameArray(array $ids)
    {
        $key = "user_array_" . count($ids);
        $in = join(", ", $ids);

        $result = \structure()->set([
            $key => [
                'get'     => [
                    'firstName',
                    'secondName',
                ],
                'class'   => \App\Provides\ProvideStructures\bcUser::class,
                'setting' => [
                    'where' => "bc_user_id IN ($in)",
                ],
            ],
        ])->getData(function ($row) {
            $result = [];
            foreach ($row as $value) {
                $result[$value['id']] = ($value['firstName'] ?? '') . ' ' .($value['secondName'] ?? '');
            }
            return $result;
        }, $key);

        return $result;

    }
}

if (!function_exists('getUserName')) {
    function getUserName($id)
    {
        $structure = structure();
        if ($structure && $structure instanceof Structure) {
            $structure = $structure->clean();
        }
        $id = settype($id, 'int');

        return $structure->set([
            'user'    =>
                [
                    'firstName',
                    'secondName',
                ],
            'class'   => 'auto',
            'setting' =>
                [
                    'where' => "id = $id",
                ],
        ])->getData(function ($row) {

            if (!empty($row)) {
                $row = $row[0];
                return ($row['firstName'] ?? '') . ' ' . ($row['secondName'] ?? '');

            }
            return '';
        }, 'user');
    }
}

if (!function_exists('getResponseHeader')) {
    function getResponseHeader($header, $response)
    {
        foreach ($response as $key => $r) {
            // Match the header name up to ':', compare lower case
            if (stripos($r, $header . ':') === 0) {
                [$headername, $headervalue] = explode(":", $r, 2);
                return trim($headervalue);
            }
        }
        return false;
    }
}

if (!function_exists('mime2ext')) {
    function mime2ext($mime)
    {
        $mime_map = [
            'image/bmp'           => 'bmp',
            'image/x-bmp'         => 'bmp',
            'image/x-bitmap'      => 'bmp',
            'image/x-xbitmap'     => 'bmp',
            'image/x-win-bitmap'  => 'bmp',
            'image/x-windows-bmp' => 'bmp',
            'image/ms-bmp'        => 'bmp',
            'image/x-ms-bmp'      => 'bmp',
            'image/jp2'           => 'jp2',
            'video/mj2'           => 'jp2',
            'image/jpx'           => 'jp2',
            'image/jpm'           => 'jp2',
            'image/jpeg'          => 'jpg',
            'image/pjpeg'         => 'jpeg',
            'image/gif'           => 'gif',
            'image/png'           => 'png',
        ];

        return isset($mime_map[$mime]) === true ? $mime_map[$mime] : false;
    }
}

if (!function_exists('isPhoto')) {
    function isPhoto($url, bool $mime_type = false)
    {
        $header = get_headers($url);
        $mime = getResponseHeader('Content-Type', $header);
        clearstatcache();
        return $mime_type === true ? mime2ext($mime) : (bool)mime2ext($mime);
    }
}

if (!function_exists('creat_sort')) {
    function creatSort(&$dataArray, $name, $joinNames, $get = null)
    {
        //todo with response or request class
        $sort = htmlspecialchars(trim(valid(($get ?: $_GET), 'sort', null)));
        if (in_array($sort, $dataArray[$name]['get'])) {
            $dataArray[$name]['setting']['order'] = $sort;
        } else {
            foreach ($dataArray[$name]['get'] as $value) {
                if ('a_' . $value == $sort) {
                    $dataArray[$name]['setting']['order'] = $sort;
                }
            }
            if (isset($dataArray[$name]['setting']['join'])) {
                foreach ($joinNames as $joinName) {
                    if (!isset($dataArray[$name]['setting']['join'][$joinName]['get'])) {
                        continue;
                    }
                    $joinNamesGet = $dataArray[$name]['setting']['join'][$joinName]['get'];
                    $joinNamesGet = !is_array($joinNamesGet) ? [$joinNamesGet] : $joinNamesGet;

                    if (in_array($sort, $joinNamesGet)) {
                        $dataArray[$name]['setting']['join'][$joinName]['setting']['order'] = $sort;
                    } else {

                        foreach ($joinNamesGet as $value) {
                            if ('a_' . $value == $sort) {
                                $dataArray[$name]['setting']['join'][$joinName]['setting']['order'] = $sort;
                            }
                        }
                    }
                }
            }
        }
        //var_dump($dataArray);

    }
}

if (!function_exists('newGenerateLink')) {
    function newGenerateLink($name, $var, $array = null, $specialSymbol = '?')
    {
        if ($array) {
            if (count(explode('=', $array)) < 2) {
                return '';
            }
            $name = explode('=', $array)[0];
            $var = explode('=', $array)[1];
        }
        $GET = $_GET;
        if (get($name) && get($name) == $var) {
            unset($GET[$name]);
        }
        unset($GET['bcurl']);
        $GET[$name] = $var;
        if ($name === 'export' || $name === 'fullExport') {
            $GET['length'] = '-1';
        }
        return $specialSymbol . http_build_query($GET);
    }
}

if (!function_exists('addToArray')) {
    function addToArray(&$arr, $key, $value, $prefix = ' ')
    {
        if (!empty($arr[$key])) {
            if ($value == $arr[$key]) {
                return $arr;
            } else {
                $arr[$key] .= $prefix . ' ' . $value;
            }
        } else {
            $arr[$key] = $value;
        }
        return $arr;
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
     * @return \Keperis\Http\Uri
     */
    function uri()
    {
        return \container()->get('request')->getUri();
    }
}

if (!function_exists('isLogin')) {
    function isLogin()
    {
        return false;
    }
}

if (!function_exists('view')) {
    function view($name, $arg = [], $pageName = null, $title = null, $data_array = null, $copyText = false)
    {
        return resource('body', [

            'content'    => resource($name, $arg)->render(),
            'pageName'   => $pageName,
            'title'      => $title,
            'data_array' => $data_array,
            'copyText'   => $copyText,
        ]);

    }
}

if (!function_exists('absoluteLink')) {

    function absoluteLink($link)
    {
        return ($link && strpos($link, 'http') === false) ? 'http://' . $link : $link;
    }
}

if (!function_exists('str_contains')) {

    /**
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     * Laravel function
     * Determine if a given string contains a given substring.
     */
    function str_contains($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('last')) {
    /**
     * Get the last element from an array.
     *
     * @param array $array
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }
}

if (!function_exists('startsWith')) {


    function startsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string)$needle) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('db')) {
    function db(): \Keperis\Core\DB
    {
        return \Keperis\Core\DB::getInstance();
    }
}

if (!function_exists('getCRMPeople')) {
    function getCRMPeople(): array
    {
        static $data = [];
        if (empty($data)) {

            $data = \App\Models\User\UserModel::this()->getCRMPeople();

        }
        return $data;

    }
}

if (!function_exists('set_structure_setting')) {
    function set_structure_setting(&$data, $value, $setting = 'where', $key = null)
    {
        $name = $key ?: key($data);
        $data[$name]['setting'][$setting] = $value;
        return $data;
    }
}

if (!function_exists('role_check')) {
    function role_check(string $role, bool $return_status = false)
    {
        //return \container()->get('auth')->check($role, $return_status);
        return true;

    }
}

if (!function_exists('studly')) {
    function studly($value, $studlyCache = [])
    {
        $key = $value;

        if (isset($studlyCache[$key])) {
            return $studlyCache[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return $studlyCache[$key] = str_replace(' ', '', $value);
    }
}

if (!function_exists('checkToken')) {
    function checkToken(string $token, $userId = null): bool
    {
        $sql = db()->selectSql('bc_user', 'bc_user_id', "bc_user_token = '" . $token . "'");

        if (empty($sql[0]['bc_user_id'])) {
            return false;
        }

        if($userId){
            return  $sql[0]['bc_user_id'] == $userId;
        }

        return true;
    }
}
if (!function_exists('current_user_id')) {
    function current_user_id()
    {
        return valid($_SESSION, 'BCUserId', 0);
    }
}

if (!function_exists('getCompanyLogoLink')) {
    function getCompanyLogoLink($user_id, $company_id)
    {
        foreach (['jpg', 'jpeg', 'png', 'gif'] as $url) {
            $url = APP_URL . "theme/bc_user_{$user_id}/logo/{$company_id}.$url?" . time();
            if (isPhoto($url)) {
                return $url;
            }
        }


        return '/images/no_logo.png';
    }
}
if (!function_exists('post')) {
    function post($key, $dafault = null)
    {
        return \container()->get('request')->getParsedBody()[$key] ?? $dafault;
    }
}
if (!function_exists('convertEmptyStringsToNull')) {

    function convertEmptyStringsToNull($value)
    {
        return is_string($value) && $value === '' ? null : $value;
    }
}
if (!function_exists('htmlentitiesUTF8')) {
    function htmlentitiesUTF8($string, $type = ENT_QUOTES)
    {
        if (is_array($string)) {
            return array_map('htmlentitiesUTF8', $string);
        }

        return htmlentities((string)$string, $type, 'utf-8');
    }

}
if (!function_exists('clean')) {
    function clean($text, $remove_tags = true)
    {
        if (!is_array($text)) {
            if (get_magic_quotes_gpc() OR get_magic_quotes_runtime()) {
                $text = stripslashes($text);
            }
            if ($remove_tags) {
                $text = @strip_tags($text);
                $text = htmlspecialchars($text, ENT_QUOTES);
            }
            $text = addslashes($text);
            $text = preg_replace('@\A\s+|\s+\z@u', '', $text);
            $text = trim($text);
        }

        return $text;
    }
}
if (!function_exists('safePostVars')) {
    function safePostVars()
    {
        if ($_POST) {
            $_POST = array_map('htmlentitiesUTF8', $_POST);
            $_POST = array_map('convertEmptyStringsToNull', $_POST);
        }
    }
}

if (!function_exists('setting')) {
    function setting(): \Keperis\Collection
    {
        return \container()->get('setting');
    }
}
if (!function_exists('creat_status_select')) {
    function creat_status_select($value, $dictionary, $id, $class = '', $reload = 0, bool $delete = false)
    {
        $result = [];

        foreach ($dictionary as $key => $val) {
            if ($key == $value) {
                $result[0] = "<span class='toUperCase'>" . strtoupper($val) . "</span>";

            } else {
                $result[$key] = "<a href='#' data-reload = '$reload' class='updateStatus' data-status = '$key' data-id = '$id' data-o = '$class'>$val</a> |";
            }
        }

        if ($result) {
            ksort($result);
        }

        if ($delete === true) {
            $result[] = "<a href='#' data-reload = '$reload' class='updateStatus' data-status = '0' data-id = '$id' data-o = '$class'>скинути</a>";
        }
        return join('<br>', $result);
    }
}
if (!function_exists('selectOption')) {
    function selectOption(array $value, $insert = null, $empty = false)
    {
        $result = "";
        $insert = !is_array($insert) ? [$insert] : $insert;
        foreach ($value as $key => $text) {
            if ($empty === true) {
                $result = "<option></option>";
                $empty = false;
            }
            $result .= "<option value='$key' " . (in_array($key, $insert) ? 'selected' : '') . ">$text</option>";
        }
        return $result;
    }
}

if (!function_exists('outputListOfDictionaryValue')) {
    function outputListOfDictionaryValue($value, $delimtr = "|")
    {
        if (!empty($value)) {
            $value = array_map(function ($a) {
                if (intval($a) > 0) {
                    return \App\Models\Dictionary\DictionaryModel::getDictionaryTitleById($a);
                }
                return $a;
            }, explode("|", $value));
            $value = join(" , ", array_diff($value, ['', null, false, '.', '|']));
        }
        return $value ?: "";
    }
}

if (!function_exists('unclean')) {
    function unclean($text)
    {
        if (!is_array($text)) {
            $text = stripslashes($text);
        }

        return $text;
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
if (!function_exists("getMassage")) {
    function getMassage($key = null)
    {
        /** @var  $request \App\Keperis\Http\Request */
        $request = \container()->get('request');
        return $key ? $request->getAttribute($key) : $request->getAttributes();
    }
}
if (!function_exists('valid_structure')) {
    function valid_structure($row, $needles)
    {
        if (!empty($row) && isset($row[0][$needles])) {
            return $row[0][$needles];
        }
        return null;
    }
}
if (!function_exists('getDictionary4Select')) {
    function getDictionary4Select($key, $order = true)
    {
        return \App\Models\Dictionary\DictionaryModel::getDictionary4Select($key, $order);
    }
}
if (!function_exists('set_structure_where')) {
    function set_structure_where($data, $where)
    {
        $key = key($data);
        $data[$key]['setting']['where'] = $where;
        return $data;
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
        /** @var  $router  \Keperis\Router\Route */
        /** @var $route \Keperis\Router\Route */
        $router = container()->get('router');
        $route = $router->lookupRoute($lookup);
        $route = $pattern ? $route->withPattern($pattern) : $route;
        return $route->getPath($parrams);
    }
}

if (!function_exists('miniJs')) {
    /**
     * @param $path
     * @return \MatthiasMullie\Minify\JS
     */
    function miniJs($path)
    {
        static $js;
        if (!$js || !($js instanceof MatthiasMullie\Minify\JS)) {
            $js = new MatthiasMullie\Minify\JS();
        }
        if (!empty($path)) {
            $path = is_array($path) ? $path : $path;
            foreach ($path as $value) {
                $js->add(ROOT_PATH . $value);
            }
        }
        return $js;
    }
}

if (!function_exists('miniCss')) {
    /**
     * @param $path
     * @return \MatthiasMullie\Minify\CSS
     */
    function miniCss($path)
    {
        static $css;
        if (!$css || !($css instanceof MatthiasMullie\Minify\CSS)) {
            $css = new MatthiasMullie\Minify\CSS();
        }
        if (!empty($path)) {
            $path = is_array($path) ? $path : $path;

            foreach ($path as $value) {
                $css->add(ROOT_PATH . $value);
            }
        }
        return $css;
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

if (!function_exists('stripWhitespaces')) {
    function stripWhitespaces($string)
    {
        $old_string = $string;
        $string = strip_tags($string);
        $string = preg_replace('/([^\pL\pN\pP\pS\pZ])|([\xC2\xA0])/u', ' ', $string);
        $string = str_replace('  ', ' ', $string);
        $string = trim($string);

        if ($string === $old_string) {
            return $string;
        } else {
            return stripWhitespaces($string);
        }
    }
}
