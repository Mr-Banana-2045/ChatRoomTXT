<?php
session_start();

$forbiddenWords = ['hack', 'fuck', 'USA', '.com', '.net', '.onion', '.us', 'xxx'];

if (!isset($_SESSION['usernames'])) {
    $_SESSION['usernames'] = [];
}

$existingNames = [];
if (file_exists('chat.txt')) {
    $messages = file_get_contents('chat.txt');
    $lines = explode("\n", $messages);
    
    foreach ($lines as $line) {
        if (preg_match('/(.*?) user has joined/', $line, $matches)) {
            $existingNames[] = $matches[1];
        }
    }
}

$_SESSION['usernames'] = array_unique(array_merge($_SESSION['usernames'], $existingNames));

function censorMessage($message, $forbiddenWords) {
    foreach ($forbiddenWords as $word) {
        $message = str_ireplace($word, '*****', $message);
    }
    return $message;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['name']) && isset($_POST['name'])) {
        $name = trim($_POST['name']);
        
        if ($name === '') {
            echo "<script>alert('Please enter a valid name.');</script>";
        } elseif (in_array($name, $_SESSION['usernames'])) {
            echo "<script>alert('This name is already taken. Please choose another one.');</script>";
        } else {
            $_SESSION['name'] = $name;
            $_SESSION['usernames'][] = $name;
            file_put_contents('chat.txt', $_SESSION['name'] . " user has joined" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    if (isset($_POST['msg'])) {
        $message = trim($_POST['msg']);
        
        if ($message !== '') {
            $sanitizedMessage = censorMessage($message, $forbiddenWords);
            file_put_contents('chat.txt', $_SESSION['name'] . " : " . $sanitizedMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "";
        }
    }
}

if (file_exists('chat.txt') && isset($_SESSION['name'])) {
    $messages = file_get_contents('chat.txt');
} else {
    $messages = '';
}

$userCount = count($_SESSION['usernames']);

$formattedMessages = '';
if ($messages) {
    foreach (explode("\n", $messages) as $msg) {
        if (trim($msg) !== '') {
            $msgContent = strstr($msg, ':') ? trim(substr($msg, strpos($msg, ':') + 1)) : $msg;
            if (strpos($msg, $_SESSION['name'] . " :") !== false) {
                $formattedMessages .= "<div class='message right'>" . htmlspecialchars($msgContent) . "</div>"; 
            } else {
                $formattedMessages .= "<div class='message left'>" . htmlspecialchars($msg) . "</div>";
            }
        }
    }
}
?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlueUsers Chat</title>
    <style>
        body {
            background-color: black;
            -webkit-user-select: none;
            -ms-user-select: none; 
            user-select: none; 
        }
        #messages {
            border: 1px solid #ccc;
            position: fixed;
            bottom:65px;
            right:10px;
            left:10px;
            top:40px;
            overflow-y: scroll;
            border: none;
            scrollbar-color: cornflowerblue #f5f2f2;
            scrollbar-width: thin;
            display: flex;
            flex-direction: column;
            padding: 10px;
        }
        .message {
            padding: 5px;
            margin: 5px;
            border-radius: 5px;
            max-width: 70%;
            background-color: #f1f1f1;
        }
        .left {
            background-color: #f1f1f1;
            padding:10px;
            font-weight: 900;
            font-family: Arial, Helvetica, sans-serif;
            align-self: flex-start;
        }
        .right {
            background-color: #a3d1ff;
            padding:10px;
            font-weight: 900;
            font-family: Arial, Helvetica, sans-serif;
            align-self: flex-end;
        }
        .msgs {
            position: absolute;
            bottom: 10px;
            left:0px;
            text-align: center;
            right:0px;
        }
        .mes {
            outline: none;
            padding: 10px;
            font-weight: 900;
            border: none;
            width: 300px;
            border-radius: 10px 0px 0px 10px;
            font-size: 15px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            color:black;
            padding-left: 10px;
        }
        .butms {
            outline: none;
            padding: 10px;
            font-weight: 900;
            border: none;
            border-radius: 0px 20px 20px 0px;
            font-size: 15px;
            background-color: cornflowerblue;
            margin-left: -10px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            color:white;
        }
        .butms:hover {
            background-color: blue;
        }
        .mes::placeholder {
            color: cornflowerblue;
            padding-left: 5px;
        }
        .names {
            position: fixed;
            bottom: 10px;
            text-align: center;
            left:0px;
            right:0px;
        }
    </style>
</head>
<body>
    <script>
        window.onload = function() {
            const savedUsername = localStorage.getItem('username');
            if (savedUsername) {
                document.getElementById('username').value = savedUsername;
            }
        };

        function saveUsername() {
            const username = document.getElementById('username').value;
            localStorage.setItem('username', username);
        }
    </script>
    <?php if (!isset($_SESSION['name'])): ?>
        <div class="names">
        <form method="POST" action="">
            <input class="mes" type="text" name="name" placeholder="Your name ..." required pattern="[A-Za-z0-9 ]+" title="Only letters and numbers are allowed">
            <input class="butms" type="submit" style="width:50px;" value="OK">
        </form>
        </div>
    <?php else: ?>
        <div class="msgs">
        <form method="POST" action="">
            <input class="mes" type="text" name="msg" placeholder="Enter message ..." required pattern="[A-Za-z0-9 ]+" title="Only letters and numbers are allowed">
            <input class="butms" type="submit" value="send">
        </form>
        </div>
    <?php endif; ?>

    <h2 style="color:white; font-family: sans-serif; text-align:center;">
        <a style="color:blue;">Blue</a>Users (<a style='color:cornflowerblue;'><?php echo $userCount; ?></a>)
    </h2>
    <div id="messages"><?php echo nl2br($formattedMessages); ?></div>

    <script>
        function fetchMessages() {
            fetch('chat.txt')
                .then(response => response.text())
                .then(data => {
                    const messagesDiv = document.getElementById('messages');
                    let formattedMessages = '';
                    data.split('\n').forEach(msg => {
                        if (msg.trim() !== '') {
                            const msgParts = msg.split(' [');
                            const msgContent = msgParts[0].includes(':') ? msgParts[0].split(':')[1].trim() : msg;
                            
                            if (msg.includes('<?php echo htmlspecialchars($_SESSION['name']); ?> :')) {
                                formattedMessages += "<div class='message right'>" + msgContent + "</div>";
                            } else {
                                formattedMessages += "<div class='message left'>" + msg + "</div>";
                            }
                        }
                    });
                    messagesDiv.innerHTML = formattedMessages;
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                });
        }
    </script>
    <script>
        function validateInput(event) {
            const key = event.key;
            const allowedKeys = /^[A-Za-z0-9 ]$/;
            if (!allowedKeys.test(key) && event.key !== "Backspace") {
                event.preventDefault();
            }
        }

        document.querySelectorAll('.mes').forEach(input => {
            input.addEventListener('keydown', validateInput);
        });
    </script>
</body>
</html>
