<div class="container mx-auto px-4 py-8 flex flex-col justify-center items-center min-h-screen">
    <div class="bg-white shadow-md rounded-lg overflow-hidden max-w-2xl w-full">
        <div class="p-6">
            <div class="flex items-center justify-center mb-6">
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                    <i class="fas fa-search text-blue-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="text-center mb-6">
                <h1 class="text-6xl font-bold mb-2 text-gray-800">404</h1>
                <h2 class="text-2xl font-semibold mb-4 text-blue-600"><?php echo $titulo ?? 'Página No Encontrada'; ?></h2>
                <p class="text-lg mb-6 text-gray-600"><?php echo $mensaje ?? 'Lo sentimos, la página que buscas no existe.'; ?></p>
            </div>

            <div class="flex flex-col md:flex-row justify-center gap-4 mb-4">
                <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center justify-center">
                    <i class="fas fa-home mr-2"></i> Volver al inicio
                </a>
                <button onclick="history.back()" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i> Regresar
                </button>
            </div>
        </div>
        
        <?php if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true'): ?>
            <div class="border-t border-gray-200 p-6 bg-gray-50">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                        <i class="fas fa-bug text-yellow-500"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Detalles del error</h3>
                </div>
                
                <div class="bg-white border border-gray-200 rounded-md p-4">
                    <pre class="text-sm overflow-auto max-h-60 text-gray-700"><?php echo $mensaje_detallado ?? $mensaje ?? ''; ?></pre>
                </div>
                
                <div class="mt-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 p-4 rounded-r-md">
                    <p><i class="fas fa-info-circle mr-2"></i>Estás viendo esta información detallada porque el modo depuración está activo.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>