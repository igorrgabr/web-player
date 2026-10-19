<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforceLoopbackHost
{
    public function handle(Request $request, Closure $next)
    {
        if (strtolower($request->getHost()) === 'localhost') {
            $port = $request->getPort();
            $portSegment = ($port && ! in_array($port, [80, 443], true)) ? ':' . $port : '';
            $targetUrl = $request->getScheme() . '://127.0.0.1' . $portSegment . $request->getRequestUri();

            return redirect()->to($targetUrl, 302);
        }

        return $next($request);
    }
}

