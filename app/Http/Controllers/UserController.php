<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\PutUsers;
use App\Http\Requests\UserRequest;
use App\Mail\sendMailRegister;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Helpers\JsonResponseHelper;

class UserController extends Controller
{
    public function login(LoginRequest $request){
        try {
            if (Auth::attempt($request->only('email', 'password'))){
                $user = Auth::user();
                $token = $user->createToken('token')->plainTextToken;
                return JsonResponseHelper::jsonResponse(['token'=> $token, 'message'=>'Login efetuado com sucesso']);
            } else {
                return JsonResponseHelper::jsonResponse(['message'=> 'Usuario ou senha invalido'], 401);       
            }
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message'=> $th->getMessage()], 500);
        }
    }

    public function allUsers(): JsonResponse{
        try {
            $User = User::all();
            return JsonResponseHelper::jsonResponse($User);   
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function deleteUser(int $id): JsonResponse{
        try {
            $User = User::findOrfail($id);
            $User->delete();
            return JsonResponseHelper::jsonResponse(['message'=>'Usuario deletado com sucesso!']);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function createUser(UserRequest $request): JsonResponse{
        $nameUser = $request->name;
        try {
            User::create([
                'name' => $nameUser,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);
            // Mail::to($request->email)->send(new sendMailRegister($nameUser));
            return JsonResponseHelper::jsonResponse(['message' => 'Usuario Criado']);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function updateUser(PutUsers $request, int $id) : JsonResponse{
        try {
            $User = User::findorfail($id);
            $User->name = $request->input('name') ?: $User->name; 
            $User->password = bcrypt($request->input('password')) ?: bcrypt($User->password);
            $User->save();
            return JsonResponseHelper::jsonResponse([$User, 'message' => 'Usuario atualizado']);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' => $th->getMessage()], 500);
        }
    }
}
