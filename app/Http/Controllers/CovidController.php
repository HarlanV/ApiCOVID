<?php

namespace App\Http\Controllers;

use App\Classes\CovidAPI;
use Illuminate\Http\Request;

class CovidController extends Controller
{
    public function index(Request $request)
    {
        $data = new CovidAPI($request->state,$request->dateStart,$request->dateEnd);

        $rankedCities = $data->getWorstCities(10);

        $response = $data->write($rankedCities);
        
        return $response;
    }
}
