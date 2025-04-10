<?php
function oblicz($dane)
{
    $waga = (float)$dane['waga'];
    $waga_wlasciwa = (float)$dane['waga_wlasciwa'];
    $wiek = (int)$dane['wiek'];
    $wzrost = (int)$dane['wzrost'];
    $plec = $dane['plec'];
    $trening = $dane['trening'];
    $ilosc_treningow = (int)$dane['ilosc_treningow'];
    $praca = $dane['praca'];
    $dieta = $dane['dieta'];
    $cel = $dane['cel'];

    if ($waga <= 0 || $waga_wlasciwa <= 0 || $wiek <= 0 || $wzrost <= 0) {
        return [
            'kalorie' => 0,
            'bialko' => 0,
            'wegle' => 0,
            'tluszcze' => 0,
            'cukry_proste_max' => 0,
            'tluszcze_nasycone_max' => 0,
            'blonnik_min' => 0
        ];
    }


    if ($plec === "kobieta") {
        $bmr = 655.1 + 9.563 * $waga + 1.85 * $wzrost - 4.676 * $wiek;
    } else {
        $bmr = 66.47 + 13.7 * $waga + 5 * $wzrost - 6.76 * $wiek;
    }

    $termiczny = 0.1 * $bmr;

    $aktywnosc = [
        "praca siedząca i tryb siedzący" => 0.3,
        "praca chodzona lub spacery regularne" => 0.35,
        "praca chodzona + lekka aktywność poza treningiem" => 0.4,
        "praca chodzona + średnia aktywność lub ciężka praca/aktywność" => 0.45,
        "ciężka praca fizyczna lub duża aktywność poza treningami" => 0.5
    ];
    $aktywnosc_kcal = $bmr * ($aktywnosc[$praca] ?? 0);

    $dieta_bonus = [
        "wolny" => 300,
        "średnia" => 350,
        "szybki" => 400
    ];
    $dieta_kcal = $dieta_bonus[$dieta] ?? 0;

    $podstawowa_kaloryka = $bmr + $termiczny + $aktywnosc_kcal + $dieta_kcal;

    $trening_kcal = [
        "bez treningu" => 0,
        "siłowy lub cardio" => 150,
        "siłowy i tabata" => 200,
        "tabata-hiit" => 250
    ];
    $trening_sredni = $trening_kcal[$trening] ?? 0;
    $trening_dziennie = $trening === "bez treningu" ? 0 : $trening_sredni * $ilosc_treningow / 7;

    $cel_mnoznik = [
        "redukcja-szczupła budowa" => 0.1,
        "redukcja-średnia budowa" => 0.18,
        "redukcja-puszysta budowa" => 0.27,
        "masa-szczupła budowa" => -0.15,
        "masa-średnia budowa" => -0.1,
        "zero kaloryczne" => 0
    ];
    $korekta = $podstawowa_kaloryka * ($cel_mnoznik[$cel] ?? 0);

    $kalorie = $podstawowa_kaloryka - $korekta + $trening_dziennie;

    $bialko = $waga_wlasciwa * ($plec === "kobieta" ? 1.6 : 2.0);
    $bialko_kcal = $bialko * 4;

    $wegle_kcal = $kalorie * 0.45;
    $wegle = $wegle_kcal / 4;

    $tluszcze_kcal = $kalorie - $bialko_kcal - $wegle_kcal;
    $tluszcze = $tluszcze_kcal / 9;

    return [
        'kalorie' => round($kalorie),
        'bialko' => round($bialko),
        'wegle' => round($wegle),
        'tluszcze' => round($tluszcze),
        'cukry_proste_max' => round($wegle * 0.35 -20),
        'tluszcze_nasycone_max' => round(($kalorie * 0.10) / 9),
        'blonnik_min' => round(($kalorie / 1000) * 11)
    ];
}

