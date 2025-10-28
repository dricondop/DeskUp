<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <style>
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
            height: 100vh;
            background-color: #f0f2f5;
        }

        /* Sidebar */
        aside {
            width: 160px;
            background-color: #3a506b;
            color: white;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        aside h2 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        nav ul {
            list-style: none;
            padding: 0;
            width: 100%;
        }

        nav li {
            margin: 12px 0;
            text-align: center;
        }

        nav a {
            color: black;
            text-decoration: none;
            font-weight: bold;
            display: block;
            padding: 6px 0;
            border-radius: 4px;
        }

        nav a:hover {
            background-color: #5bc0be;
        }

        /* Main content */
        main {
            flex: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* User list */
        ul {
            list-style: none;
            padding: 0;
            width: 100%;
            max-width: 550px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }

        li:hover {
            background-color: #f9f9f9;
        }

        select {
            margin-left: 10px;
            padding: 4px 6px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            margin-left: 5px;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: opacity 0.2s;
        }

        button:hover {
            opacity: 0.8;
        }

        button.edit {
            background-color: #3498db;
            color: white;
        }

        button.delete {
            background-color: #e74c3c;
            color: white;
        }

        #addUserBtn {
            margin-top: 20px;
            padding: 9px 18px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        #addUserBtn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <aside>
        <h2>Menu</h2>
        <nav>
            <ul>
                <li><a href="#">Dashboard</a></li>
                <li><a href="#">Users</a></li>
                <li><a href="#">Desks</a></li>
                <li><a href="#">Settings</a></li>
            </ul>
        </nav>
    </aside>

    <main>
        <h2>Users (Placeholder)</h2>
        <ul id="userList">
            <li>
                User 1
                <select>
                    <option>Desk A</option>
                    <option>Desk B</option>
                    <option>Desk C</option>
                </select>
                <button class="edit">Edit</button>
                <button class="delete">Delete</button>
            </li>
            <li>
                User 2
                <select>
                    <option>Desk A</option>
                    <option selected>Desk B</option>
                    <option>Desk C</option>
                </select>
                <button class="edit">Edit</button>
                <button class="delete">Delete</button>
            </li>
            <li>
                User 3
                <select>
                    <option>Desk A</option>
                    <option>Desk B</option>
                    <option selected>Desk C</option>
                </select>
                <button class="edit">Edit</button>
                <button class="delete">Delete</button>
            </li>
        </ul>

        <button id="addUserBtn">Add User</button>
    </main>

</body>
</html>
