<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\StoreRequest;
use App\Http\Requests\Finance\UpdateRequest;
use App\Models\Finance;

class FinanceController extends Controller
{


    public function index()
    {
        return view('user.finance.index', [
            'finances' => Finance::all(),
            'consumption' => Finance::sum('payment'),
            'daily_consumption'=> Finance::whereDate('created_at', today())->sum('payment')
        ]);
    }

    public function store(StoreRequest $request)
    {

        $validatedData = $request->validated();

//        $validatedData['status'] = Finance::STATUS_OTHER;

        $data = Finance::query()->create($validatedData);

//        dd($data);

        return redirect()->back()->with('success', 'Finance created successfully!');
    }

    public function update(UpdateRequest $request, $id)
    {
        $validatedData = $request->validated();
//        $validatedData['status'] = Finance::STATUS_OTHER;

        $finance = Finance::query()->findOrFail($id);
        $finance->update($validatedData);

        return redirect()->back()->with('success', 'Finance updated successfully!');
    }

    public function destroy($id)
    {
        $finance = Finance::query()->where('id', $id)->firstOrFail();
        $finance->delete();

        return redirect()->back()->with('success', 'Finance deleted successfully!');
    }
}
