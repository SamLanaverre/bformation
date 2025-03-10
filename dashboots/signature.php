<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oline Signature + calendar</title>
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <!-- SIGNATURE -->
    <div class="signature-container">
        <h2>Online Signature</h2>
        <canvas id="signatureCanvas" width="500" height="200"></canvas>
        <img id="savedSignature" alt="Signature sauvegardée" style="margin-top: 20px; display: none;"/>
        <br>
        <div class="button-container">
            <button id="returnButton">Cancel</button>
            <button id="clearButton">Delete</button>
            <button id="saveButton">Send</button>
        </div>

        <br>
        
    </div>
    <!-- FIN SIGNATURE -->


<script src="script.js"></script>

</body>
</html>