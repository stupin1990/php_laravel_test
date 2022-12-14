<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Call;

class ApiController extends Controller
{
    public function token(Request $request)
    {
        $email = $request->input('email', false);
        $password = $request->input('password', false);

        if (!$email || !$password) {
            return response()->json([
                'error' => 'Invalid credentials'
            ], 401);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 401);
        }

        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'error' => 'Password is invalid'
            ], 401);
        }

        $user->tokens()->delete();  

        $token = $user->createToken($email);
        $token = explode('|', $token->plainTextToken)[1];

        return response()->json([
            'type' => 'Bearer',
            'token' => $token
        ]);
    }

    public function users(Request $request)
    {
        $users = User::with(['posts', 'comments'])->get();

        return response()->json($users);
    }

    public function posts(Request $request)
    {
        if ($request->isMethod('get')) {
            $posts = Post::with(['comments'])
                ->where('user_id', $request->user()->id)
                ->get();
        }
        else {
            $user_id = $request->input('user_id', false);
            $posts = Post::with(['comments'])
                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('user_id', $user_id);
                })
                ->get();
        }

        return response()->json($posts);
    }

    public function comments(Request $request)
    {
        $user_id = $request->input('user_id', false);
        $post_id = $request->input('post_id', false);

        $comments = Comment::with(['user', 'post'])
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('user_id', $user_id);
            })
            ->when($post_id, function ($query) use ($post_id) {
                return $query->where('post_id', $post_id);
            })
            ->get();

        return response()->json($comments);
    }

    public function calls($break_time = 5, Request $request)
    {
        $calls = Call::getCallsBreaksByMonth($break_time);
        return response()->json($calls);
    }
}
