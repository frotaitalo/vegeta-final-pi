<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\CommentRequest;
use App\Http\Requests\SelledProduct;
use App\Models\Product;
use DateTime;
use GuzzleHttp\Psr7\Request;
use App\Models\Comments;
use App\Models\ProductSelled;
use App\Models\User;
use Egulias\EmailValidator\Warning\Comment;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\JsonResponseHelper;
use App\Http\Helpers\TradeProductHelper;
use App\Http\Requests\TradeRequest;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function getAllProduct() : JsonResponse{
        try {
            $products = Product::all();
            return JsonResponseHelper::jsonResponse(['products' => $products]);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function findById(int $id) : JsonResponse{
        try {
            $product = Product::findOrFail($id);
            return JsonResponseHelper::jsonResponse(['product' => $product]);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message'=>$th->getMessage()], 500);
        }
    }

    public function deleteProduct(int $id): JsonResponse{
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return JsonResponseHelper::jsonResponse(['message' => 'Produto deletado de id ' . $product->id . ' deletado com sucesso']);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message'=>$th->getMessage()], 500);
        }
    }
    public function updateProduct(Request $request, int $id) :  JsonResponse{
        try {
            $product = Product::findOrFail($id);
            $product->name = $request->input('name') ?: $product->name;
            $product->price = $request->input('price') ?: $product->price;
            $product->description = $request->input('description') ?: $product->description;
            $product->product_image = $request->input('product_image') ?: $product->product_image;
            $product->save();
            return JsonResponseHelper::jsonResponse(['message' => 'Produto de id ' . $product->id . ' atualizado com sucesso']);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message'=>$th->getMessage()], 500);
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
            return JsonResponseHelper::jsonResponse(['message'=>'Produto criado']);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message'=>$th->getMessage()], 500);
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
            return JsonResponseHelper::jsonResponse(['message' => 'comentario adicionado para o produto de id ' . $product->id]);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function updateComment(Request $request, $id) : JsonResponse {
        try {
            $comment = Comments::findOrFail($id);
            $comment->comment = $request->input('comment') ?: $comment->comment;
            $comment->save();
            return JsonResponseHelper::jsonResponse(['message'=>'Comentario de id' . $comment->id. ' atualizado']);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message'=>$th->getMessage(), 500]);
        }
    }

    public function deleteComment(int $id): JsonResponse {
        try {
            $comment = Comment::findOrFail($id);
            $comment->delete();
            return JsonResponseHelper::jsonResponse(['message' => 'Comentario deletado']);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function showComment(int $productId) : JsonResponse{
        try {
            $getComment = Comments::where('product_id', $productId)->get();
            $comments = [];
            foreach ($getComment as $comment) {
                $user = User::find($comment->user_id);
                $commentData = [
                    'User' => $user->name,
                    'comment' => $comment->comment,
                    'assessment' => $comment->assessment,
                ];
                array_push($comments, $commentData);
        }
            return JsonResponseHelper::jsonResponse(['comment' => $comments]);

        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' =>  $th->getMessage()], 500);
        }
    }

    public function userProduct() : JsonResponse{
        try {
            $user = auth()->user();
            $product = Product::join('product_selleds', 'products.id', '=', 'product_selleds.product_id' )
                            ->where('product_selleds.user_id', $user->id)
                            ->select('products.*')
                            ->get();
            if(!$product->isEmpty()){
                return JsonResponseHelper::jsonResponse(['product' => $product]);
            }else {
                return JsonResponseHelper::jsonResponse(['message' => 'Você ainda não possui produtos'], 500);
            }            
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function selledProducts(SelledProduct $request) : JsonResponse {
        $date = new DateTime();
        $today = $date->format('Y-m-d');
        try {
            $user = User::where('email', $request->email_user)->firstOrFail();
            $product = Product::where('name', $request->product_name)->firstOrFail();

            if (ProductSelled::where('user_id', $user->id)
                            ->where('product_id', $product->id)
                            ->where('serie_number', $request->number_serie)
                            ->exists()){
                return JsonResponseHelper::jsonResponse(['message' => 'O produto já existe para o usuário'], 500);
            }
            $selledProduct = ProductSelled::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'buy_date' => $today,
                'serie_number' => $request->number_serie,
            ]);
            $selledProduct->save();
            return JsonResponseHelper::jsonResponse(['message' => 'Produto vendido para o usuario ' . $user->name]);
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function tradeProduct(TradeRequest $request) : JsonResponse{
        try {
            $productTrader = new TradeProductHelper(auth(), $request, $this);
            return $productTrader->tradeProduct();
        } catch (\Exception $th) {
            return JsonResponseHelper::jsonResponse(['message' => $th->getMessage()], 500);
        }    
    }
}

