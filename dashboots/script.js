const canvas = document.getElementById('signatureCanvas');
const ctx = canvas.getContext('2d');
let drawing = false;

// Résolution souhaitée (par exemple, pour un affichage Retina avec une densité 2x)
const scale = window.devicePixelRatio || 1;

// Taille physique du canvas (ce que l'utilisateur voit)
const displayedWidth = canvas.offsetWidth;
const displayedHeight = canvas.offsetHeight;

// Ajustement de la résolution virtuelle (canvas.width et canvas.height)
canvas.width = displayedWidth * scale;
canvas.height = displayedHeight * scale;

// Ajustement de la taille visible (style.width et style.height) pour correspondre à l'interface utilisateur
canvas.style.width = `${displayedWidth}px`;
canvas.style.height = `${displayedHeight}px`;

// Adapter le contexte au nouveau ratio de pixels
ctx.scale(scale, scale);

// Paramètres personnalisés pour un trait lisse
ctx.strokeStyle = 'black';  // Couleur noire
ctx.lineWidth = 1;  // Épaisseur de 1px
ctx.lineCap = 'round';  // Extrémités arrondies
ctx.lineJoin = 'round';  // Jointures arrondies

// Corriger les bugs d'effacement et forcer une remise à zéro
function clearCanvas() {
    ctx.clearRect(0, 0, canvas.width / scale, canvas.height / scale);  // Effacer avec la bonne échelle
}

// Commence à dessiner
canvas.addEventListener('mousedown', (e) => {
    drawing = true;
    ctx.beginPath();
    ctx.moveTo(e.offsetX, e.offsetY);
});

// Dessine pendant que la souris se déplace
canvas.addEventListener('mousemove', (e) => {
    if (drawing) {
        ctx.lineTo(e.offsetX, e.offsetY); //position
        ctx.stroke(); // le trait
    }
});

// Arrête de dessiner quand on relâche la souris
canvas.addEventListener('mouseup', () => {
    drawing = false;
    ctx.closePath();
});

// Efface le contenu du canvas
document.getElementById('clearButton').addEventListener('click', () => {
    clearCanvas();
});

// Sauvegarde la signature en tant qu'image PNG et cache l'interface
document.getElementById('saveButton').addEventListener('click', () => {
    const image = canvas.toDataURL("image/png");
    const savedSignature = document.getElementById('savedSignature');
    savedSignature.src = image;
    savedSignature.style.display = 'block';

    // Cacher l'interface de signature
    canvas.style.display = 'none';
    document.getElementById('clearButton').style.display = 'none';
    document.getElementById('saveButton').style.display = 'none';

    // Afficher le bouton "Retour"
    document.getElementById('returnButton').style.display = 'block';
});

// Bouton retour pour rediriger vers index.php
document.getElementById('returnButton').addEventListener('click', () => {
    window.location.href = 'Dashboard.php';
});