$stare_dane = $_POST ?? [];
$wyniki = $_SERVER['REQUEST_METHOD'] === 'POST' ? oblicz($stare_dane) : null;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator Kalorii</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            background: #1e1e1e;
            color: #f9fafb;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 2rem;
        }
        h2, h3 {
            font-weight: bold;
            font-size: 40px;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        form, .wyniki {
            max-width: 720px;
            margin: 2.5rem auto 1.75rem;
            background: #2b2b2b;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }
        .wyniki {
            animation: fadeIn 0.8s ease-in-out;
            font-size: 1.1rem;
            border: 1px solid #444;
            padding-top: 1.5rem;
        }
        .wyniki strong {
            color: #ff047d;
            font-weight: 600;
        }
        fieldset {
            border: none;
            margin-bottom: 1.5rem;
        }
        legend {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #e0036f;
            text-align: center;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-grid select {
            width: 100%;
            min-width: 100px;
            max-width: 100%;
        }

        label {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 1.15rem;
            color: #d1d5db;
        }
        input, select {
            width: 90%;
            max-width: 300px;
            padding: 0.75rem;
            background: #3b3b3b;
            color: white;
            border: 1px solid #4b5563;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        button {
            margin-top: 1.5rem;
            width: 100%;
            padding: 1rem;
            background-color: #ff047d;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: bold;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #e0036f;
        }
        canvas {
            display: block;
            max-width: 300px;
            margin: 20px auto;
        }
        .wyniki p {
            line-height: 1.8;
            font-size: 1.25rem;
            padding: 0.35rem 0;
        }
        .wyniki h3 {
            margin-top: 0;
            padding-top: 0;
        }
        h2, h3 {
            text-shadow:
                    0 0 1px #ff047d,
                    0 0 3px #ff047d,
                    0 0 6px #ff047d;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #ff047d;
            box-shadow: 0 0 8px #ff047d80;
        }
        #ilosc_treningow {
            width: 100% !important;
            max-width: 300px !important;
        }
        #pole_treningow { grid-column: span 2; }
        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; }
            #pole_treningow { grid-column: span 1; }
        }
        #popup-alert {
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #ff047d;
            color: #fff;
            padding: 1rem 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-weight: bold;
            font-size: 1.1rem;
            z-index: 9999;
            animation: fadeSlide 0.6s ease;
        }

        @keyframes fadeSlide {
            0% {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            100% {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

    </style>
</head>
<body>
<h2>Kalkulator zapotrzebowania kalorycznego</h2>
<form method="post">
    <fieldset>
        <legend>Dane fizyczne</legend>
        <div class="form-grid">
            <div><label>Waga (kg):</label><input type="number" name="waga" step="0.1" required value="<?= htmlspecialchars($stare_dane['waga'] ?? '') ?>"></div>
            <div><label>Waga właściwa (kg):</label><input type="number" name="waga_wlasciwa" step="0.1" required value="<?= htmlspecialchars($stare_dane['waga_wlasciwa'] ?? '') ?>"></div>
            <div><label>Wiek:</label><input type="number" name="wiek" required value="<?= htmlspecialchars($stare_dane['wiek'] ?? '') ?>"></div>
            <div><label>Wzrost (cm):</label><input type="number" name="wzrost" required value="<?= htmlspecialchars($stare_dane['wzrost'] ?? '') ?>"></div>
            <div><label>Płeć:</label><select name="plec" required>
                    <option value="kobieta" <?= ($stare_dane['plec'] ?? '') === 'kobieta' ? 'selected' : '' ?>>Kobieta</option>
                    <option value="mężczyzna" <?= ($stare_dane['plec'] ?? '') === 'mężczyzna' ? 'selected' : '' ?>>Mężczyzna</option>
                </select></div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Styl życia</legend>
        <div class="form-grid">
            <div>
                <label>Typ treningu:</label>
                <select name="trening" id="typ_treningu" required>
                    <option value="bez treningu" <?= ($stare_dane['trening'] ?? '') === 'bez treningu' ? 'selected' : '' ?>>Bez treningu</option>
                    <option value="siłowy lub cardio" <?= ($stare_dane['trening'] ?? '') === 'siłowy lub cardio' ? 'selected' : '' ?>>Siłowy lub cardio</option>
                    <option value="siłowy i tabata" <?= ($stare_dane['trening'] ?? '') === 'siłowy i tabata' ? 'selected' : '' ?>>Siłowy i tabata</option>
                    <option value="tabata-hiit" <?= ($stare_dane['trening'] ?? '') === 'tabata-hiit' ? 'selected' : '' ?>>Tabata-hiit</option>
                </select>
            </div>
            <div id="pole_treningow">
                <label>Ilość treningów tygodniowo:</label>
                <select name="ilosc_treningow" id="ilosc_treningow">
                    <?php for ($i = 1; $i <= 7; $i++): ?>
                        <option value="<?= $i ?>" <?= ($stare_dane['ilosc_treningow'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label>Tryb pracy:</label>
                <select name="praca" required>
                    <option value="praca siedząca i tryb siedzący" <?= ($stare_dane['praca'] ?? '') === 'praca siedząca i tryb siedzący' ? 'selected' : '' ?>>Siedząca</option>
                    <option value="praca chodzona lub spacery regularne" <?= ($stare_dane['praca'] ?? '') === 'praca chodzona lub spacery regularne' ? 'selected' : '' ?>>Chodzona lub regularne spacery</option>
                    <option value="praca chodzona + lekka aktywność poza treningiem" <?= ($stare_dane['praca'] ?? '') === 'praca chodzona + lekka aktywność poza treningiem' ? 'selected' : '' ?>>Chodzona + lekka aktywność</option>
                    <option value="praca chodzona + średnia aktywność lub ciężka praca/aktywność" <?= ($stare_dane['praca'] ?? '') === 'praca chodzona + średnia aktywność lub ciężka praca/aktywność' ? 'selected' : '' ?>>Średnia aktywność</option>
                    <option value="ciężka praca fizyczna lub duża aktywność poza treningami" <?= ($stare_dane['praca'] ?? '') === 'ciężka praca fizyczna lub duża aktywność poza treningami' ? 'selected' : '' ?>>Ciężka praca fizyczna lub duża aktywność</option>
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Dieta i cel</legend>
        <div class="form-grid">
            <div>
                <label>Styl diety:</label>
                <select name="dieta" required>
                    <option value="wolny" <?= ($stare_dane['dieta'] ?? '') === 'wolny' ? 'selected' : '' ?>>Wolny - mało błonnika, dużo przetworzonych produktów, słodyczy</option>
                    <option value="średnia" <?= ($stare_dane['dieta'] ?? '') === 'średnia' ? 'selected' : '' ?>>Średnia - zdrowa dieta przeplatana produktami przetworzonymi i słodyczami</option>
                    <option value="szybki" <?= ($stare_dane['dieta'] ?? '') === 'szybki' ? 'selected' : '' ?>>Szybki metabolizm - odpowiednia ilość wody, błonnika, właściwa kaloryka ze zdrowych źródeł</option>
                </select>
            </div>
            <div>
                <label>Wybierz cel i budowę ciała:</label>
                <select name="cel" required>
                    <option value="redukcja-szczupła budowa" <?= ($stare_dane['cel'] ?? '') === 'redukcja-szczupła budowa' ? 'selected' : '' ?>>Redukcja - szczupła budowa</option>
                    <option value="redukcja-średnia budowa" <?= ($stare_dane['cel'] ?? '') === 'redukcja-średnia budowa' ? 'selected' : '' ?>>Redukcja - średnia budowa</option>
                    <option value="redukcja-puszysta budowa" <?= ($stare_dane['cel'] ?? '') === 'redukcja-puszysta budowa' ? 'selected' : '' ?>>Redukcja - puszysta budowa</option>
                    <option value="masa-szczupła budowa" <?= ($stare_dane['cel'] ?? '') === 'masa-szczupła budowa' ? 'selected' : '' ?>>Masa - szczupła budowa</option>
                    <option value="masa-średnia budowa" <?= ($stare_dane['cel'] ?? '') === 'masa-średnia budowa' ? 'selected' : '' ?>>Masa - średnia budowa</option>
                    <option value="zero kaloryczne" <?= ($stare_dane['cel'] ?? '') === 'zero kaloryczne' ? 'selected' : '' ?>>Zero kaloryczne</option>
                </select>
            </div>
        </div>
    </fieldset>

    <button type="submit">Oblicz</button>
</form>

<?php if ($wyniki && $wyniki['kalorie'] > 0): ?>
    <div class="wyniki" id="sekcja-wyniki">
        <h3>Wyniki:</h3>
        <p><strong>Kalorie:</strong> <?= $wyniki['kalorie'] ?> kcal</p>
        <p><strong>Białko:</strong> <?= $wyniki['bialko'] ?> g</p>
        <p><strong>Węglowodany:</strong> <?= $wyniki['wegle'] ?> g</p>
        <p><strong>Tłuszcze:</strong> <?= $wyniki['tluszcze'] ?> g</p>
        <p><strong>Cukry proste (max):</strong> <?= $wyniki['cukry_proste_max'] ?> g</p>
        <p><strong>Tłuszcze nasycone (max):</strong> <?= $wyniki['tluszcze_nasycone_max'] ?> g</p>
        <p><strong>Błonnik (min):</strong> <?= $wyniki['blonnik_min'] ?> g</p>
        <button id="drukujPDF" style="margin: 20px auto; display: block;">📄 Drukuj / Zapisz jako PDF</button>
        <canvas id="makroChart"></canvas>
    </div>
    <script>
        const ctx = document.getElementById('makroChart');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Białko', 'Węglowodany', 'Tłuszcze'],
                datasets: [{
                    data: [<?= $wyniki['bialko'] ?>, <?= $wyniki['wegle'] ?>, <?= $wyniki['tluszcze'] ?>],
                    backgroundColor: ['#ff2f7e', '#4bc0c0', '#ffcd56']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        labels: { color: '#fff' }
                    }
                }
            }
        });
    </script>
<?php elseif ($wyniki): ?>
    <script>
        window.addEventListener("DOMContentLoaded", () => {
            const popup = document.getElementById("popup-alert");
            if (popup) {
                popup.style.display = "block";
                setTimeout(() => popup.style.display = "none", 3500);
            }
        });
    </script>
<?php endif; ?>


<script>
    const typTreningu = document.getElementById("typ_treningu");
    const poleTreningow = document.getElementById("pole_treningow");

    function aktualizujWidocznoscTreningow() {
        poleTreningow.style.display = (typTreningu.value === "bez treningu") ? "none" : "block";
    }

    typTreningu.addEventListener("change", aktualizujWidocznoscTreningow);
    document.addEventListener("DOMContentLoaded", aktualizujWidocznoscTreningow);
</script>
<script>
    const drukujBtn = document.getElementById('drukujPDF');
    drukujBtn?.addEventListener('click', async () => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        const wyniki = document.querySelector('.wyniki');
        if (!wyniki) return;

        const canvas = await html2canvas(wyniki);
        const imgData = canvas.toDataURL('image/png');

        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const imgProps = doc.getImageProperties(imgData);
        const pdfWidth = pageWidth - 20;
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

        doc.addImage(imgData, 'PNG', 10, 10, pdfWidth, pdfHeight);
        doc.save('kalkulator_kalorii.pdf');
    });
</script>
<script>
    window.addEventListener("DOMContentLoaded", () => {
        const wyniki = document.getElementById("sekcja-wyniki");
        if (wyniki) {
            wyniki.scrollIntoView({ behavior: "smooth" });
        }
    });
</script>

<div id="popup-alert" style="display: none;">
    <p>🚫 Popraw dane</p>
</div>

</body>
</html>
