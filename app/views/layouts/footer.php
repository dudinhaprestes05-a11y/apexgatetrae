    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-gray-500 text-sm">
                <p>&copy; <?= date('Y') ?> Gateway PIX. Todos os direitos reservados.</p>
                <p class="mt-2">Plataforma de pagamentos PIX segura e confiável</p>
            </div>
        </div>
    </footer>

    <script>
        setTimeout(() => {
            document.querySelectorAll('.fade-in').forEach(el => {
                if (el.classList.contains('bg-green-50') || el.classList.contains('bg-red-50')) {
                    el.style.transition = 'opacity 0.5s';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 500);
                }
            });
        }, 5000);

        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.add('text-green-600');
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('text-green-600');
                }, 2000);
            });
        }

        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        }

        function formatDocument(doc) {
            doc = doc.replace(/\D/g, '');
            if (doc.length === 11) {
                return doc.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (doc.length === 14) {
                return doc.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }
            return doc;
        }
    </script>
</body>
</html>
