<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCrmChatEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(!config('app.enable_crm_chat', env('ENABLE_CRM_CHAT', false)), 404);

        return $next($request);
    }
}
