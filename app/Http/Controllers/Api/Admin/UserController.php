<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
   // ...

public function index(Request $request)
{
    $query = \App\Models\User::query()->with('kyc_record');

    if ($search = $request->string('q')->toString()) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    if ($ks = $request->string('kyc_status')->toString()) {
        $query->where('kyc_status', $ks); // approved|rejected|pending
    }

    $users = $query->orderByDesc('id')->paginate(20);

    return \App\Http\Resources\Api\UserResource::collection($users);
}

public function show(\App\Models\User $user)
{
    $user->load(['kyc_record', 'loans' => fn($q) => $q->latest()]);
    return new \App\Http\Resources\Api\UserResource($user);
}

}
