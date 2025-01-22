document.addEventListener("DOMContentLoaded", () => {
    const tutorial = document.getElementById("tutorial");
    const steps = document.querySelectorAll(".tutorial-step");
    const firstTimeKey = "firstTimeUser";

    // Vérifier si l'utilisateur a déjà vu le tutoriel
    if (!sessionStorage.getItem(firstTimeKey)) {
        tutorial.style.display = "flex";
        let currentStep = 0;

        steps[currentStep].style.display = "block";

        // Gérer les boutons "Suivant" et "Terminer"
        steps.forEach((step, index) => {
            const nextButton = step.querySelector(".next-button");
            const closeButton = step.querySelector(".close-button");

            if (nextButton) {
                nextButton.addEventListener("click", () => {
                    step.style.display = "none";
                    currentStep++;
                    if (steps[currentStep]) {
                        steps[currentStep].style.display = "block";
                    }
                });
            }

            if (closeButton) {
                closeButton.addEventListener("click", () => {
                    tutorial.style.display = "none";
                    sessionStorage.setItem(firstTimeKey, "true");
                });
            }
        });
    }
});
