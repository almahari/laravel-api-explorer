<?php

namespace NetoJose\LaravelApiExplorer\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NetoJose\LaravelApiExplorer\LaravelApiExplorer;

class GeneratePostmanCollection extends Command
{
    protected $signature = 'api:postman';

    protected $description = 'Generate postman collection';

    public function handle()
    {
        $this->info('Start generating the postman collection');

        $filename = $this->generatePostmanFile();

        $this->info('New file generated ' . $filename );
    }

    private function generatePostmanFile()
    {
        $disk = config('laravelapiexplorer.postman.disk');
        if (empty($disk))
            $this->error('disk configration is empty');

        $laravelApiExplorer = new LaravelApiExplorer;

        $routes = $laravelApiExplorer->loadRoutesInfo();
        $config = $laravelApiExplorer->getConfig();

        $content = [
            'info' => [
                'name' => config('laravelapiexplorer.postman.collection_title'),
                'schema' => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
            ],
            'item' => []
        ];

        $groups = [];
        foreach ($routes as $route) {

            $uri = $route['uri'];
            $uri = str_replace('{','{{',$uri);
            $uri = str_replace('}','}}',$uri);

            $request = [
                'name' => $route['name'] ?? $route['uri'],
                'description' => $route['description'] ?? 'description here',
                'protocolProfileBehavior' => ['disableBodyPruning' => true],

                'request' => [
                    'method' => $route['http_verb'],
                    'body' => $this->getBody($route),
                    'url' => [
                        'raw' => '{{host}}' . $uri ,
                        'host' => '{{host}}',
                        'path' => explode('/', $uri),
                    ]

                ],
            ];

            if ($this->hasAuth($route)) {
                $request['request']['auth'] = [
                    'type' => 'bearer',
                    'bearer' => [
                        [
                            'key' => 'token',
                            'value' => '{{token}}',
                            'type' => 'string'
                        ]
                    ]
                ];
            }

            if (!isset($groups[$route['controller']])) {
                $groupName = $this->getGroupName($route);;

                $groups[$route['controller']] = ['name' => $groupName, 'item' => []];
            }

            $groups[$route['controller']]['item'] [] = $request;
        }
        sort( $groups);
        $content['item'] = array_values($groups);

        $filename = 'postman_' . time() . '.json';
        Storage::disk($disk)->put($filename, json_encode($content));
        return Storage::disk($disk)->path($filename);
    }

    private function getGroupName($route)
    {
        $name = Str::before(class_basename($route['controller']), 'Controller');

        $name = preg_replace('/(?<!\ )[A-Z]/', ' $0', $name);

        return ucwords($name);
    }

    private function hasAuth($route)
    {
        $middleware = config('laravelapiexplorer.postman.auth_middleware');
        if (!$middleware)
            return false;

        return in_array($middleware, $route['middlewares']);
    }

    private function getBody($route)
    {
        $body = [];

        $parameters = [];
        foreach ($route['rules'] as $name => $rules) {
            $defaultValue = 'string';

            if(in_array('numeric',$rules))
            {
                $defaultValue = 'number';
            }

            if(in_array('uuid',$rules))
            {
                $defaultValue = 'UUID';
            }

            if(in_array('array',$rules))
            {
                $defaultValue = [$defaultValue];
            }

            $parameters [$name] = $defaultValue;
        }

        if (!empty($parameters)) {
            ray($route['rules']);
            $body= [
                'mode' => 'raw',
                'raw' => json_encode($parameters,JSON_PRETTY_PRINT),
                'options' => [
                    'raw' => [
                        'language' => 'json'
                    ]
                ]
            ];
        }

        return $body;
    }
}
