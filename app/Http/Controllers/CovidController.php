<?php

namespace App\Http\Controllers;

use App\Classes\CovidAPI;
use Illuminate\Http\Request;

class CovidController extends Controller
{
    public function index(Request $request)
    {
        $data = new CovidAPI($request->state,$request->dateStart,$request->dateEnd);

        $response = $data->response;

        #if we succeed in get the Data, then we can rank the 10 worst cities and write the results.
        if($response->successful()){
            $rankedCities = $data->getWorstCities(10);
            $response = $data->write($rankedCities);    
        }
        
        return $response;
    }
}
