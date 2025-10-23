# Render Health Check Endpoint
# Créez ce fichier pour ajouter un endpoint de santé

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'environment' => config('app.env'),
    ]);
});
