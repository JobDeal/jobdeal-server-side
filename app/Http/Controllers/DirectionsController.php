<?php

namespace App\Http\Controllers;

use App\Http\Helper;
use App\Http\Resources\JobResource;
use App\Job;
use App\User;
use Grimzy\LaravelMysqlSpatial\Types\LineString;
use Grimzy\LaravelMysqlSpatial\Types\MultiPoint;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Debug\Debug;

class DirectionsController extends Controller
{
    public static function getAddressFromLocation($lat, $lng){
        //retrieveAreas
        //TODO Change API key, this is Convoy key
        $client = new \GuzzleHttp\Client([]);
        $res = $client->request('GET', "https://reverse.geocoder.api.here.com/6.2/reversegeocode.json?app_id=xsJh063eK0TLk0OBOqhJ&app_code=Xm-WmXet_M3RFxEl62dcsw&mode=retrieveAddresses&prox=$lat,$lng,250&language=en", [
            'headers' => ["Content-Type" => "application/json"]
        ]);

        $res = \GuzzleHttp\json_decode($res->getBody()->getContents(), true);
        $result = array();

        if(count($res["Response"]["View"]) > 0){
            $address = $res["Response"]["View"][0]["Result"][0]["Location"]["Address"]["Label"];

            $result["address"] = $address;
            return response($result);
        }

        $result["address"] = "";
        return response($result);
    }

    public static function getLocationFromAddress($address){
        //TODO Change API key, this is Convoy key

        $country = "SWE,SRB"; // TODO if user is login check user country, else disable country in url


        $client = new \GuzzleHttp\Client([]);
        $res = $client->request('GET', "https://geocoder.api.here.com/6.2/geocode.json?app_id=xsJh063eK0TLk0OBOqhJ&app_code=Xm-WmXet_M3RFxEl62dcsw&searchtext=$address&country=$country&language=en", [
            'headers' => ["Content-Type" => "application/json"]
        ]);

        $res = \GuzzleHttp\json_decode($res->getBody()->getContents(), true);
        $result = array();


        if(count($res["Response"]["View"]) > 0){
            $result["lat"] = $res["Response"]["View"][0]["Result"][0]["Location"]["DisplayPosition"]["Latitude"];
            $result["lng"] = $res["Response"]["View"][0]["Result"][0]["Location"]["DisplayPosition"]["Longitude"];

            return response($result);
        }

        $result["lat"] = null;
        $result["lng"] = null;

        return response($result);
    }

}
