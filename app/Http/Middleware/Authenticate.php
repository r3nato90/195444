<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Se a requisição for API ou se não esperar explicitamente HTML, retorna NULL.
        // Isso faz o Laravel retornar um JSON {"message": "Unauthenticated"} 
        // com status 401, em vez de tentar redirecionar e dar erro de "Route not defined".
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // Apenas para rotas WEB (fora da API) que não pedem JSON
        return url('/'); 
    }
}