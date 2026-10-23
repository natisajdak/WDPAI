<!doctype html>
<html lang="pl">
<head><title>Karta Wózka </title></head>
<body>
    <main>
        <h1>Wózek: WD-01</h1>
        <section>
            <h2>Zarezerwuj maszynę</h2>
            <form action="/reservations" method="post">
                <label for="date">Data rezerwacji</label>
                <input type="date" id="date" name="date" required>
                
                <label for="start_time">Od godziny:</label>
                <input type="time" id="start_time" name="start_time" required>
                
                <button type="submit">Zapisz rezerwację</button>
            </form>
        </section>
    </main>
</body>
</html>