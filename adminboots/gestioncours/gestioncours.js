// Fonction pour vérifier la validité de la date et de l'heure de fin
function validateForm() {
    const startHour = document.querySelector('[name="start_hour"]').value;
    const endHour = document.querySelector('[name="end_hour"]').value;
    const scheduleDate = document.querySelector('[name="schedule_date"]').value;

    // Obtenir la date actuelle au format "YYYY-MM-DD"
    const currentDate = new Date().toISOString().split('T')[0];

    // Vérification si la date choisie est invalide ou dans le passé
    if (!scheduleDate || scheduleDate < currentDate) {
        alert("Vous ne pouvez pas créer un cours à une date antérieure à aujourd'hui.");
        return false;
    }

    // Convertir les heures en minutes pour comparaison
    const startHourMinutes = parseInt(startHour.split(":")[0]) * 60 + parseInt(startHour.split(":")[1]);
    const endHourMinutes = parseInt(endHour.split(":")[0]) * 60 + parseInt(endHour.split(":")[1]);

    // Vérification si l'heure de fin est avant ou égale à l'heure de départ
    if (endHourMinutes <= startHourMinutes) {
        alert("L'heure de fin doit être après l'heure de début.");
        return false;
    }

    return true;
}

// Définir la date actuelle comme valeur par défaut pour le champ de la date
window.onload = function () {
    const dateField = document.querySelector('[name="schedule_date"]');
    if (dateField) {
        dateField.value = new Date().toISOString().split('T')[0]; // Définit la date du jour par défaut
    }
};
