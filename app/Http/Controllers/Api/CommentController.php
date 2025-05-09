<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Grocery;
use App\Models\Household;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Household $household, Grocery $grocery)
    {
        $grocery->comments->load('user');
        return $grocery->comments;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Household $household, Grocery $grocery)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        Comment::factory()->create([
            'content' => $validated['content'],
            'grocery_id' => $grocery->id,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['Message' => 'Comment created successfully'], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Household $household, Grocery $grocery, Comment $comment)
    {
        $authUser = Auth::user();

        if (!$authUser->admin && !$authUser->memberHouseholds->contains('id', $household->id) && $comment->user->id !== $authUser->id) {
            return response()->json(['error' => 'Unauthenticated'], 403);
        }

        $comment->content = '[Comment deleted]';
        $comment->save();

        return response()->json(['Message' => 'Comment deleted successfully'], 200);
    }
}
