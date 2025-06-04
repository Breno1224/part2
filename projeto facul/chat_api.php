<?php
session_start();
include 'db.php'; // Seu arquivo de conexão com o banco de dados

header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4"); 

if (!$conn || $conn->connect_error) {
    error_log("chat_api.php: CRÍTICO - Falha na conexão com o banco de dados (db.php). Erro: " . ($conn ? $conn->connect_error : 'Variável $conn não definida/falsa'));
    echo json_encode(['error' => 'Erro crítico de conexão com o servidor de dados.']);
    exit;
}

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['role'])) {
    error_log("chat_api.php: Tentativa de acesso não autenticado ou sem papel definido. Session: " . print_r($_SESSION, true));
    echo json_encode(['error' => 'Usuário não autenticado ou papel não definido.']);
    exit;
}

$current_user_id = intval($_SESSION['usuario_id']);
$session_user_role = $_SESSION['role']; 

$current_chat_role = '';
if ($session_user_role === 'aluno') {
    $current_chat_role = 'aluno';
} elseif ($session_user_role === 'docente') {
    $current_chat_role = 'professor';
} elseif ($session_user_role === 'coordenacao') {
    $current_chat_role = 'coordenador';
} else {
    error_log("chat_api.php: Papel de usuário não mapeado para chat: " . $session_user_role . " para usuario_id: " . $current_user_id);
    echo json_encode(['error' => 'Papel de usuário inválido para o chat.']);
    exit;
}

$action = '';
$request_method = $_SERVER['REQUEST_METHOD'];

