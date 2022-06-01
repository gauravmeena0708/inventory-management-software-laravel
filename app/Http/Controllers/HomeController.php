<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agreement;
use App\Models\Consumable;
use App\Models\Device;
use App\Models\Server;
use App\Models\Task;
use App\Models\Payment;

class HomeController extends Controller
{
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //$data['Tasks Pending'] = Task::where('status', '!=' , 'completed')->count();

        $data[0] = [ 
            'title'=> 'Agreement Expired',
            'data'=>Agreement::where('expiry', '<' , now())->count(),
            'link'=>'./agreements/expired'
        ];
        $data[1] = [
            'title'=>'Agreement due to expire in 6 Month',
            'data'=>Agreement::where('expiry', '<' , now()->addDays(180))->where('expiry', '>' , now())->count(),
            'link'=>'./agreements/due'
        ];
        $data[2] = [
            'title'=>'Consumables out of Stock',
            'data'=>Consumable::where('min_quantity', '<' , 10)->count(),
            'link'=>'./consumables/due'
        ];
        //$data['Servers for which AMC is expired'] = Server::where('contract_expiry', '=' , NULL)->count();
        //$data['Switches with no support date or AMC'] = Device::where('support_date', '=' , NULL)->count();
        //$data['Payments Due'] = Payment::where('due_date','<',now())-> where('pending',1)->count();
        return view('home',['data'=>$data]);
    }
}
