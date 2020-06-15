<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Session;
use App\WatchList;
use App\WatchListNonAuth;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->middleware('guest');
        $this->request = $request;
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'name' => ['max:255'],
            'email' => ['max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $session_id = $this->request->session()->getId();

        //Create user
        // $user = User::create([
        //     'username' => $data['username'],
        //     'name' => isset($data['name']) ? $data['name']:'',
        //     'email' => isset($data['email']) ? $data['email']:'',
        //     'password' => Hash::make($data['password'])
        // ]);

        $user = new User();
        $user->username = $data['username'];
        $user->name = isset($data['name']) ? $data['name']:'';
        $user->email = isset($data['email']) ? $data['email']:'';
        $user->password = Hash::make('userpassword');
        $user->save();

        //Update WatchList
        $select = WatchListNonAuth::where('session_id', '=', $session_id)
            ->select(DB::raw("$user->id"), 'movie_id','created_at','updated_at');
        WatchList::insertUsing(['user_id', 'movie_id','created_at','updated_at'], $select);

        //Update MovieUser
        $select = DB::table('movie_user_non_auth')
            ->where('session_id', '=', $session_id)
            ->select(DB::raw("$user->id"), 'movie_id', 'favourite', 'created_at','updated_at');
        DB::table("movie_user")
            ->insertUsing(['user_id', 'movie_id', 'favourite', 'created_at','updated_at'], $select);

        return $user;
    }
}
