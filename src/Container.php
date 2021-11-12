<?php


namespace src;


use Exception;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\MySqlConnection;
use Psr\Container\ContainerInterface;

use src\Eloquent\Model;
use src\Http\Environment;
use src\Http\Headers;
use src\Http\Request;
use src\Http\Response;
use src\Http\ServerData;
use src\Http\Session;
use src\Middleware\Middleware;
use src\Middleware\RequestHandler;
use src\Middleware\RequestHandler\NotFoundHandler;
use src\Router\Router;
use src\Structure\Structure;


/**
 * Class Container
 * @package App\src
 * @property Environment $env
 * @property Collection $setting
 * @property Session $session
 * @property Request $request
 * @property Structure $structure
 * @property PageCreator $pageCreator
 * @property bcerpapi $api
 * @property Response $response
 * @property CallableResolver $callableResolver
 * @property Router $router
 * @property Middleware $middleware
 * @property RequestHandler $requestHandle;
 * @property MySqlConnection $connection
 */
final class Container extends \Pimple\Container implements ContainerInterface
{
    private $defaultSettings = [
        'displayErrorDetails' => true,
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'api' => []//todo
    ];


    public function __construct(array $values = [])
    {
        parent::__construct($values);

        $userSettings = isset($values['setting']) ? $values['setting'] : [];
        //var_dump($userSettings);
        $this->registerDefaultServices($userSettings);
    }

    public function registerDefaultServices($userSetting)
    {

        $defaultSettings = $this->defaultSettings;

        $this['setting'] = function () use ($userSetting, $defaultSettings) {
            //debug(array_merge($userSetting, $defaultSettings));
            return new Collection(array_merge($userSetting, $defaultSettings));
        };

        if (!isset($this['session'])) {
            $this['session'] = function () {
                return new Session();
            };
        }

        if (!isset($this['serverdata'])) {
            $this['serverdata'] = function () {
                return new ServerData($_SERVER);
            };
        }
        if (!isset($this['request'])) {
            $this['request'] = function ($c) {
                return Request::creatFromServerData($c->get('serverdata'));
            };
        }
        if (!isset($this['structure'])) {
            $this['structure'] = function () {
                return \structure();
            };
        }
        if (!isset($this['router'])) {
            $this['router'] = function ($c) {
                $route = new Router();
                $route->setContainer($c);
                return $route;
            };
        }
        if (!isset($this['response'])) {
            $this['response'] = function ($c) {
                $headers = new Headers(["Content-Type" => 'text/html; charset=UTF-8']);
                $response = new Response(200, $headers);
                return $response->withProtocolVersion($c->get('setting')->get('httpVersion'));
            };
        }
        if (!isset($this['env'])) {
            $this['env'] = function ($c) {
                return Environment::mock($_ENV);
            };
        }

        if (!isset($this['api'])) {
            $this['api'] = function () {
                return new bcerpapi();
            };
        }//todo new api class and custom value

        if (!isset($this['callableResolver'])) {
            $this['callableResolver'] = function ($c) {
                return new CallableResolver($c);
            };
        }
        if (!isset($this['middleware'])) {
            $this['middleware'] = function () {
                return new Middleware(new NotFoundHandler());
            };
        }
        if (!isset($this['requestHandle'])) {
            $this['requestHandle'] = function () {
                return new RequestHandler();
            };
        }

        if (isset($userSetting['provides']) && !empty($userSetting['provides'])) {
            foreach ($userSetting['provides'] as $name => $provide) {


                $this[$name] = function () use ($provide) {

                    if (method_exists($provide, 'boot')) {
                        return $provide::boot();
                    }
                    return null;
                };
            }
        }

        if (!isset($this['connection'])) {
            $this['connection'] = function ($e) {

                $pdo = static function (Environment $environment, $options = [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"]) {
                    $attributes = [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_EMPTY_STRING,
                        \PDO::ATTR_CASE => \PDO::CASE_LOWER,
                    ];
                    if (!$environment->hasMany(['DB_USER', 'DB_HOST', 'DB_PASS', 'DB_NAME'])) {
                        throw new \PDOException("Undefined db setting");
                    }

                    if (!$environment->has('dsn')) {
                        $environment->set('dsn', 'mysql:host=' . $environment->get('DB_HOST') . ';dbname=' . $environment->get('DB_NAME'));
                    }

                    try {
                        $pdo = new \PDO(
                            $environment->get('dsn'),
                            $environment->get('DB_USER'),
                            $environment->get('DB_PASS'),
                            $options
                        );
                        foreach ($attributes as $attribute => $value) {

                            $pdo->setAttribute($attribute, $value);
                        }
                        $pdo->exec("SET time_zone = '" . date('P') . "'");
                        $pdo->exec('SET names utf8');
                        return $pdo;
                    } catch (\PDOException $exception) {
                        ;
                        if (boolval($environment->get('APP_DEBUG', false)) === true) {
                            echo $exception->getMessage();
                        }
                        error_log($exception->getMessage());
                        die(0);
                    }
                };


                $connection = new MySqlConnection($pdo($e->env), $e->env->get('DB_NAME'));

                $resolver = new \src\Eloquent\ConnectionResolver([
                    'mysql' => $connection,
                ]);

                Model::setConnectionResolver($resolver);

                return $connection;
            };
        }

    }

    public function __get($name)
    {
        try {
            return $this->get($name);
        } catch (Exception $e) {
            error_log($e->getMessage());
            die();
        }
    }

    /**
     * @param string $id
     * @return mixed|void
     * @throws \RuntimeException
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            error_log(sprintf('Identifier "%s" is not defined.', $id));
            throw new \RuntimeException(sprintf('Identifier "%s" is not defined.', $id));
        }
        return $this->offsetGet($id);
    }

    public function has($id)
    {
        return $this->offsetExists($id);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }
}
