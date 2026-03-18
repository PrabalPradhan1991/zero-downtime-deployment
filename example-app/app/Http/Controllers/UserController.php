<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserEditRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::select(['id', 'email', 'name'])->paginate(20);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserCreateRequest $request)
    {
       $data = Arr::except($request->validated(), 'role');
       $user = User::create($data);

       $roles = Arr::get($request->validated(), 'role', []);
       collect($roles)->each(function($role) use ($user) {
           Role::create([
               'user_id' => $user->id,
               'role' => $role
           ]);
       });
       return User::with(['roles'])->find($user->id, ['id', 'name', 'email']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return User::with(['roles'])->find($id, ['id', 'name', 'email']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserEditRequest $request, string $id)
    {
        $data = Arr::except($request->validated(), 'role');
        User::where('id', $id)->update($data);

        $roles = Arr::get($request->validated(), 'role', []);
        Role::where('user_id', $id)->delete();
        collect($roles)->each(function($role) use ($id) {
            Role::create([
                'user_id' => $id,
                'role' => $role
            ]);
        });
        return User::with(['roles'])->find($id, ['id', 'name', 'email']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Role::where('user_id', $id)->delete();
        User::where('id', $id)->delete();
        return response()->json(['message' => 'deleted']);
    }
}
