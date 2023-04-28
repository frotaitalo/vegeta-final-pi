<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\CommentRequest;
use App\Http\Requests\PutProduct;
use App\Http\Requests\SelledProduct;
use App\Models\Product;
use DateTime;
use App\Models\Comments;
use App\Models\ProductSelled;
use App\Models\User;
use Egulias\EmailValidator\Warning\Comment;
use Illuminate\Http\JsonResponse;
class ProductController extends Controller
{
    public function getAllProduct() : JsonResponse{
        try {
            $products = Product::all();
            return response()->json($products);
        } catch (\Exception $th) {
            return response()->json('error', 400);
        }
    }

    public function findById(int $id) : JsonResponse{
        try {
            $product = Product::findOrFail($id);
            return response()->json($product, 200);
        } catch (\Exception $th) {
            return response()->json('error', 400);
        }
    }

    public function deleteProduct(int $id): JsonResponse{
        try {
            $product = Product::find($id);
            if(!$product){
                return response()->json('Id informado não existe', 422);
            }
            $product->delete();
            return response()->json('Produto deletado!', 200);
        } catch (\Exception $th) {
            return response()->json('error', 400);
        }
    }

    public function updateProduct(PutProduct $request, int $id) :  JsonResponse{
        try {
            $product = Product::findOrFail($id);
            if($request->all() == []){
                return response()->json('Informe ao menos um campo à ser atualizado', 422);
            }
            $product->name = $request->input('name') ?: $product->name;
            $product->price = $request->input('price') ?: $product->price;
            $product->description = $request->input('description') ?: $product->description;
            $product->product_image = $request->input('product_image') ?: $product->product_image;
        
            $product->save();
            return response()->json('Produto atualizado com sucesso', 200);
        } catch (\Exception $th) {
            return response()->json('error', 400);
        }
    }

    public function createProduct(ProductRequest $request) :  JsonResponse{
        try {
            $extensao = $request->file('product_image')->extension();
            $nome = explode('.', $request->file('product_image')->getClientOriginalName());
            $nomeArquivo = uniqid(date('HisYmd') . $nome[0]);
            $nomeArquivo = "{$nome[0]}.{$extensao}";
            $request->file('product_image')->storeAs('public/teste', $nomeArquivo);
            
            $product = Product::create([
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description,
                'product_image' => $nomeArquivo
            ]);
            $product->save();
            return response()->json('Produto criado com sucesso!', 200);
        } catch (\Exception $th) {
            return response()->json($th->getMessage(), 400);
        }    
    }

    public function newComment(CommentRequest $request): JsonResponse{
        try {
            $user = auth()->user(); 
            $product = Product::where('name', $request->product_name)->firstOrFail();
            $maxAssessment = Comments::where('product_id', $product->id)->max('count_assessment');
            $countAssessment = $maxAssessment ? $maxAssessment + 1 : 1;

            $comment = Comments::create([
                'comment' => $request->comment,
                'assessment' => $request->assessment,
                'user_id' => $user->id, 
                'product_id' => $product->id,  
                'count_assessment' => $countAssessment,
                'avg_assessment' => (($product->comments()->avg('assessment') * $product->comments()->count()) + $request->assessment) / ($product->comments()->count() + 1),
            ]);

            $comment->save();
            return response()->json('Comentario adicionado', 200);
        } catch (\Exception $th) {
            return response()->json($th->getMessage(), 400);
        }
    }

    public function deleteComment($id): JsonResponse {
        try {
            $comment = Comment::findOrFail($id);
            if(!$comment){
                return response()->json('Id informado não existe', 422);
            }
            $comment->delete();
            return response()->json('Comentario deletado', 200);
        } catch (\Exception $th) {
            return response()->json('error', 400);
        }
    }

    //Aqui irá retornar os produtos vendidos somente pelo user logado
    public function sell() : JsonResponse{
        try {
            $product = ProductSelled::all();

            return response()->json($product);
        } catch (\Exception $th) {
            return response()->json('error', 400);
        }
    }

    public function selledProducts(SelledProduct $request) : JsonResponse {
        $date = new DateTime();
        $today = $date->format('Y-m-d');
        try {
            $user = User::where('email', $request->email_user)->first();
            $product = Product::where('name', $request->product_name)->firstOrFail();

            if(!$user){
                return response()->json('Usuario invalido', 404);
            }else if(!$product){
                return response()->json('Produto invalido', 404);
            }

            $selledProduct = ProductSelled::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'buy_date' => $today,
                'serie_number' => $request->number_serie,
            ]);

            $selledProduct->save();
            return response()->json($selledProduct, 200);

        } catch (\Exception $th) {
            return response()->json($th, 400);
        }
    }

}

