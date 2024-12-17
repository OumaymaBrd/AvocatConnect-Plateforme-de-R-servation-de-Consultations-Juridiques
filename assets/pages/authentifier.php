<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification - Cabinet d'avocat Didier</title>
    <style>
        body {
            font-family: 'Georgia', serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
            color: #34495e;
        }
        input {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background-color: #2c3e50;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #34495e;
        }
        .logo {
            text-align: center;
            font-size: 48px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">⚖️</div>
        <h1>Cabinet d'avocat Didier</h1>
        <form>
            <label for="username">Identifiant</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>