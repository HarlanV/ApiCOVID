<?php

namespace App\Classes;

use ErrorException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class CovidAPI
{
    private $startData;
    private $endData;
    public $response;

    public function __construct($state,$startDate,$endDate)
    {   
        $this->response = $this->importData($state,$startDate,$endDate);
    }

    /**
     * Consume API, import data from webservice
     * 
     * @param   string  $state
     * @param   string  $startDate
     * @param   string  $endDate
     * @return  \Illuminate\Http\Client\Response    $response
     */
    public function importData($state,$startDate,$endDate)
    {

        #initiating data import from start date
        $response = $this->readData($state, $startDate);
        
        #if connection fail, save the response and stop function.
        if(!$response->successful()){
            return $response;
        }

        $this->startData = $response->json()['results'];

        #initiating data import from end date
        $response = $this->readData($state, $endDate);

        #if connection fail, save the response and stop function.
        if(!$response->successful()){
            return $response;
        }

        $this->endData = $response->json()['results'];

        return $response;
    }

    /**
     * Returns the 'n' worst cities and there's rates 
     * 
     * @param   int $n
     * @return  array
     */
    public function getWorstCities(int $n=10):array
    {
        $rates = $this->totalContamination();

        arsort($rates);

        return array_slice($rates, 0, $n, true);
    }

    /**
     * This function calculates the total amount of contaminated between dates and return
     * the percentual rate.
     * 
     * @return  array   $contaminated
     */
    private function totalContamination(): array
    {
        $contaminatedStart=[];
        $contaminated=[];

        # Calculate the total amount of contaminated at the first day
        foreach ($this->startData as $data) {
            $name = $data['city'];
            $population = $data['estimated_population'];
            $confirmed = $data['confirmed_per_100k_inhabitants'];
            if(!in_array(null,[$name, $population],true)){
                $contaminatedStart[$name] = ($confirmed*$population)/(10^5);
            }
        }

        # Calculate the total amount of contaminated at the last day
        foreach ($this->endData as $data) {
            $name = $data['city'];
            $population = $data['estimated_population'];
            $confirmed = $data['confirmed_per_100k_inhabitants'];
            if(!in_array(null,[$name,$population],true)){
                $contaminatedNow = ($confirmed*$population)/(10^5);
                try{
                    #if we have the inital data, make de difference and calculate the percentual
                    $contaminated[$name] = ($contaminatedNow - $contaminatedStart[$name])/$population ;
                }catch(ErrorException $e){
                    #if we miss startData of one city, is assumed as Zero! 
                    $contaminated[$name] = ($confirmed)/$population ;
                }
            }
        }

        return($contaminated);
    }

    /**
     * This functions configure and write the data consuming another API
     * 
     * @param   array   $rankedCities
     * @return  \Illuminate\Http\Client\Response    $status
     */
    public function write($rankedCities)
    {   
        $id = 0;

        foreach($rankedCities as $name=>$cityRate){
            $body=[
                'id' => $id,
                'nomeCidade'=>$name,
                'percentualDeCasos'=>$cityRate
            ];
    
            $response = $this->writeData($body);
            i++;
        }

        return $response;
    }

    /**
     * Consume the API that contains the data-source
     * 
     * @param   string  $state
     * @param   string  $date
     * @return  response   
     */
    private function readData($state, $date)
    {
        $token = config('app.token');

        $url = 'https://api.brasil.io/dataset/covid19/caso/data/?state='.$state.'&date='.$date;

        $response = Http::withHeaders([
            'Authorization' => 'Token '. $token
        ])->get($url);
        
        return $response;
    }

    /**
     * Write de API as required
     * 
     * @param   array   $body
     * @return  \Illuminate\Http\Client\Response    $response
     */
    private function writeData($body)
    {
        $header = [
            'MeuNome' => 'Harlan Victor'
        ];

        $url = 'https://us-central1-lms-nuvem-mestra.cloudfunctions.net/testApi';

        $response = Http::withHeaders($header)->post($url, $body);

        return $response;
    }

}
