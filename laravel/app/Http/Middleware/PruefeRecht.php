<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Lässt eine Seite nur zu, wenn die Rolle das Recht aus config/fa.php hat, z. B. recht:halle */
class PruefeRecht
{
    public function handle(Request $request, Closure $next, string $recht): Response
    {
        abort_unless($request->user()?->darf($recht), 403, 'Diese Seite ist für Ihre Rolle nicht freigegeben.');

        return $next($request);
    }
}
