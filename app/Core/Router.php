<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, $handler): void
    {
        if (!isset($this->routes['GET'])) {
            $this->routes['GET'] = [];
        }
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, $handler): void
    {
        if (!isset($this->routes['POST'])) {
            $this->routes['POST'] = [];
        }
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $uri, string $method)
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        if (!isset($this->routes[$method])) {
            http_response_code(404);
            return json_encode(['error' => 'Rota não encontrada'], JSON_UNESCAPED_UNICODE);
        }

        foreach ($this->routes[$method] as $pathPattern => $handler) {

            $regex = preg_replace_callback(
                '/\{([^\/]+)\}/',
                fn($matches) => '(?P<' . $matches[1] . '>[^\/]+)',
                $pathPattern
            );

            $regex = '#^' . str_replace('/', '\/', $regex) . '$#';

            if (preg_match($regex, $uri, $matches)) {

                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = $value;
                    }
                }

                if (is_array($handler)) {
                    [$controllerClass, $methodName] = $handler;
                    return (new $controllerClass())->{$methodName}($params);
                }
                return call_user_func($handler, $params);
            }
        }

        http_response_code(404);
        return json_encode(['error' => 'Rota não encontrada'], JSON_UNESCAPED_UNICODE);
    }
}
