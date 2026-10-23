<!doctype html>
<html lang="pl">
<head><title>Flota wózków </title></head>
<body>
    <main>
        <h1>Dostępne wózki widłowe</h1>
        <form method="get" action="/forklifts">
            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="">Wszystkie</option>
                <option value="AVAILABLE">Dostępne</option>
            </select>
            <button type="submit">Szukaj</button>
        </form>
        <article>
            <h2>WD-01 (Toyota)</h2>
            <p>Status: Dostępny | Udźwig: 2000kg</p>
            <a href="/forklifts/1">Zarezerwuj</a>
        </article>
    </main>
</body>
</html>