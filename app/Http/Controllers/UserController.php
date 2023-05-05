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
                $response = JsonResponseHelper::jsonResponse($token, 'Login efetuado com sucesso', true);   
            } else {
                $response = JsonResponseHelper::jsonResponse([], 'Usuário ou senha incorreto', false, 401);    
            }
        } catch (\Exception $th) {
            $response = JsonResponseHelper::jsonResponse([], $th->getMessage(), false, 500);
        }
        return $response;
    }

    public function allUsers(): JsonResponse{
        try {
            $User = User::all();
            $response = JsonResponseHelper::jsonResponse($User, [], true);   
        } catch (\Exception $th) {
            $response = JsonResponseHelper::jsonResponse([], $th->getMessage(), false, 500);
        }
        return $response;
    }

    public function deleteUser(int $id): JsonResponse{
        try {
            $User = User::findOrfail($id);
            $User->delete();
            $response = JsonResponseHelper::jsonResponse([], 'Usuario deletado com sucesso!', true, 200);
        } catch (\Exception $th) {
            $response = JsonResponseHelper::jsonResponse([], $th->getMessage(), false, 500);
        }
        return $response;
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
            $response= JsonResponseHelper::jsonResponse([], 'Usuario Criado', true, 201);
        } catch (\Exception $th) {
            $response= JsonResponseHelper::jsonResponse([], $th->getMessage(), false, 500);
        }
        return $response;
    }

    public function updateUser(PutUsers $request, int $id) : JsonResponse{
        try {
            $User = User::findorfail($id);
            $User->name = $request->input('name') ?: $User->name; 
            $User->email = $request->input('email') ?: $User->email; 
            $User->password = $request->input('password')?: bcrypt($User->password);
            $User->save();
            $response = JsonResponseHelper::jsonResponse($User, 'Usuario atualizado', true);
        } catch (\Exception $th) {
            $response = JsonResponseHelper::jsonResponse([], $th->getMessage(), false, 500);
        }
        return $response;
        
    }
}
