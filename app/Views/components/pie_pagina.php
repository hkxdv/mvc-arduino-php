<footer class="py-6 mt-auto border-t" style="background-color: var(--bg-primary); border-color: var(--border-color);">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
            <!-- Información del sistema -->
            <div class="mb-6 md:mb-0">
                <h5 class="text-xl font-semibold mb-3 flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-2" style="background-color: var(--accent-transparent);">
                        <i class="fas fa-microchip text-blue-500"></i>
                    </div>
                    <span style="color: var(--text-primary);">Arduino MVC</span>
                </h5>
                <p style="color: var(--text-secondary);" class="mb-1 text-sm">Sistema de monitoreo de temperatura usando Arduino con sensor LM35.</p>
                <p style="color: var(--text-secondary);" class="mb-3 text-sm">Desarrollado con PHP, Arduino y arquitectura MVC.</p>

                <div class="flex space-x-3 mt-4">

                    <a href="#" class="text-gray-400 hover:text-blue-500 transition-colors">
                        <i class="fab fa-github fa-lg"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-blue-500 transition-colors ml-3">
                        <i class="fab fa-linkedin fa-lg"></i>
                    </a>

                </div>
            </div>

            <!-- Enlaces rápidos -->
            <div>
                <h6 class="text-lg font-medium mb-4" style="color: var(--text-primary);">Enlaces Rápidos</h6>
                <ul class="space-y-2">
                    <li>
                        <a href="index.php" class="text-sm transition-colors duration-200 flex items-center"
                            style="color: var(--text-secondary);"
                            onmouseover="this.style.color='var(--accent-color)';"
                            onmouseout="this.style.color='var(--text-secondary)';">
                            <i class="fas fa-chevron-right mr-2 text-xs"></i>Inicio
                        </a>
                    </li>
                    <li>
                        <a href="index.php?option=arduino/mostrar" class="text-sm transition-colors duration-200 flex items-center"
                            style="color: var(--text-secondary);"
                            onmouseover="this.style.color='var(--accent-color)';"
                            onmouseout="this.style.color='var(--text-secondary)';">
                            <i class="fas fa-chevron-right mr-2 text-xs"></i>Temperatura
                        </a>
                    </li>
                    <li>
                        <a href="index.php?option=arduino/configurar" class="text-sm transition-colors duration-200 flex items-center"
                            style="color: var(--text-secondary);"
                            onmouseover="this.style.color='var(--accent-color)';"
                            onmouseout="this.style.color='var(--text-secondary)';">
                            <i class="fas fa-chevron-right mr-2 text-xs"></i>Configuración
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Información del sensor -->
            <div>
                <h6 class="text-lg font-medium mb-4" style="color: var(--text-primary);">Sobre el Sensor LM35</h6>
                <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mt-1 mr-2 text-green-500"></i>
                        <span>Sensor de temperatura de precisión calibrado en grados Celsius</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mt-1 mr-2 text-green-500"></i>
                        <span>Rango lineal de +2°C a +150°C</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mt-1 mr-2 text-green-500"></i>
                        <span>Precisión de ±0.5°C (a 25°C)</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mt-1 mr-2 text-green-500"></i>
                        <span>Baja impedancia de salida: 0.1Ω para cargas de 1mA</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>