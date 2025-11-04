<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control — DeskUp</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F4F7FB;
            margin: 0;
            display: flex;
            color: #3A506B;
        }

        .main-content {
            flex: 1;
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .admin-container {
            width: 90%;
            max-width: 1100px;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #3A506B;
            font-size: 2rem;
            font-weight: 700;
        }

        .page-header .subtitle {
            color: #6C7A89;
            font-size: 1rem;
            font-weight: 400;
        }

        .card {
            background: #FFFFFF;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            border-top: 5px solid #00A8A8;
            transition: transform 0.2s ease;
            margin-bottom: 2rem;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th, td {
            text-align: left;
            padding: 14px 16px;
            border-bottom: 1px solid #E3E9EF;
        }

        th {
            background-color: #3A506B;
            color: white;
            font-weight: 600;
        }

        tr:hover td {
            background-color: #F1F6FA;
        }

        .status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        .status.active {
            background-color: #00A8A8;
            color: white;
        }

        .status.inactive {
            background-color: #B0BEC5;
            color: #3A506B;
        }

        .desk-select {
            padding: 6px 10px;
            border: 1px solid #B0BEC5;
            border-radius: 6px;
            font-family: inherit;
            background: #F4F7FB;
            color: #3A506B;
            cursor: pointer;
            transition: border-color 0.2s ease;
        }

        .desk-select:hover {
            border-color: #00A8A8;
        }

        .btn-remove {
            background-color: #E63946;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .btn-remove:hover {
            background-color: #C62828;
            transform: scale(1.05);
        }

        .fade-out {
            opacity: 0;
            transform: translateX(-20px);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }
    </style>
</head>
<body>
    @include('components.sidebar')

    <div class="main-content">
        <div class="admin-container">
            <header class="page-header">
                <h1>Admin User Control</h1>
                <p class="subtitle">Manage users and assign desks</p>
            </header>

            <section class="card">
                <h2 style="margin-bottom: 1rem; color:#3A506B;">User Desk Overview</h2>
                <table id="userTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Desk</th>
                            <th>Status</th>
                            <th>Desk Height</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Jane Doe</td>
                            <td>jane@example.com</td>
                            <td>
                                <select class="desk-select" onchange="updateDesk(this)">
                                    <option value="">— Select Desk —</option>
                                </select>
                            </td>
                            <td><span class="status active" onclick="toggleDesk(this)">Active</span></td>
                            <td>102 cm</td>
                            <td><button class="btn-remove" onclick="removeUser(this)">Remove</button></td>
                        </tr>
                        <tr>
                            <td>John Smith</td>
                            <td>john@example.com</td>
                            <td>
                                <select class="desk-select" onchange="updateDesk(this)">
                                    <option value="">— Select Desk —</option>
                                </select>
                            </td>
                            <td><span class="status inactive" onclick="toggleDesk(this)">Disabled</span></td>
                            <td>—</td>
                            <td><button class="btn-remove" onclick="removeUser(this)">Remove</button></td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>

    <script>
        // Populate desk options dynamically (20 desks)
        document.querySelectorAll('.desk-select').forEach(select => {
            for (let i = 1; i <= 20; i++) {
                const option = document.createElement('option');
                option.value = 'Desk ' + i;
                option.textContent = 'Desk ' + i;
                select.appendChild(option);
            }
        });

        function toggleDesk(element) {
            const isActive = element.classList.contains('active');
            if (isActive) {
                element.textContent = 'Disabled';
                element.classList.remove('active');
                element.classList.add('inactive');
            } else {
                element.textContent = 'Active';
                element.classList.remove('inactive');
                element.classList.add('active');
            }
        }

        function removeUser(button) {
            const row = button.closest('tr');
            row.classList.add('fade-out');
            setTimeout(() => row.remove(), 400);
        }

        function updateDesk(select) {
            const user = select.closest('tr').querySelector('td:first-child').textContent;
            const desk = select.value;
            console.log(`${user} assigned to ${desk}`);
        }
    </script>
</body>
</html>
