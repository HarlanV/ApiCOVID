<?php

namespace App\Http\Controllers;


use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CovidController extends Controller
{
    public function index()
    {
        $state = 'PR';
        $date = '2020-05-10';
        $data = $this->readData($state, $date);
        $topCities = $this->dataWork($data);

        foreach($topCities as $city){
            $writeData = $this->writeData($city);
        }
        dd('fim do programa');

    }

    /**
     * Duvida: onde esta função deveria ser alocada ? Algum modo de colocar em midlware ?
     * 
     * @param string            $state
     * @param string            $date
     * @return GuzzleHttp\Psr7\ $responseBody
     */
    public static function readData($state, $date){
        $client = new Client();

        $url = 'https://api.brasil.io/dataset/covid19/caso/data/?state='.$state.'&date='.$date;

        $headers = [
            'Authorization' => 'Token cd06accc7cba9e0b48b4d3106f3ea4359f593725'
        ];

        $response = $client->request('GET', $url, [
            // 'json' => $params,
            'headers' => $headers,
            'verify'  => false,
        ]);

        $responseBody = $response->getBody();

        return $responseBody;

    }

    /**
     * Duvida: onde colocar esta função ?
     * @param GuzzleHttp\Psr7\  $data
     * @return array            $topCities
     */
    public static function dataWork($data)
    {
        # here we will get the data and conver to a 
        $data = json_decode($data)->results;
        $results=[];

        #here we're gonna treat the data and get the percentual rate
        foreach ($data as $city) {
            $name = $city->city;
            $population = $city->estimated_population;
            $confimed = $city->confirmed;
            if(!in_array(null,[$name,$population],true)){
                $percentage = ($confimed/$population)*100;
                $results[$name] = $percentage;
            
            }
        }

        #at this point we order the results and itereate to return only 'top 10' cities
        arsort($results);
        $id=0;
        foreach ($results as $city => $percentage) {
            $topCities[] = [
                'id'=>$id,
                'nomeCidade'=>$city,
                'percentualDeCasos'=>$percentage
            ];
            $id++;
            if ($id>=10) break;
        }

        return $topCities;
    }

    /**
     * Mesmo problema da readData
     * 
     * @param array             $data
     * @return GuzzleHttp\Psr7\ $response
     */
    public static function writeData($data)
    {
        $client = new Client();

        $url = 'https://us-central1-lms-nuvem-mestra.cloudfunctions.net/testApi';

        $headers = [
            'MeuNome' => 'Harlan Victor'
        ];

        $body = json_encode($data);

        $response = $client->request('POST', $url, [
            'headers' => $headers,
            'body' =>$body,
            'verify'  => false,
        ]);

        return $response;
    }
}
