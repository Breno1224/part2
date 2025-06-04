-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 04/06/2025 às 13:09
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

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
(1, 'Ana Julia', 'aluno1@sanquim.com', '1234', 'img/alunos/aluno_1_1748806355.jpg', 'hihi', 1, '2025-05-30 21:12:17', 'darkmode', 'leitura'),
(2, 'Bruno Dias', 'aluno2@sanquim.com', '1234', 'img/alunos/default_avatar.png', NULL, 1, '2025-05-30 21:12:17', 'padrao', NULL),
(3, 'Carla Mendes', 'aluno3@sanquim.com', '1234', 'img/alunos/default_avatar.png', NULL, 2, '2025-05-30 21:12:17', 'padrao', NULL),
(4, 'dudububu', 'dudububu@sanquim.com', '$2y$10$lSjvH4Z1A02.fnI51i2wu.8fK27/cVcQQsVd/9JgZMG9nKT0SE1Nm', NULL, NULL, 2, '2025-06-01 19:42:16', 'darkmode', NULL),
(6, 'Lucas Henrique Rege Arantes', 'Lucas.arantes@sanquim.com', '$2y$10$JFHE42v/E7SgE2Hu7Kw2oeflIIMFtAwfW4.WR6VGziTVPRm6P.fGW', 'img/alunos/aluno_1748888078_683dea0eb2021.png', NULL, 2, '2025-06-02 18:14:38', 'darkmode', NULL);

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
(14, 1, 'coordenador', 1, 'aluno', 'oi', '2025-06-04 00:27:04', 0);

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
(1, 1, NULL, 1, NULL, 'asdasd', 'adasdasd', '2025-05-31 22:43:25'),
(2, NULL, 1, NULL, 'TODOS_PROFESSORES', 'oi hihi', 'dudud bubu', '2025-06-01 15:31:13'),
(3, NULL, 1, NULL, 'TODOS_PROFESSORES', 'asdasd', 'asdasdasd', '2025-06-01 16:05:46'),
(4, 1, NULL, 1, 'TURMA_ESPECIFICA', 'asdasd', 'asda', '2025-06-01 18:05:50');

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
  `ementa` text DEFAULT NULL COMMENT 'Ementa ou descrição da disciplina'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `disciplinas`
--

INSERT INTO `disciplinas` (`id`, `nome_disciplina`, `ementa`) VALUES
(1, 'Matemática', NULL),
(2, 'Português', NULL),
(3, 'História', NULL);

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
(1, 1, 1, 1, '2025-06-01', 'F', '', '2025-06-01 13:52:39'),
(2, 2, 1, 1, '2025-06-01', 'P', '', '2025-06-01 13:52:39'),
(5, 1, 1, 1, '2025-06-02', 'P', '', '2025-06-01 13:52:58'),
(6, 2, 1, 1, '2025-06-02', 'F', '', '2025-06-01 13:52:58'),
(7, 1, 1, 1, '2025-06-03', 'A', '', '2025-06-01 16:03:59'),
(8, 2, 1, 1, '2025-06-03', 'P', '', '2025-06-01 16:03:59'),
(9, 1, 1, 1, '2025-06-04', 'A', '', '2025-06-01 18:02:57'),
(10, 2, 1, 1, '2025-06-04', 'F', '', '2025-06-01 18:02:57'),
(11, 1, 1, 1, '2025-06-05', 'A', 'asdasd', '2025-06-01 21:48:09'),
(12, 2, 1, 1, '2025-06-05', 'P', '', '2025-06-01 21:47:58');

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

--
-- Despejando dados para a tabela `materiais_didaticos`
--

