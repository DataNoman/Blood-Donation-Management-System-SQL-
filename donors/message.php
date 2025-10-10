<?php
session_start();

if (!isset($_SESSION['donor_logged_in']) || $_SESSION['donor_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once '../config/db_connection.php';

$current_user_id = $_SESSION['user_id'];
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id <= 0) {
    die("Invalid request ID");
}

// Get chat partner info and verify donor has accepted response for this request
$query = "SELECT 
            req.id as request_id,
            u_rec.id AS recipient_user_id,
            u_rec.first_name,
            u_rec.last_name,
            rec.hospital,
            req.quantity_ml,
            bt.type as blood_type,
            res.status as response_status
          FROM requests req
          JOIN recipients rec ON req.recipient_id = rec.id
          JOIN users u_rec ON rec.user_id = u_rec.id
          JOIN blood_types bt ON req.blood_type_id = bt.id
          JOIN responses res ON req.id = res.request_id
          JOIN donors d ON res.donor_id = d.id
          WHERE req.id = ? AND d.user_id = ? AND res.status = 'Accepted'";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $request_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$chat_data = $result->fetch_assoc();
$stmt->close();

if (!$chat_data) {
    die("Chat not found or you don't have accepted response for this request");
}

$partner_user_id = $chat_data['recipient_user_id'];
$partner_name = htmlspecialchars($chat_data['first_name'] . ' ' . $chat_data['last_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo $partner_name; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f8134dff 0%, #2325aaff 100%); min-height: 100vh; padding: 20px; }
        
        .chat-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); overflow: hidden; }
        
        .chat-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; align-items: center; gap: 15px; }
        .back-btn { color: white; text-decoration: none; font-size: 24px; transition: transform 0.2s; }
        .back-btn:hover { transform: translateX(-5px); }
        .chat-header-info h2 { font-size: 20px; margin-bottom: 5px; }
        .chat-header-info p { font-size: 13px; opacity: 0.9; }
        
        .chat-info-bar { background: #f8f9fa; padding: 12px 20px; border-bottom: 1px solid #e0e0e0; display: flex; flex-wrap: wrap; gap: 20px; font-size: 13px; color: #666; }
        .info-item { display: flex; align-items: center; gap: 5px; }
        .info-item i { color: #667eea; }
        
        .chat-messages { height: 500px; overflow-y: auto; padding: 20px; background: #f5f5f5; display: flex; flex-direction: column; gap: 10px; }
        .chat-messages::-webkit-scrollbar { width: 8px; }
        .chat-messages::-webkit-scrollbar-track { background: #f1f1f1; }
        .chat-messages::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        
        .message { padding: 10px 15px; border-radius: 18px; max-width: 70%; word-wrap: break-word; line-height: 1.4; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .sent { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
        .received { background: white; color: #333; align-self: flex-start; border-bottom-left-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        .message small { display: block; opacity: 0.7; font-size: 11px; margin-top: 5px; }
        .sent small { text-align: right; }
        
        .empty-chat { text-align: center; color: #999; padding: 40px; }
        .empty-chat i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        
        .chat-input-container { padding: 20px; background: white; border-top: 1px solid #e0e0e0; }
        .chat-input { display: flex; gap: 10px; }
        .chat-input input { flex: 1; padding: 12px 20px; border: 2px solid #e0e0e0; border-radius: 25px; font-size: 14px; transition: border-color 0.3s; }
        .chat-input input:focus { outline: none; border-color: #667eea; }
        .chat-input button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 25px; border-radius: 25px; cursor: pointer; font-size: 14px; font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; }
        .chat-input button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .chat-input button:active { transform: translateY(0); }
        
        @media (max-width: 600px) {
            body { padding: 0; }
            .chat-container { border-radius: 0; height: 100vh; }
            .chat-messages { height: calc(100vh - 200px); }
            .info-item { font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <a href="dashboard.php" class="back-btn" title="Back to Dashboard"><i class="fas fa-arrow-left"></i></a>
            <div class="chat-header-info">
                <h2><i class="fas fa-user-circle"></i> <?php echo $partner_name; ?></h2>
                <p>Recipient</p>
            </div>
        </div>
        
        <div class="chat-info-bar">
            <div class="info-item">
                <i class="fas fa-hospital"></i>
                <span><?php echo htmlspecialchars($chat_data['hospital']); ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-tint"></i>
                <span><?php echo htmlspecialchars($chat_data['blood_type']); ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-vial"></i>
                <span><?php echo htmlspecialchars($chat_data['quantity_ml']); ?> ml</span>
            </div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <div class="empty-chat">
                <i class="fas fa-comments"></i>
                <p>Loading messages...</p>
            </div>
        </div>
        
        <div class="chat-input-container">
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Type your message..." maxlength="500" onkeypress="if(event.key === 'Enter') sendMessage()">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i> Send</button>
            </div>
        </div>
    </div>
    
    <script>
        const currentUserId = <?php echo $current_user_id; ?>;
        const partnerUserId = <?php echo $partner_user_id; ?>;
        const requestId = <?php echo $request_id; ?>;
        const chatMessages = document.getElementById('chatMessages');
        let lastMessageCount = 0;
        
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function fetchMessages() {
            fetch('../config/chat_api.php?action=get_messages&request_id=' + requestId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (data.messages.length === 0) {
                            chatMessages.innerHTML = `
                                <div class="empty-chat">
                                    <i class="fas fa-comments"></i>
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            `;
                            return;
                        }

                        if (data.messages.length !== lastMessageCount) {
                            const isScrolledToBottom = chatMessages.scrollHeight - chatMessages.clientHeight <= chatMessages.scrollTop + 50;
                            
                            chatMessages.innerHTML = '';
                            data.messages.forEach(msg => {
                                const messageDiv = document.createElement('div');
                                messageDiv.classList.add('message');
                                
                                if (msg.sender_user_id == currentUserId) {
                                    messageDiv.classList.add('sent');
                                } else {
                                    messageDiv.classList.add('received');
                                }

                                messageDiv.innerHTML = `
                                    <div>${msg.message}</div>
                                    <small>${formatTime(msg.sent_at)}</small>
                                `;
                                chatMessages.appendChild(messageDiv);
                            });
                            
                            lastMessageCount = data.messages.length;

                            if (isScrolledToBottom || lastMessageCount === data.messages.length) {
                                chatMessages.scrollTop = chatMessages.scrollHeight;
                            }
                        }
                    } else {
                        console.error('Error:', data.message);
                    }
                })
                .catch(error => console.error('Error fetching messages:', error));
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();

            if (message === '') return;

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('request_id', requestId);
            formData.append('receiver_user_id', partnerUserId);
            formData.append('message', message);

            fetch('../config/chat_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    input.value = '';
                    fetchMessages();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to send message');
            });
        }

        // Initial load and polling
        fetchMessages();
        setInterval(fetchMessages, 3000);
    </script>
</body>
</html>
<?php $conn->close(); ?>