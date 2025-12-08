<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $maxRequests = 60, $decayMinutes = 1): Response
    {
        $maxRequests = (int)$maxRequests;
        $decayMinutes = (int)$decayMinutes;
        $key = 'rate_limit:' . $request->ip() . ':' . $request->path();
        
        try {
            $current = Redis::incr($key);
            
            if ($current === 1) {
                Redis::expire($key, $decayMinutes * 60);
            }
            
            if ($current > $maxRequests) {
                return response()->json([
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => Redis::ttl($key)
                ], 429);
            }
            
            $response = $next($request);
            
            $response->headers->set('X-RateLimit-Limit', (string)$maxRequests);
            $response->headers->set('X-RateLimit-Remaining', (string)max(0, $maxRequests - $current));
            $response->headers->set('X-RateLimit-Reset', (string)(time() + Redis::ttl($key)));
            
            return $response;
        } catch (\Exception $e) {
            // If Redis is unavailable, allow the request
            return $next($request);
        }
    }
}