if ($request_method === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif ($request_method === 'POST') {
    $input_data = json_decode(file_get_contents('php://input'), true);
    if (isset($input_data['action'])) {
        $action = $input_data['action'];
    } elseif (isset($_POST['action'])) { 
        $action = $_POST['action'];
    }
}

switch ($action) {
    case 'get_turma_users': 
        if ($current_chat_role !== 'aluno') {
             error_log("chat_api.php: Acesso negado para get_turma_users. Role: {$current_chat_role}");
             echo json_encode(['error' => 'Acesso negado para esta ação. Apenas para alunos.']);
             exit;
        }
        if (!isset($_SESSION['turma_id'])) {
            error_log("chat_api.php: Turma do aluno (usuario_id: {$current_user_id}) não definida na sessão.");
            echo json_encode(['error' => 'Turma do usuário não definida na sessão.']);
            exit;
        }
        $turma_id = intval($_SESSION['turma_id']);
        $users = [];
        $processed_user_ids_roles = []; 

        $sql_alunos = "SELECT id, nome, foto_url, 'aluno' as role FROM alunos WHERE turma_id = ? AND id != ?";
        $stmt_alunos = mysqli_prepare($conn, $sql_alunos);
        if ($stmt_alunos) {
            mysqli_stmt_bind_param($stmt_alunos, "ii", $turma_id, $current_user_id);
            mysqli_stmt_execute($stmt_alunos);
            $result_alunos = mysqli_stmt_get_result($stmt_alunos);
            while ($row = mysqli_fetch_assoc($result_alunos)) {
                $unique_key = $row['id'] . "_aluno";
                if (!isset($processed_user_ids_roles[$unique_key])) {
                    $users[] = $row;
                    $processed_user_ids_roles[$unique_key] = true;
                }
            }
            mysqli_stmt_close($stmt_alunos);
        } else {
            error_log("Chat API Erro (get_turma_users - alunos prepare): " . mysqli_error($conn));
        }

        $sql_professores = "SELECT DISTINCT p.id, p.nome, p.foto_url, 'professor' as role 
                            FROM professores p
                            JOIN disciplinas d ON p.id = d.professor_id
                            WHERE d.turma_id = ?";
        $stmt_professores = mysqli_prepare($conn, $sql_professores);
        if ($stmt_professores) {
            mysqli_stmt_bind_param($stmt_professores, "i", $turma_id);
            mysqli_stmt_execute($stmt_professores);
            $result_professores = mysqli_stmt_get_result($stmt_professores);
            while ($row = mysqli_fetch_assoc($result_professores)) {
                $unique_key = $row['id'] . "_professor";
                if (!isset($processed_user_ids_roles[$unique_key])) {
                    $users[] = $row;
                    $processed_user_ids_roles[$unique_key] = true;
                }
            }
            mysqli_stmt_close($stmt_professores);
        } else {
            error_log("Chat API Erro (get_turma_users - professores prepare): " . mysqli_error($conn));
        }
        
        if (empty($users) && (mysqli_errno($conn) != 0) ) { 
             error_log("Chat API: Erro DB em get_turma_users: " . mysqli_error($conn));
             echo json_encode(['error' => 'Erro ao buscar contatos da turma.']);
        } else {
             echo json_encode($users);
        }
        break;

    case 'get_professor_contacts': 
        if ($current_chat_role !== 'professor') { 
            error_log("chat_api.php: Acesso negado para get_professor_contacts. Role: {$current_chat_role}");
            echo json_encode(['error' => 'Acesso não permitido. Ação para docentes.']);
            exit;
        }
        $professor_id_session = $current_user_id;
        $contacts = [];
        $processed_user_ids_roles = []; 

        $sql_alunos_do_professor = "SELECT DISTINCT a.id, a.nome, a.foto_url, 'aluno' as role
                                    FROM alunos a
                                    JOIN turmas t ON a.turma_id = t.id
                                    JOIN disciplinas d ON t.id = d.turma_id
                                    WHERE d.professor_id = ?";
        $stmt_alunos_prof = mysqli_prepare($conn, $sql_alunos_do_professor);
        if ($stmt_alunos_prof) {
            mysqli_stmt_bind_param($stmt_alunos_prof, "i", $professor_id_session);
            mysqli_stmt_execute($stmt_alunos_prof);
            $result_alunos_prof = mysqli_stmt_get_result($stmt_alunos_prof);
            while ($row_aluno = mysqli_fetch_assoc($result_alunos_prof)) {
                $unique_key = $row_aluno['id'] . "_aluno";
                if (!isset($processed_user_ids_roles[$unique_key])) {
                    $contacts[] = $row_aluno;
                    $processed_user_ids_roles[$unique_key] = true;
                }
            }
            mysqli_stmt_close($stmt_alunos_prof);
        } else {
            error_log("Chat API Erro (get_professor_contacts - alunos prepare): " . mysqli_error($conn));
        }

        $sql_outros_profs = "SELECT id, nome, foto_url, 'professor' as role 
                             FROM professores 
                             WHERE id != ?";
        $stmt_outros_profs = mysqli_prepare($conn, $sql_outros_profs);
        if ($stmt_outros_profs) {
            mysqli_stmt_bind_param($stmt_outros_profs, "i", $professor_id_session);
            mysqli_stmt_execute($stmt_outros_profs);
            $result_outros_profs = mysqli_stmt_get_result($stmt_outros_profs);
            while ($row_prof = mysqli_fetch_assoc($result_outros_profs)) {
                $unique_key = $row_prof['id'] . "_professor";
                 if (!isset($processed_user_ids_roles[$unique_key])) {
                    $contacts[] = $row_prof;
                    $processed_user_ids_roles[$unique_key] = true;
                }
            }
            mysqli_stmt_close($stmt_outros_profs);
        } else {
            error_log("Chat API Erro (get_professor_contacts - outros profs prepare): " . mysqli_error($conn));
        }

        $sql_coordenadores = "SELECT id, nome, foto_url, 'coordenador' as role FROM coordenadores";
        $stmt_coords = mysqli_prepare($conn, $sql_coordenadores);
        if($stmt_coords) {
            mysqli_stmt_execute($stmt_coords);
            $result_coords = mysqli_stmt_get_result($stmt_coords);
            while ($row_coord = mysqli_fetch_assoc($result_coords)) {
                $unique_key = $row_coord['id'] . "_" . $row_coord['role'];
                if (!isset($processed_user_ids_roles[$unique_key])) {
                     $contacts[] = $row_coord;
                     $processed_user_ids_roles[$unique_key] = true;
                }
            }
            mysqli_stmt_close($stmt_coords);
        } else {
            error_log("Chat API Erro (get_professor_contacts - coordenadores prepare): " . mysqli_error($conn));
        }

        if (empty($contacts) && mysqli_error($conn) && (!isset($stmt_alunos_prof) || !isset($stmt_outros_profs) || !isset($stmt_coords) ) ) {
             error_log("Chat API: Erro DB em get_professor_contacts: " . mysqli_error($conn));
             echo json_encode(['error' => 'Erro ao buscar contatos para o professor.']);
        } else {
             echo json_encode($contacts);
        }
        break;
    
    case 'get_coordenador_contacts':
        if ($current_chat_role !== 'coordenador') {
            error_log("chat_api.php: Acesso negado para get_coordenador_contacts. Role: {$current_chat_role}");
            echo json_encode(['error' => 'Acesso não permitido. Ação para coordenação.']);
            exit;
        }
        $coordenador_id_session = $current_user_id;
        $contacts = [];
        $processed_user_ids_roles = [];

        // 1. Buscar todos os alunos
        $sql_all_alunos = "SELECT id, nome, foto_url, 'aluno' as role FROM alunos ORDER BY nome ASC";
        $stmt_all_alunos = mysqli_prepare($conn, $sql_all_alunos);
        if ($stmt_all_alunos) {
            mysqli_stmt_execute($stmt_all_alunos);
            $result_all_alunos = mysqli_stmt_get_result($stmt_all_alunos);
            while ($row_aluno = mysqli_fetch_assoc($result_all_alunos)) {
                $unique_key = $row_aluno['id'] . "_aluno";
                if (!isset($processed_user_ids_roles[$unique_key])) {
                    $contacts[] = $row_aluno;
                    $processed_user_ids_roles[$unique_key] = true;
                }
            }
            mysqli_stmt_close($stmt_all_alunos);
        } else {
            error_log("Chat API Erro (get_coordenador_contacts - alunos): " . mysqli_error($conn));
        }

        // 2. Buscar todos os professores
        $sql_all_profs = "SELECT id, nome, foto_url, 'professor' as role FROM professores ORDER BY nome ASC";
        $stmt_all_profs = mysqli_prepare($conn, $sql_all_profs);
        if ($stmt_all_profs) {
            mysqli_stmt_execute($stmt_all_profs);
            $result_all_profs = mysqli_stmt_get_result($stmt_all_profs);
            while ($row_prof = mysqli_fetch_assoc($result_all_profs)) {
                $unique_key = $row_prof['id'] . "_professor";
                if (!isset($processed_user_ids_roles[$unique_key])) {
                    $contacts[] = $row_prof;
                    $processed_user_ids_roles[$unique_key] = true;
                }
            }
            mysqli_stmt_close($stmt_all_profs);
        } else {
            error_log("Chat API Erro (get_coordenador_contacts - profs): " . mysqli_error($conn));
        }

        // 3. Buscar outros coordenadores (excluindo o próprio coordenador logado)
        $sql_outros_coords = "SELECT id, nome, foto_url, 'coordenador' as role FROM coordenadores WHERE id != ? ORDER BY nome ASC";
        $stmt_outros_coords = mysqli_prepare($conn, $sql_outros_coords);
        if ($stmt_outros_coords) {
            mysqli_stmt_bind_param($stmt_outros_coords, "i", $coordenador_id_session);
            mysqli_stmt_execute($stmt_outros_coords);
            $result_outros_coords = mysqli_stmt_get_result($stmt_outros_coords);
            while ($row_coord = mysqli_fetch_assoc($result_outros_coords)) {
                $unique_key = $row_coord['id'] . "_coordenador";
                if (!isset($processed_user_ids_roles[$unique_key])) { 
                     $contacts[] = $row_coord;
                     $processed_user_ids_roles[$unique_key] = true;
                }
            }
            mysqli_stmt_close($stmt_outros_coords);
        } else {
            error_log("Chat API Erro (get_coordenador_contacts - outros coords): " . mysqli_error($conn));
        }
        
        if (empty($contacts) && mysqli_error($conn) && (!isset($stmt_all_alunos) || !isset($stmt_all_profs) || !isset($stmt_outros_coords) ) ) {
             error_log("Chat API: Erro DB em get_coordenador_contacts: " . mysqli_error($conn));
             echo json_encode(['error' => 'Erro ao buscar contatos para a coordenação.']);
        } else {
             echo json_encode($contacts);
        }
        break;


    case 'get_messages':
        if (!isset($_GET['contact_id']) || !isset($_GET['contact_role'])) {
            error_log("chat_api.php: get_messages - Parâmetros faltando. GET: " . print_r($_GET, true));
            echo json_encode(['error' => 'ID ou Papel do contato não fornecido.']);
            exit;
        }
        $contact_id = intval($_GET['contact_id']);
        $contact_role = $_GET['contact_role'];

        if(!in_array($contact_role, ['aluno', 'professor', 'coordenador'])) {
            error_log("chat_api.php: get_messages - Papel do contato inválido: " . $contact_role);
            echo json_encode(['error' => 'Papel do contato inválido.']);
            exit;
        }

        $messages = [];
        $sql = "SELECT id, sender_id, sender_role, receiver_id, receiver_role, message_text, timestamp 
                FROM chat_messages 
                WHERE 
                    (sender_id = ? AND sender_role = ? AND receiver_id = ? AND receiver_role = ?) OR 
                    (sender_id = ? AND sender_role = ? AND receiver_id = ? AND receiver_role = ?)
                ORDER BY timestamp ASC";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isisisis", 
                $current_user_id, $current_chat_role, $contact_id, $contact_role,
                $contact_id, $contact_role, $current_user_id, $current_chat_role
            );
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $messages[] = $row;
                    }
                } else {
                    error_log("Chat API Erro (get_messages - get_result): " . mysqli_error($conn));
                }
            } else {
                error_log("Chat API Erro (get_messages - execute): " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
            
            $sql_update_read = "UPDATE chat_messages SET read_status = TRUE 
                                WHERE receiver_id = ? AND receiver_role = ? AND sender_id = ? AND sender_role = ? AND read_status = FALSE";
            $stmt_update = mysqli_prepare($conn, $sql_update_read);
            if($stmt_update) {
               mysqli_stmt_bind_param($stmt_update, "isis", $current_user_id, $current_chat_role, $contact_id, $contact_role);
               mysqli_stmt_execute($stmt_update);
               mysqli_stmt_close($stmt_update);
            } else {
                error_log("Chat API Erro (get_messages - update_read prepare): " . mysqli_error($conn));
            }
        } else { 
            error_log("Chat API Erro (get_messages - prepare): " . mysqli_error($conn));
            echo json_encode(['error' => 'Erro interno ao preparar busca de mensagens.']); 
            exit; 
        }
        echo json_encode($messages);
        break;

    case 'send_message':
        $data = $input_data; 
        if (empty($data) && isset($_POST['receiver_id'])) { 
            $data = $_POST;
        }

        if (!isset($data['receiver_id']) || !isset($data['receiver_role']) || !isset($data['text'])) {
            echo json_encode(['error' => 'Dados da mensagem incompletos (ID, Papel do destinatário ou texto faltando).']);
            exit;
        }
        $receiver_id = intval($data['receiver_id']);
        $receiver_role = $data['receiver_role']; 
        $message_text = trim($data['text']);

        if(!in_array($receiver_role, ['aluno', 'professor', 'coordenador'])) { 
            echo json_encode(['error' => 'Papel do destinatário inválido.']);
            exit;
        }
        if (empty($message_text)) {
            echo json_encode(['error' => 'A mensagem não pode estar vazia.']);
            exit;
        }

        $sql = "INSERT INTO chat_messages (sender_id, sender_role, receiver_id, receiver_role, message_text) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isiss", $current_user_id, $current_chat_role, $receiver_id, $receiver_role, $message_text);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode([
                    'success' => true, 
                    'message_id' => mysqli_insert_id($conn), 
                    'timestamp' => date('Y-m-d H:i:s'),
                    'sender_id' => $current_user_id, 
                    'sender_role' => $current_chat_role
                ]);
            } else {
                error_log("Chat API Erro (send_message execute): " . mysqli_stmt_error($stmt));
                echo json_encode(['error' => 'Erro interno ao enviar mensagem.']);
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Chat API Erro (send_message prepare): " . mysqli_error($conn));
            echo json_encode(['error' => 'Erro interno ao preparar mensagem.']);
        }
        break;

    default:
        error_log("chat_api.php: Ação de chat inválida ou não fornecida: '{$action}'");
        echo json_encode(['error' => 'Ação de chat inválida ou não fornecida.']);
}

if (isset($conn)) {
    mysqli_close($conn);
}
?>