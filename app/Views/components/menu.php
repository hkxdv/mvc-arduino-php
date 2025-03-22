<!-- Menú de navegación principal -->
<nav class="border-b border-slate-300 dark:border-slate-600" style="background-color: var(--background-secondary); box-shadow: var(--shadow-sm);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo y título -->
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="index.php" class="flex items-center" style="color: var(--text-primary);">
                        <i class="fas fa-microchip text-xl mr-2"></i>
                        <span class="font-bold text-xl">Arduino MVC</span>
                    </a>
                </div>
            </div>

            <!-- Enlaces de navegación escritorio -->
            <div class="hidden md:flex md:items-center md:space-x-2">
                <ul class="flex space-x-1">

                    <li>
                        <a class="px-3 py-4 flex items-center rounded-md transition-all duration-200"
                            style="color: var(--text-secondary);"
                            onmouseover="this.style.backgroundColor='var(--accent-transparent)'; this.style.color='var(--text-primary)';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)';"
                            href="index.php?option=arduino/mostrar">
                            <i class="fas fa-thermometer-half mr-2"></i>Temperatura
                        </a>
                    </li>

                    <li>
                        <a class="px-3 py-4 flex items-center rounded-md transition-all duration-200"
                            style="color: var(--text-secondary);"
                            onmouseover="this.style.backgroundColor='var(--accent-transparent)'; this.style.color='var(--text-primary)';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)';"
                            href="index.php?option=arduino/configurar">
                            <i class="fas fa-cog mr-2"></i>Configuración
                        </a>
                    </li>

                    <li>
                        <a class="px-3 py-4 flex items-center rounded-md transition-all duration-200"
                            style="color: var(--text-secondary);"
                            onmouseover="this.style.backgroundColor='var(--accent-transparent)'; this.style.color='var(--text-primary)';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)';"
                            href="index.php?option=arduino/diagnostico">
                            <i class="fas fa-stethoscope mr-2"></i>Diagnóstico
                        </a>
                    </li>

                    <li>
                        <a class="px-3 py-4 flex items-center rounded-md transition-all duration-200"
                            style="color: var(--text-secondary);"
                            onmouseover="this.style.backgroundColor='var(--accent-transparent)'; this.style.color='var(--text-primary)';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)';"
                            href="index.php?option=arduino/webserver">
                            <i class="fas fa-server mr-2"></i>WebSocket
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>

</nav>

<script>
    // Resaltar opción activa en el menú
    document.addEventListener('DOMContentLoaded', () => {
        // Obtener la URL actual
        const currentUrl = window.location.href;

        // Encontrar todos los enlaces en el menú
        const menuLinks = document.querySelectorAll('nav a');

        // Iterar sobre los enlaces y aplicar estilo al enlace activo
        menuLinks.forEach(link => {
            if (currentUrl.includes(link.getAttribute('href'))) {
                // Aplicar estilo de enlace activo
                link.style.backgroundColor = 'var(--accent-transparent)';
                link.style.color = 'var(--accent)';
                link.style.fontWeight = 'bold';

                // Modificar comportamiento de hover
                link.onmouseover = function() {
                    this.style.backgroundColor = 'var(--accent-transparent)';
                    this.style.color = 'var(--accent)';
                };

                link.onmouseout = function() {
                    this.style.backgroundColor = 'var(--accent-transparent)';
                    this.style.color = 'var(--accent)';
                };
            }
        });
    });
</script>