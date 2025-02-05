<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Messages\Message;
use App\Models\Comment;
use Exception;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function store($item_id, CommentRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();
        $validated['item_id'] = $item_id;
        $validated['user_id'] = $user->id;

        try {
            Comment::create($validated);
            return back()->with('message', Message::get('comment.success.create'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return back()
                ->withInput()
                ->with('message', Message::get('comment.failed.create'));
        }
    }

    public function update(CommentRequest $request, $item_id, $comment_id)
    {
        $user = auth()->user();

        try {
            Comment::where('id', $comment_id)
                // 他のユーザのコメントは編集させない
                ->where('user_id', $user->id)
                ->update(['comment' => $request->comment]);
            return redirect()
                ->route('item.show', ['item_id' => $item_id])
                ->with('message', Message::get('comment.success.update'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return back()
                ->withInput()
                ->with('message', Message::get('comment.failed.update'));
        }
    }

    public function delete($item_id, $comment_id)
    {
        $user = auth()->user();
        Comment::where('id', $comment_id)
            // 他のユーザのコメントは削除させない
            ->where('user_id', $user->id)
            ->delete();
        return redirect()
            ->route('item.show', ['item_id' => $item_id])
            ->with('message', Message::get('comment.success.delete'));
    }
}
