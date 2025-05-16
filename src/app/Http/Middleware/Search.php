<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Condition;
use Closure;

class Search
{
    public function handle($request, Closure $next)
    {
        $categories = Category::all();
        $conditions = Condition::all();

        // View Composerでデータを共有
        view()->share([
            'categories' => $categories,
            'conditions' => $conditions,
        ]);

        return $next($request);
    }
}