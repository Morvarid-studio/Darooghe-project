<?php

namespace App\Http\Controllers;

use App\Models\BankInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankInfoController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'        => 'required|integer',
            'bank_name'      => 'required|string|max:255',
            'branch_name'    => 'nullable|string|max:255',
            'branch_code'    => 'nullable|string|max:50',
            'account_number' => 'required|string|max:60',
            'sheba'          => 'required|string',
            'status'         => 'nullable|string',
            'is_active'      => 'nullable|boolean',

        ]);

        $bankInfo = BankInfo::create($validated);



        return response()->json([
            'message' => 'اطلاعات با موفقیت ثبت شد.',
            'data' => $bankInfo
        ], 201);
    }



    public function show(Request $request)
    {
        $user = Auth::user();

        $bankInfos = BankInfo::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // بازگرداندن JSON
        return response()->json($bankInfos);
    }


    public function update(Request $request)
    {

        $oldBankInfo = BankInfo::where('user_id', auth()->id())
            ->where('archived', false)
            ->first();


        $validated = $request->validate([
            'bank_name'      => 'sometimes|required|string|max:255',
            'branch_name'    => 'sometimes|required|string|max:255',
            'branch_code'    => 'sometimes|required|string|max:50',
            'account_number' => 'sometimes|required|string|max:60',
            'sheba'          => 'sometimes|required|string',
            'status'         => 'nullable|string',
            'is_active'      => 'nullable|boolean',
        ]);


        if ($oldBankInfo) {
            $oldBankInfo->update([
                'archived' => true,
            ]);
        }


        $newBankInfo = BankInfo::create(array_merge(
            $validated,
            [
                'user_id' => auth()->id(),
                'archived' => false,
            ]
        ));

        return response()->json([
            'message' => 'اطلاعات جدید ثبت شد.',
            'data' => $newBankInfo
        ]);
    }

}