INSERT INTO `materiais_didaticos` (`id`, `disciplina_id`, `turma_id`, `professor_id`, `titulo`, `descricao`, `arquivo_path_ou_link`, `tipo_material`, `data_upload`) VALUES
(1, 1, 1, 2, 'video aula', 'sim', 'https://www.youtube.com/watch?v=Pd39Zym7PGk', 'video teste', '2025-05-30 22:25:32'),
(2, 3, 1, 1, 'blabla', 'asdasd', 'https://www.youtube.com/watch?v=ZWCA8ejKUdA', 'asdasd', '2025-05-30 22:45:28'),
(3, 3, 1, 1, 'asdsad', 'asdasdasd', 'https://www.youtube.com/watch?v=Jw65sVevIhM', 'asdasd', '2025-05-30 23:04:13'),
(4, 1, 1, 1, 'gfhjgfhg', 'jkbkj', 'https://www.roblox.com/pt/games/126884695634066/Grow-a-Garden#!/game-instances', 'hbhjbj', '2025-05-31 15:58:07'),
(5, 2, NULL, 1, 'dudububu', 'asdasd', 'uploads/materiais_didaticos/1748814533_683ccac5e1917_33417793.pdf', 'dudububu', '2025-06-01 21:48:53');

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
(1, 1, 3, 1, 1, 'p1:', 8.00, '2025-05-31', 1),
(2, 2, 3, 1, 1, 'p1:', 8.00, '2025-05-31', 1),
(3, 1, 2, 1, 1, 'p2', 2.00, '2025-05-31', 1),
(4, 2, 2, 1, 1, 'p2', 3.00, '2025-05-31', 1),
(5, 1, 3, 1, 1, 'p3', 9.00, '2025-05-31', 2),
(6, 1, 2, 1, 1, 'p1', 3.00, '2025-06-01', 3),
(7, 2, 2, 1, 1, 'p1', 2.00, '2025-06-01', 3);

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
(1, 'Carlos Alberto', 'prof1@sanquim.com', '1234', 'img/professores/professor_1_1748784720.jpeg', 'oi gosto muito de dar aula', 'darkmode', '2025-05-30 21:12:17'),
(2, 'Mariana Silva', 'prof2@sanquim.com', '1234', 'img/professores/default_avatar.png', NULL, 'padrao', '2025-05-30 21:12:17'),
(3, 'dudubuub', 'dudu@sanquim.com', '$2y$10$172wEjKU.WS8OAYJo4RwH..FxIkEtvwnS7Wu2.9V6mRHu7W3DZYwu', NULL, NULL, 'darkmode', '2025-06-02 19:00:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `professores_turmas_disciplinas`
--

CREATE TABLE `professores_turmas_disciplinas` (
  `professor_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Define quais professores ensinam quais disciplinas em quais turmas';

--
-- Despejando dados para a tabela `professores_turmas_disciplinas`
--

INSERT INTO `professores_turmas_disciplinas` (`professor_id`, `turma_id`, `disciplina_id`) VALUES
(1, 1, 1),
(1, 1, 3),
(2, 2, 2);

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
(1, 1, 1, 3, '2025-06-01', 'asdasd', 'sim', 'uploads/materiais_relatorios/relatorio_1_1_3_20250601_683caedf24197.jpg', 'foda em', 1, '2025-06-01 21:51:54', '2025-06-01 19:49:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id` int(11) NOT NULL,
  `nome_turma` varchar(100) NOT NULL,
  `ano_letivo` year(4) DEFAULT NULL COMMENT 'Ex: 2024, 2025'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `turmas`
--

INSERT INTO `turmas` (`id`, `nome_turma`, `ano_letivo`) VALUES
(1, '1º Ano A - Matutino', '2024'),
(2, '2º Ano B - Vespertino', '2024');

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
  ADD PRIMARY KEY (`professor_id`,`turma_id`,`disciplina_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `disciplina_id` (`disciplina_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `comunicados`
--
ALTER TABLE `comunicados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `coordenadores`
--
ALTER TABLE `coordenadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `frequencia`
--
ALTER TABLE `frequencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `materiais_didaticos`
--
ALTER TABLE `materiais_didaticos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `notas`
--
ALTER TABLE `notas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `professores`
--
ALTER TABLE `professores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `relatorios_aula`
--
ALTER TABLE `relatorios_aula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  ADD CONSTRAINT `professores_turmas_disciplinas_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `professores_turmas_disciplinas_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `professores_turmas_disciplinas_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `relatorios_aula`
--
ALTER TABLE `relatorios_aula`
  ADD CONSTRAINT `fk_relatorio_coordenador_comentario` FOREIGN KEY (`coordenador_id_comentario`) REFERENCES `coordenadores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `relatorios_aula_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `relatorios_aula_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `relatorios_aula_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
