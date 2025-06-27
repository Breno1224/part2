-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 27/06/2025 às 14:54
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `acadmix_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos`
--

CREATE TABLE `alunos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto_url` varchar(255) DEFAULT 'img/alunos/default_avatar.png',
  `biografia` text DEFAULT NULL,
  `turma_id` int(11) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `tema_perfil` varchar(50) DEFAULT 'padrao' COMMENT 'Tema de perfil escolhido pelo aluno',
  `interesses` varchar(255) DEFAULT NULL COMMENT 'Interesses ou hobbies do aluno, separados por vírgula'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `alunos`
--

INSERT INTO `alunos` (`id`, `nome`, `email`, `senha`, `foto_url`, `biografia`, `turma_id`, `data_criacao`, `tema_perfil`, `interesses`) VALUES
(6, 'Lucas Henrique Rege Arantes', 'Lucas.arantes@sanquim.com', '$2y$10$JFHE42v/E7SgE2Hu7Kw2oeflIIMFtAwfW4.WR6VGziTVPRm6P.fGW', 'img/alunos/aluno_6_1749041831.jpg', NULL, 4, '2025-06-02 18:14:38', 'padrao', NULL),
(7, 'eduardo', 'eduardo@sanquim.com', '$2y$10$KtKfVZKaavX8vuoSpwfm7OVJhW8GnK2LiNP7OEeGsoOpWgfT2tz5m', NULL, NULL, 5, '2025-06-22 15:55:25', 'padrao', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` varchar(50) DEFAULT NULL,
  `receiver_id` int(11) NOT NULL,
  `receiver_role` varchar(50) DEFAULT NULL,
  `message_text` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_status` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender_id`, `sender_role`, `receiver_id`, `receiver_role`, `message_text`, `timestamp`, `read_status`) VALUES
(1, 4, NULL, 6, NULL, 'oi', '2025-06-03 20:36:08', 0),
(2, 4, NULL, 3, NULL, 'oi', '2025-06-03 20:39:06', 0),
(3, 6, NULL, 4, NULL, 'oi', '2025-06-03 21:02:29', 0),
(4, 3, NULL, 1, NULL, 'oi', '2025-06-03 21:14:57', 0),
(5, 3, NULL, 1, NULL, 'oi', '2025-06-03 21:15:16', 0),
(6, 3, NULL, 2, NULL, 'oi', '2025-06-03 21:15:23', 0),
(7, 3, NULL, 1, NULL, 'tchau', '2025-06-03 21:19:34', 0),
(8, 3, 'professor', 1, 'professor', 'oi', '2025-06-03 21:25:22', 0),
(9, 3, 'professor', 1, 'professor', 'oi', '2025-06-03 21:29:03', 0),
(10, 3, 'professor', 2, 'professor', 'oi', '2025-06-03 21:31:21', 0),
(11, 3, 'professor', 1, 'professor', 'oi', '2025-06-03 21:35:26', 0),
(12, 3, 'professor', 1, 'professor', 'eae', '2025-06-03 21:39:59', 0),
(13, 3, 'professor', 1, 'professor', 'oi', '2025-06-03 22:01:20', 0),
(14, 1, 'coordenador', 1, 'aluno', 'oi', '2025-06-04 00:27:04', 0),
(15, 1, 'coordenador', 6, 'aluno', 'oi', '2025-06-05 15:53:18', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `comunicados`
--

CREATE TABLE `comunicados` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) DEFAULT NULL,
  `coordenador_id` int(11) DEFAULT NULL,
  `turma_id` int(11) DEFAULT NULL,
  `publico_alvo` enum('TODOS_ALUNOS','TURMA_ESPECIFICA','TODOS_PROFESSORES','PROFESSOR_GERAL_ALUNOS') DEFAULT NULL COMMENT 'Define o público principal do comunicado',
  `titulo` varchar(255) NOT NULL,
  `conteudo` text NOT NULL,
  `data_publicacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comunicados`
--

INSERT INTO `comunicados` (`id`, `professor_id`, `coordenador_id`, `turma_id`, `publico_alvo`, `titulo`, `conteudo`, `data_publicacao`) VALUES
(3, NULL, 1, NULL, 'TODOS_PROFESSORES', 'asdasd', 'asdasdasd', '2025-06-01 16:05:46'),
(5, 4, NULL, NULL, 'PROFESSOR_GERAL_ALUNOS', 'teste', 'testando', '2025-06-22 15:21:08'),
(6, 4, NULL, 4, 'TURMA_ESPECIFICA', 'teste 1', 'testanndo', '2025-06-22 15:23:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `coordenadores`
--

CREATE TABLE `coordenadores` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto_url` varchar(255) DEFAULT 'img/coordenadores/default_avatar.png',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `tema_perfil` varchar(50) DEFAULT 'padrao' COMMENT 'Tema de perfil escolhido pelo coordenador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `coordenadores`
--

INSERT INTO `coordenadores` (`id`, `nome`, `email`, `senha`, `foto_url`, `data_criacao`, `tema_perfil`) VALUES
(1, 'Ana Moreira', 'coord1@sanquim.com', 'coordS#quim1', 'img/coordenadores/default_avatar.png', '2025-06-01 14:19:57', 'padrao'),
(2, 'Roberto Dias', 'coord2@sanquim.com', 'coordS#quim2', 'img/coordenadores/default_avatar.png', '2025-06-01 14:19:57', 'padrao'),
(3, 'Cláudia Ventura', 'coord3@sanquim.com', 'coordS#quim3', 'img/coordenadores/default_avatar.png', '2025-06-01 14:19:57', 'padrao'),
(4, 'Fernando Bastos', 'coord4@sanquim.com', 'coordS#quim4', 'img/coordenadores/default_avatar.png', '2025-06-01 14:19:57', 'padrao'),
(5, 'Patrícia Guedes', 'coord5@sanquim.com', 'coordS#quim5', 'img/coordenadores/default_avatar.png', '2025-06-01 14:19:57', 'padrao');

-- --------------------------------------------------------

--
-- Estrutura para tabela `disciplinas`
--

CREATE TABLE `disciplinas` (
  `id` int(11) NOT NULL,
  `nome_disciplina` varchar(100) NOT NULL,
  `ementa` text DEFAULT NULL COMMENT 'Ementa ou descrição da disciplina',
  `carga_horaria` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `disciplinas`
--

INSERT INTO `disciplinas` (`id`, `nome_disciplina`, `ementa`, `carga_horaria`) VALUES
(1, 'Matemática', NULL, NULL),
(2, 'Português', NULL, NULL),
(3, 'História', NULL, NULL),
(4, 'artes', '', 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `frequencia`
--

CREATE TABLE `frequencia` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `data_aula` date NOT NULL,
  `status` enum('P','F','A','FJ') NOT NULL COMMENT 'P=Presente, F=Falta, A=Atrasado, FJ=Falta Justificada',
  `observacao` varchar(255) DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `frequencia`
--

INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `professor_id`, `data_aula`, `status`, `observacao`, `data_registro`) VALUES
(17, 6, 4, 4, '2025-06-11', 'P', '', '2025-06-10 22:18:59'),
(18, 6, 4, 4, '2025-06-12', 'F', '', '2025-06-10 22:19:06'),
(19, 6, 4, 4, '2025-06-13', 'F', '', '2025-06-13 18:49:32'),
(20, 6, 4, 4, '2025-06-22', 'P', '', '2025-06-22 15:15:25'),
(21, 6, 4, 4, '2025-06-24', 'P', '', '2025-06-22 15:22:56'),
(22, 6, 4, 4, '2025-06-25', 'A', '', '2025-06-22 15:25:10'),
(23, 7, 4, 4, '2025-06-26', 'P', '', '2025-06-26 21:35:58'),
(24, 6, 4, 4, '2025-06-26', 'FJ', '', '2025-06-26 21:35:58'),
(25, 7, 4, 4, '2025-06-27', 'P', '', '2025-06-26 22:29:12'),
(26, 6, 4, 4, '2025-06-27', 'FJ', '', '2025-06-26 22:29:12'),
(31, 7, 4, 4, '2025-06-28', 'F', '', '2025-06-27 00:14:48'),
(32, 6, 4, 4, '2025-06-28', 'A', '', '2025-06-27 00:14:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `materiais_didaticos`
--

CREATE TABLE `materiais_didaticos` (
  `id` int(11) NOT NULL,
  `disciplina_id` int(11) DEFAULT NULL,
  `turma_id` int(11) DEFAULT NULL,
  `professor_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `arquivo_path_ou_link` varchar(512) NOT NULL,
  `tipo_material` varchar(100) DEFAULT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notas`
--

CREATE TABLE `notas` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL,
  `avaliacao` varchar(100) DEFAULT NULL,
  `nota` decimal(4,2) DEFAULT NULL,
  `data_lancamento` date NOT NULL,
  `bimestre` int(11) DEFAULT NULL COMMENT '1, 2, 3 ou 4, se aplicável'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notas`
--

INSERT INTO `notas` (`id`, `aluno_id`, `disciplina_id`, `professor_id`, `turma_id`, `avaliacao`, `nota`, `data_lancamento`, `bimestre`) VALUES
(8, 6, 3, 4, 4, '1', 10.00, '2025-06-11', 1),
(9, 6, 3, 4, 4, '1', 4.00, '2025-06-11', 2),
(10, 6, 3, 4, 4, 'p1', 10.00, '2025-06-22', 1),
(11, 7, 3, 4, 4, 'p1', 6.00, '2025-06-26', 3),
(12, 6, 3, 4, 4, 'p1', 5.00, '2025-06-26', 3),
(13, 7, 2, 4, 4, 'p1', 10.00, '2025-06-27', 1),
(14, 6, 2, 4, 4, 'p1', 10.00, '2025-06-27', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `professores`
--

CREATE TABLE `professores` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto_url` varchar(255) DEFAULT 'img/professores/default_avatar.png',
  `biografia` text DEFAULT NULL,
  `tema_perfil` varchar(50) DEFAULT 'padrao',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professores`
--

INSERT INTO `professores` (`id`, `nome`, `email`, `senha`, `foto_url`, `biografia`, `tema_perfil`, `data_criacao`) VALUES
(4, 'professor teste', 'profteste@sanquim.com', '$2y$10$ESyPge7OgaBeCG4ZV/A94.RRAv14BKm5jV9IeeqYbhNbHqrqin81S', NULL, NULL, 'padrao', '2025-06-04 13:51:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `professores_turmas_disciplinas`
--

CREATE TABLE `professores_turmas_disciplinas` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professores_turmas_disciplinas`
--

INSERT INTO `professores_turmas_disciplinas` (`id`, `professor_id`, `turma_id`, `disciplina_id`) VALUES
(2, 4, 4, 1),
(3, 4, 4, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `professor_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_inicio` datetime NOT NULL COMMENT 'Quando a prova fica disponível',
  `data_prazo` datetime NOT NULL COMMENT 'Prazo final para fazer a prova',
  `duracao_minutos` int(11) DEFAULT 0 COMMENT 'Tempo limite em minutos (0 = sem limite)',
  `aleatorizar_questoes` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `quiz_opcoes`
--

CREATE TABLE `quiz_opcoes` (
  `id` int(11) NOT NULL,
  `questao_id` int(11) NOT NULL,
  `texto_opcao` text NOT NULL,
  `is_correta` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `quiz_questoes`
--

CREATE TABLE `quiz_questoes` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `texto_pergunta` text NOT NULL,
  `tipo_pergunta` enum('multipla_escolha','dissertativa') NOT NULL,
  `pontos` decimal(5,2) NOT NULL DEFAULT 1.00,
  `ordem` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `quiz_respostas_alunos`
--

CREATE TABLE `quiz_respostas_alunos` (
  `id` int(11) NOT NULL,
  `tentativa_id` int(11) NOT NULL,
  `questao_id` int(11) NOT NULL,
  `opcao_id_selecionada` int(11) DEFAULT NULL COMMENT 'FK para quiz_opcoes se for múltipla escolha',
  `texto_resposta_dissertativa` text DEFAULT NULL COMMENT 'Resposta para questões abertas',
  `is_correta_auto` tinyint(1) DEFAULT NULL COMMENT 'Resultado da autocorreção para múltipla escolha',
  `pontos_obtidos` decimal(5,2) DEFAULT NULL COMMENT 'Pontos atribuídos à questão pelo professor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `quiz_tentativas_alunos`
--

CREATE TABLE `quiz_tentativas_alunos` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `data_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_fim` datetime DEFAULT NULL COMMENT 'Momento em que o aluno finalizou a tentativa',
  `nota_final` decimal(5,2) DEFAULT NULL COMMENT 'Nota final atribuída pelo professor',
  `status` enum('em_andamento','finalizado','avaliado') NOT NULL DEFAULT 'em_andamento',
  `feedback_professor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_aula`
--

CREATE TABLE `relatorios_aula` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `data_aula` date NOT NULL,
  `conteudo_lecionado` text NOT NULL,
  `observacoes` text DEFAULT NULL,
  `material_aula_path` varchar(255) DEFAULT NULL,
  `comentario_coordenacao` text DEFAULT NULL,
  `coordenador_id_comentario` int(11) DEFAULT NULL,
  `data_comentario_coordenacao` datetime DEFAULT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `relatorios_aula`
--

INSERT INTO `relatorios_aula` (`id`, `professor_id`, `turma_id`, `disciplina_id`, `data_aula`, `conteudo_lecionado`, `observacoes`, `material_aula_path`, `comentario_coordenacao`, `coordenador_id_comentario`, `data_comentario_coordenacao`, `data_envio`) VALUES
(3, 4, 4, 3, '2025-06-22', 'isso mesmoi', 'foi legal', NULL, 'legal uhul', 1, '2025-06-27 02:17:19', '2025-06-22 15:28:02');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tarefas`
--

CREATE TABLE `tarefas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `professor_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_prazo` datetime NOT NULL,
  `arquivo_path_professor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tarefas`
--

INSERT INTO `tarefas` (`id`, `titulo`, `descricao`, `professor_id`, `disciplina_id`, `turma_id`, `data_criacao`, `data_prazo`, `arquivo_path_professor`) VALUES
(1, 'oi', 'oi', 4, 3, 4, '2025-06-27 12:47:41', '2025-06-28 09:47:00', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tarefas_submissoes`
--

CREATE TABLE `tarefas_submissoes` (
  `id` int(11) NOT NULL,
  `tarefa_id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `data_submissao` timestamp NOT NULL DEFAULT current_timestamp(),
  `arquivo_path_aluno` varchar(255) NOT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `feedback_professor` text DEFAULT NULL,
  `data_avaliacao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id` int(11) NOT NULL,
  `nome_turma` varchar(100) NOT NULL,
  `ano_letivo` year(4) DEFAULT NULL COMMENT 'Ex: 2024, 2025',
  `periodo` varchar(50) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `turmas`
--

INSERT INTO `turmas` (`id`, `nome_turma`, `ano_letivo`, `periodo`, `data_criacao`) VALUES
(4, '1 ano teste', '2025', 'Integral', '2025-06-04 13:51:09'),
(5, '3 ano a', '2025', 'Manhã', '2025-06-27 00:17:56');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alunos`
--
ALTER TABLE `alunos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_receiver` (`receiver_id`),
  ADD KEY `idx_conversation` (`sender_id`,`receiver_id`,`timestamp`);

--
-- Índices de tabela `comunicados`
--
ALTER TABLE `comunicados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `fk_comunicados_coordenador` (`coordenador_id`);

--
-- Índices de tabela `coordenadores`
--
ALTER TABLE `coordenadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome_disciplina` (`nome_disciplina`);

--
-- Índices de tabela `frequencia`
--
ALTER TABLE `frequencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_aluno_turma_data` (`aluno_id`,`turma_id`,`data_aula`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `materiais_didaticos`
--
ALTER TABLE `materiais_didaticos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `professores`
--
ALTER TABLE `professores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `professores_turmas_disciplinas`
--
ALTER TABLE `professores_turmas_disciplinas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_prof_turma_disc` (`professor_id`,`turma_id`,`disciplina_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `disciplina_id` (`disciplina_id`);

--
-- Índices de tabela `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `quiz_opcoes`
--
ALTER TABLE `quiz_opcoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `questao_id` (`questao_id`);

--
-- Índices de tabela `quiz_questoes`
--
ALTER TABLE `quiz_questoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Índices de tabela `quiz_respostas_alunos`
--
ALTER TABLE `quiz_respostas_alunos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tentativa_id` (`tentativa_id`),
  ADD KEY `questao_id` (`questao_id`),
  ADD KEY `opcao_id_selecionada` (`opcao_id_selecionada`);

--
-- Índices de tabela `quiz_tentativas_alunos`
--
ALTER TABLE `quiz_tentativas_alunos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_quiz_aluno` (`quiz_id`,`aluno_id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `relatorios_aula`
--
ALTER TABLE `relatorios_aula`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_prof_turma_disc_data` (`professor_id`,`turma_id`,`disciplina_id`,`data_aula`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `fk_relatorio_coordenador_comentario` (`coordenador_id_comentario`);

--
-- Índices de tabela `tarefas`
--
ALTER TABLE `tarefas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `tarefas_submissoes`
--
ALTER TABLE `tarefas_submissoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_tarefa_aluno` (`tarefa_id`,`aluno_id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `turmas`
--
ALTER TABLE `turmas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome_turma` (`nome_turma`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alunos`
--
ALTER TABLE `alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `comunicados`
--
ALTER TABLE `comunicados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `coordenadores`
--
ALTER TABLE `coordenadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `frequencia`
--
ALTER TABLE `frequencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `materiais_didaticos`
--
ALTER TABLE `materiais_didaticos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `notas`
--
ALTER TABLE `notas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `professores`
--
ALTER TABLE `professores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `professores_turmas_disciplinas`
--
ALTER TABLE `professores_turmas_disciplinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `quiz_opcoes`
--
ALTER TABLE `quiz_opcoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `quiz_questoes`
--
ALTER TABLE `quiz_questoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `quiz_respostas_alunos`
--
ALTER TABLE `quiz_respostas_alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `quiz_tentativas_alunos`
--
ALTER TABLE `quiz_tentativas_alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `relatorios_aula`
--
ALTER TABLE `relatorios_aula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `tarefas`
--
ALTER TABLE `tarefas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `tarefas_submissoes`
--
ALTER TABLE `tarefas_submissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `alunos`
--
ALTER TABLE `alunos`
  ADD CONSTRAINT `alunos_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `comunicados`
--
ALTER TABLE `comunicados`
  ADD CONSTRAINT `comunicados_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comunicados_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_comunicados_coordenador` FOREIGN KEY (`coordenador_id`) REFERENCES `coordenadores` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `frequencia`
--
ALTER TABLE `frequencia`
  ADD CONSTRAINT `frequencia_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `frequencia_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `frequencia_ibfk_3` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `materiais_didaticos`
--
ALTER TABLE `materiais_didaticos`
  ADD CONSTRAINT `materiais_didaticos_ibfk_1` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `materiais_didaticos_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `materiais_didaticos_ibfk_3` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_ibfk_3` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_ibfk_4` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `professores_turmas_disciplinas`
--
ALTER TABLE `professores_turmas_disciplinas`
  ADD CONSTRAINT `professores_turmas_disciplinas_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `professores_turmas_disciplinas_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `professores_turmas_disciplinas_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizzes_ibfk_3` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `quiz_opcoes`
--
ALTER TABLE `quiz_opcoes`
  ADD CONSTRAINT `quiz_opcoes_ibfk_1` FOREIGN KEY (`questao_id`) REFERENCES `quiz_questoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `quiz_questoes`
--
ALTER TABLE `quiz_questoes`
  ADD CONSTRAINT `quiz_questoes_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `quiz_respostas_alunos`
--
ALTER TABLE `quiz_respostas_alunos`
  ADD CONSTRAINT `quiz_respostas_alunos_ibfk_1` FOREIGN KEY (`tentativa_id`) REFERENCES `quiz_tentativas_alunos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_respostas_alunos_ibfk_2` FOREIGN KEY (`questao_id`) REFERENCES `quiz_questoes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_respostas_alunos_ibfk_3` FOREIGN KEY (`opcao_id_selecionada`) REFERENCES `quiz_opcoes` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `quiz_tentativas_alunos`
--
ALTER TABLE `quiz_tentativas_alunos`
  ADD CONSTRAINT `quiz_tentativas_alunos_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_tentativas_alunos_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `relatorios_aula`
--
ALTER TABLE `relatorios_aula`
  ADD CONSTRAINT `fk_relatorio_coordenador_comentario` FOREIGN KEY (`coordenador_id_comentario`) REFERENCES `coordenadores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `relatorios_aula_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `relatorios_aula_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `relatorios_aula_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tarefas`
--
ALTER TABLE `tarefas`
  ADD CONSTRAINT `tarefas_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tarefas_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tarefas_ibfk_3` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tarefas_submissoes`
--
ALTER TABLE `tarefas_submissoes`
  ADD CONSTRAINT `tarefas_submissoes_ibfk_1` FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tarefas_submissoes_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
