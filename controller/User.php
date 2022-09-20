<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class User
{
    private string $host = "database:3306";
    private string $db_name = "cloud_storage";
    private string $username = "root";
    private string $password = 'tiger';
    public ?PDO $conn;

    public function __construct()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }

    public function user($method, $param)
    {
        if ($method === 'GET' && empty($param)) {
            $this->htmlUser("<h2>Список пользователей:</h2>" . PHP_EOL . $this->list());
        } elseif ($method === 'GET' && !empty($param['id'])) {
            $this->htmlUser("<h2>Файл с номером id:  " . $param['id'] . "</h2>" . PHP_EOL . $this->showUser($param['id']));
        } elseif ($method === 'POST' && empty($param) && empty($_POST['method'])) {
            $this->htmlUser("<h2>Результат добавления пользователя:</h2>" . PHP_EOL . $this->add($_REQUEST));
        } elseif (!empty($_POST['method']) && $_POST['method'] === 'PUT') {
            $this->htmlUser("<h2>Результат изменения пользователя</h2>" . PHP_EOL . $this->update());
        } elseif (!empty($_POST['method']) && $_POST['method'] === 'DELETE') {
            if (!empty($_POST['id'])) {
                $info = $this->delete($_POST['id']);
            } else {
                $info = 'id не введено';
            }
            $this->htmlUser("<h2>Результат удаления пользователя</h2>" . PHP_EOL . $info);
        } elseif ($method === 'GET' && !empty($param['email']) && !empty($param['password'])) {
            $this->htmlUser("<h2>Результат логина</h2>" . PHP_EOL . $this->login($param['email'], $param['password']));
        } elseif ($method === 'GET' && !empty($param['logout'])) {
            $this->htmlUser("<h2>Результат выхода из сессии</h2>" . PHP_EOL . $this->logout());
        } elseif ($method === 'GET' && isset($param['reset_password'])) {
            $this->htmlUser("<h2>Результат сбрроса пароля</h2>" . PHP_EOL . $this->resetPassword($param['reset_password']));
        } else {
            $this->htmlUser("<h2>Результат:</h2>" . PHP_EOL . 'incorrect URI or param');
        }
    }



    public function list()
    {
        $statement = $this->conn->query("SELECT * FROM user");
        $statement->execute();
        $data = $statement->fetchAll();
        return json_encode($data);
    }

    public function showUser(int $userId)
    {
        $statement = $this->conn->prepare("SELECT * FROM user WHERE id = :id");;
        $statement->execute(['id' => $userId]);
        $data = $statement->fetch();
        if ($data > 0) {
            return json_encode($data);
        } else {
            return 'нет такого user id';
        }
    }

    public function add(array $request)
    {
        if (!empty($request['name']) && !empty($request['email']) && !empty($request['password']) &&
            !empty($request['role']) && !empty($request['age']) && !empty($request['sex'])) {

            $hashEmail = $this->checkEmail($request['email']);

            $hashPassword = $this->checkPassword($request['password']);

            try {
                $statement = $this->conn->prepare("INSERT INTO user(name, email, password, role, age, sex) 
                    VALUE (:name, :email , :password, :role, :age, :sex)");

                $statement->execute(['name' => $request['name'],
                    'email' => $hashEmail,
                    'password' => $hashPassword,
                    'role' => $request['role'],
                    'age' => $request['age'],
                    'sex' => $request['sex'],]);
                return 'Пользователь добавлен';
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        } else {
            return 'invalid data values specified';
        }
    }

    public function update()
    {
        if (!empty($id = $_POST['id'])) {
            try {
            $statement = $this->conn->prepare("SELECT * FROM user WHERE id = :id");;
            $statement->execute(['id' => $id]);
            $data = $statement->fetch();
            $name = !empty($_POST['name']) ? $_POST['name'] : $data['name'];
            $email = !empty($_POST['email']) ? $this->checkEmail($_POST['email']) : $data['email'];
            $password = !empty($_POST['password']) ? $this->checkPassword($_POST['password']) : $data['email'];
            $role = !empty($_POST['role']) ? $_POST['role'] : $data['role'];
            $age = !empty($_POST['age']) ? $_POST['age'] : $data['age'];
            $sex = !empty($_POST['sex']) ? $_POST['sex'] : $data['sex'];

                $statement = $this->conn->prepare("UPDATE `user` SET `name`= :name, `email`= :email,
                  `password`= :password, `role`= :role,`age`= :age,`sex`= :sex WHERE id = :id");
                $statement->execute(['id' => $id, 'name' => $name, 'email' => $email, 'password' => $password,
                    'role' => $role, 'age' => $age, 'sex' => $sex]);
                return 'Данные пользователя изменены';
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        } else {
            return 'Id пользователя не указан';
        }
    }

    public function delete(int $userId)
    {
        try {
            $statement = $this->conn->prepare("SELECT `id` FROM `user` WHERE `id` = :id");
            $statement->execute(['id' => $userId]);
            $result = $statement->fetch();
            if ($result) {
                $statement = $this->conn->prepare("DELETE FROM `user` WHERE `id` = :id");
                $statement->execute(['id' => $userId]);
                return 'пользователь удален';
            } else {
                return 'пользователь с таким id не найден';
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function login($email, $password)
    {
        try {
            $statement = $this->conn->prepare("SELECT `id`, `email`, `password`, `role` FROM `user`");
            $statement->execute();
            $statement->fetch();
            while ($result = $statement->fetch()) {
                if (password_verify($email, $result['email']) && password_verify($password, $result['password'])) {
                    $id = $result['id'];
                }
            }

            if (!empty($id)) {
                $token = rand(100000, 999999);
                $hashToken = password_hash($token, PASSWORD_BCRYPT);
                try {
                    $statement = $this->conn->prepare("UPDATE `user` SET `token`= :token WHERE id = :id");
                    $statement->execute(['id' => $id, 'token' => $hashToken]);
                    setcookie('token', $hashToken, time() + 3600, '/');
                    return 'session start';
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            } else {
                return 'login failed';
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function logout()
    {
        if (!empty($_COOKIE['token'])) {
            setcookie('token', '0', time() - 3600, '/');
            return 'logout';
        } else {
            return 'нет активных сессий';
        }
    }

    public function resetPassword($email)
    {
        try {
            $statement = $this->conn->prepare("SELECT `id`, `email` FROM `user`");
            $statement->execute();
            $statement->fetch();
            while($result = $statement->fetch()){
                if(password_verify($email, $result['email'])){
                    $data[] = true;
                    $userId = $result['id'];
                }
            }

            if (!empty($data)) {
                $newPassword = rand(100000,999999);
                $hashPassword = password_hash($newPassword, PASSWORD_BCRYPT);

                $statement = $this->conn->prepare("UPDATE `user` SET `password`= :password WHERE id = :id");
                $statement->execute(['id' => $userId, 'password' => $hashPassword ]);

                $mail = new PHPMailer();
                try {
                    $mail->isSMTP();
                    $mail->CharSet = "UTF-8";
                    $mail->SMTPAuth = true;
                    $mail->Debugoutput = function($str) {$GLOBALS['status'][] = $str;};

                    $mail->Host = 'smtp.mail.ru';
                    $mail->Username = $_ENV['MAIL_LOG'];
                    $mail->Password = $_ENV['MAIL_PASS'];
                    $mail->SMTPSecure = 'ssl';
                    $mail->Port = 465;
                    $mail->setFrom('ilukhin.sergey@mail.ru', 'Письмо из cloud storage');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Восстановление пароля от cloud storage';
                    $mail->Body = 'Ваш новый пароль от сайта cloud storage: ' . $newPassword . PHP_EOL .
                        'Измените его при следующем входе';
                    $mail->send();
                    return 'Message has been sent';
                } catch (Exception $e) {
                    return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            }
            else {
                return 'email entered incorrectly';
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    private function checkEmail($email): string
    {
        try {
            $statement = $this->conn->prepare("SELECT `email` FROM `user`");
            $statement->execute();
            $statement->fetch();
            while ($result = $statement->fetch()) {
                if (password_verify($email, $result['email'])) {
                    return 'Пользователь с таким email уже существует ';
                }
            }
            return password_hash($email, PASSWORD_BCRYPT);
        } catch (Exception $e) {
            return 'Message Error: ' . $e;
        }
    }

        private function checkPassword($password): string
        {
        if (!preg_match("/^[a-zA-Z0-9]+$/",$password)) {
            return 'Пароль может состоять только из букв английского алфавита и цифр';
        } elseif(strlen($password) < 3 or strlen($password) > 30) {
            return 'Пароль должен быть не меньше 3-х символов и не больше 30';
        } else {
            return password_hash($password, PASSWORD_BCRYPT);
        }
    }

    public function htmlUser($info)
    {
        ?>
        <html>
        <head>
        </head>
        <body>
        <h1>/user/</h1>
        <?php
        print_r($info)
        ?>
        <form action="http://localhost/user/" method="GET">
            <h5>Вывести список пользователей</h5>
            <input type="submit" value="list users">
        </form>
        <form action="http://localhost/user/" method="GET">
            <h5>Вывести пользователя по id</h5>
            <label>id: <input type="number" name="id"></label>
            <input type="submit" value="go"">
        </form>
        <form action="http://localhost/user/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Добавление нового пользователя</h5>
            <label>name: <input type="text" name="name"></label>
            <label>email: <input type="text" name="email"></label>
            <label>password: <input type="text" name="password"></label>
            <label>role: <input type="text" name="role"></label>
            <label>age: <input type="text" name="age"></label>
            <label>sex: <input type="text" name="sex"></label>
            <input type="submit" value="add user">
        </form>
        <form action="http://localhost/user/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Изменить данные пользователя</h5>
            <label>id: <input type="text" name="id"></label>
            <label>name: <input type="text" name="name"></label>
            <label>email: <input type="text" name="email"></label>
            <label>password: <input type="text" name="password"></label>
            <label>role: <input type="text" name="role"></label>
            <label>age: <input type="text" name="age"></label>
            <label>sex: <input type="text" name="sex"></label>
            <input type="hidden" name="method" value="PUT">
            <input type="submit" value="update user">
        </form>

        <form action="http://localhost/user/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Удаления пользователя</h5>
            <label>id: <input type="number" name="id"></label>
            <input type="hidden" name="method" value="DELETE">
            <input type="submit" value="Отправить"></p>
        </form>
        <form action="http://localhost/user/" method="GET">
            <h5>Залогинится</h5>
            <label>email: <input type="email" name="email"></label>
            <label>password: <input type="password" name="password"></label>
            <input type="submit" value="login">
        </form>
        <form action="http://localhost/user/" method="GET">
            <h5>Выйти</h5>
            <input type="submit" value="logout" name="logout">
        </form>
        <form action="http://localhost/user/" method="GET" enctype="application/x-www-form-urlencoded" name="form2">
            <h5>Восстановить пароль</h5>
            <label>email: <input type="email" name="reset_password"></label>
            <input type="submit" value="reset_password" name="reset_password">
        </form>

        <h3>Доступные станицы</h3>
        <form action="http://localhost/admin/user/" method="GET">
            <h5>Перейти на страницу /admin/user/</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/file/" method="GET">
            <h5>Перейти на страницу /file/</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/files/share/" method="GET">
            <h5>Перейти на страницу /files/share/</h5>
            <input type="submit" value="go">
        </form>
        </body>
        </html>
        <?php
    }
}
