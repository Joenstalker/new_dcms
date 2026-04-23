<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DistributedUpdateChecker;
use App\Services\DistributedUpdateExecutor;
use App\Http\Middleware\EnsureUserIsAdmin;

class UpdateController
{
    public function check(DistributedUpdateChecker $checker)
    {
        return response()->json($checker->check());
    }

    public function apply(Request $request, DistributedUpdateChecker $checker, DistributedUpdateExecutor $executor)
    {
        $tag = $request->input('tag');
        $res = $checker->check();

        if ($tag) {
            $target = $tag;
        } else {
            if (empty($res['hasUpdate'])) {
                return response()->json(['ok' => false, 'message' => 'No update available'], 400);
            }
            $target = $res['latest'];
        }

        $result = $executor->executeUpdate($target, $res['release'] ?? [], auth()->user()->email ?? 'system');
        return response()->json($result);
    }
}
