<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Illuminate\Database\Capsule\Manager as Capsule;

require 'helpers.php';

return function (App $app) {
    // Middleware de CORS
    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    });

    // Configuração da rota OPTIONS
    $app->options('/{routes:.+}', function (Request $request, Response $response, $args) {
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    });

    // Configuração do Eloquent ORM
    $capsule = new Capsule;
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => 'mysql_container',
        'database' => 'datafrete',
        'username' => 'user',
        'password' => 'password',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix' => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    // Rota para listar os cálculos de distância
    $app->get('/list', function (Request $request, Response $response, $args) {
        $calculos = Capsule::table('ceps_distance')->get();
        $response->getBody()->write($calculos->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/calculate-distance', function (Request $request, Response $response, $args) {
        $data = $request->getParsedBody();
        $cepOrigem = $data['cep_origem'];
        $cepDestino = $data['cep_destino'];

        $coordsOrigem = getCoordinates($cepOrigem);
        $coordsDestino = getCoordinates($cepDestino);
        $distance = calculateDistance($coordsOrigem, $coordsDestino);

        // Inserir os dados no banco de dados
        $id = Capsule::table('ceps_distance')->insertGetId([
            'cep_origem' => $cepOrigem,
            'cep_destino' => $cepDestino,
            'distancia' => $distance
        ]);

        $response->getBody()->write(json_encode(['id' => $id]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/upload-csv', function (Request $request, Response $response, $args) {
        $uploadedFiles = $request->getUploadedFiles();
        $csvFile = $uploadedFiles['file'];

        if ($csvFile->getError() === UPLOAD_ERR_OK) {
            $stream = $csvFile->getStream();
            $csvData = $stream->getContents();
            $rows = array_map(function ($row) {
                return str_getcsv($row, ';');
            }, explode("\n", $csvData));
            $header = array_shift($rows);

            $results = [];
            foreach ($rows as $row) {
                if (count($row) < 2) {
                    continue;
                }
                $cepOrigem = $row[0];
                $cepDestino = $row[1];

                try {
                    $coordsOrigem = getCoordinates($cepOrigem);
                    $coordsDestino = getCoordinates($cepDestino);
                    if (!$coordsOrigem || !$coordsDestino) {
                        continue;
                    }

                    $distance = calculateDistance($coordsOrigem, $coordsDestino);

                    $id = Capsule::table('ceps_distance')->insertGetId([
                        'cep_origem' => $cepOrigem,
                        'cep_destino' => $cepDestino,
                        'distancia' => $distance
                    ]);

                    $results[] = ['id' => $id, 'cep_origem' => $cepOrigem, 'cep_destino' => $cepDestino, 'distancia' => $distance];
                } catch (\Exception $e) {
                    // Log the error and continue processing the next row
                    error_log("Error processing CEPs: {$cepOrigem} to {$cepDestino}. Error: " . $e->getMessage());
                    continue;
                }
            }

            $response->getBody()->write(json_encode($results));
            return $response
                ->withHeader('Content-Type', 'application/json');
        } else {
            return $response
                ->withStatus(400)
                ->write(json_encode(['error' => 'File upload error']));
        }
    });

    // Rota para atualizar um cálculo de distância existente
    $app->put('/update-distance/{id}', function (Request $request, Response $response, $args) {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $cepOrigem = $data['cep_origem'];
        $cepDestino = $data['cep_destino'];

        $coordsOrigem = getCoordinates($cepOrigem);
        $coordsDestino = getCoordinates($cepDestino);

        if (isset($coordsOrigem['error']) || isset($coordsDestino['error'])) {
            $errorMessages = [];
            if (isset($coordsOrigem['error'])) {
                $errorMessages['cep_origem'] = $coordsOrigem['error'];
            }
            if (isset($coordsDestino['error'])) {
                $errorMessages['cep_destino'] = $coordsDestino['error'];
            }

            $response->getBody()->write(json_encode(['status' => 'error', 'errors' => $errorMessages]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $distance = calculateDistance($coordsOrigem, $coordsDestino);

        // Atualizar os dados no banco de dados
        Capsule::table('ceps_distance')
            ->where('id', $id)
            ->update([
                'cep_origem' => $cepOrigem,
                'cep_destino' => $cepDestino,
                'distancia' => $distance
            ]);

        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
