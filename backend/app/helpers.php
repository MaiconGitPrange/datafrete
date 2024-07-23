<?php
// helpers.php

$rateLimit = [];
$maxRequests = 100; // Número máximo de requisições

function isRateLimited($ip)
{
    global $rateLimit, $maxRequests;

    $currentTime = time();
    if (!isset($rateLimit[$ip])) {
        $rateLimit[$ip] = [];
    }

    // Remove entradas antigas
    $rateLimit[$ip] = array_filter($rateLimit[$ip], function ($timestamp) use ($currentTime) {
        return ($currentTime - $timestamp) < 60;
    });

    // Verifica se atingiu o limite
    if (count($rateLimit[$ip]) >= $maxRequests) {
        return true;
    }

    // Adiciona timestamp da nova requisição
    $rateLimit[$ip][] = $currentTime;
    return false;
}

function getCoordinates($cep)
{
    $ip = $_SERVER['REMOTE_ADDR'];

    if (isRateLimited($ip)) {
        error_log("Rate limit exceeded for IP: {$ip}");
        return ['error' => 'Rate limit exceeded'];
    }

    $url = "https://brasilapi.com.br/api/cep/v2/{$cep}";
    $response = @file_get_contents($url);

    if ($response === false) {
        $error = error_get_last();
        error_log("Error fetching coordinates for CEP: {$cep}. Error: " . $error['message']);
        return ['error' => 'Error fetching coordinates'];
    }

    $data = json_decode($response, true);

    if (isset($data['errors'])) {
        error_log("Error fetching coordinates for CEP: {$cep}. Error: " . $data['message']);
        return ['error' => 'CEP not found'];
    }

    if (!isset($data['location']['coordinates'])) {
        error_log("Invalid response for CEP: {$cep}");
        return ['error' => 'Invalid CEP'];
    }

    return $data['location']['coordinates'];
}

function calculateDistance($coords1, $coords2)
{
    $lat1 = $coords1['latitude'];
    $lon1 = $coords1['longitude'];
    $lat2 = $coords2['latitude'];
    $lon2 = $coords2['longitude'];

    $R = 6371; // Raio da Terra em km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $R * $c; // Distância em km
    return round($distance, 2);
}
