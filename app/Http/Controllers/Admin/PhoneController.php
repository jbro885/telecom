<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Phone;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Validator;
use Laracasts\Flash\Flash;

class PhoneController extends Controller {

	protected function formatValidationErrors(Validator $validator)
	{
		Flash::error($validator->errors()->first());
		return $validator->errors()->all();
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$users = User::all();
		$users->load('phone');
		$users->load('contract');
		$phones = Phone::all();
		$revenue = 0;
		$phoneSales = [];
		foreach($users as $user)
		{
			foreach($user->phone as $phone)
			{
				$phoneSales[] = $phone;
				$revenue+=$phone->costs;
			}
			foreach($user->contract as $contract)
			{
				$revenue+=$contract->phone_price;
			}
		}

		return view('admin.phone.index',['phones'=>$phones,'revenue'=>$revenue,'phoneSales'=>$phoneSales]);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('admin.phone.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
		$rules = [
			'brand'         =>  'required|alpha_dash',
		    'model'         =>  'required|alpha_num',
		    'description'   =>  'required',
		    'costs'         =>  'required|numeric',
		];
		$this->validate($request,$rules);

		$data = [
			'brand'         =>  $request->brand,
			'model'         =>  $request->model,
		    'description'   =>  $request->description,
		    'costs'         => $request->costs,
		];

		Phone::create($data);
		if($request->continue==='')
		{
			Flash::success('The phone has been added');
			return Redirect::action('Admin\PhoneController@create');
		}
		else{
			Flash::success('The phone has been added');
			return Redirect::action('Admin\PhoneController@index');
		}

	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$phone = Phone::find($id);

		return view('admin.phone.show',['phone'=>$phone]);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$phone = Phone::find($id);
		if(is_array(json_decode($phone->pictures))) {
			$pictures = json_decode($phone->pictures);
		}
		else{
			$pictures = [];
		}

		return view('admin.phone.edit',['phone'=>$phone,'pictures'=>$pictures]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$phone = Phone::find($id);
		$data = Input::except('_token','label','continue');
		$phone->update($data);
		if (isset($_POST['continue'])) {
			return Redirect::action('Admin\PhoneController@edit',$phone->id);
		}
		else{
			return Redirect::action('Admin\PhoneController@all');
		}
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		Phone::destroy($id);
		Flash::success('The phone has been deleted');
		return Redirect::action('Admin\PhoneController@all');
	}

	public function all()
	{
		$phones = Phone::all();

		return view('admin.phone.list',['phones'=>$phones]);
	}

	public function addImage($id)
	{
		if(Input::hasFile('file')&&Input::file('file')->isValid())
		{
			$phone = Phone::find($id);
			$name = time().Input::file('file')->getClientOriginalName();
			$location = 'images/phones/';
			$file = Input::file('file')->move($location,$name);

			$pictures = json_decode($phone->pictures);
			$pictures[] = $location.$name;
			$data['pictures']=json_encode($pictures);
			$phone->update($data);

			echo '{"jsonrpc" : "2.0", "result" : OK, "id" : "id"}';
		}
		else
		{
			echo '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}';
		}
	}

}
