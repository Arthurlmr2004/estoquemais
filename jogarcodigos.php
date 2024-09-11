<script>
        function calcularPrecoTotal() {
            const precoInput = document.getElementById('preco_produto');
            const quantidadeInput = document.getElementById('quantidade');
            const precoTotalInput = document.getElementById('preco_total');

            const preco = parseFloat(precoInput.value);
            const quantidade = parseInt(quantidadeInput.value, 10);

            if (!isNaN(preco) && !isNaN(quantidade)) {
                precoTotalInput.value = (preco * quantidade).toFixed(2);
            } else {
                precoTotalInput.value = '0.00';
            }
        }

        function atualizarPreco() {
            const produtoSelect = document.getElementById('produto_id');
            const precoInput = document.getElementById('preco_produto');

            const selectedOption = produtoSelect.options[produtoSelect.selectedIndex];
            precoInput.value = selectedOption.dataset.preco;

            calcularPrecoTotal();
        }

        // Função para definir a data de hoje automaticamente no campo de data
        function definirDataAtual() {
            const dataVendaInput = document.getElementById('data_venda');
            const hoje = new Date();

            // Formatando a data no formato YYYY-MM-DD
            const dia = String(hoje.getDate()).padStart(2, '0');
            const mes = String(hoje.getMonth() + 1).padStart(2, '0');
            const ano = hoje.getFullYear();

            // Define o valor do input como a data de hoje
            dataVendaInput.value = `${ano}-${mes}-${dia}`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Chama a função para definir a data atual quando a página carregar
            definirDataAtual();

            document.getElementById('produto_id').addEventListener('change', atualizarPreco);
            document.getElementById('quantidade').addEventListener('input', calcularPrecoTotal);
            document.querySelector('form').addEventListener('submit', function(event) {
                if (!validarData()) {
                    event.preventDefault();
                }
            });

            // Mostrar o modal se necessário
            <?php if ($showModal): ?>
                document.getElementById('successModal').style.display = 'flex';
            <?php endif; ?>
        });

        // Função para fechar o modal
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
            window.location.href = 'painel.php?page=cadastro_vendas';
        }
    </script>