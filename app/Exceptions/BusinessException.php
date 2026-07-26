<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Error de regla de negocio (no un 500). Laravel lo convierte en:
 * - Marketplace (Blade): redirect back con ->with('error', mensaje).
 * - ERP (Filament): las Actions/Pages deben capturarla y mostrar Notification::make()->danger().
 * - API (JSON): 422 con { error, code: 'BUSINESS_ERROR' }.
 *
 * Ver constitution.md §4.
 */
class BusinessException extends Exception
{
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $this->getMessage(),
                'code' => 'BUSINESS_ERROR',
            ], 422);
        }

        return back()->with('error', $this->getMessage());
    }
}
