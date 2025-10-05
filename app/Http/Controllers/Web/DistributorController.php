<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\User;

class DistributorController extends Controller
{
	public function index()
	{
		return [
			'rows' => Distributor::all()->each(function($v){
				$v->name = User::find($v->user_id)->name;
				if ($v->parentid)
				{
					$v->_parentId = $v->parentid;
				}
			})
		];
	}

	public function create(\App\Http\Requests\Distributor\CreateRequest $request)
	{
		$parentid = request('parentid', 0);

		if ($parentid)
		{
			$parentid = Distributor::where('user_id', $parentid)->first()->id;
		}

		$distributor = Distributor::create([
			'user_id'  => request('user_id'),
			'parentid' => $parentid,
			'number'   => 0
		]);

		$distributor->name = User::find($distributor->user_id)->name;

		return $distributor;
	}

	public function update(\App\Http\Requests\Distributor\UpdateRequest $request)
	{
		$parentid = request('parentid', 0);

		if ($parentid)
		{
			$parentid = Distributor::where('user_id', $parentid)->first()->id;
		}

		Distributor::find(request('id'))->update([
			'parentid' => $parentid,
		]);
	}

	public function remove(\App\Http\Requests\Distributor\RemoveRequest $request)
	{
		Distributor::find(request('id'))->delete();
	}

	public function updateNumber()
	{
		Distributor::chunk(100, function($distributors){
			foreach ($distributors as $distributor) {
				$count = $distributor->getAllChild()->count();
				$distributor->update([
					'number' => $count ? $count-1 : 0
				]);
			}
		});
	}
}
