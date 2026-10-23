<!doctype html>
<html lang="pl">
<head><title>Zgłoś usterkę - Widlak</title></head>
<body>
    <main>
        <h1>Zgłoś nową usterkę (WD-01)</h1>
        <form action="/faults" method="post" enctype="multipart/form-data">
            <label for="title">Krótki opis (tytuł):</label>
            <input type="text" id="title" name="title" required>
            
            <label for="severity">Priorytet:</label>
            <select name="severity" id="severity" required>
                <option value="LOW">Niski (nie przeszkadza w pracy)</option>
                <option value="CRITICAL">Krytyczny (wymaga wyłączenia maszyny)</option>
            </select>

            <label for="photo">Dodaj zdjęcie:</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg, image/png">

            <button type="submit">Wyślij zgłoszenie</button>
        </form>
    </main>
</body>
</html>