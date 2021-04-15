<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\AccountRepositoryInterface;
use Illuminate\Support\Facades\Redis;

class AccountRepository implements AccountRepositoryInterface
{
    /**
    * @return all users 
    * with pagination
    */
    public function all()
    {
        try{
            $clients =  Client::with('users')->paginate(10);
            $client_data = [];
    
            foreach($clients as $client){
                $my_data = [ 
                    "id"   => $client->id,
                    "name" => $client->client_name,
                    "address1" =>  $client->address1,
                    "address2" =>  $client->address2,
                    "city"     =>  $client->city,
                    "state"    =>  $client->state,
                    "country"  =>  $client->country,
                    "zipCode"  =>  $client->phone_no1,
                    "latitude" =>  $client->latitude,
                    "longitude"=>  $client->longitude,
                    "phoneNo1" =>  $client->phone_no1,
                    "phoneNo2" =>  $client->phone_no2,
                    "totalUser" => [
                        "all"      => $client->users->count(),
                        "active"   => $client->users->where('status',"Active")->count(),
                        "inactive" => $client->users->where('status',"Inactive")->count(),
                    ],
                    "startValidity" => $client->start_validity,
                    "endValifity"   => $client->end_validity,
                    "status"        => $client->status,
                    "createdAt"     => $client->created_at,
                    "updatedAt"     => $client->updated_at
                ];
                array_push($client_data, $my_data);
            }
            
            $links = [
                "path"         => $clients->path(),
                "firstPageUrl" => $clients->url(1),
                "lastPageUrl"  => $clients->url($clients->lastPage()),
                "nextPageUrl"  => $clients->nextPageUrl(),
                "prevPageUrl"  => $clients->previousPageUrl()
            ];

            $meta = [
                "currentPage" => $clients->currentPage(),
                "from"        => $clients->firstItem(),
                "lastPage"    => $clients->lastPage(),
                "perPage"     => $clients->perPage(),
                "to"          => $clients->lastItem(),
                "total"       =>  $clients->total(),
                "count"       =>  $clients->count()
            ];

            $data = ["data" => $client_data, "links" => $links, "meta" => $meta];
            return $data;
            
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 400);
        }
    }

    /**
    * register new client and user
    *
    */
    public function register(Request $request)
    {
        try{
            $validator = Validator::make($request->all(),[
                "name"     =>  'required|string|max:100',
                "address1" =>  'required',
                "address2" =>  'required',
                "city"     =>  'required|string|max:100',
                "state"    =>  'required|string|max:100',
                "country"  =>  'required|max:100',
                "zipCode"  =>  'required|max:20',
                "phoneNo1" =>  'required|max:20',
                "user.firstName" =>  'required|max:100',
                "user.lastName"  =>  'required|max:100',
                "user.email"     =>  'required|email|max:150|unique:App\Models\User,email',
                "user.password"  =>  'required|max:255',
                "user.passwordConfirmation"  =>  'required|max:255|same:user.password',
                "user.phone"     =>  'required|max:20'

            ]);
            
            if ($validator->fails()) {
                return response()->json($validator->messages(), 400);
            }

            $redis_data = Redis::get($request->address1);

            if($redis_data){
                return $redis_data;
                $redis_data = json_decode($redis_data,true);
                $lat = $redis_data['latitude'];
                $long =$redis_data['longitude'];

            }else{
                $arrContextOptions=array(
                    "ssl"=>array(
                        "verify_peer"=>false,
                        "verify_peer_name"=>false,
                    ),
                );
                
                $apiKey = env('GEOCODE_API_KEY'); //Please add your working Geocode api key in env file.
                
                @$json = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($request->address1)."&sensor=false&key=".$apiKey,false,stream_context_create($arrContextOptions));
                @$lat  = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
                @$long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};

                Redis::set($request->address1, json_encode(["latitude" => @$lat, "longitude" => @$long]));
            }

            if($lat && $long)
            {
                $start_validity = Carbon::now()->format("Y-m-d");
                $end_validity   = Carbon::now()->addDays(15)->format("Y-m-d");
                $status = "Active";
                $last_password_reset = Carbon::now()->format("Y-m--d H:i:s");

                $client_data = [
                    "client_name" => $request->name,
                    "address1" => $request->address1,
                    "address2" => $request->address2,
                    "city" => $request->city,
                    "state" => $request->state,
                    "country" => $request->country,
                    "latitude" => @$lat,
                    "longitude" => @$long,
                    "phone_no1" => $request->phoneNo1,
                    "phone_no2" => $request->phoneNo2,
                    "zip" => $request->zipCode,
                    "start_validity" => $start_validity,
                    "end_validity" => $end_validity,
                    "status" => $status
                ];
                $client = Client::create($client_data);

                $user_data = [
                    "client_id" => $client->id,
                    "first_name" => $request->user['firstName'],
                    "last_name" => $request->user['lastName'],
                    "email" => $request->user['email'],
                    "password" => Hash::make($request->user['password']),
                    "phone" => $request->user['phone'],
                    "last_password_reset" => $last_password_reset,
                    "status" => $status
                ];
                User::create($user_data);
                Db::commit();
                return response()->json('account registered successfully', 200);
            }else{
                return response()->json("No coordinates found,Please add a correct address", 400);
            }
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json($e->getMessage(), 400);
        }
    }

}