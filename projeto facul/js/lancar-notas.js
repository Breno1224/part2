document.addEventListener("DOMContentLoaded", () => {
    // Remova a simulação de turmas e disciplinas se elas são carregadas pelo PHP
    // const turmas = ["1A", "2B"];
    // const disciplinas = ["Matemática", "História"];
    // ... (código antigo de popular selects)
});

async function carregarAlunos() {
    const turmaId = document.getElementById("turmaSelect").value;
    const disciplinaId = document.getElementById("disciplinaSelect").value;
    const avaliacao = document.getElementById("avaliacaoInput").value.trim();
    const bimestre = document.getElementById("bimestreSelect").value;
    const statusMessage = document.getElementById("statusMessage");

    statusMessage.classList.add('hidden'); // Esconde mensagens antigas

    if (!turmaId || !disciplinaId || !avaliacao || !bimestre) {
        alert("Por favor, selecione Turma, Disciplina, preencha a Avaliação e selecione o Bimestre antes de carregar os alunos.");
        return;
    }

    // Preenche os campos ocultos no formulário de notas
    document.getElementById("turma_id_form").value = turmaId;
    document.getElementById("disciplina_id_form").value = disciplinaId;
    document.getElementById("avaliacao_form").value = avaliacao;
    document.getElementById("bimestre_form").value = bimestre;

    try {
        const response = await fetch(`buscar_alunos.php?turma_id=${turmaId}`);
        if (!response.ok) {
            throw new Error('Falha ao buscar alunos. Status: ' + response.status);
        }
        const alunos = await response.json();

        const alunosTableBody = document.getElementById("alunosTableBody");
        alunosTableBody.innerHTML = ""; // Limpa a tabela

        if (alunos.length === 0) {
            alunosTableBody.innerHTML = '<tr><td colspan="3">Nenhum aluno encontrado para esta turma.</td></tr>';
        } else {
            alunos.forEach(aluno => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${aluno.id}</td>
                    <td>${aluno.nome}</td>
                    <td>
                        <input type="number" name="notas[${aluno.id}]" step="0.01" min="0" max="10" style="width: 80px;">
                    </td>
                `;
                alunosTableBody.appendChild(tr);
            });
        }
        document.getElementById("alunosSection").classList.remove("hidden");

    } catch (error) {
        console.error("Erro ao carregar alunos:", error);
        alunosTableBody.innerHTML = `<tr><td colspan="3">Erro ao carregar alunos: ${error.message}</td></tr>`;
        document.getElementById("alunosSection").classList.remove("hidden"); // Mostra a seção para exibir o erro
    }
}

document.getElementById("notasForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const statusMessage = document.getElementById("statusMessage");
    const formData = new FormData(e.target);

    // Adicionar a data de lançamento (o backend também pode fazer isso)
    // const hoje = new Date().toISOString().slice(0, 10); // Formato YYYY-MM-DD
    // formData.append('data_lancamento', hoje);

    try {
        const response = await fetch("salvar_notas.php", {
            method: "POST",
            body: formData
        });

        const resultText = await response.text(); // Pega o texto da resposta para depuração
        
        if (!response.ok) {
             throw new Error(`Erro do servidor: ${response.status} - ${resultText}`);
        }

        const result = JSON.parse(resultText); // Tenta parsear como JSON

        if (result.success) {
            statusMessage.textContent = result.message || "Notas lançadas com sucesso!";
            statusMessage.className = 'status-success';
            // document.getElementById("alunosSection").classList.add("hidden"); // Opcional: esconder após sucesso
            e.target.reset(); // Limpa o formulário de notas (não os seletores principais)
             // Limpar apenas as notas, mantendo os campos de seleção e a lista de alunos
            const inputsNotas = document.querySelectorAll('#alunosTableBody input[type="number"]');
            inputsNotas.forEach(input => input.value = '');
            // document.getElementById("alunosSection").classList.add("hidden"); // Descomente se quiser esconder a lista
        } else {
            statusMessage.textContent = result.message || "Falha ao lançar notas.";
            statusMessage.className = 'status-error';
        }
    } catch (error) {
        console.error("Erro ao enviar notas:", error);
        statusMessage.textContent = `Erro ao enviar notas: ${error.message}. Verifique o console para detalhes.`;
        statusMessage.className = 'status-error';
    }
    statusMessage.classList.remove('hidden');
});